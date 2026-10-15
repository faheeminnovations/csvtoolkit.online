-- =============================================================================
--  CSV Repair Toolkit — Seed Data
-- =============================================================================

USE `csvtoolkit`;

-- ── Categories ────────────────────────────────────────────────────────────────
INSERT INTO `tool_categories` (`name`, `slug`, `description`, `icon`, `sort_order`, `status`) VALUES
('Repair & Fix',    'repair-fix',    'Tools for repairing and fixing broken or malformed CSV files.', 'bi-tools',          1, 1),
('Convert',         'convert',       'Convert CSV files to and from other formats.',                   'bi-arrow-left-right',2, 1),
('Analyze',         'analyze',       'Analyze, validate, and inspect CSV file content.',               'bi-search',         3, 1),
('Clean & Process', 'clean-process', 'Clean, deduplicate, merge, and split CSV data.',                 'bi-funnel',         4, 1),
('View & Compare',  'view-compare',  'View CSV file contents and compare two CSV files side by side.', 'bi-eye',            5, 1);

-- ── Tools ─────────────────────────────────────────────────────────────────────
INSERT INTO `tools` (`category_id`, `name`, `slug`, `short_description`, `meta_title`, `meta_description`, `h1`, `is_popular`, `sort_order`, `status`) VALUES

(1, 'CSV Repair Tool',
 'csv-repair',
 'Automatically detect and fix common CSV formatting errors including broken quotes, mismatched columns, and encoding issues.',
 'CSV Repair Tool — Fix Broken CSV Files Online Free',
 'Repair malformed CSV files instantly. Our free CSV Repair Tool auto-detects and fixes broken quotes, mismatched delimiters, encoding issues, and column count errors.',
 'CSV Repair Tool — Fix Broken CSV Files Instantly',
 1, 1, 1),

(3, 'CSV Encoding Detector',
 'csv-encoding-detector',
 'Detect the character encoding of any CSV file — UTF-8, UTF-16, ISO-8859-1, Windows-1252, and more.',
 'CSV Encoding Detector — Detect File Encoding Online Free',
 'Instantly detect the character encoding of any CSV file. Supports UTF-8, UTF-16, ISO-8859-1, Windows-1252, and dozens more encodings.',
 'CSV Encoding Detector — Detect Character Encoding Online',
 1, 2, 1),

(2, 'CSV Delimiter Converter',
 'csv-delimiter-converter',
 'Convert CSV files between delimiters — comma, semicolon, tab, pipe, and custom characters.',
 'CSV Delimiter Converter — Change CSV Delimiter Online Free',
 'Convert CSV files between any delimiter: comma, semicolon, tab, pipe, or custom. Free online delimiter converter with preview.',
 'CSV Delimiter Converter — Change Delimiters Instantly',
 1, 3, 1),

(3, 'CSV Validator',
 'csv-validator',
 'Validate your CSV file for structure errors, encoding issues, missing headers, inconsistent columns, and more.',
 'CSV Validator — Validate CSV File Structure Online Free',
 'Validate CSV files for structural errors, encoding problems, and format inconsistencies. Get a detailed error report with row and column references.',
 'CSV Validator — Check Your CSV File for Errors',
 1, 4, 1),

(4, 'Remove Duplicate Rows',
 'remove-duplicates',
 'Remove duplicate rows from CSV files based on all columns or a selected subset of key columns.',
 'Remove Duplicate Rows from CSV — Free Online Deduplication Tool',
 'Remove duplicate rows from any CSV file online. Choose which columns to compare, preview results, and download the cleaned file.',
 'Remove Duplicate Rows from CSV Files Online',
 1, 5, 1),

(2, 'CSV to JSON Converter',
 'csv-to-json',
 'Convert CSV files to JSON format. Supports array of objects, array of arrays, and keyed object output.',
 'CSV to JSON Converter — Convert CSV to JSON Online Free',
 'Convert CSV files to JSON instantly. Choose array of objects, array of arrays, or nested output formats. Free online CSV to JSON tool.',
 'CSV to JSON Converter — Free Online Tool',
 1, 6, 1),

(4, 'CSV Merger',
 'csv-merger',
 'Merge multiple CSV files into one. Align columns by header name or position, and handle missing values gracefully.',
 'CSV Merger — Merge Multiple CSV Files Online Free',
 'Merge up to 10 CSV files into a single file online. Match columns by name or position, handle duplicates, and download the combined CSV.',
 'CSV Merger — Combine Multiple CSV Files Online',
 0, 7, 1),

(4, 'CSV Splitter',
 'csv-splitter',
 'Split large CSV files into smaller chunks by row count or file size. Download each chunk individually.',
 'CSV Splitter — Split Large CSV Files Online Free',
 'Split large CSV files into smaller chunks by number of rows or file size. Each split file keeps the original header row.',
 'CSV Splitter — Split Large CSV Files into Smaller Parts',
 0, 8, 1),

