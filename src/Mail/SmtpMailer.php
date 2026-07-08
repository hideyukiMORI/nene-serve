<?php

declare(strict_types=1);

namespace NeneServe\Mail;

use Nene2\Http\ClockInterface;
use Nene2\Http\UtcClock;

/**
 * Dependency-free SMTP client (fsockopen) supporting STARTTLS and AUTH LOGIN —
 * enough for Mailpit in dev and a standard authenticated submission server in
 * production. Framework-free, consistent with the rest of the runtime.
 *
 * Any protocol deviation throws {@see MailerException}; the connection is always
 * closed. The password is never logged.
 */
final class SmtpMailer implements MailerInterface
{
    /** @var resource|null */
    private $socket;

    public function __construct(
        private readonly SmtpConfig $config,
        private readonly ClockInterface $clock = new UtcClock(),
    ) {
    }

    public function send(Email $email): void
    {
        $this->connect();
        try {
            $this->expect(220);
            $this->ehlo();

            if ($this->config->encryption === 'starttls') {
                $this->command('STARTTLS', 220);
                $this->enableCrypto();
                $this->ehlo();
            }

            if ($this->config->usesAuth()) {
                $this->authenticate();
            }

            $this->command('MAIL FROM:<' . $this->config->fromAddress . '>', 250);
            $this->command('RCPT TO:<' . $email->toAddress . '>', 250, 251);
            $this->command('DATA', 354);
            $this->write($this->buildMessage($email) . "\r\n.\r\n");
            $this->expect(250);
            $this->write("QUIT\r\n");
        } finally {
            $this->close();
        }
    }

    private function connect(): void
    {
        $prefix = $this->config->encryption === 'tls' ? 'ssl://' : '';
        $errno = 0;
        $errstr = '';
        $socket = @fsockopen(
            $prefix . $this->config->host,
            $this->config->port,
            $errno,
            $errstr,
            (float) $this->config->timeoutSeconds,
        );
        if ($socket === false) {
            throw new MailerException(sprintf('SMTP connect failed: %s (%d)', $errstr, $errno));
        }
        stream_set_timeout($socket, $this->config->timeoutSeconds);
        $this->socket = $socket;
    }

    private function ehlo(): void
    {
        $this->command('EHLO ' . $this->clientName(), 250);
    }

    private function authenticate(): void
    {
        $this->command('AUTH LOGIN', 334);
        $this->command(base64_encode($this->config->username), 334);
        $this->command(base64_encode($this->config->password), 235);
    }

    private function enableCrypto(): void
    {
        $socket = $this->requireSocket();
        $ok = stream_socket_enable_crypto(
            $socket,
            true,
            STREAM_CRYPTO_METHOD_TLS_CLIENT,
        );
        if ($ok !== true) {
            throw new MailerException('STARTTLS negotiation failed.');
        }
    }

    private function buildMessage(Email $email): string
    {
        $headers = [
            'From: ' . $this->formatFrom(),
            'To: <' . $email->toAddress . '>',
            'Subject: ' . $this->encodeHeader($email->subject),
            'Date: ' . $this->clock->now()->format('D, d M Y H:i:s') . ' +0000',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
        ];

        return implode("\r\n", $headers) . "\r\n\r\n"
            . trim(chunk_split(base64_encode($email->textBody)));
    }

    private function formatFrom(): string
    {
        $address = '<' . $this->config->fromAddress . '>';

        return $this->config->fromName === ''
            ? $address
            : $this->encodeHeader($this->config->fromName) . ' ' . $address;
    }

    private function encodeHeader(string $value): string
    {
        // RFC 2047 encoded-word so non-ASCII subjects/names survive transport.
        if (preg_match('/^[\x20-\x7E]*$/', $value) === 1) {
            return $value;
        }

        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    private function clientName(): string
    {
        $host = gethostname();

        return $host === false ? 'localhost' : $host;
    }

    private function command(string $line, int ...$expected): void
    {
        $this->write($line . "\r\n");
        $this->expect(...$expected);
    }

    private function write(string $data): void
    {
        $written = @fwrite($this->requireSocket(), $data);
        if ($written === false) {
            throw new MailerException('SMTP write failed.');
        }
    }

    private function expect(int ...$codes): void
    {
        $code = $this->readReplyCode();
        if (!in_array($code, $codes, true)) {
            throw new MailerException('Unexpected SMTP reply code ' . $code . '.');
        }
    }

    private function readReplyCode(): int
    {
        $socket = $this->requireSocket();
        $code = 0;
        // SMTP multiline replies use "NNN-" for continuation and "NNN " for the last.
        do {
            $line = fgets($socket, 515);
            if ($line === false) {
                throw new MailerException('SMTP connection closed unexpectedly.');
            }
            $code = (int) substr($line, 0, 3);
            $continues = isset($line[3]) && $line[3] === '-';
        } while ($continues);

        return $code;
    }

    /** @return resource */
    private function requireSocket()
    {
        if ($this->socket === null) {
            throw new MailerException('SMTP socket is not open.');
        }

        return $this->socket;
    }

    private function close(): void
    {
        if ($this->socket !== null) {
            @fclose($this->socket);
            $this->socket = null;
        }
    }
}
