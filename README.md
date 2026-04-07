# ICA Tracker

Full-stack academic performance management platform for ICA (Internal Continuous Assessment) operations, role-based analytics, and institution-wide communication.

This project was designed for real college workflows where multiple stakeholders (Admin, Program Chair, Faculty, Student, System Admin) need one reliable source of truth for classes, components, marks, alerts, and timetable updates.

## Why This Project Matters

Traditional ICA tracking in spreadsheets creates duplicate data, inconsistent reporting, and delayed communication.

ICA Tracker solves that by providing:

- Centralized academic data management
- Role-aware dashboards and reporting
- Structured CSV onboarding for large student batches
- SMTP-based notification workflows for operational communication
- Academic calendar aligned filtering and progress visibility

## Core Capabilities

- Multi-role access with dedicated dashboards:
	- Admin
	- Program Chair
	- Faculty
	- Student
	- System Admin
- Class, subject, section, and ICA component lifecycle management
- ICA marks entry, progress tracking, and comparison reporting
- Bulk student import with schema validation and controlled upsert behavior
- Optional NMIMS college email onboarding through CSV and forms
- Alerting and manual mailing workflows with audience targeting
- Timetable upload, preview, and broadcast support
- Academic context support (term/semester aware filtering)

## Recent Enhancements

- Program Chair dashboard data consistency and compatibility improvements
- Program Chair dashboard layout and visualization upgrade (system-admin style alignment)
- Test Mail audience targeting:
	- Classes
	- Faculty
	- Program Chair
	- Everyone
- Multi-class selection for class-wise mailing
- Other Reason mail scenario with required message input
- Bulk upload behavior hardened to prevent redundant student rewrites:
	- Existing SAP + Class records keep core data unchanged
	- Only missing NMIMS college email is filled
- Detailed validation feedback for invalid non-NMIMS email entries (with SAP ID and student name)

## Tech Stack

- Backend: PHP
- Database: MySQL / MariaDB
- Frontend: HTML, CSS, JavaScript
- Mail: PHPMailer over SMTP
- Environment management: vlucas/phpdotenv
- Dependency management: Composer

## Dependencies

Defined in [composer.json](composer.json):

- phpmailer/phpmailer
- vlucas/phpdotenv

## High-Level Architecture

- Root PHP pages: route-level modules and dashboards
- [includes/](includes/) : shared helpers (auth bootstrap, academic context, notifications, logging)
- [app/](app/) : bootstrap, PSR-4 autoloaded domain support
- [config/](config/) : app, database, mail, session configuration
- [uploads/](uploads/) : uploaded artifacts (such as timetables)
- [storage/logs/](storage/logs/) : runtime logs
- SQL dump: [ica_tracker (35).sql](ica_tracker%20(35).sql)

## Key Modules (Interview Navigation)

- Authentication and entry:
	- [index.php](index.php)
	- [login.php](login.php)
	- [admin_login.php](admin_login.php)
- Dashboards:
	- [admin_dashboard.php](admin_dashboard.php)
	- [program_dashboard.php](program_dashboard.php)
	- [teacher_dashboard.php](teacher_dashboard.php)
	- [student_dashboard.php](student_dashboard.php)
	- [system_admin_dashboard.php](system_admin_dashboard.php)
- Student onboarding and maintenance:
	- [bulk_add_students.php](bulk_add_students.php)
	- [edit_student.php](edit_student.php)
- Mailing and alerts:
	- [test_mail.php](test_mail.php)
	- [send_alerts.php](send_alerts.php)
	- [includes/email_notifications.php](includes/email_notifications.php)
- Reports and analytics:
	- [reports.php](reports.php)
	- [program_reports.php](program_reports.php)
	- [subject_comparison.php](subject_comparison.php)

## Local Setup (XAMPP)

1. Install XAMPP (or equivalent PHP + MySQL stack).
2. Place the project inside your XAMPP htdocs folder as ica_tracker.
3. From project root, install dependencies:

```bash
composer install
composer dump-autoload
```

4. Configure environment variables in .env (create from sample if needed):
	 - Database credentials
	 - SMTP credentials
	 - App URL
5. Start Apache and MySQL.
6. Create a database, for example: ica_tracker.
7. Import [ica_tracker (35).sql](ica_tracker%20(35).sql).
8. Ensure write permission for [uploads/](uploads/) and [storage/logs/](storage/logs/).
9. Open:

```text
http://localhost/ica_tracker
```

## Mail Configuration

- Enable mail via .env flags/settings.
- Default SMTP flow is PHPMailer based.
- For Gmail SMTP, use App Password (2FA-enabled account).
- Primary mail template:
	- [emailtemplate.html](emailtemplate.html)

## Bulk Upload Rules

Required CSV columns (any order):

- S/N
- ROLL NO
- SAP ID
- NAME OF STUDENT

Mandatory for new joining students:

- COLLEGE EMAIL (NMIMS domains only)

Data behavior:

- New SAP + Class: inserts new student
	- Insert is allowed only when a valid NMIMS COLLEGE EMAIL is provided
- Existing SAP + Class:
	- Student core profile is preserved
	- Missing college email is filled only if valid NMIMS email is provided
- Invalid non-NMIMS emails are ignored and reported with student-level details

## Security and Reliability Notes

- Session-based role guards on protected pages
- Parameterized SQL usage across critical flows
- Server-side validation for CSV ingest and mail audiences
- Logging support for tracing mail and runtime issues
- Environment-driven secrets management via .env

## Interview Talking Points

Use these points when presenting this project:

1. Problem to Product:
Built a role-based academic operations platform replacing spreadsheet-driven workflows.

2. Data Integrity:
Implemented controlled upsert logic to prevent redundant student rewrites while allowing incremental email enrichment.

3. Backward Compatibility:
Handled schema variance in dashboard queries to avoid runtime failures across older databases.

4. Communication Layer:
Integrated SMTP notifications and admin-controlled audience targeting with scenario templates.

5. Product Thinking:
Prioritized operational UX by adding recipient previews, multi-class targeting, and actionable upload error reporting.

6. Production Mindset:
Used logging, validation, and modular helpers to improve maintainability and troubleshooting.

## Suggested Demo Flow (5-7 minutes)

1. Login as Admin and open [admin_dashboard.php](admin_dashboard.php)
2. Show bulk student upload with mandatory NMIMS email for new joins and controlled enrichment for existing records in [bulk_add_students.php](bulk_add_students.php)
3. Show manual mailing with audience targeting in [test_mail.php](test_mail.php)
4. Show Program Chair analytics in [program_dashboard.php](program_dashboard.php)
5. Show reporting view in [program_reports.php](program_reports.php)

## Future Scope

- API-first service layer for integrations
- Queue-based asynchronous email dispatch
- Audit dashboards for data quality and mail delivery metrics
- Granular permissions model beyond role-level access

## License

Internal academic project.
