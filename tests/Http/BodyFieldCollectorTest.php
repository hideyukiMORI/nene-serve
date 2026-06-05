<?php

declare(strict_types=1);

namespace NeneServe\Tests\Http;

use Nene2\Validation\ValidationException;
use NeneServe\Http\BodyFieldCollector;
use PHPUnit\Framework\TestCase;

/**
 * Collect-all body validation: each accessor returns a safe default and records
 * an error rather than throwing, so {@see BodyFieldCollector::throwIfInvalid()}
 * can report every problem at once. Boundaries (empty, whitespace, wrong type,
 * out-of-set) are probed in both passing and failing directions.
 */
final class BodyFieldCollectorTest extends TestCase
{
    public function testReturnsValuesAndDoesNotThrowWhenAllValid(): void
    {
        $c = new BodyFieldCollector(['name' => 'Acme', 'budget' => 1000, 'mode' => 'cpm']);

        self::assertSame('Acme', $c->requiredString('name', 'Name is required.'));
        self::assertSame(1000, $c->requiredInt('budget', 'Budget must be an integer.'));
        self::assertSame('cpm', $c->oneOf('mode', ['cpm', 'cpc'], 'Bad mode.'));
        $c->throwIfInvalid();

        $this->addToAssertionCount(1);
    }

    public function testRequiredStringTrimsWhenRequested(): void
    {
        $c = new BodyFieldCollector(['name' => '  Acme  ']);

        self::assertSame('Acme', $c->requiredString('name', 'Name is required.', trim: true));
        $c->throwIfInvalid();
        $this->addToAssertionCount(1);
    }

    public function testRequiredStringKeepsSurroundingSpaceWhenNotTrimming(): void
    {
        $c = new BodyFieldCollector(['name' => ' x ']);

        self::assertSame(' x ', $c->requiredString('name', 'Name is required.'));
        $c->throwIfInvalid();
        $this->addToAssertionCount(1);
    }

    /** @return iterable<string, array{array<string, mixed>, bool}> */
    public static function requiredStringRejections(): iterable
    {
        yield 'missing key' => [[], false];
        yield 'empty string' => [['name' => ''], false];
        yield 'non-string int' => [['name' => 5], false];
        yield 'non-string array' => [['name' => ['x']], false];
        yield 'null' => [['name' => null], false];
        yield 'whitespace only with trim' => [['name' => '   '], true];
    }

    /**
     * @param array<string, mixed> $body
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('requiredStringRejections')]
    public function testRequiredStringRecordsErrorAndReturnsEmptyDefault(array $body, bool $trim): void
    {
        $c = new BodyFieldCollector($body);

        self::assertSame('', $c->requiredString('name', 'Name is required.', trim: $trim));
        $this->expectException(ValidationException::class);
        $c->throwIfInvalid();
    }

    /** @return iterable<string, array{mixed}> */
    public static function requiredIntRejections(): iterable
    {
        yield 'missing' => [[]];
        yield 'numeric string' => [['n' => '5']];
        yield 'float' => [['n' => 1.5]];
        yield 'bool true' => [['n' => true]];
        yield 'null' => [['n' => null]];
    }

    /**
     * @param array<string, mixed> $body
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('requiredIntRejections')]
    public function testRequiredIntRecordsErrorAndReturnsZeroDefault(array $body): void
    {
        $c = new BodyFieldCollector($body);

        self::assertSame(0, $c->requiredInt('n', 'n must be an integer.'));
        $this->expectException(ValidationException::class);
        $c->throwIfInvalid();
    }

    public function testRequiredIntAcceptsZeroAndNegative(): void
    {
        $c = new BodyFieldCollector(['a' => 0, 'b' => -100]);

        self::assertSame(0, $c->requiredInt('a', 'bad'));
        self::assertSame(-100, $c->requiredInt('b', 'bad'));
        $c->throwIfInvalid();
        $this->addToAssertionCount(1);
    }

    public function testOneOfUsesDefaultWhenMissing(): void
    {
        $c = new BodyFieldCollector([]);

        self::assertSame('starttls', $c->oneOf('enc', ['none', 'starttls', 'tls'], 'bad', 'starttls'));
        $c->throwIfInvalid();
        $this->addToAssertionCount(1);
    }

    public function testOneOfRejectsValueOutsideTheSet(): void
    {
        $c = new BodyFieldCollector(['enc' => 'ssl']);

        self::assertSame('ssl', $c->oneOf('enc', ['none', 'starttls', 'tls'], 'bad', 'starttls'));
        $this->expectException(ValidationException::class);
        $c->throwIfInvalid();
    }

    public function testOneOfRejectsWhenDefaultIsNotInTheSet(): void
    {
        // No key, empty default → '' is not allowed → error.
        $c = new BodyFieldCollector([]);

        $c->oneOf('kind', ['export', 'erasure'], 'Kind must be export or erasure.');
        $this->expectException(ValidationException::class);
        $c->throwIfInvalid();
    }

    public function testAccumulatesEveryError(): void
    {
        $c = new BodyFieldCollector(['name' => '', 'budget' => 'x']);
        $c->requiredString('name', 'Name is required.');
        $c->requiredInt('budget', 'Budget must be an integer.');
        $c->oneOf('mode', ['cpm'], 'Bad mode.');

        try {
            $c->throwIfInvalid();
            self::fail('Expected ValidationException.');
        } catch (ValidationException $e) {
            $fields = array_map(static fn ($err) => $err->field, $e->errors());
            self::assertSame(['name', 'budget', 'mode'], $fields);
        }
    }

    public function testThrowIfInvalidIsNoOpWhenNothingValidated(): void
    {
        (new BodyFieldCollector([]))->throwIfInvalid();
        $this->addToAssertionCount(1);
    }
}