(5, 'CSV File Comparison',
 'csv-file-comparison',
 'Compare two CSV files side by side to find added, removed, and modified rows.',
 'CSV File Comparison — Compare Two CSV Files Online Free',
 'Compare two CSV files to find differences: added rows, removed rows, and modified cells. Visual diff view with downloadable report.',
 'CSV File Comparison — Find Differences Between Two CSV Files',
 0, 9, 1),

(5, 'CSV Online Viewer',
 'csv-viewer',
 'View and browse CSV file contents in a clean, searchable, sortable table directly in your browser.',
 'CSV Online Viewer — View CSV Files in Browser Free',
 'Open and view any CSV file in your browser without uploading. Searchable, sortable table with column statistics and row count.',
 'CSV Online Viewer — View CSV Files in Your Browser',
 1, 10, 1);

-- ── FAQs ──────────────────────────────────────────────────────────────────────
INSERT INTO `tool_faqs` (`tool_id`, `question`, `answer`, `sort_order`) VALUES

-- CSV Repair FAQs
(1, 'What types of CSV errors can this tool fix?',
 'The CSV Repair Tool can fix unmatched quotes, inconsistent column counts, BOM characters, mixed line endings (CRLF/LF), and common encoding issues.',
 1),
(1, 'Is my file uploaded to a server?',
 'Yes, for server-side repair processing the file is temporarily stored in a secure location outside the web root and deleted immediately after processing.',
 2),
(1, 'What is the maximum file size?',
 'The maximum supported file size is 10 MB. For larger files, consider splitting the file first using the CSV Splitter tool.',
 3),

-- Encoding Detector FAQs
(2, 'What encodings can this tool detect?',
 'The tool can detect UTF-8 (with and without BOM), UTF-16 LE/BE, ISO-8859-1, Windows-1252, ASCII, and several other common encodings.',
 1),
(2, 'Why does encoding matter in CSV files?',
 'CSV files are plain text. If opened with the wrong encoding, non-ASCII characters like accented letters, currency symbols, and CJK characters will appear as garbage. Knowing the encoding lets you open the file correctly.',
 2),

-- Validator FAQs
(4, 'What does the CSV Validator check?',
 'It checks for consistent column counts per row, valid UTF-8 encoding, quoted field correctness, header presence, empty rows, and potential formula injection strings.',
 1),
(4, 'Will the validator modify my file?',
 'No. The CSV Validator is read-only. It reports errors but does not change your file. Use the CSV Repair Tool to fix detected issues.',
 2),

-- Remove Duplicates FAQs
(5, 'Can I choose which columns to compare for duplicates?',
 'Yes. You can deduplicate based on all columns or select specific key columns. This lets you, for example, remove rows with the same email address even if other columns differ.',
 1),
(5, 'Does the tool preserve the first or last occurrence of a duplicate?',
 'By default the first occurrence is kept and subsequent duplicates are removed. You can change this to keep the last occurrence in the options.',
 2),

-- JSON Converter FAQs
(6, 'What JSON output formats are supported?',
 'The tool supports three formats: Array of Objects (each row becomes a key-value object), Array of Arrays (rows as plain arrays), and a Pretty-printed version of either.',
 1),

-- Merger FAQs
(7, 'What happens if the CSV files have different columns?',
 'You can choose to align by header name (missing columns are filled with empty values) or by column position (files must have the same number of columns).',
 1),

-- Splitter FAQs
(8, 'Will each split file include the header row?',
 'Yes. Each output chunk automatically includes the original header row so every split file is independently valid.',
 1),

-- Comparison FAQs
(9, 'How does the comparison work?',
 'The tool compares rows from both files. A row is considered identical when all selected key column values match. Added rows, removed rows, and modified cells are highlighted in different colours.',
 1),

-- Viewer FAQs
(10, 'Is my file processed in the browser or on the server?',
 'The CSV Viewer processes your file entirely in the browser using JavaScript. Your data is never uploaded to any server.',
 1),
(10, 'How large a file can I view?',
 'The viewer handles files up to 10 MB comfortably in modern browsers. Very large files may be paginated to maintain performance.',
 2);

-- ── Site settings ─────────────────────────────────────────────────────────────
INSERT INTO `site_settings` (`setting_key`, `setting_val`) VALUES
('site_name',        'CSV Repair Toolkit'),
('site_tagline',     'Free Online CSV Tools'),
('contact_email',    'hello@csvtoolkit.online'),
('adsense_id',       ''),
('ads_enabled',      '0'),
('analytics_id',     ''),
('footer_text',      '© 2024 CSV Repair Toolkit. All rights reserved.');

-- ── Default admin user (password: Admin@123 — CHANGE IMMEDIATELY) ─────────────
INSERT INTO `admin_users` (`username`, `email`, `password_hash`) VALUES
('admin', 'admin@csvtoolkit.online',
 '$2y$12$tgq8SQxLinuujB.t5J205./Q9QB73/3z6yIR4JxWZKWN2DM2F0RaG');
-- ^ bcrypt of 'Admin@123' — generated with password_hash('Admin@123', PASSWORD_BCRYPT, ['cost'=>12])
