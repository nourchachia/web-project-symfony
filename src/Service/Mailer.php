<?php
namespace App\Service;

class Mailer
{
    public function __construct(
        private string $smtpHost,
        private int    $smtpPort,
        private string $smtpUser,
        private string $smtpPass,
        private string $smtpFrom,
        private string $smtpFromName,
    ) {}

    public function send(string $to, string $subject, string $htmlBody): bool
    {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ]
        ]);

        $errno = 0; $errstr = '';
        $socket = @stream_socket_client(
            "tcp://{$this->smtpHost}:{$this->smtpPort}",
            $errno, $errstr, 15,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            error_log("[Mailer] Connection failed: {$errstr}");
            return false;
        }

        stream_set_timeout($socket, 15);

        $cmd = function(string $command) use ($socket): string {
            if ($command !== '') fwrite($socket, $command . "\r\n");
            $response = '';
            while ($line = fgets($socket, 512)) {
                $response .= $line;
                if (isset($line[3]) && $line[3] === ' ') break;
            }
            return $response;
        };

        $cmd('');
        $cmd('EHLO localhost');

        $r = $cmd('STARTTLS');
        if (strpos($r, '220') === false) {
            fclose($socket); return false;
        }

        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            error_log('[Mailer] TLS handshake failed.');
            fclose($socket); return false;
        }

        $cmd('EHLO localhost');
        $cmd('AUTH LOGIN');
        $cmd(base64_encode($this->smtpUser));
        $r = $cmd(base64_encode($this->smtpPass));

        if (strpos($r, '235') === false) {
            error_log('[Mailer] SMTP authentication failed.');
            fclose($socket); return false;
        }

        $cmd("MAIL FROM:<{$this->smtpFrom}>");
        $cmd("RCPT TO:<{$to}>");
        $cmd('DATA');

        $date  = date('r');
        $msgId = '<' . bin2hex(random_bytes(8)) . '@theatro.insat>';
        $message =
            "Date: {$date}\r\n" .
            "From: {$this->smtpFromName} <{$this->smtpFrom}>\r\n" .
            "To: {$to}\r\n" .
            "Message-ID: {$msgId}\r\n" .
            "Subject: {$subject}\r\n" .
            "MIME-Version: 1.0\r\n" .
            "Content-Type: text/html; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: base64\r\n" .
            "\r\n" .
            chunk_split(base64_encode($htmlBody)) .
            "\r\n.\r\n";

        fwrite($socket, $message);
        $r = $cmd('');

        $cmd('QUIT');
        fclose($socket);

        return strpos($r, '250') !== false;
    }
}