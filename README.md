# ICA Tracker

Comprehensive web application for ICA (Internal Continuous Assessment) lifecycle management across Admin, Program Chair, Faculty, Student, and System Admin roles.

It centralizes class setup, subject mapping, ICA component design, marks operations, progress analytics, alerts, and communication workflows in a single PHP/MySQL platform.

## 1. Project Overview

ICA Tracker is designed for real academic operations where multiple stakeholders need synchronized data and actionable dashboards.

Key outcomes:

- One source of truth for classes, sections, subjects, and marks.
- Role-specific dashboards and workflows.
- Academic-term-aware filtering and reporting.
- Operational communication through SMTP notifications.
- Bulk onboarding for students with validation and quality controls.

## 2. Technology Stack

- Backend: PHP (procedural + modular helpers)
- Database: MySQL / MariaDB
- Frontend: HTML, CSS, JavaScript, Chart.js
- Mail: PHPMailer
- Config/env: `vlucas/phpdotenv`
- Dependency manager: Composer

Dependencies from [composer.json](composer.json):

- `phpmailer/phpmailer` `^6.8`
- `vlucas/phpdotenv` `^5.6`

## 3. Architecture

Core runtime and configuration:

- Bootstrap: [app/bootstrap.php](app/bootstrap.php)
- DB connection entrypoint: [db_connect.php](db_connect.php)
- Session + startup init: [includes/init.php](includes/init.php)

Configuration files:

- App: [config/app.php](config/app.php)
- Database: [config/database.php](config/database.php)
- Mail: [config/mail.php](config/mail.php)
- Session: [config/session.php](config/session.php)

Shared utility modules (high-use):

- Academic context: [includes/academic_context.php](includes/academic_context.php)
- Term switcher UI: [includes/term_switcher_ui.php](includes/term_switcher_ui.php)
- Email notifications: [includes/email_notifications.php](includes/email_notifications.php)
- Mail sender wrapper: [includes/mailer.php](includes/mailer.php)
- Activity log helper: [includes/activity_logger.php](includes/activity_logger.php)

## 4. Directory Layout

Top-level directories:

- [app/](app/) - bootstrap + namespaced support classes
- [config/](config/) - runtime config maps
- [includes/](includes/) - shared feature helpers
- [css/](css/) - additional stylesheet assets
- [Classes/](Classes/) - templates and class resources
- [uploads/](uploads/) - uploaded files (timetables, etc.)
- [storage/](storage/) - logs and runtime files
- [vendor/](vendor/) - Composer packages

Top-level SQL dump:

- [ica_tracker (35).sql](ica_tracker%20(35).sql)

## 5. Role-Based Feature Map

### Admin

- Dashboard and admin actions: [admin_dashboard.php](admin_dashboard.php)
- Manage classes: [create_classes.php](create_classes.php)
- Manage subjects: [create_subjects.php](create_subjects.php)
- Assign faculty: [assign_teachers.php](assign_teachers.php)
- Manage teachers: [manage_teachers.php](manage_teachers.php)
- Manage sections/divisions: [manage_sections.php](manage_sections.php)
- Manage roles: [change_roles.php](change_roles.php)
- Bulk add/edit students: [bulk_add_students.php](bulk_add_students.php), [edit_student.php](edit_student.php)
- Manual mailing: [test_mail.php](test_mail.php)
- Academic calendar: [manage_academic_calendar.php](manage_academic_calendar.php)

### Program Chair

- Dashboard: [program_dashboard.php](program_dashboard.php)
- Teacher analytics: [teacher_progress.php](teacher_progress.php)
- Student analytics: [student_progress.php](student_progress.php)
- Course analytics: [course_progress.php](course_progress.php)
- Reports: [program_reports.php](program_reports.php), [reports.php](reports.php)
- Alerts: [send_alerts.php](send_alerts.php)
- Settings: [settings.php](settings.php)

### Faculty

- Dashboard: [teacher_dashboard.php](teacher_dashboard.php)
- Update syllabus progress: [update_progress.php](update_progress.php)
- ICA component creation: [create_ica_components.php](create_ica_components.php)
- ICA marks operations: [manage_ica_marks.php](manage_ica_marks.php)
- Assignments flow: [assignments.php](assignments.php)
- Faculty reports and alerts: [view_reports.php](view_reports.php), [view_alerts.php](view_alerts.php)
- Timetable page: [timetable.php](timetable.php)

