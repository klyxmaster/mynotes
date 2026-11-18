<?php
declare(strict_types=1);
// Copy to smtp.php. Install PHPMailer with Composer in this project's vendor/ folder.
// Set your SMTP credentials in this local file only, never in GitHub.
function mynotes_send_code(string $email, string $code, int $minutes): bool
{
    $autoload = __DIR__ . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        error_log('MyNotes: PHPMailer not installed');
        return false;
    }
    require_once $autoload;
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.example.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'notes@example.com';
        $mail->Password = 'REPLACE_WITH_SMTP_APP_PASSWORD';
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom('notes@example.com', 'MyNotes');
        $mail->addAddress($email);
        $mail->Subject = 'Your MyNotes login code';
        $mail->Body = "Your MyNotes login code is: {$code}\nExpires in {$minutes} minutes.\nIf you did not request it, ignore this email.";
        return $mail->send();
    } catch (\Throwable $e) {
        error_log('MyNotes SMTP delivery failed');
        return false;
    }
}
