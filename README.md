# MyNotes

**MyNotes** is a self-hosted, browser-based note organizer built with procedural PHP, MySQL, JavaScript, and CSS. It provides a dark-themed dashboard for writing, importing, finding, and organizing notes without depending on a hosted notes service.

## Screenshot

![MyNotes Dark-Themed Notes Dashboard](images/MyNotes%20Dark-Themed%20Notes%20Dashboard.png)

## Features

### Notes and editing
- Create and edit notes with a title and body.
- Choose **Text** or **Checklist** note types. Checklist content is stored as text, with one item per line.
- Pin or unpin important notes so they appear first.
- Archive and unarchive notes.
- Move notes to Trash, restore individual notes, or **Empty Trash** to permanently delete all trashed notes.
- Trash a note directly from its editing window.
- Search note titles and bodies.
- Receive warnings about exact duplicate notes or existing notes with the same title when creating a note; optionally save anyway.
- Retain the page scroll position after common note actions.

### Categories and tags
- Create top-level categories and subcategories.
- Assign an existing category or create a new category/subcategory while editing a note.
- Filter the active-note dashboard using category checkboxes in the sidebar. Selected parent categories include their subcategories.
- Keep pinned notes visible in the active dashboard even when filtering categories.
- Add comma-separated tags to notes.

### Bulk operations
- Select multiple note cards using the **+** button; selected notes display a check mark.
- See the number of selected notes in the left sidebar.
- Move selected notes to an existing category, or create a new top-level category during the operation.
- Archive or trash selected notes in one action, with confirmation for destructive operations.
- Clear the current selection. Bulk actions accept up to **300 note IDs** per request.
- The normal category controls are hidden while bulk selection is active, keeping bulk tools accessible in the sidebar rather than a floating overlay.

### Google Keep import
- Import notes from a **Google Takeout ZIP** containing Google Keep JSON files.
- Import note titles, text, checklist items and checked states, labels, original timestamps, pin state, and archive state.
- Use the first imported label as the category while retaining all labels as tags.
- Skip previously imported identical JSON entries using a source hash.
- Import supported attachments to private storage when present in the archive: JPEG, PNG, GIF, WebP, MP3, MP4 audio, OGG audio, and PDF, subject to file-size and validation limits.
- Display counts of imported, skipped, and unreadable notes.

### External links
- Detect HTTP and HTTPS links in the note editor and show clickable previews without modifying the note text.
- Open preview links in a new browser tab.
- Display YouTube thumbnails for supported YouTube video and Shorts URLs when available.
- Other sites, including Facebook and Instagram, receive clickable URL cards; thumbnail images are **not guaranteed**.
- Show up to five distinct link previews per note.

### Interface
- Dark theme with yellow accents, card-based note grid, and favicon.
- Sidebar for All Notes, Archive, Trash, and category filters.
- Responsive navigation with a menu toggle for narrower screens.
- Editor dialog for adding and updating notes.

### Accounts and administration
- Sign in with an emailed six-digit verification code; no conventional account password is required.
- Restrict access to explicitly allowed email addresses.
- Keep notes and categories associated with their owning user.
- Administrator controls for allowing/disabling users, changing the administrator's email, session inactivity timeout, code expiration, and maximum import size.
- HTTPS requirement outside localhost, CSRF protection for forms, secure session cookies, prepared database queries, output escaping, and security response headers.

## Requirements

- Web server with **PHP 8.1+** and MySQL/MariaDB.
- PHP extensions: PDO MySQL, mbstring, ZipArchive, and fileinfo.
- HTTPS for a deployed installation.
- Working SMTP delivery for sign-in codes; the supplied SMTP template uses **PHPMailer** and its Composer autoloader.
- A writable, access-protected `storage/` directory for imported attachments.

## Installation

1. Create a dedicated MySQL database and user, then import `schema.sql` with phpMyAdmin or another MySQL client.
2. Copy `config.dist.php` to `config.php`. Set your database host, name, username, password, and mail sender address.
3. Copy `smtp.dist.php` to `smtp.php`. Configure the SMTP host, sender, credentials, encryption, and port. Install PHPMailer or adapt its autoloader path to your existing installation.
4. Create a `storage/` directory writable by PHP and **prevent direct web access** to its contents. Keeping storage outside the public web root is preferable, but requires adjusting the storage paths in the application.
5. Add your first administrator to `mn_users`, replacing the example email:

   ```sql
   INSERT INTO mn_users (email, enabled, is_admin)
   VALUES ('you@example.com', 1, 1);
   ```

6. Upload the application to your HTTPS-enabled web directory. Ensure the server uses the included access-control configuration where applicable.
7. Open `login.php`, request a code, and confirm that email delivery and sign-in work.
8. If migrating from Google Keep, export your notes through Google Takeout, then upload the ZIP through **Import**. Test a small archive first.

## Everyday use

1. Use **+ New note** to write a note, choose its type, and assign a category or tags.
2. Select categories in the left sidebar to filter the dashboard. Use the search field to find matching titles or note bodies.
3. Open a note card to edit it or follow its link previews.
4. Use **Pin**, **Archive**, or **Trash** on a card for quick actions.
5. For many notes at once, press **+** on each card, then use the bulk controls in the sidebar to categorize, archive, or trash the selection.
6. Visit **Trash** to restore notes or permanently empty the trash.

## Configuration and repository safety

The repository includes `config.dist.php` and `smtp.dist.php` as examples. **Never commit real credentials** from `config.php` or `smtp.php`, or private files under `storage/`.

The `.gitignore` should exclude these private files and folders. Check staged files before every push:

```bash
git add .
git diff --cached --name-only
git commit -m "Describe the update"
git push
```

Pushing to GitHub does **not** automatically update a live hosting installation unless a deployment workflow has been configured separately.

## Current limitations

- Imported attachment files and metadata are supported, but a complete attachment gallery, upload manager, and inline media viewer are not implemented.
- Facebook, Instagram, and other sites may not expose usable thumbnails. Their links remain clickable.
- Checklist items are represented as text rather than an interactive check-off interface.
- Duplicate detection warns about exact matches and matching titles; there is no fuzzy similarity engine or automatic merge tool.
- Google Keep import runs synchronously and may time out on very large archives. The importer limits archive entries and total uncompressed size.
- The dashboard currently limits results to 300 notes per query; it does not provide pagination.
- This is a self-hosted project, **not a security-audited service**. Review access controls, backups, and server configuration before using it for sensitive information.

## Backups

Back up **both** the MySQL database and the private `storage/` directory. A GitHub copy of the application code is not a backup of your notes or attachments.