### Student

- Dashboard: [student_dashboard.php](student_dashboard.php)
- Marks view: [view_marks.php](view_marks.php)
- Assignment marks: [view_assignment_marks.php](view_assignment_marks.php)
- Timetable view: [view_timetable.php](view_timetable.php)

### System Admin

- Dashboard: [system_admin_dashboard.php](system_admin_dashboard.php)
- Activity feed: [system_admin_activity_feed.php](system_admin_activity_feed.php)
- Backup/restore export flow: [system_admin_export_sql.php](system_admin_export_sql.php)

## 6. Full Setup Guide (Windows + XAMPP)

### Prerequisites

- Windows with XAMPP (Apache + MySQL)
- PHP compatible with dependencies
- Composer
- Git

### Installation Steps

1. Clone project into XAMPP htdocs:

```powershell
cd D:\XAMPP\htdocs
git clone https://github.com/krishna-2-005/ica_tracker.git
cd ica_tracker
```

2. Install dependencies:

```powershell
composer install
composer dump-autoload
```

3. Configure env:

```powershell
copy .env.example .env
```

4. Update `.env` with your local credentials.

5. Create database and import dump:

- Create DB: `ica_tracker`
- Import [ica_tracker (35).sql](ica_tracker%20(35).sql)

6. Start Apache and MySQL from XAMPP.

7. Open app:

```text
http://localhost/ica_tracker
```

## 7. Environment Variables Reference

Sample source: [.env.example](.env.example)

### Application

- `APP_NAME`
- `APP_ENV`
- `APP_DEBUG`
- `APP_URL`
- `APP_TIMEZONE`
- `APP_LOCALE`

### Database

- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `DB_CHARSET`
- `DB_SOCKET`
- `DB_TIMEOUT`

### Session

- `SESSION_NAME`
- `SESSION_LIFETIME`
- `SESSION_PATH`
- `SESSION_DOMAIN`
- `SESSION_SECURE`
- `SESSION_HTTP_ONLY`
- `SESSION_SAME_SITE`

### Mail

- `MAIL_ENABLED`
- `MAIL_HOST`
- `MAIL_PORT`
- `MAIL_USERNAME`
- `MAIL_PASSWORD`
- `MAIL_ENCRYPTION`
- `MAIL_FROM_ADDRESS`
- `MAIL_FROM_NAME`
- `MAIL_DEBUG`

### Logging

- `LOG_PATH`

Important security note:

- Never commit real credentials.
- Immediately rotate passwords if they were ever committed by mistake.

## 8. Authentication and Access Pages

- Main entry: [index.php](index.php)
- Login pages: [login.php](login.php), [admin_login.php](admin_login.php), [login_as.php](login_as.php)
- Account actions: [forgot_password.php](forgot_password.php), [reset_password.php](reset_password.php), [change_password.php](change_password.php), [logout.php](logout.php)

## 9. Data Operations and Bulk Upload Rules

Student bulk upload page: [bulk_add_students.php](bulk_add_students.php)

Expected CSV columns:

- `S/N`
- `ROLL NO`
- `SAP ID`
- `NAME OF STUDENT`
- `COLLEGE EMAIL` (mandatory for new joins, validated for NMIMS domains)

Behavior summary:

- Existing student rows are not blindly overwritten.
- Missing valid college emails are enriched.
- Invalid non-approved domains are rejected with feedback.

Templates:

- [download_template.php](download_template.php)
- [download_class_students_template.php](download_class_students_template.php)
- [Classes/Student List Template .csv](Classes/Student%20List%20Template%20.csv)

## 10. ICA and Marks Workflow

Recommended functional sequence:

1. Create classes/sections and subjects.
2. Assign teachers to subject/class/division.
3. Define ICA components per subject.
4. Enter/publish marks.
5. View analytics and reports.
6. Trigger alerts where necessary.

Primary pages:

- Components: [create_ica_components.php](create_ica_components.php)
- Marks entry: [manage_ica_marks.php](manage_ica_marks.php)
- Marks fetch endpoints: [get_ica_components.php](get_ica_components.php), [get_student_marks.php](get_student_marks.php), [get_subject_ica_components.php](get_subject_ica_components.php)

