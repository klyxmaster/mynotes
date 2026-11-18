<?php
declare(strict_types=1);

// Example SMTP implementation. Copy to smtp.php, configure PHPMailer,
// and ensure PHPMailer is installed via Composer (vendor/autoload.php).
use PHPMailer\PHPMailer\PHPMailer;

function mynotes_send_code(string $email, string $code, int $minutes): bool
{
    // This is the function signature used by the current MyNotes installation.
    // If your login.php uses a different signature, adapt this example accordingly.
    require_once __DIR__ . '/vendor/autoload.php';
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.example.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'notes@example.com';
        $mail->Password = 'your_smtp_app_password';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('notes@example.com', 'MyNotes');
        $mail->addAddress($email);
        $mail->Subject = 'MyNotes login code';
        $mail->Body = "Your MyNotes login code is: {$code}\nExpires in {$minutes} minutes.";
        return $mail->send();
    } catch (\Throwable $e) {
        error_log('MyNotes SMTP delivery failed');
        return false;
    }
}
