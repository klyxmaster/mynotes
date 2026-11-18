# MyNotes

A self-hosted PHP/MySQL notes application with categories, tags, search, pinning, trash, Google Keep import, and link previews.

## Requirements

- PHP 8.1+ with PDO MySQL, mbstring, ZipArchive and fileinfo
- MySQL or MariaDB
- HTTPS
- SMTP credentials and PHPMailer (install with `composer require phpmailer/phpmailer`)

## Installation

1. Import `schema.sql` into a MySQL database.
2. Copy `config.dist.php` to `config.php` and enter your own database details, mail sender, and unique random secret.
3. Copy `smtp.dist.php` to `smtp.php` and enter your SMTP server settings. Install PHPMailer in `vendor/` with Composer.
4. Create the first authorized user in MySQL: `INSERT INTO mn_users(email,enabled,is_admin) VALUES ('you@example.com',1,1);` Substitute your email address.
5. Upload the application files to your HTTPS-enabled PHP web host. Ensure `storage/` is writable by PHP and direct web access to it is blocked.
6. Sign in at `login.php` and verify delivery of the six-digit email code.
7. Import a small Google Keep Takeout ZIP before attempting a larger import.

## Security and deployment

Do not upload real `config.php`, `smtp.php`, note contents, database exports, or server logs to GitHub. `.gitignore` protects only untracked files. Verify your web server honors `.htaccess`; for non-Apache servers configure equivalent restrictions. Keep your installation private unless you have reviewed its security and backups.

## Notes

This repository is a source distribution, not a hosted demo. The `smtp.dist.php` template is standalone and requires locally installed PHPMailer. The original author's live installation uses a separate private PHPMailer installation. Social media preview images may be unavailable depending on provider access.

## License

MIT. See `LICENSE`.
