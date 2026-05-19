<?php
/**
 * Mailer SMTP minimal — pas de dépendance externe
 * Supporte : STARTTLS (port 587), SSL (port 465), authentification LOGIN
 */
class SmtpMailer {
    private string $host;
    private int    $port;
    private string $secure;   // 'tls' | 'ssl' | ''
    private string $user;
    private string $pass;
    private string $fromEmail;
    private string $fromName;
    private $socket = null;
    private int $timeout = 30;

    public function __construct(array $cfg) {
        $this->host      = $cfg['host']      ?? '';
        $this->port      = (int)($cfg['port'] ?? 587);
        $this->secure    = strtolower($cfg['secure'] ?? 'tls');
        $this->user      = $cfg['user']      ?? '';
        $this->pass      = $cfg['pass']      ?? '';
        $this->fromEmail = $cfg['from']      ?? $cfg['user'] ?? '';
        $this->fromName  = $cfg['from_name'] ?? '';
    }

    public function send(string $to, string $subject, string $htmlBody): void {
        $this->connect();
        $this->auth();
        $this->sendMail($to, $subject, $htmlBody);
        $this->quit();
    }

    private function connect(): void {
        $prefix = ($this->secure === 'ssl') ? 'ssl://' : '';
        $this->socket = @stream_socket_client(
            $prefix . $this->host . ':' . $this->port,
            $errno, $errstr, $this->timeout,
            STREAM_CLIENT_CONNECT
        );
        if (!$this->socket) {
            throw new RuntimeException("Connexion SMTP impossible : $errstr ($errno)");
        }
        stream_set_timeout($this->socket, $this->timeout);
        $this->expect(220);
        $this->cmd("EHLO " . gethostname(), 250);

        if ($this->secure === 'tls') {
            $this->cmd("STARTTLS", 220);
            stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $this->cmd("EHLO " . gethostname(), 250);
        }
    }

    private function auth(): void {
        $this->cmd("AUTH LOGIN", 334);
        $this->cmd(base64_encode($this->user), 334);
        $this->cmd(base64_encode($this->pass), 235);
    }

    private function sendMail(string $to, string $subject, string $htmlBody): void {
        $from = $this->fromName
            ? '"' . $this->fromName . '" <' . $this->fromEmail . '>'
            : $this->fromEmail;

        $boundary = md5(uniqid('', true));
        $headers  = implode("\r\n", [
            "From: $from",
            "To: $to",
            "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=",
            "MIME-Version: 1.0",
            "Content-Type: multipart/alternative; boundary=\"$boundary\"",
            "Date: " . date('r'),
        ]);

        $plainText = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));
        $body = "--$boundary\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($plainText)) . "\r\n"
            . "--$boundary\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($htmlBody)) . "\r\n"
            . "--$boundary--";

        $this->cmd("MAIL FROM:<{$this->fromEmail}>", 250);
        $this->cmd("RCPT TO:<$to>", 250);
        $this->cmd("DATA", 354);
        fwrite($this->socket, $headers . "\r\n\r\n" . $body . "\r\n.\r\n");
        $this->expect(250);
    }

    private function quit(): void {
        $this->cmd("QUIT", 221);
        fclose($this->socket);
    }

    private function cmd(string $cmd, int $expect): string {
        fwrite($this->socket, $cmd . "\r\n");
        return $this->expect($expect);
    }

    private function expect(int $code): string {
        $response = '';
        while ($line = fgets($this->socket, 512)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        $actual = (int)substr($response, 0, 3);
        if ($actual !== $code) {
            throw new RuntimeException("SMTP attendait $code, reçu $actual : " . trim($response));
        }
        return $response;
    }
}
