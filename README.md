# ICA Tracker

A PHP/MySQL web application for managing ICA (Internal Continuous Assessment) components, marks, dashboards, and reporting for students, teachers, and administrators.

## Features
- Role-based dashboards (admin, teacher, student, program).
- Manage subjects, classes, sections, and ICA components.
- Record and view ICA/assignment marks and progress.
- Generate reports and comparisons.
- Academic calendar management.
- Alerts and notifications.
- Timetable preview and tracking.

## Tech Stack
- PHP (server-side)
- MySQL/MariaDB (database)
- HTML/CSS/JS (front-end)
- Composer dependencies (see composer.json)
- PHPMailer & SMTP mailers (configurable via .env)
- vlucas/phpdotenv for environment management

## Project Structure (high level)
- Core pages: *.php in the project root
- Shared logic: includes/
- Styles: styles.css, ica_tracker.css, program_dashboard.css, css/
- Uploads: uploads/
- Database dump: ica_tracker (35).sql

## Setup (Local Development)
1. Install XAMPP (or another PHP + MySQL stack).
2. Place this project in your web root (e.g., D:\xampp\htdocs\ica_tracker).
3. From the project root run `composer install` and `composer dump-autoload` to refresh vendor autoload files.
4. Copy `.env.example` to `.env` and update database and mail credentials.
5. Start Apache and MySQL.
6. Create a database (e.g., ica_tracker) in phpMyAdmin.
7. Import the database dump from `ica_tracker (35).sql`.
8. Ensure `/storage/logs` is writable by the web server user.
9. Open http://localhost/ica_tracker in your browser.

## Email Setup (Gmail)
- Enable mail by keeping `MAIL_ENABLED=true` in `.env`.
- In your Gmail account `icatrackerstme@gmail.com`, enable two-factor authentication and create an app password (select Mail + Other).
- Paste the generated 16-character app password into `MAIL_PASSWORD` in `.env` (no spaces).
- Keep the other provided defaults (`smtp.gmail.com`, port `587`, TLS) unless you need a different provider.
- If you change the sender name or address, update `MAIL_FROM_NAME` and `MAIL_FROM_ADDRESS` accordingly.

## Configuration Files
- config/app.php: general app configuration with .env overrides
- config/database.php: database credentials (fed by .env)
- config/mail.php: SMTP/mail settings (fed by .env)
- config/session.php: session cookie behaviour
- smtp_config.php: legacy mail override (optional, prefer .env)
- includes/academic_context.php: academic context helpers

## Common Entry Points
- index.php: landing page
- login.php / admin_login.php: authentication
- admin_dashboard.php / teacher_dashboard.php / student_dashboard.php / program_dashboard.php

## Data & Templates
- CSV templates are located under Classes/ and root CSV files.

## Notes
- Ensure file upload permissions for uploads/ and storage/ directories.
- Run `composer dump-autoload` after pulling new changes or updating composer.json.
- Keep `.env` out of version control (see .gitignore).

## License
Internal project — add a license if required.
