<?php

use PHPMailer\PHPMailer\PHPMailer;

class MailService
{
    // Store SMTP settings supplied by config; keep credentials out of this file.
    public function __construct(private array $config) {}

    // Call after preparing the reset link to send a subject and body to the recipient.
    public function send(
        string $to,
        string $subject,
        string $body
    ): void {
        // 1. Create a fresh message and enable exceptions for delivery failures.
        $mail = new PHPMailer(true);

        // 2. Select SMTP and configure the server address and port.
        $mail->isSMTP();
        $mail->Host = $this->config['host'];
        $mail->Port = $this->config['port'];
        // 3. Authenticate with the configured email account and app password.
        $mail->SMTPAuth = true;
        $mail->Username = $this->config['username'];
        $mail->Password = $this->config['password'];

        // 4. Read the connection encryption setting and reject unsupported values.
        $encryption = $this->config['encryption'];

        if (!in_array($encryption, ['tls', 'ssl'], true)) {
            throw new InvalidArgumentException(
                'Unsupported mail encryption.'
            );
        }

        // 5. Apply STARTTLS or implicit TLS according to the configuration.
        $mail->SMTPSecure = $encryption === 'tls'
            ? PHPMailer::ENCRYPTION_STARTTLS
            : PHPMailer::ENCRYPTION_SMTPS;

        // 6. Support UTF-8 text and set the SMTP timeout.
        $mail->CharSet = 'UTF-8';
        $mail->Timeout = 20;

        // 7. Set the sender address and display name.
        $mail->setFrom(
            $this->config['from_address'],
            $this->config['from_name']
        );

        // 8. Set the recipient and compose a plain-text message.
        $mail->addAddress($to);
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body = $body;

        // 9. Send the email; the caller must handle any exception.
        $mail->send();
    }
}