## 11. Reporting and Analytics Modules

- Program-level: [program_dashboard.php](program_dashboard.php), [program_reports.php](program_reports.php)
- Teacher-level: [teacher_progress.php](teacher_progress.php)
- Student-level: [student_progress.php](student_progress.php), [student_dashboard.php](student_dashboard.php)
- Course-level: [course_progress.php](course_progress.php)
- Comparative analysis: [subject_comparison.php](subject_comparison.php), [subject_comparison_details.php](subject_comparison_details.php)
- Export/report generation: [generate_report.php](generate_report.php), [view_reports.php](view_reports.php)

## 12. Alerts and Communication

- Alert workflows: [alerts.php](alerts.php), [send_alerts.php](send_alerts.php), [trigger_scaled_alert.php](trigger_scaled_alert.php)
- Mail templates: [emailtemplate.html](emailtemplate.html)
- Notification engine: [includes/email_notifications.php](includes/email_notifications.php)
- Manual mailing utility: [test_mail.php](test_mail.php)

## 13. Timetable Management

- Faculty timetable management: [timetable.php](timetable.php), [preview_timetable.php](preview_timetable.php)
- Student timetable view: [view_timetable.php](view_timetable.php)
- Uploaded files stored in: [uploads/](uploads/)

## 14. API-Like Endpoints (AJAX/Fetch)

Common data endpoints used by dashboards/forms:

- [get_classes.php](get_classes.php)
- [get_sections.php](get_sections.php)
- [get_semesters.php](get_semesters.php)
- [get_student.php](get_student.php)
- [get_students_for_class.php](get_students_for_class.php)
- [get_students_for_subject.php](get_students_for_subject.php)
- [get_teachers_by_school.php](get_teachers_by_school.php)
- [get_teachers_by_department.php](get_teachers_by_department.php)
- [get_progress_status.php](get_progress_status.php)
- [get_users.php](get_users.php)

## 15. Logging, Debugging, and Diagnostics

Logs and debug artifacts:

- Runtime logs: [storage/](storage/), `storage/logs/app.log`
- Legacy/debug scripts in root:
  - [debug_pc.php](debug_pc.php)
  - [debug_query.php](debug_query.php)
  - [debug_teacher_details.php](debug_teacher_details.php)
  - [debug.log](debug.log)

Troubleshooting checklist:

- Confirm `.env` values and DB credentials.
- Verify Apache/MySQL service status.
- Ensure DB import completed.
- Check mail config and SMTP restrictions.
- Check logs for stack traces and SQL errors.

## 16. Git Workflow Notes

If push is rejected due to remote updates:

```powershell
git fetch origin
git pull --rebase origin main
git push origin main
```

If rebase conflict occurs:

```powershell
git status
# resolve files
git add <resolved-files>
git rebase --continue
```

Abort only if needed:

```powershell
git rebase --abort
```

## 17. Security Practices

- Keep `.env` out of version control.
- Do not store real credentials in docs/screenshots.
- Use strong session settings and secure cookies in production.
- Validate all file uploads and input sources.
- Prefer parameterized queries and strict type checks.

## 18. Deployment Readiness Checklist

- `composer install --no-dev` on server
- production `.env` configured
- DB migration/import verified
- writable `uploads/` and `storage/logs/`
- HTTPS enabled
- `APP_DEBUG=false`
- SMTP validated in production

## 19. Quick Page Index

Core pages in root include:

- [admin_dashboard.php](admin_dashboard.php)
- [program_dashboard.php](program_dashboard.php)
- [teacher_dashboard.php](teacher_dashboard.php)
- [student_dashboard.php](student_dashboard.php)
- [system_admin_dashboard.php](system_admin_dashboard.php)
- [reports.php](reports.php)
- [view_reports.php](view_reports.php)
- [manage_ica_marks.php](manage_ica_marks.php)
- [create_ica_components.php](create_ica_components.php)
- [assignments.php](assignments.php)
- [view_assignment_marks.php](view_assignment_marks.php)

## 20. Project Status and Ownership

Project type: internal academic operations platform.

Current repository:

- `https://github.com/krishna-2-005/ica_tracker`

## 21. License

Internal project. Add an explicit license file if external distribution is planned.
