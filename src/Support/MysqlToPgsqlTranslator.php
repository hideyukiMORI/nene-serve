<?php

declare(strict_types=1);

namespace NeneServe\Support;

/**
 * Translates the MySQL DDL in database/migrations to PostgreSQL DDL, so the MySQL
 * migrations stay the single source of truth (PostgreSQL support, issue #120).
 *
 * Handled constructs (the only ones the migrations use): strip
 * ENGINE/CHARSET/COLLATE; `ENUM(...)` → TEXT (values are validated in the app);
 * DATETIME → TIMESTAMP; drop `ON UPDATE CURRENT_TIMESTAMP`; CHAR(n) → VARCHAR(n)
 * (PG CHAR is blank-padded); TINYINT/INT(n) → SMALLINT/INTEGER; AUTO_INCREMENT →
 * GENERATED identity; drop column position hints (FIRST / AFTER x); inline
 * non-unique `KEY name (cols)` → a separate CREATE INDEX; `UNIQUE KEY name (cols)`
 * → CONSTRAINT … UNIQUE; `DROP FOREIGN KEY` → `DROP CONSTRAINT`.
 */
final class MysqlToPgsqlTranslator
{
    /** Translates every statement in one migration file's SQL. */
    public function translateFile(string $sql): string
    {
        $out = [];

        foreach ($this->statements($sql) as $statement) {
            // Non-unique indexes can't live inside a PG CREATE TABLE / ALTER, so
            // they are collected here and emitted as separate CREATE INDEX.
            $indexes = [];
            $translated = $this->translateStatement($statement, $indexes);

            if ($translated !== '') {
                $out[] = $translated . ';';
            }

            foreach ($indexes as $index) {
                $out[] = $index . ';';
            }
        }

        return implode("\n", $out);
    }

    /** @param list<string> $indexes */
    private function translateStatement(string $statement, array &$indexes): string
    {
        if (preg_match('/^\s*CREATE TABLE\s+(\w+)/i', $statement, $m) === 1) {
            return $this->translateCreateTable($m[1], $statement, $indexes);
        }

        if (preg_match('/^\s*ALTER TABLE\s+(\w+)\s+(.*)$/is', $statement, $m) === 1) {
            return $this->translateAlterTable($m[1], $m[2], $indexes);
        }

        return $this->scalarTypes($statement);
    }

    /** @param list<string> $indexes */
    private function translateCreateTable(string $table, string $statement, array &$indexes): string
    {
        $open = strpos($statement, '(');
        $close = strrpos($statement, ')');

        if ($open === false || $close === false) {
            return $statement;
        }

        $body = substr($statement, $open + 1, $close - $open - 1);
        $kept = [];

        foreach ($this->splitTopLevel($body) as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (preg_match('/^UNIQUE KEY\s+(\w+)\s*\((.+)\)$/is', $line, $m) === 1) {
                $kept[] = sprintf('CONSTRAINT %s UNIQUE (%s)', $m[1], $m[2]);

                continue;
            }

            if (preg_match('/^KEY\s+(\w+)\s*\((.+)\)$/is', $line, $m) === 1) {
                $indexes[] = sprintf('CREATE INDEX %s ON %s (%s)', $m[1], $table, $m[2]);

                continue;
            }

            $kept[] = $this->columnDefinition($line);
        }

        return sprintf("CREATE TABLE %s (\n    %s\n)", $table, implode(",\n    ", $kept));
    }

    /** @param list<string> $indexes */
    private function translateAlterTable(string $table, string $clausesRaw, array &$indexes): string
    {
        if (preg_match('/^DROP FOREIGN KEY\s+(\w+)$/is', trim($clausesRaw), $m) === 1) {
            return sprintf('ALTER TABLE %s DROP CONSTRAINT %s', $table, $m[1]);
        }

        $alterClauses = [];

        foreach ($this->splitTopLevel($clausesRaw) as $clause) {
            $clause = trim($clause);

            if ($clause === '') {
                continue;
            }

            if (preg_match('/^ADD\s+UNIQUE KEY\s+(\w+)\s*\((.+)\)$/is', $clause, $m) === 1) {
                $alterClauses[] = sprintf('ADD CONSTRAINT %s UNIQUE (%s)', $m[1], $m[2]);

                continue;
            }

            if (preg_match('/^ADD\s+KEY\s+(\w+)\s*\((.+)\)$/is', $clause, $m) === 1) {
                $indexes[] = sprintf('CREATE INDEX %s ON %s (%s)', $m[1], $table, $m[2]);

                continue;
            }

            if (preg_match('/^ADD\s+(CONSTRAINT\b.*)$/is', $clause, $m) === 1) {
                $alterClauses[] = 'ADD ' . $this->scalarTypes($m[1]);

                continue;
            }

            if (preg_match('/^ADD\s+COLUMN\s+(.*)$/is', $clause, $m) === 1) {
                $alterClauses[] = 'ADD COLUMN ' . $this->columnDefinition($m[1]);

                continue;
            }

            $alterClauses[] = $this->scalarTypes($clause);
        }

        if ($alterClauses === []) {
            return '';
        }

        return sprintf('ALTER TABLE %s %s', $table, implode(', ', $alterClauses));
    }

    private function columnDefinition(string $def): string
    {
        $def = $this->scalarTypes($def);
        $def = (string) preg_replace('/\s+(FIRST|AFTER\s+\w+)\b/i', '', $def);
        $def = (string) preg_replace('/\bAUTO_INCREMENT\b/i', 'GENERATED BY DEFAULT AS IDENTITY', $def);

        return trim((string) preg_replace('/\s+ON UPDATE CURRENT_TIMESTAMP\b/i', '', $def));
    }

    private function scalarTypes(string $sql): string
    {
        $sql = (string) preg_replace('/\bENUM\s*\([^)]*\)/i', 'TEXT', $sql);
        $sql = (string) preg_replace('/\bDATETIME\b/i', 'TIMESTAMP', $sql);
        $sql = (string) preg_replace('/\bCHAR\s*\((\d+)\)/i', 'VARCHAR($1)', $sql);
        $sql = (string) preg_replace('/\bTINYINT\s*(\(\d+\))?/i', 'SMALLINT', $sql);
        $sql = (string) preg_replace('/\bBIGINT\s*\(\d+\)/i', 'BIGINT', $sql);
        $sql = (string) preg_replace('/\bINT\s*\(\d+\)/i', 'INTEGER', $sql);

        return (string) preg_replace('/\)\s*ENGINE\s*=.*$/is', ')', $sql);
    }

    /**
     * Splits on top-level commas (ignoring commas inside parentheses).
     *
     * @return list<string>
     */
    private function splitTopLevel(string $s): array
    {
        $parts = [];
        $depth = 0;
        $buffer = '';

        foreach (str_split($s) as $char) {
            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;
            }

            if ($char === ',' && $depth === 0) {
                $parts[] = $buffer;
                $buffer = '';

                continue;
            }

            $buffer .= $char;
        }

        if (trim($buffer) !== '') {
            $parts[] = $buffer;
        }

        return $parts;
    }

    /** @return list<string> */
    private function statements(string $sql): array
    {
        $sql = (string) preg_replace('/^\s*--.*$/m', '', $sql);
        $out = [];

        foreach (preg_split('/;\R/s', trim($sql)) ?: [] as $chunk) {
            $chunk = trim($chunk);

            if ($chunk !== '') {
                $out[] = rtrim($chunk, ';');
            }
        }

        return $out;
    }
}
