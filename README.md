# CSV Repair Toolkit

A production-ready online tools website built with **Core PHP 8.2**, **MySQL**, **Bootstrap 5**, and vanilla JavaScript. No Laravel, no frameworks — just clean, modular PHP.

---

## Table of Contents

1. [Features](#features)
2. [Requirements](#requirements)
3. [Project Structure](#project-structure)
4. [Local Development Setup](#local-development-setup)
5. [Database Setup](#database-setup)
6. [Configuration](#configuration)
7. [Hostinger Deployment](#hostinger-deployment)
8. [Admin Panel](#admin-panel)
9. [File Permissions](#file-permissions)
10. [Security Notes](#security-notes)
11. [AdSense Setup](#adsense-setup)
12. [Troubleshooting](#troubleshooting)

---

## Features

| Tool | Description |
|---|---|
| CSV Repair | Fix broken quotes, encoding, BOM, mismatched columns, formula injection |
| Encoding Detector | Detect UTF-8, UTF-16, ISO-8859-1, Windows-1252, and more |
| Delimiter Converter | Convert between comma, semicolon, tab, pipe, or custom delimiters |
| CSV Validator | Row-level structural and encoding validation with error report |
| Remove Duplicates | Deduplicate by all columns or selected key columns |
| CSV to JSON | Convert to array-of-objects, array-of-arrays, or keyed object |
| CSV Merger | Combine up to 10 CSV files by column name or position |
| CSV Splitter | Split large files into chunks, each with the header row |
| File Comparison | Visual diff of two CSV files — added, removed, modified rows |
| CSV Viewer | 100% browser-based viewer — file never leaves your device |

**Additional:**
- Admin panel with tool/FAQ/category/settings management
- Dynamic XML sitemap
- CSRF protection on all POST forms
- Secure temporary file handling (outside web root, auto-deleted)
- SEO metadata, Open Graph, structured data (WebApplication, FAQPage)
- Google AdSense-ready layout
- Responsive Bootstrap 5 UI

---

## Requirements

| Requirement | Minimum |
|---|---|
| PHP | 8.1 or higher |
| MySQL / MariaDB | 5.7 / 10.3 or higher |
| PHP Extensions | `pdo_mysql`, `mbstring`, `fileinfo`, `json`, `iconv` |
| Apache | `mod_rewrite` enabled |
| Disk space | 50 MB minimum |

---

## Project Structure

```
csvtoolkit.online/
├── index.php                  ← Front controller
├── .htaccess                  ← URL rewriting + security headers
├── robots.txt
├── sitemap.php
├── ads.txt
├── config/
│   ├── config.php             ← App config (env vars preferred)
│   ├── database.php           ← PDO singleton
│   └── app.php                ← Route map
├── includes/
│   ├── header.php
│   ├── navbar.php
│   ├── footer.php
│   ├── functions.php          ← Helpers, DB queries, file utils
│   ├── csrf.php
│   ├── seo.php
│   ├── download.php           ← Secure file download endpoint
│   └── tool-layout.php        ← Shared tool page wrapper
├── assets/
│   ├── css/main.css
│   ├── js/main.js
│   └── images/favicon.svg
├── tools/
│   ├── csv-repair/index.php
│   ├── csv-encoding-detector/index.php
│   ├── csv-delimiter-converter/index.php
│   ├── csv-validator/index.php
│   ├── remove-duplicates/index.php
│   ├── csv-to-json/index.php
│   ├── csv-merger/index.php
│   ├── csv-splitter/index.php
│   ├── csv-file-comparison/index.php
│   └── csv-viewer/index.php
├── services/
│   ├── CsvParser.php
│   ├── CsvRepairService.php
│   ├── CsvValidationService.php
│   ├── CsvConversionService.php
│   ├── CsvCleaningService.php
│   └── CsvComparisonService.php
├── admin/
│   ├── index.php              ← Admin router
│   ├── auth.php
│   ├── login.php
│   ├── logout.php
│   ├── layout.php
│   ├── dashboard.php
│   ├── tools.php
│   ├── tools-edit.php
│   ├── categories.php
│   ├── faqs.php
│   ├── messages.php
│   └── settings.php
├── pages/
│   ├── about.php
│   ├── contact.php
│   ├── privacy-policy.php
│   ├── terms.php
│   └── blog.php
├── views/home/index.php
├── errors/404.php
├── storage/temp/              ← Temp files (outside web root recommended)
├── database/
│   ├── schema.sql
│   └── seed.sql
└── README.md
```

---

## Local Development Setup

### 1. XAMPP / MAMP / Laragon

```bash
# Place the project in your web root
# XAMPP:   /Applications/XAMPP/xamppfiles/htdocs/csvtoolkit.online/
# Windows: C:\xampp\htdocs\csvtoolkit.online\

# Make sure mod_rewrite is enabled in httpd.conf:
# LoadModule rewrite_module modules/mod_rewrite.so
# AllowOverride All  (in the <Directory> block for htdocs)
```

### 2. Create the database

```sql
-- In phpMyAdmin or MySQL CLI:
SOURCE /path/to/csvtoolkit.online/database/schema.sql;
SOURCE /path/to/csvtoolkit.online/database/seed.sql;
```

### 3. Configure environment variables

The preferred method is environment variables (no credentials in files):

```bash
# Set in your Apache VirtualHost or .env loader:
export DB_HOST=127.0.0.1
export DB_NAME=csvtoolkit
export DB_USER=root
export DB_PASS=
export APP_URL=http://localhost/csvtoolkit.online
export APP_ENV=development
```

Alternatively, edit `config/config.php` directly **only for local dev**:

```php
define('APP_URL', 'http://localhost/csvtoolkit.online');
define('APP_ENV', 'development');
// DB_* constants can be set directly for local testing
```

> ⚠️ Never commit real credentials to Git.

### 4. Set storage permissions

```bash
chmod 755 storage/
chmod 755 storage/temp/
```

### 5. Visit the site

```
http://localhost/csvtoolkit.online/
```

---

## Database Setup

```bash
# Full setup in one command:
mysql -u root -p < database/schema.sql
mysql -u root -p < database/seed.sql
```

This creates:
- `tool_categories` — 5 categories
- `tools` — 10 tools with SEO metadata
- `tool_faqs` — sample FAQs per tool
- `site_settings` — configurable key-value settings
- `admin_users` — default admin account
- `contact_messages` — contact form storage

**Default admin credentials (change immediately):**

| Field | Value |
|---|---|
| Username | `admin` |
| Password | `Admin@123` |

Change via Admin → Settings → Change Password.

---

## Configuration

### config/config.php — Key constants

| Constant | Default | Description |
|---|---|---|
| `APP_URL` | env `APP_URL` | Production base URL (no trailing slash) |
| `APP_ENV` | `production` | `development` or `production` |
| `MAX_FILE_SIZE` | `10485760` (500 MB) | Max upload size in bytes |
| `MAX_MERGE_FILES` | `10` | Max files for CSV Merger |
| `STORAGE_PATH` | `storage/temp` | Temp file directory |
| `TEMP_FILE_TTL` | `3600` | Seconds before temp files are deleted |
| `ADSENSE_PUBLISHER_ID` | env `ADSENSE_PUBLISHER_ID` | AdSense pub-XXXXXXX ID |
| `SESSION_LIFETIME` | `1800` | Admin session lifetime in seconds |

---

## Hostinger Deployment

### 1. Upload files

Upload all files to your Hostinger `public_html/` directory (or a subdirectory).

```
public_html/
├── index.php
├── .htaccess
├── robots.txt
├── sitemap.php
└── ... (all other files)
```

### 2. Move storage outside public_html (recommended)

```bash
# On Hostinger, you can place storage one level up:
mkdir /home/username/storage/temp

# Then update STORAGE_PATH in config/config.php:
define('STORAGE_PATH', '/home/username/storage/temp');
```

### 3. Create the database

1. Log in to Hostinger hPanel
2. Go to **Databases → MySQL Databases**
3. Create a database and user
4. Open **phpMyAdmin**, select your database
5. Import `database/schema.sql` then `database/seed.sql`

### 4. Set environment / config

On Hostinger, set environment variables via hPanel → **Advanced → PHP Configuration**, or edit `config/config.php` directly:

```php
define('APP_URL', 'https://csvtoolkit.online');
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'u123456_csvtoolkit');
define('DB_USER', 'u123456_admin');
define('DB_PASS', 'your_secure_password');
```

### 5. Verify .htaccess

If clean URLs are not working, confirm **mod_rewrite** is enabled in hPanel → **Advanced → Apache Settings**.

### 6. Set file permissions

```bash
chmod 644 .htaccess
chmod 644 index.php
chmod 755 storage/
chmod 755 storage/temp/
find . -name "*.php" -exec chmod 644 {} \;
```

### 7. Test key URLs

```
https://csvtoolkit.online/
https://csvtoolkit.online/csv-repair/
https://csvtoolkit.online/sitemap.php
https://csvtoolkit.online/robots.txt
https://csvtoolkit.online/admin/
```

---

## Admin Panel

Access at: `https://csvtoolkit.online/admin/`

| Page | URL | Description |
|---|---|---|
| Login | `/admin/login/` | Secure login |
| Dashboard | `/admin/dashboard/` | Stats overview |
| Tools | `/admin/tools/` | Publish/unpublish tools |
| Edit Tool | `/admin/tools/edit/?id=X` | Edit SEO, content, metadata |
| Categories | `/admin/categories/` | Manage categories |
| FAQs | `/admin/faqs/` | Manage per-tool FAQs |
| Messages | `/admin/messages/` | View contact form submissions |
| Settings | `/admin/settings/` | Site name, AdSense ID, analytics |

**First login:**
1. Go to `/admin/`
2. Username: `admin` / Password: `Admin@123`
3. Immediately go to **Settings** and change the password

---

## File Permissions

| Path | Permission | Reason |
|---|---|---|
| `storage/temp/` | `755` | PHP must write temp files |
| `config/config.php` | `640` | Protect credentials |
| `database/` | `750` | Prevent public access |
| `services/` | `750` | Prevent direct access |
| All `.php` files | `644` | Standard web files |
| `.htaccess` | `644` | Must be readable by Apache |

---

## Security Notes

- Temporary files are stored **outside the public web root** when `STORAGE_PATH` is configured above `public_html/`
- All file downloads go through `includes/download.php` which validates the token and deletes after serving
- CSRF tokens are required on all state-changing POST forms
- Admin passwords use `password_hash()` with bcrypt cost 12
- SQL queries use PDO prepared statements throughout
- XSS output escaping via `e()` and `eAttr()` helpers
- Session ID is regenerated on login and every 5 minutes
- Rate limiting on contact form (3 per IP per hour)
- `.htaccess` blocks direct access to `config/`, `storage/`, `database/`, `services/`
- In `production` mode, PHP errors are logged to `storage/error.log` and never displayed

---

## AdSense Setup

1. Apply for Google AdSense at [adsense.google.com](https://adsense.google.com)
2. After approval, get your **Publisher ID** (format: `ca-pub-XXXXXXXXXXXXXXXXX`)
3. In Admin → Settings, enter the Publisher ID and enable ads
4. Update `ads.txt` with your actual Publisher ID:
   ```
   google.com, pub-XXXXXXXXXXXXXXXXX, DIRECT, f08c47fec0942fa0
   ```
5. Ad slots are already reserved in `includes/tool-layout.php` — they activate automatically

> AdSense approval requires original content, a Privacy Policy, and real traffic. The About, Contact, Privacy Policy, and Terms pages are already in place.

---

## Troubleshooting

### Clean URLs not working (404 on all pages)
- Confirm `mod_rewrite` is enabled
- Confirm `AllowOverride All` is set in the Apache `<Directory>` block
- Check `.htaccess` is uploaded and not renamed

### Database connection error
- Verify `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` values
- On Hostinger, `DB_HOST` is usually `127.0.0.1`
- Ensure the database and user exist and the user has full privileges

### File upload fails
- Check `upload_max_filesize` and `post_max_size` in `php.ini` (≥ 12M)
- Verify `storage/temp/` is writable: `chmod 755 storage/temp/`
- Check available disk space

### Tools show "Could not read file"
- Verify the temp storage path exists and is writable
- Check PHP `open_basedir` restrictions on your host

### Admin login not working
- Ensure the database is seeded: `mysql -u root -p < database/seed.sql`
- Try resetting the password hash directly in the DB:
  ```sql
  UPDATE admin_users
  SET password_hash = '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
  WHERE username = 'admin';
  -- This sets the password to: Admin@123
  ```

### Sitemap shows wrong URLs
- Set `APP_URL` to your production domain (no trailing slash) in `config/config.php`

### Session issues on shared hosting
- Some hosts restrict session save paths. Add to `.htaccess`:
  ```apache
  php_value session.save_path "/home/username/sessions"
  ```

---

## License

MIT License. Free to use, modify, and deploy.

---

*Built with Core PHP 8.2 · MySQL · Bootstrap 5 · No frameworks required*
