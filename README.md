# MyNotes 0.1 - deployment preview
Procedural PHP 8.1+ / MySQL / JavaScript. No framework or external services except outgoing email.

## Installation
1. Create `/MyNotes` on hosting.com (or a separate subdomain document root). Do not overwrite the existing book website.
2. Import `schema.sql` into your existing MySQL database via phpMyAdmin. All tables use the `mn_` prefix.
3. Copy `config.example.php` to `config.php`, enter the hosting database connection values **on your own computer**, and configure a verified mail sender and random secret. Do not share credentials in chat.
4. In phpMyAdmin, allow your first account manually: `INSERT INTO mn_users(email,enabled,is_admin) VALUES ('YOUR_EMAIL',1,1);` Change YOUR_EMAIL to your actual email.
5. Upload the PHP, CSS, JS, and .htaccess files to `/MyNotes`. Ensure `storage` exists, is writable by PHP, and is protected from direct web access. Ideally move storage outside the web root and update the two file paths.
6. Visit `/MyNotes/login.php` over HTTPS and test a six-digit code before importing. PHP `mail()` needs to be configured and verified with your hosting provider; successful acceptance does not guarantee delivery.
7. Import a small Google Keep Takeout ZIP first, verify counts and dates, then import the full archive. The ZIP importer requires PHP ZipArchive and fileinfo extensions.

## Limitations and next release
- This is an MVP preview, **not yet production-audited**. Do not put confidential notes on a public host until deployment security is tested.
- Supports text, checklist text, labels/tags, categories, archive, pin, trash, exact duplicate and same-title warning, basic full-text-like search, per-user ownership, code sign-in and admin settings.
- Attachments are stored and metadata imported but no attachment gallery/management UI yet. No live fuzzy similarity scoring or merge interface yet. The current duplicate check warns on exact match or same title; manual merge is not implemented.
- ZIP import is synchronous and may time out on thousands of notes. For production, implement chunked background-safe imports with resumable progress and transactions.
- Automatic category creation during entry and import is supported; parent categories are selected from existing categories. Deeper nesting is not yet guarded against circular edits (category editing is not implemented).
- Session timeout setting is an inactivity limit; code verification requires a functioning mail server.
- Do not upload the book-site ZIP, database password, or private authentication files to the public directory.
- Back up MySQL database and storage directory routinely.
