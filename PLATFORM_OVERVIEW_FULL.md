# ICA Tracker Platform Overview (Auto-Generated)

Generated on: 2026-04-08 11:40:09
Workspace root: D:\XAMPP\htdocs\ica_tracker
Total files discovered: 1873

## 1) Platform Snapshot
- Core purpose: Internal Continuous Assessment tracking platform with role-based dashboards for system admin, program admin, teachers, and students.
- Primary stack: PHP application (procedural + modular folders), MySQL-compatible SQL schema dump, Composer dependencies, PHPMailer integration.
- Styling/UI assets: Global CSS plus module-specific styles and templates.

## 2) Database Connectivity and Operations
- Files with DB connection indicators: 74
- Files with DB read indicators: 120
- Files with DB write indicators: 374

### DB Connection Related Files
- .\admin_dashboard.php
- .\admin_login.php
- .\alerts.php
- .\assign_teachers.php
- .\assignments.php
- .\bulk_add_students.php
- .\change_password.php
- .\change_roles.php
- .\check_components.php
- .\course_progress.php
- .\create_classes.php
- .\create_ica_components.php
- .\create_subjects.php
- .\db_connect.php
- .\debug_pc.php
- .\debug_query.php
- .\download_class_students_template.php
- .\edit_profile.php
- .\edit_student.php
- .\forgot_password.php
- .\generate_platform_overview.ps1
- .\get_classes.php
- .\get_classes_for_subject.php
- .\get_ica_components.php
- .\get_progress_status.php
- .\get_sections.php
- .\get_semesters.php
- .\get_student.php
- .\get_student_marks.php
- .\get_students_for_class.php
- .\get_students_for_subject.php
- .\get_subject_ica_components.php
- .\get_teachers_by_department.php
- .\get_teachers_by_school.php
- .\get_users.php
- .\login.php
- .\logout.php
- .\manage_academic_calendar.php
- .\manage_electives.php
- .\manage_ica_marks.php
- .\manage_sections.php
- .\manage_teachers.php
- .\PLATFORM_OVERVIEW_FULL.md
- .\program_dashboard.php
- .\program_reports.php
- .\README.md
- .\reports.php
- .\reset_password.php
- .\send_alerts.php
- .\set_academic_context.php
- .\settings.php
- .\signup.php
- .\storage\logs\app.log
- .\student_dashboard.php
- .\student_progress.php
- .\subject_comparison.php
- .\system_admin_activity_feed.php
- .\system_admin_dashboard.php
- .\system_admin_export_sql.php
- .\teacher_dashboard.php
- .\teacher_progress.php
- .\test_mail.php
- .\timetable.php
- .\track_teachers.php
- .\trigger_scaled_alert.php
- .\update_progress.php
- .\vendor\phpmailer\phpmailer\examples\mailing_list.phps
- .\view_alerts.php
- .\view_assignment_marks.php
- .\view_marks.php
- .\view_progress.php
- .\view_reports.php
- .\view_timetable.php
- .git\index

### DB Read Related Files
- .\admin_login.php
- .\alert_helpers.php
- .\alerts.php
- .\assign_teachers.php
- .\assignments.php
- .\bulk_add_students.php
- .\change_password.php
- .\change_roles.php
- .\check_components.php
- .\course_progress.php
- .\create_classes.php
- .\create_ica_components.php
- .\create_subjects.php
- .\css\style.css
- .\debug.log
- .\debug_pc.php
- .\debug_query.php
- .\download_class_students_template.php
- .\edit_profile.php
- .\forgot_password.php
- .\generate_platform_overview.ps1
- .\get_classes.php
- .\get_classes_for_subject.php
- .\get_ica_components.php
- .\get_progress_status.php
- .\get_sections.php
- .\get_semesters.php
- .\get_student.php
- .\get_student_marks.php
- .\get_students_for_class.php
- .\get_students_for_subject.php
- .\get_subject_ica_components.php
- .\get_teachers_by_department.php
- .\get_teachers_by_school.php
- .\get_users.php
- .\ica_tracker.css
- .\includes\academic_context.php
- .\includes\activity_logger.php
- .\includes\assignment_helpers.php
- .\includes\email_notifications.php
- .\includes\settings_helpers.php
- .\includes\student_context.php
- .\includes\term_switcher_ui.php
- .\login.php
- .\login_as.php
- .\manage_academic_calendar.php
- .\manage_electives.php
- .\manage_ica_marks.php
- .\manage_sections.php
- .\manage_teachers.php
- .\PHPMailer\get_oauth_token.php
- .\PHPMailer\SMTPUTF8.md
- .\PHPMailer\src\PHPMailer.php
- .\PHPMailer\src\SMTP.php
- .\PLATFORM_OVERVIEW_FULL.md
- .\program_dashboard.php
- .\program_reports.php
- .\reports.php
- .\reset_password.php
- .\send_alerts.php
- .\settings.php
- .\signup.php
- .\storage\logs\app.log
- .\student_dashboard.php
- .\student_progress.php
- .\styles.css
- .\subject_comparison.php
- .\system_admin_activity_feed.php
- .\system_admin_dashboard.php
- .\system_admin_export_sql.php
- .\teacher_dashboard.php
- .\teacher_progress.php
- .\test_mail.php
- .\timetable.php
- .\track_teachers.php
- .\update_progress.php
- .\vendor\phpmailer\phpmailer\examples\contactform.phps
- .\vendor\phpmailer\phpmailer\examples\contactform-ajax.phps
- .\vendor\phpmailer\phpmailer\examples\mailing_list.phps
- .\vendor\phpmailer\phpmailer\examples\send_multiple_file_upload.phps
- .\vendor\phpmailer\phpmailer\examples\simple_contact_form.phps
- .\vendor\phpmailer\phpmailer\get_oauth_token.php
- .\vendor\phpmailer\phpmailer\SMTPUTF8.md
- .\vendor\phpmailer\phpmailer\src\PHPMailer.php
- .\vendor\phpmailer\phpmailer\src\SMTP.php
- .\vendor\phpmailer\phpmailer\test\Fixtures\LocalizationTest\phpmailer.lang-yy.php
- .\vendor\phpmailer\phpmailer\test\PHPMailer\AddEmbeddedImageTest.php
- .\vendor\phpmailer\phpmailer\test\PHPMailer\AddStringAttachmentTest.php
- .\vendor\phpmailer\phpmailer\test\PHPMailer\AddStringEmbeddedImageTest.php
- .\vendor\phpmailer\phpmailer\test\PHPMailer\PHPMailerTest.php
- .\view_alerts.php
- .\view_assignment_marks.php
- .\view_marks.php
- .\view_progress.php
- .\view_reports.php
- .\view_timetable.php
- .git\hooks\fsmonitor-watchman.sample
- .venv\Lib\site-packages\pip\_internal\build_env.py
- .venv\Lib\site-packages\pip\_internal\commands\__pycache__\cache.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\commands\__pycache__\list.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\commands\cache.py
- .venv\Lib\site-packages\pip\_internal\commands\list.py
- .venv\Lib\site-packages\pip\_internal\utils\filesystem.py
- .venv\Lib\site-packages\pip\_vendor\pygments\__pycache__\plugin.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\pygments\lexers\python.py
- .venv\Lib\site-packages\pip\_vendor\pygments\plugin.py
- .venv\Lib\site-packages\pip\_vendor\requests\__pycache__\adapters.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\requests\__pycache__\utils.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\requests\adapters.py
- .venv\Lib\site-packages\pip\_vendor\requests\utils.py
- .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\prompt.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\rich\prompt.py
- .venv\Lib\site-packages\pip\_vendor\urllib3\contrib\__pycache__\pyopenssl.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\urllib3\contrib\pyopenssl.py
- .venv\Lib\site-packages\pip\_vendor\urllib3\contrib\securetransport.py
- .venv\Lib\site-packages\pip\_vendor\urllib3\util\__pycache__\ssl_.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\urllib3\util\__pycache__\wait.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\urllib3\util\connection.py
- .venv\Lib\site-packages\pip\_vendor\urllib3\util\ssl_.py
- .venv\Lib\site-packages\pip\_vendor\urllib3\util\wait.py

### DB Write Related Files
- .\admin_dashboard.php
- .\admin_login.php
- .\alert_helpers.php
- .\alerts.php
- .\assign_teachers.php
- .\assignments.php
- .\bulk_add_students.php
- .\change_password.php
- .\change_roles.php
- .\course_progress.php
- .\create_classes.php
- .\create_ica_components.php
- .\create_subjects.php
- .\edit_profile.php
- .\edit_student.php
- .\forgot_password.php
- .\generate_platform_overview.ps1
- .\get_student_marks.php
- .\ica_tracker (35).sql
- .\includes\academic_context.php
- .\includes\activity_logger.php
- .\includes\assignment_helpers.php
- .\includes\email_notifications.php
- .\login.php
- .\login_as.php
- .\manage_academic_calendar.php
- .\manage_electives.php
- .\manage_ica_marks.php
- .\manage_sections.php
- .\manage_teachers.php
- .\PHPMailer\LICENSE
- .\PHPMailer\README.md
- .\PHPMailer\src\DSNConfigurator.php
- .\PHPMailer\src\PHPMailer.php
- .\PHPMailer\src\SMTP.php
- .\PLATFORM_OVERVIEW_FULL.md
- .\program_dashboard.php
- .\program_reports.php
- .\README.md
- .\reports.php
- .\reset_password.php
- .\send_alerts.php
- .\settings.php
- .\signup.php
- .\storage\logs\app.log
- .\subject_comparison.php
- .\system_admin_export_sql.php
- .\teacher_dashboard.php
- .\teacher_progress.php
- .\test_mail.php
- .\timetable.php
- .\update_progress.php
- .\vendor\autoload.php
- .\vendor\phpmailer\phpmailer\.github\workflows\scorecards.yml
- .\vendor\phpmailer\phpmailer\changelog.md
- .\vendor\phpmailer\phpmailer\examples\azure_xoauth2.phps
- .\vendor\phpmailer\phpmailer\examples\callback.phps
- .\vendor\phpmailer\phpmailer\examples\contactform.phps
- .\vendor\phpmailer\phpmailer\examples\contactform-ajax.phps
- .\vendor\phpmailer\phpmailer\examples\DKIM_gen_keys.phps
- .\vendor\phpmailer\phpmailer\examples\exceptions.phps
- .\vendor\phpmailer\phpmailer\examples\extending.phps
- .\vendor\phpmailer\phpmailer\examples\gmail.phps
- .\vendor\phpmailer\phpmailer\examples\gmail_xoauth.phps
- .\vendor\phpmailer\phpmailer\examples\mail.phps
- .\vendor\phpmailer\phpmailer\examples\mailing_list.phps
- .\vendor\phpmailer\phpmailer\examples\pop_before_smtp.phps
- .\vendor\phpmailer\phpmailer\examples\README.md
- .\vendor\phpmailer\phpmailer\examples\send_file_upload.phps
- .\vendor\phpmailer\phpmailer\examples\send_multiple_file_upload.phps
- .\vendor\phpmailer\phpmailer\examples\sendmail.phps
- .\vendor\phpmailer\phpmailer\examples\sendoauth2.phps
- .\vendor\phpmailer\phpmailer\examples\smime_signed_mail.phps
- .\vendor\phpmailer\phpmailer\examples\smtp.phps
- .\vendor\phpmailer\phpmailer\examples\smtp_check.phps
- .\vendor\phpmailer\phpmailer\examples\smtp_low_memory.phps
- .\vendor\phpmailer\phpmailer\examples\smtp_no_auth.phps
- .\vendor\phpmailer\phpmailer\examples\ssl_options.phps
- .\vendor\phpmailer\phpmailer\LICENSE
- .\vendor\phpmailer\phpmailer\README.md
- .\vendor\phpmailer\phpmailer\src\DSNConfigurator.php
- .\vendor\phpmailer\phpmailer\src\PHPMailer.php
- .\vendor\phpmailer\phpmailer\src\SMTP.php
- .\vendor\phpmailer\phpmailer\test\fakesendmail.sh
- .\vendor\phpmailer\phpmailer\test\PHPMailer\CustomHeaderTest.php
- .\vendor\phpmailer\phpmailer\test\PHPMailer\PHPMailerTest.php
- .\vendor\phpmailer\phpmailer\test\PHPMailer\ReplyToGetSetClearTest.php
- .\vendor\phpmailer\phpmailer\test\PHPMailer\ValidateAddressTest.php
- .\vendor\phpmailer\phpmailer\test\TestCase.php
- .\vendor\phpmailer\phpmailer\UPGRADING.md
- .\view_alerts.php
- .\view_assignment_marks.php
- .\view_marks.php
- .\view_progress.php
- .\view_reports.php
- .git\hooks\fsmonitor-watchman.sample
- .git\hooks\post-update.sample
- .git\hooks\pre-push.sample
- .git\hooks\pre-rebase.sample
- .git\hooks\push-to-checkout.sample
- .git\hooks\sendemail-validate.sample
- .git\hooks\update.sample
- .git\logs\refs\remotes\origin\main
- .venv\Lib\site-packages\pip\__main__.py
- .venv\Lib\site-packages\pip\__pip-runner__.py
- .venv\Lib\site-packages\pip\_internal\__pycache__\build_env.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\__pycache__\cache.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\__pycache__\exceptions.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\__pycache__\self_outdated_check.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\build_env.py
- .venv\Lib\site-packages\pip\_internal\cache.py
- .venv\Lib\site-packages\pip\_internal\cli\__pycache__\parser.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\cli\__pycache__\req_command.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\cli\main_parser.py
- .venv\Lib\site-packages\pip\_internal\cli\parser.py
- .venv\Lib\site-packages\pip\_internal\cli\progress_bars.py
- .venv\Lib\site-packages\pip\_internal\cli\req_command.py
- .venv\Lib\site-packages\pip\_internal\commands\__init__.py
- .venv\Lib\site-packages\pip\_internal\commands\__pycache__\__init__.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\commands\__pycache__\index.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\commands\__pycache__\install.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\commands\__pycache__\list.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\commands\debug.py
- .venv\Lib\site-packages\pip\_internal\commands\download.py
- .venv\Lib\site-packages\pip\_internal\commands\freeze.py
- .venv\Lib\site-packages\pip\_internal\commands\hash.py
- .venv\Lib\site-packages\pip\_internal\commands\index.py
- .venv\Lib\site-packages\pip\_internal\commands\install.py
- .venv\Lib\site-packages\pip\_internal\commands\list.py
- .venv\Lib\site-packages\pip\_internal\commands\lock.py
- .venv\Lib\site-packages\pip\_internal\commands\search.py
- .venv\Lib\site-packages\pip\_internal\commands\wheel.py
- .venv\Lib\site-packages\pip\_internal\configuration.py
- .venv\Lib\site-packages\pip\_internal\distributions\__init__.py
- .venv\Lib\site-packages\pip\_internal\distributions\__pycache__\base.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\distributions\base.py
- .venv\Lib\site-packages\pip\_internal\exceptions.py
- .venv\Lib\site-packages\pip\_internal\index\__pycache__\collector.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\index\__pycache__\package_finder.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\index\collector.py
- .venv\Lib\site-packages\pip\_internal\index\package_finder.py
- .venv\Lib\site-packages\pip\_internal\locations\__init__.py
- .venv\Lib\site-packages\pip\_internal\locations\__pycache__\__init__.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\locations\_distutils.py
- .venv\Lib\site-packages\pip\_internal\locations\_sysconfig.py
- .venv\Lib\site-packages\pip\_internal\metadata\__pycache__\_json.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\metadata\_json.py
- .venv\Lib\site-packages\pip\_internal\metadata\base.py
- .venv\Lib\site-packages\pip\_internal\metadata\pkg_resources.py
- .venv\Lib\site-packages\pip\_internal\models\__pycache__\link.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\models\__pycache__\pylock.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\models\__pycache__\search_scope.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\models\__pycache__\selection_prefs.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\models\link.py
- .venv\Lib\site-packages\pip\_internal\models\pylock.py
- .venv\Lib\site-packages\pip\_internal\models\search_scope.py
- .venv\Lib\site-packages\pip\_internal\models\selection_prefs.py
- .venv\Lib\site-packages\pip\_internal\network\__pycache__\cache.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\network\__pycache__\lazy_wheel.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\network\__pycache__\session.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\network\cache.py
- .venv\Lib\site-packages\pip\_internal\network\download.py
- .venv\Lib\site-packages\pip\_internal\network\lazy_wheel.py
- .venv\Lib\site-packages\pip\_internal\network\session.py
- .venv\Lib\site-packages\pip\_internal\operations\build\build_tracker.py
- .venv\Lib\site-packages\pip\_internal\operations\check.py
- .venv\Lib\site-packages\pip\_internal\operations\install\__pycache__\wheel.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\operations\install\wheel.py
- .venv\Lib\site-packages\pip\_internal\operations\prepare.py
- .venv\Lib\site-packages\pip\_internal\req\__pycache__\constructors.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\req\__pycache__\req_file.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\req\__pycache__\req_install.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\req\__pycache__\req_set.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\req\__pycache__\req_uninstall.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\req\constructors.py
- .venv\Lib\site-packages\pip\_internal\req\req_file.py
- .venv\Lib\site-packages\pip\_internal\req\req_install.py
- .venv\Lib\site-packages\pip\_internal\req\req_set.py
- .venv\Lib\site-packages\pip\_internal\req\req_uninstall.py
- .venv\Lib\site-packages\pip\_internal\resolution\legacy\resolver.py
- .venv\Lib\site-packages\pip\_internal\resolution\resolvelib\__pycache__\found_candidates.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\resolution\resolvelib\factory.py
- .venv\Lib\site-packages\pip\_internal\resolution\resolvelib\found_candidates.py
- .venv\Lib\site-packages\pip\_internal\self_outdated_check.py
- .venv\Lib\site-packages\pip\_internal\utils\__pycache__\_jaraco_text.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\utils\__pycache__\appdirs.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\utils\__pycache__\misc.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\utils\__pycache__\subprocess.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\utils\__pycache__\temp_dir.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\utils\_jaraco_text.py
- .venv\Lib\site-packages\pip\_internal\utils\appdirs.py
- .venv\Lib\site-packages\pip\_internal\utils\compatibility_tags.py
- .venv\Lib\site-packages\pip\_internal\utils\filesystem.py
- .venv\Lib\site-packages\pip\_internal\utils\hashes.py
- .venv\Lib\site-packages\pip\_internal\utils\misc.py
- .venv\Lib\site-packages\pip\_internal\utils\subprocess.py
- .venv\Lib\site-packages\pip\_internal\utils\temp_dir.py
- .venv\Lib\site-packages\pip\_internal\utils\unpacking.py
- .venv\Lib\site-packages\pip\_internal\vcs\__pycache__\bazaar.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\vcs\__pycache__\git.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\vcs\__pycache__\mercurial.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\vcs\__pycache__\subversion.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\vcs\__pycache__\versioncontrol.cpython-312.pyc
- .venv\Lib\site-packages\pip\_internal\vcs\bazaar.py
- .venv\Lib\site-packages\pip\_internal\vcs\git.py
- .venv\Lib\site-packages\pip\_internal\vcs\mercurial.py
- .venv\Lib\site-packages\pip\_internal\vcs\subversion.py
- .venv\Lib\site-packages\pip\_internal\vcs\versioncontrol.py
- .venv\Lib\site-packages\pip\_internal\wheel_builder.py
- .venv\Lib\site-packages\pip\_vendor\__init__.py
- .venv\Lib\site-packages\pip\_vendor\cachecontrol\__pycache__\cache.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\cachecontrol\__pycache__\controller.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\cachecontrol\__pycache__\filewrapper.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\cachecontrol\adapter.py
- .venv\Lib\site-packages\pip\_vendor\cachecontrol\cache.py
- .venv\Lib\site-packages\pip\_vendor\cachecontrol\caches\__pycache__\file_cache.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\cachecontrol\caches\__pycache__\redis_cache.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\cachecontrol\caches\file_cache.py
- .venv\Lib\site-packages\pip\_vendor\cachecontrol\caches\redis_cache.py
- .venv\Lib\site-packages\pip\_vendor\cachecontrol\controller.py
- .venv\Lib\site-packages\pip\_vendor\cachecontrol\filewrapper.py
- .venv\Lib\site-packages\pip\_vendor\cachecontrol\heuristics.py
- .venv\Lib\site-packages\pip\_vendor\cachecontrol\serialize.py
- .venv\Lib\site-packages\pip\_vendor\distlib\__pycache__\compat.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\distlib\__pycache__\scripts.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\distlib\__pycache__\util.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\distlib\compat.py
- .venv\Lib\site-packages\pip\_vendor\distlib\LICENSE.txt
- .venv\Lib\site-packages\pip\_vendor\distlib\resources.py
- .venv\Lib\site-packages\pip\_vendor\distlib\scripts.py
- .venv\Lib\site-packages\pip\_vendor\distlib\util.py
- .venv\Lib\site-packages\pip\_vendor\distro\__pycache__\distro.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\distro\distro.py
- .venv\Lib\site-packages\pip\_vendor\msgpack\__pycache__\ext.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\msgpack\ext.py
- .venv\Lib\site-packages\pip\_vendor\packaging\__pycache__\specifiers.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\packaging\__pycache__\tags.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\packaging\_manylinux.py
- .venv\Lib\site-packages\pip\_vendor\packaging\_parser.py
- .venv\Lib\site-packages\pip\_vendor\packaging\licenses\__init__.py
- .venv\Lib\site-packages\pip\_vendor\packaging\markers.py
- .venv\Lib\site-packages\pip\_vendor\packaging\metadata.py
- .venv\Lib\site-packages\pip\_vendor\packaging\specifiers.py
- .venv\Lib\site-packages\pip\_vendor\packaging\tags.py
- .venv\Lib\site-packages\pip\_vendor\packaging\version.py
- .venv\Lib\site-packages\pip\_vendor\pkg_resources\__init__.py
- .venv\Lib\site-packages\pip\_vendor\pkg_resources\__pycache__\__init__.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\platformdirs\__pycache__\api.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\platformdirs\api.py
- .venv\Lib\site-packages\pip\_vendor\platformdirs\unix.py
- .venv\Lib\site-packages\pip\_vendor\pygments\__pycache__\filter.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\pygments\__pycache__\scanner.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\pygments\filter.py
- .venv\Lib\site-packages\pip\_vendor\pygments\filters\__init__.py
- .venv\Lib\site-packages\pip\_vendor\pygments\filters\__pycache__\__init__.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\pygments\formatters\__init__.py
- .venv\Lib\site-packages\pip\_vendor\pygments\formatters\_mapping.py
- .venv\Lib\site-packages\pip\_vendor\pygments\lexer.py
- .venv\Lib\site-packages\pip\_vendor\pygments\lexers\__init__.py
- .venv\Lib\site-packages\pip\_vendor\pygments\lexers\python.py
- .venv\Lib\site-packages\pip\_vendor\pygments\scanner.py
- .venv\Lib\site-packages\pip\_vendor\pygments\sphinxext.py
- .venv\Lib\site-packages\pip\_vendor\pyproject_hooks\_impl.py
- .venv\Lib\site-packages\pip\_vendor\pyproject_hooks\_in_process\_in_process.py
- .venv\Lib\site-packages\pip\_vendor\README.rst
- .venv\Lib\site-packages\pip\_vendor\requests\__init__.py
- .venv\Lib\site-packages\pip\_vendor\requests\__pycache__\adapters.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\requests\__pycache__\api.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\requests\__pycache__\cookies.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\requests\__pycache__\models.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\requests\__pycache__\packages.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\requests\__pycache__\sessions.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\requests\__pycache__\structures.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\requests\__pycache__\utils.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\requests\adapters.py
- .venv\Lib\site-packages\pip\_vendor\requests\api.py
- .venv\Lib\site-packages\pip\_vendor\requests\cookies.py
- .venv\Lib\site-packages\pip\_vendor\requests\models.py
- .venv\Lib\site-packages\pip\_vendor\requests\packages.py
- .venv\Lib\site-packages\pip\_vendor\requests\sessions.py
- .venv\Lib\site-packages\pip\_vendor\requests\structures.py
- .venv\Lib\site-packages\pip\_vendor\requests\utils.py
- .venv\Lib\site-packages\pip\_vendor\resolvelib\resolvers\__pycache__\resolution.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\resolvelib\resolvers\resolution.py
- .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\_emoji_replace.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\_inspect.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\_null_file.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\color.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\console.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\containers.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\control.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\emoji.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\jupyter.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\layout.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\live.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\markup.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\pretty.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\progress.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\repr.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\segment.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\status.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\style.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\table.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\text.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\traceback.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\rich\_emoji_replace.py
- .venv\Lib\site-packages\pip\_vendor\rich\_inspect.py
- .venv\Lib\site-packages\pip\_vendor\rich\_null_file.py
- .venv\Lib\site-packages\pip\_vendor\rich\_ratio.py
- .venv\Lib\site-packages\pip\_vendor\rich\_wrap.py
- .venv\Lib\site-packages\pip\_vendor\rich\align.py
- .venv\Lib\site-packages\pip\_vendor\rich\color.py
- .venv\Lib\site-packages\pip\_vendor\rich\console.py
- .venv\Lib\site-packages\pip\_vendor\rich\containers.py
- .venv\Lib\site-packages\pip\_vendor\rich\control.py
- .venv\Lib\site-packages\pip\_vendor\rich\emoji.py
- .venv\Lib\site-packages\pip\_vendor\rich\jupyter.py
- .venv\Lib\site-packages\pip\_vendor\rich\layout.py
- .venv\Lib\site-packages\pip\_vendor\rich\live.py
- .venv\Lib\site-packages\pip\_vendor\rich\logging.py
- .venv\Lib\site-packages\pip\_vendor\rich\markup.py
- .venv\Lib\site-packages\pip\_vendor\rich\panel.py
- .venv\Lib\site-packages\pip\_vendor\rich\pretty.py
- .venv\Lib\site-packages\pip\_vendor\rich\progress.py
- .venv\Lib\site-packages\pip\_vendor\rich\progress_bar.py
- .venv\Lib\site-packages\pip\_vendor\rich\repr.py
- .venv\Lib\site-packages\pip\_vendor\rich\rule.py
- .venv\Lib\site-packages\pip\_vendor\rich\screen.py
- .venv\Lib\site-packages\pip\_vendor\rich\segment.py
- .venv\Lib\site-packages\pip\_vendor\rich\spinner.py
- .venv\Lib\site-packages\pip\_vendor\rich\status.py
- .venv\Lib\site-packages\pip\_vendor\rich\style.py
- .venv\Lib\site-packages\pip\_vendor\rich\syntax.py
- .venv\Lib\site-packages\pip\_vendor\rich\table.py
- .venv\Lib\site-packages\pip\_vendor\rich\text.py
- .venv\Lib\site-packages\pip\_vendor\rich\theme.py
- .venv\Lib\site-packages\pip\_vendor\rich\traceback.py
- .venv\Lib\site-packages\pip\_vendor\rich\tree.py
- .venv\Lib\site-packages\pip\_vendor\tomli\_parser.py
- .venv\Lib\site-packages\pip\_vendor\tomli_w\_writer.py
- .venv\Lib\site-packages\pip\_vendor\truststore\__pycache__\_macos.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\truststore\_api.py
- .venv\Lib\site-packages\pip\_vendor\truststore\_macos.py
- .venv\Lib\site-packages\pip\_vendor\truststore\_ssl_constants.py
- .venv\Lib\site-packages\pip\_vendor\truststore\_windows.py
- .venv\Lib\site-packages\pip\_vendor\urllib3\__init__.py
- .venv\Lib\site-packages\pip\_vendor\urllib3\__pycache__\_collections.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\urllib3\__pycache__\connectionpool.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\urllib3\__pycache__\poolmanager.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\urllib3\__pycache__\request.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\urllib3\_collections.py
- .venv\Lib\site-packages\pip\_vendor\urllib3\connection.py
- .venv\Lib\site-packages\pip\_vendor\urllib3\connectionpool.py
- .venv\Lib\site-packages\pip\_vendor\urllib3\contrib\_securetransport\__pycache__\low_level.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\urllib3\contrib\_securetransport\low_level.py
- .venv\Lib\site-packages\pip\_vendor\urllib3\contrib\securetransport.py
- .venv\Lib\site-packages\pip\_vendor\urllib3\fields.py
- .venv\Lib\site-packages\pip\_vendor\urllib3\packages\__pycache__\six.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\urllib3\packages\backports\__pycache__\makefile.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\urllib3\packages\backports\makefile.py
- .venv\Lib\site-packages\pip\_vendor\urllib3\packages\six.py
- .venv\Lib\site-packages\pip\_vendor\urllib3\poolmanager.py
- .venv\Lib\site-packages\pip\_vendor\urllib3\request.py
- .venv\Lib\site-packages\pip\_vendor\urllib3\util\__pycache__\retry.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\urllib3\util\__pycache__\ssltransport.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\urllib3\util\__pycache__\timeout.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\urllib3\util\retry.py
- .venv\Lib\site-packages\pip\_vendor\urllib3\util\ssl_.py
- .venv\Lib\site-packages\pip\_vendor\urllib3\util\ssl_match_hostname.py
- .venv\Lib\site-packages\pip\_vendor\urllib3\util\ssltransport.py
- .venv\Lib\site-packages\pip\_vendor\urllib3\util\timeout.py
- .venv\Lib\site-packages\pip\_vendor\urllib3\util\url.py
- .venv\Lib\site-packages\pip-25.3.dist-info\licenses\src\pip\_vendor\distlib\LICENSE.txt
- .venv\Scripts\activate.bat

## 3) File Interaction Map
- Files using filesystem I/O indicators: 102
- .\app\bootstrap.php
- .\app\Support\helpers.php
- .\assignments.php
- .\bulk_add_students.php
- .\course_progress.php
- .\download_class_students_template.php
- .\download_template.php
- .\includes\assignment_helpers.php
- .\includes\email_notifications.php
- .\manage_ica_marks.php
- .\PHPMailer\src\PHPMailer.php
- .\PHPMailer\src\POP3.php
- .\PHPMailer\src\SMTP.php
- .\preview_timetable.php
- .\program_dashboard.php
- .\program_reports.php
- .\student_progress.php
- .\system_admin_export_sql.php
- .\timetable.php
- .\trigger_scaled_alert.php
- .\vendor\autoload.php
- .\vendor\composer\platform_check.php
- .\vendor\phpmailer\phpmailer\changelog.md
- .\vendor\phpmailer\phpmailer\examples\azure_xoauth2.phps
- .\vendor\phpmailer\phpmailer\examples\callback.phps
- .\vendor\phpmailer\phpmailer\examples\DKIM_gen_keys.phps
- .\vendor\phpmailer\phpmailer\examples\DKIM_sign.phps
- .\vendor\phpmailer\phpmailer\examples\exceptions.phps
- .\vendor\phpmailer\phpmailer\examples\gmail.phps
- .\vendor\phpmailer\phpmailer\examples\gmail_xoauth.phps
- .\vendor\phpmailer\phpmailer\examples\mail.phps
- .\vendor\phpmailer\phpmailer\examples\mailing_list.phps
- .\vendor\phpmailer\phpmailer\examples\pop_before_smtp.phps
- .\vendor\phpmailer\phpmailer\examples\send_file_upload.phps
- .\vendor\phpmailer\phpmailer\examples\send_multiple_file_upload.phps
- .\vendor\phpmailer\phpmailer\examples\sendmail.phps
- .\vendor\phpmailer\phpmailer\examples\smime_signed_mail.phps
- .\vendor\phpmailer\phpmailer\examples\smtp.phps
- .\vendor\phpmailer\phpmailer\examples\smtp_no_auth.phps
- .\vendor\phpmailer\phpmailer\examples\ssl_options.phps
- .\vendor\phpmailer\phpmailer\src\PHPMailer.php
- .\vendor\phpmailer\phpmailer\src\POP3.php
- .\vendor\phpmailer\phpmailer\src\SMTP.php
- .\vendor\phpmailer\phpmailer\test\Fixtures\LocalizationTest\phpmailer.lang-yy.php
- .\vendor\phpmailer\phpmailer\test\PHPMailer\AddStringEmbeddedImageTest.php
- .\vendor\phpmailer\phpmailer\test\PHPMailer\DKIMTest.php
- .\vendor\phpmailer\phpmailer\test\PHPMailer\PHPMailerTest.php
- .\view_assignment_marks.php
- .\view_reports.php
- .venv\Lib\site-packages\pip\_internal\build_env.py
- .venv\Lib\site-packages\pip\_internal\cli\cmdoptions.py
- .venv\Lib\site-packages\pip\_internal\cli\parser.py
- .venv\Lib\site-packages\pip\_internal\commands\cache.py
- .venv\Lib\site-packages\pip\_internal\commands\wheel.py
- .venv\Lib\site-packages\pip\_internal\index\sources.py
- .venv\Lib\site-packages\pip\_internal\models\direct_url.py
- .venv\Lib\site-packages\pip\_internal\network\auth.py
- .venv\Lib\site-packages\pip\_internal\network\download.py
- .venv\Lib\site-packages\pip\_internal\network\lazy_wheel.py
- .venv\Lib\site-packages\pip\_internal\operations\build\build_tracker.py
- .venv\Lib\site-packages\pip\_internal\operations\install\wheel.py
- .venv\Lib\site-packages\pip\_internal\operations\prepare.py
- .venv\Lib\site-packages\pip\_internal\req\constructors.py
- .venv\Lib\site-packages\pip\_internal\req\req_file.py
- .venv\Lib\site-packages\pip\_internal\req\req_install.py
- .venv\Lib\site-packages\pip\_internal\req\req_uninstall.py
- .venv\Lib\site-packages\pip\_internal\utils\filesystem.py
- .venv\Lib\site-packages\pip\_internal\utils\subprocess.py
- .venv\Lib\site-packages\pip\_internal\utils\temp_dir.py
- .venv\Lib\site-packages\pip\_vendor\__init__.py
- .venv\Lib\site-packages\pip\_vendor\distlib\__pycache__\compat.cpython-312.pyc
- .venv\Lib\site-packages\pip\_vendor\distlib\compat.py
- .venv\Lib\site-packages\pip\_vendor\distlib\scripts.py
- .venv\Lib\site-packages\pip\_vendor\distlib\util.py
- .venv\Lib\site-packages\pip\_vendor\packaging\metadata.py
- .venv\Lib\site-packages\pip\_vendor\pkg_resources\__init__.py
- .venv\Lib\site-packages\pip\_vendor\platformdirs\api.py
- .venv\Lib\site-packages\pip\_vendor\pyproject_hooks\_impl.py
- .venv\Lib\site-packages\pip\_vendor\pyproject_hooks\_in_process\_in_process.py
- .venv\Lib\site-packages\pip\_vendor\requests\auth.py
- .venv\Lib\site-packages\pip\_vendor\requests\cookies.py
- .venv\Lib\site-packages\pip\_vendor\requests\models.py
- .venv\Lib\site-packages\pip\_vendor\requests\sessions.py
- .venv\Lib\site-packages\pip\_vendor\requests\structures.py
- .venv\Lib\site-packages\pip\_vendor\requests\utils.py
- .venv\Lib\site-packages\pip\_vendor\resolvelib\resolvers\resolution.py
- .venv\Lib\site-packages\pip\_vendor\resolvelib\structs.py
- .venv\Lib\site-packages\pip\_vendor\rich\_inspect.py
- .venv\Lib\site-packages\pip\_vendor\rich\console.py
- .venv\Lib\site-packages\pip\_vendor\rich\highlighter.py
- .venv\Lib\site-packages\pip\_vendor\rich\panel.py
- .venv\Lib\site-packages\pip\_vendor\rich\progress.py
- .venv\Lib\site-packages\pip\_vendor\rich\prompt.py
- .venv\Lib\site-packages\pip\_vendor\rich\style.py
- .venv\Lib\site-packages\pip\_vendor\rich\table.py
- .venv\Lib\site-packages\pip\_vendor\rich\text.py
- .venv\Lib\site-packages\pip\_vendor\rich\theme.py
- .venv\Lib\site-packages\pip\_vendor\urllib3\_collections.py
- .venv\Lib\site-packages\pip\_vendor\urllib3\connection.py
- .venv\Lib\site-packages\pip\_vendor\urllib3\connectionpool.py
- .venv\Lib\site-packages\pip\_vendor\urllib3\packages\six.py
- .venv\Lib\site-packages\pip\_vendor\urllib3\poolmanager.py

## 4) Include/Require Dependency Edges
- .\composer.json -> : {
        
- .\composer.lock -> : {
                
- .\create_ica_components.php -> alert_helpers.php
- .\debug_teacher_details.php -> program_reports.php
- .\PHPMailer\composer.json -> : {
        
- .\PHPMailer\get_oauth_token.php -> vendor/autoload.php
- .\PHPMailer\README.md -> path/to/PHPMailer/src/Exception.php
- .\PHPMailer\README.md -> path/to/PHPMailer/src/PHPMailer.php
- .\PHPMailer\README.md -> path/to/PHPMailer/src/SMTP.php
- .\PHPMailer\README.md -> vendor/autoload.php
- .\storage\logs\app.log -> C:\\xampp\\htdocs...
- .\trigger_scaled_alert.php -> alert_helpers.php
- .\vendor\composer\installed.json -> : {
                
- .\vendor\composer\InstalledVersions.php -> composer-runtime-api ^2.0
- .\vendor\phpmailer\phpmailer\composer.json -> : {
        
- .\vendor\phpmailer\phpmailer\examples\azure_xoauth2.phps -> ../vendor/autoload.php
- .\vendor\phpmailer\phpmailer\examples\callback.phps -> ../vendor/autoload.php
- .\vendor\phpmailer\phpmailer\examples\contactform.phps -> ../vendor/autoload.php
- .\vendor\phpmailer\phpmailer\examples\contactform-ajax.phps -> ../vendor/autoload.php
- .\vendor\phpmailer\phpmailer\examples\DKIM_sign.phps -> ../vendor/autoload.php
- .\vendor\phpmailer\phpmailer\examples\exceptions.phps -> ../vendor/autoload.php
- .\vendor\phpmailer\phpmailer\examples\extending.phps -> ../vendor/autoload.php
- .\vendor\phpmailer\phpmailer\examples\gmail.phps -> ../vendor/autoload.php
- .\vendor\phpmailer\phpmailer\examples\gmail_xoauth.phps -> ../vendor/autoload.php
- .\vendor\phpmailer\phpmailer\examples\mail.phps -> ../vendor/autoload.php
- .\vendor\phpmailer\phpmailer\examples\mailing_list.phps -> ../vendor/autoload.php
- .\vendor\phpmailer\phpmailer\examples\pop_before_smtp.phps -> ../vendor/autoload.php
- .\vendor\phpmailer\phpmailer\examples\send_file_upload.phps -> ../vendor/autoload.php
- .\vendor\phpmailer\phpmailer\examples\send_multiple_file_upload.phps -> ../vendor/autoload.php
- .\vendor\phpmailer\phpmailer\examples\sendmail.phps -> ../vendor/autoload.php
- .\vendor\phpmailer\phpmailer\examples\sendoauth2.phps -> vendor/autoload.php
- .\vendor\phpmailer\phpmailer\examples\simple_contact_form.phps -> ../vendor/autoload.php
- .\vendor\phpmailer\phpmailer\examples\smime_signed_mail.phps -> ../vendor/autoload.php
- .\vendor\phpmailer\phpmailer\examples\smtp.phps -> ../vendor/autoload.php
- .\vendor\phpmailer\phpmailer\examples\smtp_check.phps -> ../vendor/autoload.php
- .\vendor\phpmailer\phpmailer\examples\smtp_no_auth.phps -> ../vendor/autoload.php
- .\vendor\phpmailer\phpmailer\examples\ssl_options.phps -> ../vendor/autoload.php
- .\vendor\phpmailer\phpmailer\get_oauth_token.php -> vendor/autoload.php
- .\vendor\phpmailer\phpmailer\README.md -> path/to/PHPMailer/src/Exception.php
- .\vendor\phpmailer\phpmailer\README.md -> path/to/PHPMailer/src/PHPMailer.php
- .\vendor\phpmailer\phpmailer\README.md -> path/to/PHPMailer/src/SMTP.php
- .\vendor\phpmailer\phpmailer\README.md -> vendor/autoload.php
- .\vendor\phpmailer\phpmailer\UPGRADING.md -> class.phpmailer.php
- .\vendor\phpmailer\phpmailer\UPGRADING.md -> class.smtp.php
- .\vendor\phpmailer\phpmailer\UPGRADING.md -> PHPMailerAutoload.php
- .\vendor\phpmailer\phpmailer\UPGRADING.md -> src/Exception.php
- .\vendor\phpmailer\phpmailer\UPGRADING.md -> src/PHPMailer.php
- .\vendor\phpmailer\phpmailer\UPGRADING.md -> src/SMTP.php
- .\vendor\phpmailer\phpmailer\UPGRADING.md -> vendor/autoload.php
- .venv\Lib\site-packages\pip\_internal\locations\__init__.py -> )
        )
        if skip_cpython_build:
            continue

        warning_contexts.append((old_v, new_v, f
- .venv\Lib\site-packages\pip\_internal\locations\__init__.py -> , 
- .venv\Lib\site-packages\pip\_internal\locations\_distutils.py -> ,
            
- .venv\Lib\site-packages\pip\_internal\locations\_sysconfig.py -> , 
- .venv\Lib\site-packages\pip\_internal\locations\_sysconfig.py -> ] = os.path.join(base, 
- .venv\Lib\site-packages\pip\_internal\locations\_sysconfig.py -> ], dist_name),
        scripts=paths[
- .venv\Lib\site-packages\pip\_internal\models\search_scope.py -> 
                        
- .venv\Lib\site-packages\pip\_vendor\dependency_groups\__init__.py -> ,
    
- .venv\Lib\site-packages\pip\_vendor\pkg_resources\__init__.py -> ,
    
- .venv\Lib\site-packages\pip\_vendor\pygments\lexer.py -> , 
- .venv\Lib\site-packages\pip\_vendor\pygments\lexer.py -> state
- .venv\Lib\site-packages\pip\_vendor\pygments\lexers\python.py -> , 
- .venv\Lib\site-packages\pip\_vendor\pygments\lexers\python.py -> ,
        
- .venv\Lib\site-packages\pip\_vendor\pygments\lexers\python.py -> backtick
- .venv\Lib\site-packages\pip\_vendor\pygments\lexers\python.py -> builtins
- .venv\Lib\site-packages\pip\_vendor\pygments\lexers\python.py -> bytesescape
- .venv\Lib\site-packages\pip\_vendor\pygments\lexers\python.py -> expr
- .venv\Lib\site-packages\pip\_vendor\pygments\lexers\python.py -> expr-keywords
- .venv\Lib\site-packages\pip\_vendor\pygments\lexers\python.py -> fstrings-double
- .venv\Lib\site-packages\pip\_vendor\pygments\lexers\python.py -> fstrings-single
- .venv\Lib\site-packages\pip\_vendor\pygments\lexers\python.py -> keywords
- .venv\Lib\site-packages\pip\_vendor\pygments\lexers\python.py -> magicfuncs
- .venv\Lib\site-packages\pip\_vendor\pygments\lexers\python.py -> magicvars
- .venv\Lib\site-packages\pip\_vendor\pygments\lexers\python.py -> name
- .venv\Lib\site-packages\pip\_vendor\pygments\lexers\python.py -> nl
- .venv\Lib\site-packages\pip\_vendor\pygments\lexers\python.py -> numbers
- .venv\Lib\site-packages\pip\_vendor\pygments\lexers\python.py -> rfstringescape
- .venv\Lib\site-packages\pip\_vendor\pygments\lexers\python.py -> soft-keywords
- .venv\Lib\site-packages\pip\_vendor\pygments\lexers\python.py -> stringescape
- .venv\Lib\site-packages\pip\_vendor\pygments\lexers\python.py -> strings
- .venv\Lib\site-packages\pip\_vendor\pygments\lexers\python.py -> strings-double
- .venv\Lib\site-packages\pip\_vendor\pygments\lexers\python.py -> strings-single
- .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\console.cpython-312.pyc -> 

- .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\segment.cpython-312.pyc -> 

- .venv\Lib\site-packages\pip\_vendor\rich\console.py -> \n
- .venv\Lib\site-packages\pip\_vendor\rich\segment.py -> \n

## 5) Full File Inventory and Classification (No File Skipped)
| Path | Ext | Size(bytes) | Text | DB Conn | DB Read | DB Write | File I/O | SQL Ops | Includes |
|---|---:|---:|---:|---:|---:|---:|---:|---|---|
| .\3rd Year CSEDS.csv | .csv | 420 | True | False | False | False | False |  |  |
| .\3rd Year CSEDS.xlsx | .xlsx | 9398 | False | False | False | False | False |  |  |
| .\Academic Calendar - MPSTME - All Programmes - 2025-26_Student Version.pdf | .pdf | 108468 | False | False | False | False | False |  |  |
| .\admin_dashboard.php | .php | 7248 | True | True | False | True | False | CREATE |  |
| .\admin_login.php | .php | 18544 | True | True | True | True | False | SELECT, UPDATE, DROP |  |
| .\alert_helpers.php | .php | 4850 | True | False | True | True | False | SELECT, INSERT |  |
| .\alerts.php | .php | 2407 | True | True | True | True | False | UPDATE, SELECT |  |
| .\app\bootstrap.php | .php | 4974 | True | False | False | False | True |  |  |
| .\app\Database\Connection.php | .php | 2843 | True | False | False | False | False |  |  |
| .\app\Support\ConfigRepository.php | .php | 1544 | True | False | False | False | False |  |  |
| .\app\Support\helpers.php | .php | 4970 | True | False | False | False | True |  |  |
| .\assign_teachers.php | .php | 111152 | True | True | True | True | False | SELECT, INSERT, CREATE, DELETE, UPDATE, REPLACE |  |
| .\assignments.php | .php | 73456 | True | True | True | True | True | SELECT, INSERT, UPDATE, CREATE, REPLACE, DELETE |  |
| .\bulk_add_students.php | .php | 167502 | True | True | True | True | True | SELECT, ALTER, INSERT, UPDATE, CREATE, DELETE, REPLACE |  |
| .\change_password.php | .php | 11811 | True | True | True | True | False | SELECT, UPDATE, INSERT |  |
| .\change_roles.php | .php | 9140 | True | True | True | True | False | SELECT, UPDATE, CREATE |  |
| .\check_components.php | .php | 1036 | True | True | True | False | False | SELECT |  |
| .\Classes\1st Year\1st_Year_CE -C NISHAD.csv | .csv | 1410 | True | False | False | False | False |  |  |
| .\Classes\1st Year\1st_Year_CE -C NISHAD.xlsx | .xlsx | 10494 | False | False | False | False | False |  |  |
| .\Classes\1st Year\1st_Year_CE -D SHREYAS.csv | .csv | 1921 | True | False | False | False | False |  |  |
| .\Classes\1st Year\1st_Year_CE -D SHREYAS.xlsx | .xlsx | 11420 | False | False | False | False | False |  |  |
| .\Classes\1st Year\1st_Year_CSDS- B TANMAY.csv | .csv | 2340 | True | False | False | False | False |  |  |
| .\Classes\1st Year\1st_Year_CSDS- B TANMAY.xlsx | .xlsx | 13202 | False | False | False | False | False |  |  |
| .\Classes\1st Year\1st_Year_CSDS-A ANJU JASLIN.csv | .csv | 2451 | True | False | False | False | False |  |  |
| .\Classes\1st Year\1st_Year_CSDS-A ANJU JASLIN.xlsx | .xlsx | 13700 | False | False | False | False | False |  |  |
| .\Classes\2nd Year\2nd Year CE Hansini.csv | .csv | 1677 | True | False | False | False | False |  |  |
| .\Classes\2nd Year\2nd Year CSEDS_A Ryan.csv | .csv | 1798 | True | False | False | False | False |  |  |
| .\Classes\2nd Year\2nd Year CSEDS_B PARIDHI NEW - Copy.csv | .csv | 1402 | True | False | False | False | False |  |  |
| .\Classes\2nd Year\2nd Year CSEDS_B PARIDHI NEW.csv | .csv | 1402 | True | False | False | False | False |  |  |
| .\Classes\2nd Year\2nd_Year_CSDS-B PARIDHI.xlsx | .xlsx | 12861 | False | False | False | False | False |  |  |
| .\Classes\2nd Year\CE EXCEL.xlsx | .xlsx | 10885 | False | False | False | False | False |  |  |
| .\Classes\2nd Year\DW-DIV-A ATTENDACE LIST.xlsx | .xlsx | 14973 | False | False | False | False | False |  |  |
| .\Classes\3rd Year\3RD YR CE.csv | .csv | 284 | True | False | False | False | False |  |  |
| .\Classes\3rd Year\3RD YR DS.csv | .csv | 1311 | True | False | False | False | False |  |  |
| .\Classes\3rd Year\3RD YR DS.xlsx | .xlsx | 11968 | False | False | False | False | False |  |  |
| .\Classes\3rd Year\SE.pdf | .pdf | 200972 | False | False | False | False | False |  |  |
| .\Classes\4th Year\4th Year PAVITHRA.csv | .csv | 1181 | True | False | False | False | False |  |  |
| .\Classes\4th Year\STME - 2026.xlsx | .xlsx | 11896 | False | False | False | False | False |  |  |
| .\Classes\4th Year\WhatsApp Image 2025-12-22 at 08.47.55.jpeg | .jpeg | 126017 | False | False | False | False | False |  |  |
| .\Classes\4th Year\WhatsApp Image 2025-12-22 at 08.47.56 (1).jpeg | .jpeg | 180750 | False | False | False | False | False |  |  |
| .\Classes\4th Year\WhatsApp Image 2025-12-22 at 08.47.56 (2).jpeg | .jpeg | 173470 | False | False | False | False | False |  |  |
| .\Classes\4th Year\WhatsApp Image 2025-12-22 at 08.47.56.jpeg | .jpeg | 131834 | False | False | False | False | False |  |  |
| .\Classes\Class Timetables\Term 1 Schedule- 14Jul2025 to 15Nov2025\2ND YEAR CSEDS DIV A SEM 3.jpg | .jpg | 98298 | False | False | False | False | False |  |  |
| .\Classes\Class Timetables\Term 1 Schedule- 14Jul2025 to 15Nov2025\2ND YEAR CSEDS DIV B SEM 3.jpg | .jpg | 98499 | False | False | False | False | False |  |  |
| .\Classes\Class Timetables\Term 1 Schedule- 14Jul2025 to 15Nov2025\First year_STME_21-07-2025(1).xlsx | .xlsx | 0 | False | False | False | False | False |  |  |
| .\Classes\Class Timetables\Term 1 Schedule- 14Jul2025 to 15Nov2025\Higher Sem STME_14-07-2025.xlsx | .xlsx | 0 | False | False | False | False | False |  |  |
| .\Classes\Class Timetables\Term 2 Schedule- 2Jan2026 to 25Apr2026\STME_Hyd_Timetable_Term_II.xlsx | .xlsx | 106748 | False | False | False | False | False |  |  |
| .\Classes\Faculty Details\Faculty Details 2.jpeg | .jpeg | 273072 | False | False | False | False | False |  |  |
| .\Classes\Faculty Details\Faculty Details.jpeg | .jpeg | 352318 | False | False | False | False | False |  |  |
| .\Classes\Faculty Details\Faculty_Details.xlsx | .xlsx | 10252 | False | False | False | False | False |  |  |
| .\Classes\Student List Template .csv | .csv | 716 | True | False | False | False | False |  |  |
| .\Classes\Student List Template .xlsx | .xlsx | 9009 | False | False | False | False | False |  |  |
| .\composer.json | .json | 385 | True | False | False | False | False |  | : {
         |
| .\composer.lock | .lock | 4126 | True | False | False | False | False |  | : {
                 |
| .\config\app.php | .php | 416 | True | False | False | False | False |  |  |
| .\config\database.php | .php | 546 | True | False | False | False | False |  |  |
| .\config\mail.php | .php | 632 | True | False | False | False | False |  |  |
| .\config\session.php | .php | 465 | True | False | False | False | False |  |  |
| .\course_progress.php | .php | 92134 | True | True | True | True | True | SELECT, UPDATE |  |
| .\create_classes.php | .php | 82640 | True | True | True | True | False | SELECT, ALTER, UPDATE, DELETE, INSERT, CREATE |  |
| .\create_ica_components.php | .php | 64747 | True | True | True | True | False | ALTER, SELECT, DELETE, INSERT, UPDATE, CREATE, REPLACE | alert_helpers.php |
| .\create_subjects.php | .php | 119979 | True | True | True | True | False | CREATE, UPDATE, ALTER, SELECT, DELETE, REPLACE, INSERT |  |
| .\css\names_list.csv | .csv | 361 | True | False | False | False | False |  |  |
| .\css\names_list_fixed.csv | .csv | 371 | True | False | False | False | False |  |  |
| .\css\style.css | .css | 5131 | True | False | True | False | False | SELECT |  |
| .\db_connect.php | .php | 8301 | True | True | False | False | False |  |  |
| .\debug.log | .log | 200 | True | False | True | False | False | SELECT |  |
| .\debug_pc.php | .php | 404 | True | True | True | False | False | SELECT |  |
| .\debug_query.php | .php | 399 | True | True | True | False | False | SELECT |  |
| .\debug_teacher_details.php | .php | 235 | True | False | False | False | False |  | program_reports.php |
| .\download (1).jpeg | .jpeg | 11035 | False | False | False | False | False |  |  |
| .\download_class_students_template.php | .php | 2948 | True | True | True | False | True | SELECT |  |
| .\download_template.php | .php | 516 | True | False | False | False | True |  |  |
| .\edit_profile.php | .php | 20741 | True | True | True | True | False | SELECT, UPDATE, INSERT |  |
| .\edit_student.php | .php | 1525 | True | True | False | True | False | UPDATE |  |
| .\emailtemplate.html | .html | 5847 | True | False | False | False | False |  |  |
| .\Final ica image.jpg | .jpg | 56512 | False | False | False | False | False |  |  |
| .\forgot_password.php | .php | 12982 | True | True | True | True | False | CREATE, SELECT, INSERT, DROP |  |
| .\generate_platform_overview.ps1 | .ps1 | 7263 | True | True | True | True | False | SELECT, INSERT, UPDATE, DELETE, REPLACE, CREATE, ALTER, DROP, TRUNCATE |  |
| .\generate_report.php | .php | 0 | True | False | False | False | False |  |  |
| .\get_classes.php | .php | 2108 | True | True | True | False | False | SELECT |  |
| .\get_classes_for_subject.php | .php | 4997 | True | True | True | False | False | SELECT |  |
| .\get_ica_components.php | .php | 2729 | True | True | True | False | False | SELECT |  |
| .\get_progress_status.php | .php | 2806 | True | True | True | False | False | SELECT |  |
| .\get_sections.php | .php | 694 | True | True | True | False | False | SELECT |  |
| .\get_semesters.php | .php | 1479 | True | True | True | False | False | SELECT |  |
| .\get_student.php | .php | 843 | True | True | True | False | False | SELECT |  |
| .\get_student_marks.php | .php | 1055 | True | True | True | True | False | SELECT, CREATE |  |
| .\get_students_for_class.php | .php | 4608 | True | True | True | False | False | SELECT |  |
| .\get_students_for_subject.php | .php | 3832 | True | True | True | False | False | SELECT |  |
| .\get_subject_ica_components.php | .php | 2229 | True | True | True | False | False | SELECT |  |
| .\get_teachers_by_department.php | .php | 1295 | True | True | True | False | False | SELECT |  |
| .\get_teachers_by_school.php | .php | 1586 | True | True | True | False | False | SELECT |  |
| .\get_users.php | .php | 1335 | True | True | True | False | False | SELECT |  |
| .\ica_tracker (35).sql | .sql | 217341 | True | False | False | True | False | CREATE, INSERT, UPDATE, ALTER, DELETE |  |
| .\ica_tracker.css | .css | 23134 | True | False | True | False | False | SELECT |  |
| .\includes\academic_context.php | .php | 12908 | True | False | True | True | False | SELECT, ALTER, UPDATE |  |
| .\includes\activity_logger.php | .php | 13338 | True | False | True | True | False | CREATE, ALTER, SELECT, INSERT |  |
| .\includes\assignment_helpers.php | .php | 7241 | True | False | True | True | True | SELECT, ALTER, UPDATE |  |
| .\includes\email_notifications.php | .php | 34858 | True | False | True | True | True | REPLACE, UPDATE, SELECT |  |
| .\includes\init.php | .php | 128 | True | False | False | False | False |  |  |
| .\includes\mailer.php | .php | 6053 | True | False | False | False | False |  |  |
| .\includes\settings_helpers.php | .php | 2591 | True | False | True | False | False | SELECT |  |
| .\includes\student_context.php | .php | 9997 | True | False | True | False | False | SELECT |  |
| .\includes\term_switcher_ui.php | .php | 8621 | True | False | True | False | False | SELECT |  |
| .\index.php | .php | 21958 | True | False | False | False | False |  |  |
| .\login.php | .php | 27820 | True | True | True | True | False | SELECT, UPDATE, CREATE, INSERT, DROP |  |
| .\login_as.php | .php | 7193 | True | False | True | True | False | SELECT, DROP |  |
| .\logout.php | .php | 7384 | True | True | False | False | False |  |  |
| .\manage_academic_calendar.php | .php | 29119 | True | True | True | True | False | INSERT, UPDATE, SELECT, CREATE |  |
| .\manage_electives.php | .php | 45047 | True | True | True | True | False | CREATE, UPDATE, ALTER, DROP, SELECT, INSERT, DELETE |  |
| .\manage_ica_marks.php | .php | 140082 | True | True | True | True | True | SELECT, ALTER, INSERT, UPDATE, REPLACE, DELETE |  |
| .\manage_sections.php | .php | 8756 | True | True | True | True | False | SELECT, INSERT, CREATE |  |
| .\manage_teachers.php | .php | 28911 | True | True | True | True | False | UPDATE, SELECT, INSERT, CREATE |  |
| .\mid 1 image.jpg | .jpg | 45266 | False | False | False | False | False |  |  |
| .\mid 2 image .jpg | .jpg | 46189 | False | False | False | False | False |  |  |
| .\new\3rd Year\3RD YR DS with ica component.csv | .csv | 1584 | True | False | False | False | False |  |  |
| .\nmimshorizontal.jpg | .jpg | 156428 | False | False | False | False | False |  |  |
| .\nmimslogo.png | .png | 28044 | False | False | False | False | False |  |  |
| .\nmimsvertical.jpg | .jpg | 81323 | False | False | False | False | False |  |  |
| .\PHPMailer\COMMITMENT |  | 2092 | True | False | False | False | False |  |  |
| .\PHPMailer\composer.json | .json | 2815 | True | False | False | False | False |  | : {
         |
| .\PHPMailer\get_oauth_token.php | .php | 6261 | True | False | True | False | False | SELECT | vendor/autoload.php |
| .\PHPMailer\language\phpmailer.lang-af.php | .php | 1584 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-ar.php | .php | 2024 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-as.php | .php | 3792 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-az.php | .php | 1749 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-ba.php | .php | 1745 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-be.php | .php | 2178 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-bg.php | .php | 2196 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-bn.php | .php | 3845 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-ca.php | .php | 1730 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-cs.php | .php | 1798 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-da.php | .php | 2409 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-de.php | .php | 1886 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-el.php | .php | 3307 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-eo.php | .php | 1665 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-es.php | .php | 2588 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-et.php | .php | 1744 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-fa.php | .php | 2079 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-fi.php | .php | 1659 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-fo.php | .php | 1637 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-fr.php | .php | 2732 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-gl.php | .php | 1742 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-he.php | .php | 1812 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-hi.php | .php | 3768 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-hr.php | .php | 1754 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-hu.php | .php | 1717 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-hy.php | .php | 2185 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-id.php | .php | 1997 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-it.php | .php | 1819 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-ja.php | .php | 2934 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-ka.php | .php | 2884 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-ko.php | .php | 1771 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-ku.php | .php | 2238 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-lt.php | .php | 1627 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-lv.php | .php | 1643 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-mg.php | .php | 1782 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-mn.php | .php | 2186 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-ms.php | .php | 1734 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-nb.php | .php | 2288 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-nl.php | .php | 2365 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-pl.php | .php | 2635 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-pt.php | .php | 2465 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-pt_br.php | .php | 2719 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-ro.php | .php | 2448 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-ru.php | .php | 3297 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-si.php | .php | 3425 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-sk.php | .php | 1909 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-sl.php | .php | 2577 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-sr.php | .php | 2301 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-sr_latn.php | .php | 1814 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-sv.php | .php | 1610 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-tl.php | .php | 1721 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-tr.php | .php | 2606 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-uk.php | .php | 2282 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-ur.php | .php | 2265 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-vi.php | .php | 1793 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-zh.php | .php | 1669 | True | False | False | False | False |  |  |
| .\PHPMailer\language\phpmailer.lang-zh_cn.php | .php | 2333 | True | False | False | False | False |  |  |
| .\PHPMailer\LICENSE |  | 26529 | True | False | False | True | False | ALTER |  |
| .\PHPMailer\README.md | .md | 16832 | True | False | False | True | False | CREATE, UPDATE | path/to/PHPMailer/src/Exception.php; path/to/PHPMailer/src/PHPMailer.php; path/to/PHPMailer/src/SMTP.php; vendor/autoload.php |
| .\PHPMailer\SECURITY.md | .md | 7585 | True | False | False | False | False |  |  |
| .\PHPMailer\SMTPUTF8.md | .md | 5915 | True | False | True | False | False | SELECT |  |
| .\PHPMailer\src\DSNConfigurator.php | .php | 6883 | True | False | False | True | False | CREATE |  |
| .\PHPMailer\src\Exception.php | .php | 1256 | True | False | False | False | False |  |  |
| .\PHPMailer\src\OAuth.php | .php | 3791 | True | False | False | False | False |  |  |
| .\PHPMailer\src\OAuthTokenProvider.php | .php | 1538 | True | False | False | False | False |  |  |
| .\PHPMailer\src\PHPMailer.php | .php | 187910 | True | False | True | True | True | CREATE, SELECT, REPLACE, DROP, DELETE |  |
| .\PHPMailer\src\POP3.php | .php | 12352 | True | False | False | False | True |  |  |
| .\PHPMailer\src\SMTP.php | .php | 51856 | True | False | True | True | True | CREATE, SELECT |  |
| .\PHPMailer\VERSION |  | 7 | True | False | False | False | False |  |  |
| .\PLATFORM_OVERVIEW_FULL.md | .md | 276962 | True | True | True | True | False | UPDATE, CREATE, SELECT, DROP, INSERT, DELETE, REPLACE, ALTER, TRUNCATE |  |
| .\preview_timetable.php | .php | 2349 | True | False | False | False | True |  |  |
| .\program_dashboard.php | .php | 110622 | True | True | True | True | True | ALTER, SELECT, UPDATE, INSERT |  |
| .\program_reports.php | .php | 67676 | True | True | True | True | True | SELECT, UPDATE |  |
| .\README.md | .md | 12396 | True | True | False | True | False | UPDATE, CREATE |  |
| .\remove image.jpg | .jpg | 60141 | False | False | False | False | False |  |  |
| .\reports.php | .php | 6949 | True | True | True | True | False | SELECT, UPDATE |  |
| .\Required Changes in ICA Dashboard.docx | .docx | 13603 | False | False | False | False | False |  |  |
| .\reset_password.php | .php | 9979 | True | True | True | True | False | SELECT, UPDATE, DELETE, DROP, CREATE |  |
| .\send_alerts.php | .php | 10958 | True | True | True | True | False | INSERT, SELECT |  |
| .\set_academic_context.php | .php | 1856 | True | True | False | False | False |  |  |
| .\settings.php | .php | 7728 | True | True | True | True | False | INSERT, UPDATE, SELECT |  |
| .\signup.php | .php | 23097 | True | True | True | True | False | SELECT, INSERT, DROP, CREATE |  |
| .\smtp_config.php | .php | 788 | True | False | False | False | False |  |  |
| .\STME_SE_ICA_3rdYear_CSEDS.pdf | .pdf | 200972 | False | False | False | False | False |  |  |
| .\storage\logs\.gitignore | .gitignore | 16 | True | False | False | False | False |  |  |
| .\storage\logs\app.log | .log | 19473 | True | True | True | True | False | SELECT, UPDATE | C:\\xampp\\htdocs...; C:\\xampp\\htdocs...; C:\\xampp\\htdocs... |
| .\student_dashboard.php | .php | 68668 | True | True | True | False | False | SELECT |  |
| .\student_progress.php | .php | 75543 | True | True | True | False | True | SELECT |  |
| .\student_template (3).csv | .csv | 716 | True | False | False | False | False |  |  |
| .\styles.css | .css | 5227 | True | False | True | False | False | SELECT |  |
| .\subject_comparison.php | .php | 44919 | True | True | True | True | False | SELECT, UPDATE |  |
| .\subject_comparison_details.php | .php | 108 | True | False | False | False | False |  |  |
| .\system_admin_activity_feed.php | .php | 28379 | True | True | True | False | False | SELECT |  |
| .\system_admin_dashboard.php | .php | 16148 | True | True | True | False | False | SELECT |  |
| .\system_admin_export_sql.php | .php | 24741 | True | True | True | True | True | CREATE, SELECT, INSERT, DROP |  |
| .\teacher_dashboard.php | .php | 68820 | True | True | True | True | False | SELECT, ALTER, DROP, UPDATE, REPLACE |  |
| .\teacher_progress.php | .php | 98183 | True | True | True | True | False | SELECT, UPDATE |  |
| .\test_mail.php | .php | 28296 | True | True | True | True | False | CREATE, INSERT, UPDATE, SELECT |  |
| .\timetable.php | .php | 27960 | True | True | True | True | True | SELECT, UPDATE, INSERT, DELETE, REPLACE |  |
| .\track_teachers.php | .php | 2367 | True | True | True | False | False | SELECT |  |
| .\trigger_scaled_alert.php | .php | 1296 | True | True | False | False | True |  | alert_helpers.php |
| .\try.php | .php | 0 | True | False | False | False | False |  |  |
| .\update_progress.php | .php | 118256 | True | True | True | True | False | SELECT, ALTER, DROP, UPDATE, INSERT |  |
| .\uploads\40004495_1754812418.jpg | .jpg | 58594 | False | False | False | False | False |  |  |
| .\uploads\class_timetables\class_21_20251112061511_438e4495.pdf | .pdf | 454598 | False | False | False | False | False |  |  |
| .\uploads\class_timetables\class_27_20260102103645_315defbf.xlsx | .xlsx | 109314 | False | False | False | False | False |  |  |
| .\uploads\class_timetables\class_28_20260102103602_da3346ac.xlsx | .xlsx | 109314 | False | False | False | False | False |  |  |
| .\uploads\class_timetables\class_31_20260102103625_11afb4e0.xlsx | .xlsx | 109314 | False | False | False | False | False |  |  |
| .\uploads\class_timetables\class_32_20260102103613_d8d62f05.xlsx | .xlsx | 109314 | False | False | False | False | False |  |  |
| .\uploads\class_timetables\class_33_20260102103654_47a30f85.xlsx | .xlsx | 109314 | False | False | False | False | False |  |  |
| .\uploads\class_timetables\class_34_20260102103635_7866e386.xlsx | .xlsx | 109314 | False | False | False | False | False |  |  |
| .\uploads\Program chair images\Ashwini_Deshpande.jpg | .jpg | 20779 | False | False | False | False | False |  |  |
| .\uploads\Program chair images\Chandrakant_Wani.jpg | .jpg | 45001 | False | False | False | False | False |  |  |
| .\uploads\Program chair images\padma.jpeg | .jpeg | 110838 | False | False | False | False | False |  |  |
| .\uploads\Program chair images\Sai_Sailaja_Bharatam.jpg | .jpg | 7995 | False | False | False | False | False |  |  |
| .\uploads\Program chair images\Srividya.jpg | .jpg | 11105 | False | False | False | False | False |  |  |
| .\vendor\autoload.php | .php | 748 | True | False | False | True | True | UPDATE |  |
| .\vendor\composer\autoload_classmap.php | .php | 222 | True | False | False | False | False |  |  |
| .\vendor\composer\autoload_namespaces.php | .php | 139 | True | False | False | False | False |  |  |
| .\vendor\composer\autoload_psr4.php | .php | 213 | True | False | False | False | False |  |  |
| .\vendor\composer\autoload_real.php | .php | 1137 | True | False | False | False | False |  |  |
| .\vendor\composer\autoload_static.php | .php | 1093 | True | False | False | False | False |  |  |
| .\vendor\composer\ClassLoader.php | .php | 16378 | True | False | False | False | False |  |  |
| .\vendor\composer\installed.json | .json | 3769 | True | False | False | False | False |  | : {
                 |
| .\vendor\composer\installed.php | .php | 1068 | True | False | False | False | False |  |  |
| .\vendor\composer\InstalledVersions.php | .php | 17395 | True | False | False | False | False |  | composer-runtime-api ^2.0 |
| .\vendor\composer\LICENSE |  | 1070 | True | False | False | False | False |  |  |
| .\vendor\composer\platform_check.php | .php | 917 | True | False | False | False | True |  |  |
| .\vendor\phpmailer\phpmailer\.codecov.yml | .yml | 363 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\.editorconfig | .editorconfig | 235 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\.gitattributes | .gitattributes | 432 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\.github\actions\build-docs\Dockerfile |  | 448 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\.github\actions\build-docs\entrypoint.sh | .sh | 48 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\.github\dependabot.yml | .yml | 410 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\.github\FUNDING.yml | .yml | 180 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\.github\ISSUE_TEMPLATE\bug_report.md | .md | 1053 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\.github\workflows\docs.yaml | .yaml | 760 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\.github\workflows\scorecards.yml | .yml | 2301 | True | False | False | True | False | CREATE |  |
| .\vendor\phpmailer\phpmailer\.github\workflows\tests.yml | .yml | 7914 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\.gitignore | .gitignore | 279 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\.phan\config.php | .php | 1369 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\changelog.md | .md | 55353 | True | False | False | True | True | UPDATE, REPLACE, CREATE, DROP |  |
| .\vendor\phpmailer\phpmailer\COMMITMENT |  | 2138 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\composer.json | .json | 2855 | True | False | False | False | False |  | : {
         |
| .\vendor\phpmailer\phpmailer\docs\README.md | .md | 1002 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\examples\azure_xoauth2.phps | .phps | 4176 | True | False | False | True | True | CREATE, REPLACE | ../vendor/autoload.php |
| .\vendor\phpmailer\phpmailer\examples\callback.phps | .phps | 2367 | True | False | False | True | True | CREATE | ../vendor/autoload.php; ../vendor/autoload.php |
| .\vendor\phpmailer\phpmailer\examples\contactform.phps | .phps | 3692 | True | False | True | True | False | CREATE, SELECT | ../vendor/autoload.php |
| .\vendor\phpmailer\phpmailer\examples\contactform-ajax.phps | .phps | 5261 | True | False | True | True | False | CREATE, SELECT | ../vendor/autoload.php |
| .\vendor\phpmailer\phpmailer\examples\contents.html | .html | 585 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\examples\contentsutf8.html | .html | 1182 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\examples\DKIM_gen_keys.phps | .phps | 3398 | True | False | False | True | True | CREATE, DELETE |  |
| .\vendor\phpmailer\phpmailer\examples\DKIM_sign.phps | .phps | 1815 | True | False | False | False | True |  | ../vendor/autoload.php |
| .\vendor\phpmailer\phpmailer\examples\exceptions.phps | .phps | 1699 | True | False | False | True | True | CREATE, REPLACE | ../vendor/autoload.php |
| .\vendor\phpmailer\phpmailer\examples\extending.phps | .phps | 2690 | True | False | False | True | False | CREATE | ../vendor/autoload.php |
| .\vendor\phpmailer\phpmailer\examples\gmail.phps | .phps | 3852 | True | False | False | True | True | CREATE, REPLACE | ../vendor/autoload.php |
| .\vendor\phpmailer\phpmailer\examples\gmail_xoauth.phps | .phps | 4002 | True | False | False | True | True | CREATE, REPLACE | ../vendor/autoload.php |
| .\vendor\phpmailer\phpmailer\examples\images\PHPMailer card logo.afdesign | .afdesign | 29525 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\examples\images\PHPMailer card logo.png | .png | 26755 | False | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\examples\images\PHPMailer card logo.svg | .svg | 54868 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\examples\images\phpmailer.png | .png | 5831 | False | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\examples\images\phpmailer_mini.png | .png | 1842 | False | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\examples\mail.phps | .phps | 1180 | True | False | False | True | True | CREATE, REPLACE | ../vendor/autoload.php |
| .\vendor\phpmailer\phpmailer\examples\mailing_list.phps | .phps | 3287 | True | True | True | True | True | SELECT, ALTER, UPDATE | ../vendor/autoload.php |
| .\vendor\phpmailer\phpmailer\examples\pop_before_smtp.phps | .phps | 2489 | True | False | False | True | True | CREATE, REPLACE | ../vendor/autoload.php |
| .\vendor\phpmailer\phpmailer\examples\README.md | .md | 8388 | True | False | False | True | False | CREATE |  |
| .\vendor\phpmailer\phpmailer\examples\send_file_upload.phps | .phps | 2140 | True | False | False | True | True | CREATE | ../vendor/autoload.php |
| .\vendor\phpmailer\phpmailer\examples\send_multiple_file_upload.phps | .phps | 2089 | True | False | True | True | True | CREATE, SELECT | ../vendor/autoload.php |
| .\vendor\phpmailer\phpmailer\examples\sendmail.phps | .phps | 1253 | True | False | False | True | True | CREATE, REPLACE | ../vendor/autoload.php |
| .\vendor\phpmailer\phpmailer\examples\sendoauth2.phps | .phps | 5661 | True | False | False | True | False | CREATE, INSERT | vendor/autoload.php |
| .\vendor\phpmailer\phpmailer\examples\simple_contact_form.phps | .phps | 3975 | True | False | True | False | False | SELECT | ../vendor/autoload.php |
| .\vendor\phpmailer\phpmailer\examples\smime_signed_mail.phps | .phps | 4384 | True | False | False | True | True | CREATE, REPLACE | ../vendor/autoload.php |
| .\vendor\phpmailer\phpmailer\examples\smtp.phps | .phps | 2308 | True | False | False | True | True | CREATE, REPLACE | ../vendor/autoload.php |
| .\vendor\phpmailer\phpmailer\examples\smtp_check.phps | .phps | 2122 | True | False | False | True | False | CREATE | ../vendor/autoload.php |
| .\vendor\phpmailer\phpmailer\examples\smtp_low_memory.phps | .phps | 5048 | True | False | False | True | False | ALTER |  |
| .\vendor\phpmailer\phpmailer\examples\smtp_no_auth.phps | .phps | 1918 | True | False | False | True | True | CREATE, REPLACE | ../vendor/autoload.php |
| .\vendor\phpmailer\phpmailer\examples\ssl_options.phps | .phps | 2413 | True | False | False | True | True | CREATE | ../vendor/autoload.php |
| .\vendor\phpmailer\phpmailer\get_oauth_token.php | .php | 6443 | True | False | True | False | False | SELECT | vendor/autoload.php |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-af.php | .php | 1610 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-ar.php | .php | 2051 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-as.php | .php | 3827 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-az.php | .php | 1776 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-ba.php | .php | 1772 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-be.php | .php | 2205 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-bg.php | .php | 2223 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-bn.php | .php | 3880 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-ca.php | .php | 1757 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-cs.php | .php | 1826 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-da.php | .php | 2445 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-de.php | .php | 1914 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-el.php | .php | 3340 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-eo.php | .php | 1691 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-es.php | .php | 2624 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-et.php | .php | 1772 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-fa.php | .php | 2107 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-fi.php | .php | 1686 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-fo.php | .php | 1664 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-fr.php | .php | 2768 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-gl.php | .php | 1769 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-he.php | .php | 1839 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-hi.php | .php | 3803 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-hr.php | .php | 1781 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-hu.php | .php | 1744 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-hy.php | .php | 2212 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-id.php | .php | 2028 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-it.php | .php | 1847 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-ja.php | .php | 2971 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-ka.php | .php | 2911 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-ko.php | .php | 1798 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-ku.php | .php | 2265 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-lt.php | .php | 1654 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-lv.php | .php | 1670 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-mg.php | .php | 1809 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-mn.php | .php | 2213 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-ms.php | .php | 1761 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-nb.php | .php | 2321 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-nl.php | .php | 2399 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-pl.php | .php | 2668 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-pt.php | .php | 2499 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-pt_br.php | .php | 2757 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-ro.php | .php | 2481 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-ru.php | .php | 3333 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-si.php | .php | 3459 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-sk.php | .php | 1939 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-sl.php | .php | 2613 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-sr.php | .php | 2329 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-sr_latn.php | .php | 1842 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-sv.php | .php | 1637 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-tl.php | .php | 1749 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-tr.php | .php | 2644 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-uk.php | .php | 2310 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-ur.php | .php | 2295 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-vi.php | .php | 1820 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-zh.php | .php | 1698 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\language\phpmailer.lang-zh_cn.php | .php | 2369 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\LICENSE |  | 27030 | True | False | False | True | False | ALTER |  |
| .\vendor\phpmailer\phpmailer\phpcs.xml.dist | .dist | 1947 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\phpdoc.dist.xml | .xml | 471 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\phpunit.xml.dist | .dist | 1215 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\README.md | .md | 17064 | True | False | False | True | False | CREATE, UPDATE | path/to/PHPMailer/src/Exception.php; path/to/PHPMailer/src/PHPMailer.php; path/to/PHPMailer/src/SMTP.php; vendor/autoload.php |
| .\vendor\phpmailer\phpmailer\SECURITY.md | .md | 7622 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\SMTPUTF8.md | .md | 5963 | True | False | True | False | False | SELECT |  |
| .\vendor\phpmailer\phpmailer\src\DSNConfigurator.php | .php | 7128 | True | False | False | True | False | CREATE |  |
| .\vendor\phpmailer\phpmailer\src\Exception.php | .php | 1296 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\src\OAuth.php | .php | 3930 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\src\OAuthTokenProvider.php | .php | 1582 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\src\PHPMailer.php | .php | 193132 | True | False | True | True | True | CREATE, SELECT, REPLACE, DROP, DELETE |  |
| .\vendor\phpmailer\phpmailer\src\POP3.php | .php | 12821 | True | False | False | False | True |  |  |
| .\vendor\phpmailer\phpmailer\src\SMTP.php | .php | 51644 | True | False | True | True | True | CREATE, SELECT |  |
| .\vendor\phpmailer\phpmailer\test\DebugLogTestListener.php | .php | 1116 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\fakepopserver.sh | .sh | 3186 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\fakesendmail.sh | .sh | 518 | True | False | False | True | False | CREATE |  |
| .\vendor\phpmailer\phpmailer\test\Fixtures\FileIsAccessibleTest\accessible.txt | .txt | 13 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\Fixtures\FileIsAccessibleTest\inaccessible.txt | .txt | 13 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\Fixtures\LocalizationTest\phpmailer.lang-fr.php | .php | 266 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\Fixtures\LocalizationTest\phpmailer.lang-nl.php | .php | 266 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\Fixtures\LocalizationTest\phpmailer.lang-xa_scri_cc.php | .php | 190 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\Fixtures\LocalizationTest\phpmailer.lang-xb_scri.php | .php | 182 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\Fixtures\LocalizationTest\phpmailer.lang-xc_cc.php | .php | 183 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\Fixtures\LocalizationTest\phpmailer.lang-xd_cc.php | .php | 183 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\Fixtures\LocalizationTest\phpmailer.lang-xd_scri.php | .php | 182 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\Fixtures\LocalizationTest\phpmailer.lang-xe.php | .php | 175 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\Fixtures\LocalizationTest\phpmailer.lang-xx.php | .php | 193 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\Fixtures\LocalizationTest\phpmailer.lang-yy.php | .php | 600 | True | False | True | False | True |  |  |
| .\vendor\phpmailer\phpmailer\test\Fixtures\LocalizationTest\phpmailer.lang-zz.php | .php | 867 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\Language\TranslationCompletenessTest.php | .php | 3397 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\OAuth\OAuthTest.php | .php | 2543 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\AddEmbeddedImageTest.php | .php | 6620 | True | False | True | False | False | SELECT |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\AddrFormatTest.php | .php | 2145 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\AddStringAttachmentTest.php | .php | 5328 | True | False | True | False | False | SELECT |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\AddStringEmbeddedImageTest.php | .php | 5681 | True | False | True | False | True | SELECT |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\AuthCRAMMD5Test.php | .php | 1593 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\CustomHeaderTest.php | .php | 9434 | True | False | False | True | False | UPDATE |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\DKIMTest.php | .php | 9357 | True | False | False | False | True |  |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\DKIMWithoutExceptionsTest.php | .php | 1268 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\DSNConfiguratorTest.php | .php | 6660 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\EncodeQTest.php | .php | 4072 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\EncodeStringTest.php | .php | 4707 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\FileIsAccessibleTest.php | .php | 3415 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\FilenameToTypeTest.php | .php | 2291 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\GenerateIdTest.php | .php | 2705 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\GetLastMessageIDTest.php | .php | 3420 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\HasLineLongerThanMaxTest.php | .php | 4821 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\Html2TextTest.php | .php | 9971 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\ICalTest.php | .php | 4435 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\IsPermittedPathTest.php | .php | 3846 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\IsValidHostTest.php | .php | 4295 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\LocalizationTest.php | .php | 18967 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\MailTransportTest.php | .php | 3777 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\MbPathinfoTest.php | .php | 5890 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\MimeTypesTest.php | .php | 1995 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\NormalizeBreaksTest.php | .php | 3724 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\ParseAddressesTest.php | .php | 14631 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\PHPMailerTest.php | .php | 52006 | True | False | True | True | True | SELECT, CREATE |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\PunyencodeAddressTest.php | .php | 4899 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\QuotedStringTest.php | .php | 2151 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\ReplyToGetSetClearTest.php | .php | 17624 | True | False | False | True | False | CREATE |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\SetErrorTest.php | .php | 5335 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\SetFromTest.php | .php | 7264 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\SetTest.php | .php | 2395 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\SetWordWrapTest.php | .php | 4126 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\Utf8CharBoundaryTest.php | .php | 2014 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\ValidateAddressCustomValidatorTest.php | .php | 3750 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\ValidateAddressTest.php | .php | 17561 | True | False | False | True | False | CREATE |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\WrapTextTest.php | .php | 6214 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\PHPMailer\XMailerTest.php | .php | 2062 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\POP3\PopBeforeSmtpTest.php | .php | 3895 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\PreSendTestCase.php | .php | 1461 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\runfakepopserver.sh | .sh | 307 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\Security\DenialOfServiceVectorsTest.php | .php | 1650 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\SendTestCase.php | .php | 4456 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\testbootstrap-dist.php | .php | 248 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\test\TestCase.php | .php | 12725 | True | False | False | True | False | UPDATE, CREATE |  |
| .\vendor\phpmailer\phpmailer\test\validators.php | .php | 181 | True | False | False | False | False |  |  |
| .\vendor\phpmailer\phpmailer\UPGRADING.md | .md | 5563 | True | False | False | True | False | UPDATE, CREATE | PHPMailerAutoload.php; class.phpmailer.php; class.smtp.php; vendor/autoload.php; src/PHPMailer.php; src/SMTP.php; src/Exception.php; vendor/autoload.php; vendor/autoload.php |
| .\vendor\phpmailer\phpmailer\VERSION |  | 8 | True | False | False | False | False |  |  |
| .\view_alerts.php | .php | 7586 | True | True | True | True | False | UPDATE, SELECT |  |
| .\view_assignment_marks.php | .php | 31165 | True | True | True | True | True | SELECT, UPDATE |  |
| .\view_marks.php | .php | 21001 | True | True | True | True | False | SELECT, UPDATE |  |
| .\view_progress.php | .php | 35701 | True | True | True | True | False | SELECT, UPDATE |  |
| .\view_reports.php | .php | 70857 | True | True | True | True | True | SELECT, UPDATE, DELETE, REPLACE |  |
| .\view_timetable.php | .php | 20714 | True | True | True | False | False | SELECT |  |
| .\x.jpg | .jpg | 58594 | False | False | False | False | False |  |  |
| .env | .env | 716 | True | False | False | False | False |  |  |
| .env.example | .example | 714 | True | False | False | False | False |  |  |
| .git\COMMIT_EDITMSG |  | 40 | True | False | False | False | False |  |  |
| .git\config |  | 298 | True | False | False | False | False |  |  |
| .git\description |  | 73 | True | False | False | False | False |  |  |
| .git\FETCH_HEAD |  | 104 | True | False | False | False | False |  |  |
| .git\HEAD |  | 21 | True | False | False | False | False |  |  |
| .git\hooks\applypatch-msg.sample | .sample | 478 | True | False | False | False | False |  |  |
| .git\hooks\commit-msg.sample | .sample | 896 | True | False | False | False | False |  |  |
| .git\hooks\fsmonitor-watchman.sample | .sample | 4726 | True | False | True | True | False | UPDATE, SELECT |  |
| .git\hooks\post-update.sample | .sample | 189 | True | False | False | True | False | UPDATE |  |
| .git\hooks\pre-applypatch.sample | .sample | 424 | True | False | False | False | False |  |  |
| .git\hooks\pre-commit.sample | .sample | 1649 | True | False | False | False | False |  |  |
| .git\hooks\pre-merge-commit.sample | .sample | 416 | True | False | False | False | False |  |  |
| .git\hooks\prepare-commit-msg.sample | .sample | 1492 | True | False | False | False | False |  |  |
| .git\hooks\pre-push.sample | .sample | 1374 | True | False | False | True | False | DELETE, UPDATE |  |
| .git\hooks\pre-rebase.sample | .sample | 4898 | True | False | False | True | False | DELETE |  |
| .git\hooks\pre-receive.sample | .sample | 544 | True | False | False | False | False |  |  |
| .git\hooks\push-to-checkout.sample | .sample | 2783 | True | False | False | True | False | UPDATE |  |
| .git\hooks\sendemail-validate.sample | .sample | 2308 | True | False | False | True | False | REPLACE |  |
| .git\hooks\update.sample | .sample | 3650 | True | False | False | True | False | UPDATE, DELETE |  |
| .git\index |  | 26198 | True | True | False | False | False |  |  |
| .git\info\exclude |  | 240 | True | False | False | False | False |  |  |
| .git\logs\HEAD |  | 4695 | True | False | False | False | False |  |  |
| .git\logs\refs\heads\main |  | 2695 | True | False | False | False | False |  |  |
| .git\logs\refs\remotes\origin\HEAD |  | 154 | True | False | False | False | False |  |  |
| .git\logs\refs\remotes\origin\main |  | 827 | True | False | False | True | False | UPDATE |  |
| .git\objects\00\ffb968656603884d78550f699dfbf5d91add84 |  | 174 | True | False | False | False | False |  |  |
| .git\objects\01\1e1d913533a61829d922a6ab5564c114ec408f |  | 3252 | True | False | False | False | False |  |  |
| .git\objects\01\eb1ceb7f3bbe2074a71c3d56eaaa1718410187 |  | 2554 | True | False | False | False | False |  |  |
| .git\objects\02\8f5bc49693b7339371b1350a3db9aa001452ad |  | 846 | True | False | False | False | False |  |  |
| .git\objects\02\9d2d05f67b05f6a96eae7e28491a56543c58a8 |  | 693 | True | False | False | False | False |  |  |
| .git\objects\02\c7c050a901082cd3ddd72eeb5914a3446b1366 |  | 96710 | True | False | False | False | False |  |  |
| .git\objects\03\d491165b586c70d9eaab7ca25fc08f95de761e |  | 1099 | True | False | False | False | False |  |  |
| .git\objects\04\4d786de1c4d47f226d76e0acbd213f1ff2b675 |  | 544 | True | False | False | False | False |  |  |
| .git\objects\04\bfdef33f8360123df16bf7efde531f511075f4 |  | 434 | True | False | False | False | False |  |  |
| .git\objects\04\d262c7243aad8c53eb4823207fbab1eb1362fa |  | 886 | True | False | False | False | False |  |  |
| .git\objects\04\d5eb9259973c13cfe6d58aea58745f99154000 |  | 635 | True | False | False | False | False |  |  |
| .git\objects\05\9261af4f7c25f58728d4b306fb81c93684f8f3 |  | 131 | True | False | False | False | False |  |  |
| .git\objects\05\97a6c757cd2f1f1033d93323a30914b87050f9 |  | 201 | True | False | False | False | False |  |  |
| .git\objects\06\0490421a726a86c2a847cb1a6daead7765d383 |  | 78579 | True | False | False | False | False |  |  |
| .git\objects\06\765e799adb652dffee8a08e7a6970738f2fed3 |  | 944 | True | False | False | False | False |  |  |
| .git\objects\07\08c2cbbf4b65dbd8de3bb157bcfe6304322a6f |  | 3597 | True | False | False | False | False |  |  |
| .git\objects\07\4da0053660cbc709440d18b29df8c46471a2c7 |  | 420 | True | False | False | False | False |  |  |
| .git\objects\08\3ccaf98437885672a02047c5e827248ef09511 |  | 10523 | True | False | False | False | False |  |  |
| .git\objects\08\a6b733312c9c530614e2335f169d148856e409 |  | 760 | True | False | False | False | False |  |  |
| .git\objects\09\711635f35d1d9833020b3b3d14d0a4e91eda4d |  | 323 | True | False | False | False | False |  |  |
| .git\objects\09\c1a2cfefaa986c846b41743e856c945beff3cd |  | 706 | True | False | False | False | False |  |  |
| .git\objects\09\fb5634c684e0780426588f8dbc5285054f8e68 |  | 3595 | True | False | False | False | False |  |  |
| .git\objects\0a\3f695b980bf75ac15ec392117f26b1241fd79a |  | 147 | True | False | False | False | False |  |  |
| .git\objects\0a\4881b6f8fb3c5f8b89425ddadebb0cc6e621cb |  | 2534 | True | False | False | False | False |  |  |
| .git\objects\0a\e029457ae971410375547180f1c542af02a622 |  | 3565 | True | False | False | False | False |  |  |
| .git\objects\0b\2a72d52486210e0b8b3c8ceabb10843e1b1409 |  | 654 | True | False | False | False | False |  |  |
| .git\objects\0b\5280f75e6f5e3939e1a062bc2eca7316bdba65 |  | 944 | True | False | False | False | False |  |  |
| .git\objects\0b\9de0f1274bbd4f85d316b477abc65d0306e14d |  | 946 | True | False | False | False | False |  |  |
| .git\objects\0b\d7066ff16400df519845d2031c41af295d4b7d |  | 5239 | True | False | False | False | False |  |  |
| .git\objects\0b\fa13e11f3707219ea40c1c92485320779edc98 |  | 24358 | True | False | False | False | False |  |  |
| .git\objects\0c\1bb32cdca2dbeae18ab5df19aeb203bc11857a |  | 10946 | True | False | False | False | False |  |  |
| .git\objects\0c\708a3e81faaffad085756f347efa0f6f176431 |  | 18692 | True | False | False | False | False |  |  |
| .git\objects\0d\8e3acc8242b3ed2883344a6f8b4baa4e68e24b |  | 1251 | True | False | False | False | False |  |  |
| .git\objects\0e\54a00b639833b9df81e14f85d6c5e82e979da9 |  | 2534 | True | False | False | False | False |  |  |
| .git\objects\0e\6537737c90b8cdbccdcd77d8e5310dc7686af2 |  | 1344 | True | False | False | False | False |  |  |
| .git\objects\0e\d1633c2c854d3840bbe2bdccaa99abd11ff7e0 |  | 2147 | True | False | False | False | False |  |  |
| .git\objects\0f\a878384d1fe8f3e234b6a09ebaef166fde1812 |  | 153 | True | False | False | False | False |  |  |
| .git\objects\0f\b0a2c194b8590999a5ed79e357d4a9c1e9d8b8 |  | 165 | True | False | False | False | False |  |  |
| .git\objects\0f\bad1ca20a65ff22444eca2f56acf8dbc867e66 |  | 3497 | True | False | False | False | False |  |  |
| .git\objects\10\3a1fff5a2479b2b46eb4de1a68b3f71f2fe695 |  | 463 | True | False | False | False | False |  |  |
| .git\objects\10\98135368b7929c42f6135ae6200a415afb010b |  | 3564 | True | False | False | False | False |  |  |
| .git\objects\10\b09b6c09bdabfb5a82647b7b9be47dbde8df81 |  | 4180 | True | False | False | False | False |  |  |
| .git\objects\11\2f6b230c964db9a2e342588fbb6fc2a2fff427 |  | 41423 | True | False | False | False | False |  |  |
| .git\objects\11\90a1e20da262ccc791a621233719b528011206 |  | 4110 | True | False | False | False | False |  |  |
| .git\objects\12\7663b9cbee0bf28196e05fa65bd70581814521 |  | 228 | True | False | False | False | False |  |  |
| .git\objects\12\ad14f2ebe237a8a0988b58f5e7ac64399c2241 |  | 434 | True | False | False | False | False |  |  |
| .git\objects\13\4120cfc7b790ee25fa7874ae0322d730b8df57 |  | 297 | True | False | False | False | False |  |  |
| .git\objects\14\07a732cdad2874c75886af808e59b37ec47334 |  | 20635 | True | False | False | False | False |  |  |
| .git\objects\14\20c657e9fef881311df415ec86a0d82dcf4f5b |  | 23371 | True | False | False | False | False |  |  |
| .git\objects\14\66583f68293ad91c3c4dece81670765b353d55 |  | 2160 | True | False | False | False | False |  |  |
| .git\objects\14\fe8ab95bf1d54d58bab391c48d923e962eab43 |  | 44885 | True | False | False | False | False |  |  |
| .git\objects\15\48b44cd9c4f3165cf538e33c387b471195cd53 |  | 16905 | True | False | False | False | False |  |  |
| .git\objects\15\a2ff3ad6d8d6ea2b6b1f9552c62d745ffc9bf4 |  | 126 | True | False | False | False | False |  |  |
| .git\objects\16\37d082dbe1c070a7e78ac5b7579fd7ced59857 |  | 5898 | True | False | False | False | False |  |  |
| .git\objects\17\669407a7383af735769e01b31084a966d293b4 |  | 994 | True | False | False | False | False |  |  |
| .git\objects\17\fc5ead5537d5dbb04c9e7529e1b7bb630e294f |  | 19184 | True | False | False | False | False |  |  |
| .git\objects\18\49c45bfb44e03aa245034b67ad032171361516 |  | 16527 | True | False | False | False | False |  |  |
| .git\objects\18\8d81ffb904a0fc1f20b82ddbd29932cadcbcdb |  | 757 | True | False | False | False | False |  |  |
| .git\objects\1a\e1240acccafc14c4d023e83d090766674c487d |  | 28908 | True | False | False | False | False |  |  |
| .git\objects\1a\f6c5469bba48db66f670280cd0481cb21a94f4 |  | 5889 | True | False | False | False | False |  |  |
| .git\objects\1b\c73f4fa783731c700ae9dba7d4b1751e35be96 |  | 5128 | True | False | False | False | False |  |  |
| .git\objects\1b\c945938be9c506dbc2f2f6bc6b8123d0e5d983 |  | 15486 | True | False | False | False | False |  |  |
| .git\objects\1b\d2495d3bc3ce2ff43de9664c3fabc041c0483b |  | 965 | True | False | False | False | False |  |  |
| .git\objects\1c\7e799dbfadbc0c7f1c2a3ba738eccceb419ce6 |  | 2086 | True | False | False | False | False |  |  |
| .git\objects\1e\cec2de6ff245fd87e1dd879fd2ab555c17f15b |  | 688 | True | False | False | False | False |  |  |
| .git\objects\20\4808d03e14faf77bb9d109a4b4770fbba42f41 |  | 6228 | True | False | False | False | False |  |  |
| .git\objects\20\52022fd8e1e0829ce5496bd663c0aed41dd634 |  | 3901 | True | False | False | False | False |  |  |
| .git\objects\20\764b90f5099581197bce767159e37d2f4dd42f |  | 16392 | True | False | False | False | False |  |  |
| .git\objects\20\79e2b8c7b6349e2f9e7f14a1c4a7fdba2b7bcd |  | 5753 | True | False | False | False | False |  |  |
| .git\objects\20\f74c9008d8d1767d2845fcf78e8132d9109c0d |  | 2231 | True | False | False | False | False |  |  |
| .git\objects\21\2a11f13562def0b3e9d21cc4ac921ae95708f0 |  | 787 | True | False | False | False | False |  |  |
| .git\objects\21\d04872600681a5a72c1065651360d1e0997aa8 |  | 296 | True | False | False | False | False |  |  |
| .git\objects\23\4f2ea54d3d735100564dd09d3532cf3e34d034 |  | 189 | True | False | False | False | False |  |  |
| .git\objects\24\a6d0e9727ff6cb22c1695388ba2d3621b865d8 |  | 4705 | True | False | False | False | False |  |  |
| .git\objects\25\373729879c4b7fef146101c8d036811308d439 |  | 23695 | True | False | False | False | False |  |  |
| .git\objects\25\423d2013abe852beffdb71fa32ba7cb29e110e |  | 381 | True | False | False | False | False |  |  |
| .git\objects\25\e24f8ec24542fdd2ded59d7ad869d48ad3d2f6 |  | 8448 | True | False | False | False | False |  |  |
| .git\objects\26\7e76e07945405e1c4b5d8af692ebc591b6d52d |  | 358 | True | False | False | False | False |  |  |
| .git\objects\27\2e8c901225229e7d2e4ac3288ef970fb61ce2c |  | 1072 | True | False | False | False | False |  |  |
| .git\objects\27\f52a1f5f4301a0f32ea8282db2e12f3bf9e7ef |  | 2404 | True | False | False | False | False |  |  |
| .git\objects\28\354b06fa42fd8b338b748f94e5a0d7250bafc2 |  | 14163 | True | False | False | False | False |  |  |
| .git\objects\28\4705e082f7c6c30c5d1d1a92a79cf1bcd9d313 |  | 97 | True | False | False | False | False |  |  |
| .git\objects\28\567a00f3dc72b2d9a72721eff4ab760fd4ae96 |  | 164 | True | False | False | False | False |  |  |
| .git\objects\28\edf2bdadc97898cc116d87bc7d99ba897ace16 |  | 10980 | True | False | False | False | False |  |  |
| .git\objects\28\fe907510a852d971c0059d265f88288c4f6f53 |  | 7574 | True | False | False | False | False |  |  |
| .git\objects\29\1dce847bf05feb9c6e8c320e962ba759724755 |  | 15261 | True | False | False | False | False |  |  |
| .git\objects\29\277549c86c17df6f82d97653f3c955195444c4 |  | 30707 | True | False | False | False | False |  |  |
| .git\objects\29\5a47f95cb257fd3cb2e4f44011934a3db4f157 |  | 899 | True | False | False | False | False |  |  |
| .git\objects\29\6d26ca825ee393df1497d8417d97df74eb778f |  | 296063 | True | False | False | False | False |  |  |
| .git\objects\2a\f0edeff0e04fb0efa36042e648744a5420f426 |  | 2500 | True | False | False | False | False |  |  |
| .git\objects\2b\49e82a5a6833f216f003df1b208c6787ce9c2d |  | 2734 | True | False | False | False | False |  |  |
| .git\objects\2b\637c3328b2a49d91b36eae61d26d82033fccd9 |  | 1932 | True | False | False | False | False |  |  |
| .git\objects\2c\5c4be9f0180a475b7dfea32dc4758e2c36702b |  | 7232 | True | False | False | False | False |  |  |
| .git\objects\2d\5ebfcbe6c22637881c8baa9a16f228337daec5 |  | 16082 | True | False | False | False | False |  |  |
| .git\objects\2d\a5a315b6693b6fc29fe2d0fff262f5bf2bf7b9 |  | 7871 | True | False | False | False | False |  |  |
| .git\objects\2f\550b501a08a1f91d0a37bd3285e1dd60514bab |  | 1313 | True | False | False | False | False |  |  |
| .git\objects\30\4514bce7064bb8c9b16da1bbd4a3cd8d8e2203 |  | 9339 | True | False | False | False | False |  |  |
| .git\objects\31\9deb3738fc7c99a9ba5b2fc93da63bce9a121a |  | 16025 | True | False | False | False | False |  |  |
| .git\objects\32\7d282038242f2e1ac58f44347fbe25b56088b3 |  | 208 | True | False | False | False | False |  |  |
| .git\objects\32\7dfbafaf259a9f7a129e5fa3add6a69ac7065e |  | 1422 | True | False | False | False | False |  |  |
| .git\objects\33\9ee5753b7fc4080508e8acd300a7e6b797b784 |  | 1347 | True | False | False | False | False |  |  |
| .git\objects\33\bfcc07e8f38f98df27c18b9e32b0f74bf6813d |  | 10156 | True | False | False | False | False |  |  |
| .git\objects\34\684855a5da7e7b7e53bb840bf64fd178aa57ef |  | 731 | True | False | False | False | False |  |  |
| .git\objects\35\bdedce44014d787a6015c0209d891b860e2fe6 |  | 2598 | True | False | False | False | False |  |  |
| .git\objects\35\e4e7000e987e87619b52d167dbab731adc20cc |  | 816 | True | False | False | False | False |  |  |
| .git\objects\36\2db685891da9dca996dbde0a3760b50f258438 |  | 425 | True | False | False | False | False |  |  |
| .git\objects\36\4357f81e10643bb6b18f60ed113a6ec8026c4a |  | 961 | True | False | False | False | False |  |  |
| .git\objects\36\94f344ab58a0a8f802b7880797854a2b67aa41 |  | 731 | True | False | False | False | False |  |  |
| .git\objects\36\d9fe29fa8bd8ddea2956780c9643f910119bf1 |  | 661 | True | False | False | False | False |  |  |
| .git\objects\37\591434a6a3aac0bcc5f49dfc66a3014d8ed7db |  | 15207 | True | False | False | False | False |  |  |
| .git\objects\38\27a34597c43ede9a6d73a9003b526dfeb167d6 |  | 4675 | True | False | False | False | False |  |  |
| .git\objects\39\0529376a2969f75a2166a30fa312bd3be673ef |  | 16423 | True | False | False | False | False |  |  |
| .git\objects\39\1e49a107fd22c8470dad4724046c52dd4e20ef |  | 44199 | True | False | False | False | False |  |  |
| .git\objects\3a\bf47d1b20ba25119b646407291a2390d3141cb |  | 351 | True | False | False | False | False |  |  |
| .git\objects\3a\daaa127670ae7a95fa5a7bae96e1557ccc62da |  | 16825 | True | False | False | False | False |  |  |
| .git\objects\3b\f759c0f45a494cc6189c9c7d313d5403b64264 |  | 110799 | True | False | False | False | False |  |  |
| .git\objects\3c\0af66a687052352b7e9b47329ed121e11c6b01 |  | 3882 | True | False | False | False | False |  |  |
| .git\objects\3c\45bc1c35b5df8e98b537913d6b1698e27b171e |  | 1096 | True | False | False | False | False |  |  |
| .git\objects\3c\800aece13bcc370c0e0971df1c0093121ddb92 |  | 7965 | True | False | False | False | False |  |  |
| .git\objects\3c\aa5d210dc9312671ff24b046dff0632f3b699e |  | 833 | True | False | False | False | False |  |  |
| .git\objects\3d\866e3661641e051047a454fcc49622180662dd |  | 16829 | True | False | False | False | False |  |  |
| .git\objects\3d\ea055bbc28be71b11112f208c3de8a7df3128e |  | 987 | True | False | False | False | False |  |  |
| .git\objects\3e\00c25963274b4a67c2d22ba60e7f6df900443b |  | 1105 | True | False | False | False | False |  |  |
| .git\objects\3f\107989aa3e2549777c76f8bb8e7283ee8e1968 |  | 8252 | True | False | False | False | False |  |  |
| .git\objects\3f\3ab3760916280824084bcff39a3778d7372282 |  | 737 | True | False | False | False | False |  |  |
| .git\objects\3f\443e8e57ce49616c1c74c2d2bc26342b49d3bd |  | 5760 | True | False | False | False | False |  |  |
| .git\objects\40\6d2c45ec2ce803856d6cc2f92043f39e2673cd |  | 15510 | True | False | False | False | False |  |  |
| .git\objects\40\7714da66d947fb5a4d16a0f6ccd7ef96b56430 |  | 124 | True | False | False | False | False |  |  |
| .git\objects\41\30fb61b6e3a0a887186d92af6b16ddb681cdb7 |  | 8316 | True | False | False | False | False |  |  |
| .git\objects\41\6c907a4980ad87f90dcec3f2d9f05accae6724 |  | 149 | True | False | False | False | False |  |  |
| .git\objects\41\d05137f899e0a06e087c610d424e3e78c508f4 |  | 2577 | True | False | False | False | False |  |  |
| .git\objects\42\227f5357eda00e82bb38a42889f0ce75f2fdb0 |  | 182 | True | False | False | False | False |  |  |
| .git\objects\42\7f9301bf430459979d189adfee2ebd10f2593c |  | 23506 | True | False | False | False | False |  |  |
| .git\objects\43\0367459d179b913f24d8ddc7a4ec3c28875e5d |  | 567 | True | False | False | False | False |  |  |
| .git\objects\43\5c63c1c34cd8ea06ad5097926e10a8adc84f7e |  | 6235 | True | False | False | False | False |  |  |
| .git\objects\44\3b9638a5bd1fe0cba62c8368a57ab22c27e6a4 |  | 841 | True | False | False | False | False |  |  |
| .git\objects\44\696058f58c13e5da6e6e79191e687c5193de3e |  | 796 | True | False | False | False | False |  |  |
| .git\objects\44\b58de3a8a7cb7477d9551b6a96737e1dae92ef |  | 448 | True | False | False | False | False |  |  |
| .git\objects\45\bef9155386fa0009f5f2f1f9dbe2d67a7bb7bb |  | 977 | True | False | False | False | False |  |  |
| .git\objects\45\c13bd2fbb588cbec3248f970c6e81bcce1017e |  | 17777 | True | False | False | False | False |  |  |
| .git\objects\46\0b82e6074fb62e18f4428eec849a232a7a7022 |  | 198896 | True | False | False | False | False |  |  |
| .git\objects\46\190a5393e53a7b92cee8549b955e08b30e3e3f |  | 578 | True | False | False | False | False |  |  |
| .git\objects\47\3651080410de5f1557b5070497e45f5b1a1adf |  | 1396 | True | False | False | False | False |  |  |
| .git\objects\47\fce1773eabe500b78c52f44f6a73f0939cc8ee |  | 9193 | True | False | False | False | False |  |  |
| .git\objects\48\2a48c214df3569787ca2f0ccd50344e2ccfd6c |  | 9324 | True | False | False | False | False |  |  |
| .git\objects\48\2cdbeb2ffb888676d89b84b42f71bf9c68ade8 |  | 3557 | True | False | False | False | False |  |  |
| .git\objects\48\aee5d66d19d3e8475f106858b53d9bd259aa6d |  | 148 | True | False | False | False | False |  |  |
| .git\objects\48\c84c46af74003c6bb4a24cf8f364d621829ce2 |  | 1152 | True | False | False | False | False |  |  |
| .git\objects\49\d23b003b488b2ab22215172b11baab27d60673 |  | 1459 | True | False | False | False | False |  |  |
| .git\objects\4a\8050b4037bf873a294f466aabdc143b5983485 |  | 300 | True | False | False | False | False |  |  |
| .git\objects\4a\c9bd23d3a2069f9deac3fa30c4b70549ffc78b |  | 125129 | True | False | False | False | False |  |  |
| .git\objects\4b\971cd965eb91d78bdd42004cdbfd7fe6f586de |  | 634 | True | False | False | False | False |  |  |
| .git\objects\4c\21b557e29273dc03cf7888745b1fee4808d2b5 |  | 2499 | True | False | False | False | False |  |  |
| .git\objects\4d\29c6a946b28e7c82f9488e90d8abad2c5fdf6f |  | 183 | True | False | False | False | False |  |  |
| .git\objects\4d\b0ade26988ab4ac119273d1821a7b8a7a13745 |  | 6617 | True | False | False | False | False |  |  |
| .git\objects\4d\d185318bd2038f87fdd565cd141fa03487bb5c |  | 3379 | True | False | False | False | False |  |  |
| .git\objects\4e\74bfb7b8f4aafdacdfa6650d7f6ccada7dd917 |  | 1067 | True | False | False | False | False |  |  |
| .git\objects\4e\fe5f3aac25d3880bd8fb442357a5f43917b707 |  | 2612 | True | False | False | False | False |  |  |
| .git\objects\4f\0375bb32eff5f90d515367795e9f749790b1f3 |  | 5104 | True | False | False | False | False |  |  |
| .git\objects\4f\115b1c5818240b787d058245495094dc795704 |  | 705 | True | False | False | False | False |  |  |
| .git\objects\4f\34026dfa6310767529d0763f92c990894b1a3a |  | 3068 | True | False | False | False | False |  |  |
| .git\objects\4f\352d9bc2d06b087e5c47a0a9e2094a3c54427d |  | 626 | True | False | False | False | False |  |  |
| .git\objects\4f\90de12079eeee5826c5196d41ae551611902f5 |  | 3329 | True | False | False | False | False |  |  |
| .git\objects\4f\ae531e7f0ac1b0debca76c0f39560660bd857c |  | 59 | True | False | False | False | False |  |  |
| .git\objects\50\d0fa52e200e047032714c74db734173475ccf5 |  | 192 | True | False | False | False | False |  |  |
| .git\objects\51\5f83e820e99c5c20679d36b9ef98d7125394fa |  | 1576 | True | False | False | False | False |  |  |
| .git\objects\51\fe403b40b51ca97784f06b3994bb1817a5dac0 |  | 1065 | True | False | False | False | False |  |  |
| .git\objects\52\2522804356b5811411ad3cfac498835f51c354 |  | 2131 | True | False | False | False | False |  |  |
| .git\objects\52\39865a6f8b4c95e968432b1f30aff55878e165 |  | 1100 | True | False | False | False | False |  |  |
| .git\objects\53\2c1a6c14e73777ffa73649df3a3d2d7c8f48b9 |  | 414 | True | False | False | False | False |  |  |
| .git\objects\53\48d32538459fad78e1bd2a6c74c800a42405e5 |  | 22662 | True | False | False | False | False |  |  |
| .git\objects\53\aaa204b2046bb4b0f6d565f9cebad7b066c515 |  | 198897 | True | False | False | False | False |  |  |
| .git\objects\54\c54fa33a8d557e9ad1e27e38e42a605078a7b5 |  | 29699 | True | False | False | False | False |  |  |
| .git\objects\55\163838489fea52911ffc8a915c9f5f29c25422 |  | 212 | True | False | False | False | False |  |  |
| .git\objects\55\2167ef62c18d2fc0ae764e51462767488734cf |  | 758 | True | False | False | False | False |  |  |
| .git\objects\55\253c648f7c2105ae8efe4741f41396ba9037ec |  | 123 | True | False | False | False | False |  |  |
| .git\objects\56\7fdb58b319168371742a087883b88df70c7041 |  | 735 | True | False | False | False | False |  |  |
| .git\objects\57\10962e498c5d9df951829372c6c7f732182231 |  | 1855 | True | False | False | False | False |  |  |
| .git\objects\57\57cacc6d4a275d70f6c4607c70ea48bfd45eb9 |  | 7846 | True | False | False | False | False |  |  |
| .git\objects\58\927c78afdf52f41fbec405adba6e1a65a57882 |  | 1822 | True | False | False | False | False |  |  |
| .git\objects\59\124a91c017ee699c50a3dd78eafca39b5b79d3 |  | 32238 | True | False | False | False | False |  |  |
| .git\objects\59\87b12e8c2433565d72382b5d3174cfeb076975 |  | 18685 | True | False | False | False | False |  |  |
| .git\objects\5a\205ea10d105396b8ed6e8bd8d4805734c2b4c2 |  | 6317 | True | False | False | False | False |  |  |
| .git\objects\5a\27900c22f69785083d2e7652ccf63c01a3439a |  | 20633 | True | False | False | False | False |  |  |
| .git\objects\5a\44441710b51fecd0eb8324da10f23b8c705c71 |  | 10013 | True | False | False | False | False |  |  |
| .git\objects\5a\c151f72f8eaf321ff06ee01e872168981796ac |  | 19513 | True | False | False | False | False |  |  |
| .git\objects\5b\41f1390295d3b1060e66e86863f9ecf536afab |  | 383 | True | False | False | False | False |  |  |
| .git\objects\5c\785a04fa3d69a59ca4b9dd39224ac64d1e3d65 |  | 627 | True | False | False | False | False |  |  |
| .git\objects\5c\e2b9f682574c19aad082ab7a0a61a8b30be1af |  | 18756 | True | False | False | False | False |  |  |
| .git\objects\5d\2383e785324387e8d19cfe72c0f21b9d6a94ee |  | 14148 | True | False | False | False | False |  |  |
| .git\objects\5d\453411624ff21e8d19a9fae0a0c7ad701363ab |  | 5492 | True | False | False | False | False |  |  |
| .git\objects\5d\52b27bd18098cad1ff6beb3b8f71f875817a3c |  | 7232 | True | False | False | False | False |  |  |
| .git\objects\60\30947a8d85cff0a37de8aed4aa85f83656fe47 |  | 253 | True | False | False | False | False |  |  |
| .git\objects\60\a6249d1f98d393294a37633eeaef32d33afc37 |  | 15478 | True | False | False | False | False |  |  |
| .git\objects\60\b766230be603dc6c65fd6880bf422d4bec9a99 |  | 17792 | True | False | False | False | False |  |  |
| .git\objects\61\9864ab59c47eff91a7846cf0d181fb3756e4c0 |  | 991 | True | False | False | False | False |  |  |
| .git\objects\62\138329acba6e8e11974987a82c80fa9f1057b4 |  | 783 | True | False | False | False | False |  |  |
| .git\objects\62\609a60c372f3c5839382e32040d837a959ee63 |  | 6931 | True | False | False | False | False |  |  |
| .git\objects\62\d87781dccd7e408afb622c8b14f6d61163948f |  | 256 | True | False | False | False | False |  |  |
| .git\objects\62\f5bc7bb830d73e980954527a1d4e94e095c175 |  | 6218 | True | False | False | False | False |  |  |
| .git\objects\63\f0f522f06bed03d3546648ac77f6a7b4bf49d1 |  | 119 | True | False | False | False | False |  |  |
| .git\objects\63\fadf94dd95cd73115de9068d1fda4897ea08a1 |  | 105842 | True | False | False | False | False |  |  |
| .git\objects\64\76e3d8028d39df81ac442ca8e25c814d968e56 |  | 9681 | True | False | False | False | False |  |  |
| .git\objects\67\4605b8e03e5d5a7dbba658658a615c2a5dd28d |  | 194 | True | False | False | False | False |  |  |
| .git\objects\67\56fe885496342883df0942edd9a598fa185c6f |  | 1834 | True | False | False | False | False |  |  |
| .git\objects\67\9b18cf9f1ff637a8fad62ef1f4feffe51a1268 |  | 721 | True | False | False | False | False |  |  |
| .git\objects\69\88e077457af454c586baaa97c4520634c68afd |  | 2132 | True | False | False | False | False |  |  |
| .git\objects\69\91db6694f653c225fc7bebe75ec48d162704b2 |  | 1606 | True | False | False | False | False |  |  |
| .git\objects\6a\0237d73ba85a63a12c773838413e5915fed9ce |  | 522 | True | False | False | False | False |  |  |
| .git\objects\6b\351526f5510a9804b2168b47c2b27d74f347ff |  | 25920 | True | False | False | False | False |  |  |
| .git\objects\6b\e54a0b73113a38d36ee75a2a4abb68fbddc4e3 |  | 355825 | True | False | False | False | False |  |  |
| .git\objects\6c\12c7cc685d9e8137216cdb849fc5b364c1b02d |  | 3577 | True | False | False | False | False |  |  |
| .git\objects\6c\150e18095a8f7910315199bdd58a76c32aee29 |  | 249 | True | False | False | False | False |  |  |
| .git\objects\6c\366da1ee4467ef5b7acdf2ca0607cd516b1bf9 |  | 6928 | True | False | False | False | False |  |  |
| .git\objects\6d\1e6373901cf01fd79a0299b771cb37be72a3a7 |  | 693 | True | False | False | False | False |  |  |
| .git\objects\6d\e934effe1e5fd73dd0777e588276eac1098d32 |  | 984 | True | False | False | False | False |  |  |
| .git\objects\6d\fefcf51c369c3a3e7d4798c281122f8628b58f |  | 2468 | True | False | False | False | False |  |  |
| .git\objects\6f\1e32ede054cc19d7d608af9390274e979794b5 |  | 22380 | True | False | False | False | False |  |  |
| .git\objects\70\1bf46d9e5f1bd029a04390496950db97b98487 |  | 20543 | True | False | False | False | False |  |  |
| .git\objects\70\30855b6b2eac92518141dca47729a937f4bd3a |  | 24252 | True | False | False | False | False |  |  |
| .git\objects\70\58c1f05e22b4dbf802ca3b2cdd2681b353ff88 |  | 2223 | True | False | False | False | False |  |  |
| .git\objects\71\d38178dcf9f0f0d3e384b163da41dfc02ad9d9 |  | 24054 | True | False | False | False | False |  |  |
| .git\objects\71\db338343fbc967958d7909c3a2c9af9e89c918 |  | 721 | True | False | False | False | False |  |  |
| .git\objects\71\e1091946058b1008a3282c055b82dffe72bd03 |  | 813 | True | False | False | False | False |  |  |
| .git\objects\72\05e35e0eef2257fe8ed9dc708f7f31081db194 |  | 7204 | True | False | False | False | False |  |  |
| .git\objects\72\53406316b474932c6f35d65053a7838d0b45d3 |  | 1557 | True | False | False | False | False |  |  |
| .git\objects\74\06fe620c9b7a64c4636a301f6b24df94ddb168 |  | 215 | True | False | False | False | False |  |  |
| .git\objects\74\a68d50221f8e43afa2cf9fe0e2e18cec8ab258 |  | 1227 | True | False | False | False | False |  |  |
| .git\objects\75\41b691a2414edb3fb8e5f0679b3f35fe9442e3 |  | 16074 | True | False | False | False | False |  |  |
| .git\objects\75\d2f3d87edf13cb533f7119a0fbf33747139802 |  | 123 | True | False | False | False | False |  |  |
| .git\objects\76\2087bc6e88ccf4e80ae3fc57f32aabfb94da1c |  | 5506 | True | False | False | False | False |  |  |
| .git\objects\76\6a9fcaadc6b11b6cb6cc4ec57e7c7379a6da12 |  | 5702 | True | False | False | False | False |  |  |
| .git\objects\77\542193f69df40bc333be37ddfe4552cd2aa17b |  | 3567 | True | False | False | False | False |  |  |
| .git\objects\77\e74349ed1c460113d1f46e43c8cdbe44191f58 |  | 5099 | True | False | False | False | False |  |  |
| .git\objects\78\08b1902f8f1b8b435272e7bd7b639552114e3c |  | 20504 | True | False | False | False | False |  |  |
| .git\objects\78\24d8f7eafe8db890975f0fa2dfab31435900da |  | 4230 | True | False | False | False | False |  |  |
| .git\objects\78\305441a8dcfa0fdaa57ff5aa72d1531f75eb20 |  | 179009 | True | False | False | False | False |  |  |
| .git\objects\78\aab2b685eb35f3bc864c6bd162b350c603a1a4 |  | 7199 | True | False | False | False | False |  |  |
| .git\objects\79\32d362fb52854d1b40d7892b8f6eef357772b0 |  | 1517 | True | False | False | False | False |  |  |
| .git\objects\79\43e38612a49ff17ac714cb1a17ddf572a5248e |  | 16818 | True | False | False | False | False |  |  |
| .git\objects\79\a6802679a95d2d061445fe0df88abefa8dbe36 |  | 1022 | True | False | False | False | False |  |  |
| .git\objects\79\d8c74853c66d903ae340d8cdb7e35a3155a426 |  | 3598 | True | False | False | False | False |  |  |
| .git\objects\7a\62c27de74debdbe99f27972bfc80179d16506c |  | 2389 | True | False | False | False | False |  |  |
| .git\objects\7b\df44ca3f09c4812ad22543f3971c38e6ca7845 |  | 893 | True | False | False | False | False |  |  |
| .git\objects\7c\40f9ebe90755946492ad5a4365eb96e5455166 |  | 26003 | True | False | False | False | False |  |  |
| .git\objects\7d\77b8b792d075d4bbbfd09002fb7ad44a962190 |  | 274 | True | False | False | False | False |  |  |
| .git\objects\7d\92c76bed41a9d188bebe5650144b39e51294e5 |  | 20669 | True | False | False | False | False |  |  |
| .git\objects\7f\641f55d0b7cafa72cb13baad804223336be779 |  | 3360 | True | False | False | False | False |  |  |
| .git\objects\7f\84a6fa347860d051056af3f049d091bc2c11b8 |  | 1237 | True | False | False | False | False |  |  |
| .git\objects\80\13f37c4dff3ba4fab3645bc175049f2aa085c0 |  | 1402 | True | False | False | False | False |  |  |
| .git\objects\80\3763aa2350ac4ccdaf0a7af5df67b1e0f32667 |  | 6811 | True | False | False | False | False |  |  |
| .git\objects\80\be240ab23df91c006e8d4be51db82058ef1f39 |  | 2078 | True | False | False | False | False |  |  |
| .git\objects\80\cd909caa62b2c4b03e092a2e9643588c9ed472 |  | 16914 | True | False | False | False | False |  |  |
| .git\objects\80\d816e33ca91b442e928be9d0528f1230927ca9 |  | 233 | True | False | False | False | False |  |  |
| .git\objects\81\43438f4f79e4651708e98d86feec030429d2d0 |  | 33429 | True | False | False | False | False |  |  |
| .git\objects\81\9c597b282989db5ba3643a5ac7842d885075f0 |  | 193 | True | False | False | False | False |  |  |
| .git\objects\81\c00adc25cdb11163cad6ea085620ef1d53cdae |  | 3598 | True | False | False | False | False |  |  |
| .git\objects\81\d90b0e8f7058c13c49f3515b56c897456b496f |  | 1127532 | True | False | False | False | False |  |  |
| .git\objects\82\29d5e257e4b1a2a948e8e9d67bbffcb66d62d8 |  | 982 | True | False | False | False | False |  |  |
| .git\objects\82\66deac05d882f7e9cd31bbb47c466ba5aadbe4 |  | 594 | True | False | False | False | False |  |  |
| .git\objects\82\da669a71ab615bb580aa6ad37299a30613734b |  | 1135 | True | False | False | False | False |  |  |
| .git\objects\83\f293d7961f2407f20a3fbcd622a70a6538aacc |  | 825 | True | False | False | False | False |  |  |
| .git\objects\83\f4d5e47d44274a23cdfe5b30fbcb919d78a30d |  | 2459 | True | False | False | False | False |  |  |
| .git\objects\84\e9837d78ea020f2edbd22bb69ca474e0ee6e4d |  | 5251 | True | False | False | False | False |  |  |
| .git\objects\84\fae26a9ed29bc0fb606f8372450f4ac990d783 |  | 1569 | True | False | False | False | False |  |  |
| .git\objects\85\1060f10498cfbfa3776c8e8384a2e696966247 |  | 281 | True | False | False | False | False |  |  |
| .git\objects\85\36e4f60ed2eb1c47b757495f10ed1afa365454 |  | 15818 | True | False | False | False | False |  |  |
| .git\objects\85\ddef3e0dfab4e152e9f98b25ed5b1c7aa0b56b |  | 51760 | True | False | False | False | False |  |  |
| .git\objects\86\2a4e1a4261271817528a9f2867752a928170db |  | 7052 | True | False | False | False | False |  |  |
| .git\objects\86\bfb36d084ad213674a85c2be7c4fee18c18b88 |  | 182 | True | False | False | False | False |  |  |
| .git\objects\87\051a83029604ee25aa6e8f031a5067ce2a847e |  | 9152 | True | False | False | False | False |  |  |
| .git\objects\87\a0d60a7fc40b1918adaa3cf033bf2154bb7a0e |  | 987 | True | False | False | False | False |  |  |
| .git\objects\88\0fa42d6af6080a952dc68b2adce2861ec5d683 |  | 1554 | True | False | False | False | False |  |  |
| .git\objects\89\4eb81fd4c0bc1a85a030222d304fdc523f246a |  | 195 | True | False | False | False | False |  |  |
| .git\objects\89\6f01d0bfd85fa67a3e322cff110ee348f85be9 |  | 9689 | True | False | False | False | False |  |  |
| .git\objects\89\df7cd23c47803dceb634c75ee5b52365813fda |  | 659 | True | False | False | False | False |  |  |
| .git\objects\89\faad3f97efd2308b81e1d7c945677d9b908b5e |  | 14401 | True | False | False | False | False |  |  |
| .git\objects\8a\94f6a0445ecdc1f793da2f59f2c9ac2673d946 |  | 740 | True | False | False | False | False |  |  |
| .git\objects\8a\bb1a69d14d392bb4efbb69a099eef04bf9c556 |  | 2645 | True | False | False | False | False |  |  |
| .git\objects\8b\6ae18eca72368fd9b41cdfae78fb1dacf7c912 |  | 76 | True | False | False | False | False |  |  |
| .git\objects\8b\cfa2ae29d77c6f64cc065ab1487078458cc4b2 |  | 14949 | True | False | False | False | False |  |  |
| .git\objects\8c\97dd947c643319dbba4755eec276f5b222de90 |  | 827 | True | False | False | False | False |  |  |
| .git\objects\8c\aca2378ae119bd685075657302ced1bdadf309 |  | 10919 | True | False | False | False | False |  |  |
| .git\objects\8e\36d0a19387d03f81460e60b3ee7295a747409d |  | 1024 | True | False | False | False | False |  |  |
| .git\objects\8f\388e584dc4f18257065285224627ae831a31c4 |  | 2518 | True | False | False | False | False |  |  |
| .git\objects\8f\ffedbbcc33395d4e5b2f1f7d26b333523de6ff |  | 3462 | True | False | False | False | False |  |  |
| .git\objects\90\33b4772f1704f3bee0486a334bc6d004541d2e |  | 18751 | True | False | False | False | False |  |  |
| .git\objects\90\5f7a4943a97608ce8b278b1ffd6a33db9105c0 |  | 3563 | True | False | False | False | False |  |  |
| .git\objects\91\55a9138fe3f24788b94cf1840253be6e2a9c16 |  | 4781 | True | False | False | False | False |  |  |
| .git\objects\91\c69a438c9c7e4a86ae4791f480b01d713caaea |  | 77130 | True | False | False | False | False |  |  |
| .git\objects\92\0e7c94743dde32ca14c8d8642913488edc2094 |  | 1913 | True | False | False | False | False |  |  |
| .git\objects\93\4062da5969290820b93aa766c0400a6021e08d |  | 992 | True | False | False | False | False |  |  |
| .git\objects\93\9a46f794ad25b8884245d7675eb9feef123221 |  | 130852 | True | False | False | False | False |  |  |
| .git\objects\93\addc9e335ca1676831cf6741b84b01b69ffbd7 |  | 753 | True | False | False | False | False |  |  |
| .git\objects\93\b3bbab57faf307be1ed490270eafe2da5ee65c |  | 308 | True | False | False | False | False |  |  |
| .git\objects\94\937c96204011813db86040a9e694f60682dfde |  | 23910 | True | False | False | False | False |  |  |
| .git\objects\95\0bd874a372270210b66529c18815ed806490fe |  | 8658 | True | False | False | False | False |  |  |
| .git\objects\95\43300a5e35c651c51a8f741f524d27aa80f743 |  | 20588 | True | False | False | False | False |  |  |
| .git\objects\95\82c3d1e5e35376311d023e0042d6c0dfddc105 |  | 20497 | True | False | False | False | False |  |  |
| .git\objects\96\233b34ccba706a9f89dca87a9282a3cd836e0a |  | 55 | True | False | False | False | False |  |  |
| .git\objects\96\9d14727e385637e40bc0fc9f52c1839dc3f107 |  | 4708 | True | False | False | False | False |  |  |
| .git\objects\97\14a89a787fb3df96750ba9a2deab082992de4d |  | 7818 | True | False | False | False | False |  |  |
| .git\objects\97\216ea60f810423616ec7b8b733a3186e9557e9 |  | 1123 | True | False | False | False | False |  |  |
| .git\objects\97\7365230f142e2768593bc2a85403e3eecea3fc |  | 4550 | True | False | False | False | False |  |  |
| .git\objects\98\72c1921947e336c82987b6fd7625ae2ca2737d |  | 687 | True | False | False | False | False |  |  |
| .git\objects\99\138a54fa3c511c9709cbe142ffc84347ade06b |  | 1517 | True | False | False | False | False |  |  |
| .git\objects\99\9cb70a91de18d746490714bcefca0590d428a1 |  | 23793 | True | False | False | False | False |  |  |
| .git\objects\9a\556ba866e75375b0bb2d0b23f1f866bd6b609c |  | 6773 | True | False | False | False | False |  |  |
| .git\objects\9a\b8c2a463a7e2a4e4913ad693135f38f8d36489 |  | 3312 | True | False | False | False | False |  |  |
| .git\objects\9a\dada3b2503cf5ee11c0ad79b5d8fd09ed05df0 |  | 408 | True | False | False | False | False |  |  |
| .git\objects\9b\9c104feed13b0afdc2250d5b26ae7ea3a1b5ca |  | 872 | True | False | False | False | False |  |  |
| .git\objects\9b\9ce8ae2a46e93bc0f3c251b0cd478dec811c46 |  | 256 | True | False | False | False | False |  |  |
| .git\objects\9c\0325b5e3a0727bc5ea51dadfb7635eed551b68 |  | 10785 | True | False | False | False | False |  |  |
| .git\objects\9c\fec468b2c96d13e6b16d9ed6e75df5b8c1b903 |  | 176 | True | False | False | False | False |  |  |
| .git\objects\9e\739baa9fbf0ca23accadf10d6b3179acf8f517 |  | 42790 | True | False | False | False | False |  |  |
| .git\objects\9e\8362c4c501dd14f8ce7e5cf1e914f7061b953d |  | 84 | True | False | False | False | False |  |  |
| .git\objects\9e\92ddaaf793ed2dd64d4eea6b1fe563bb880a1e |  | 901 | True | False | False | False | False |  |  |
| .git\objects\9e\d9b79edea64adfec5a735310646ab5dbff8bfd |  | 15300 | True | False | False | False | False |  |  |
| .git\objects\9f\8e6ffd0985db41baf3e505282653c0feff2017 |  | 5117 | True | False | False | False | False |  |  |
| .git\objects\9f\f30b794c98140bde20cd1f310110277c1198ba |  | 23184 | True | False | False | False | False |  |  |
| .git\objects\a0\87174d6bbe93fcd25ea69356dff695cdcbb1c9 |  | 126 | True | False | False | False | False |  |  |
| .git\objects\a1\089a6a7d3413a0265c2e67aeacb52a44dd8a26 |  | 435654 | True | False | False | False | False |  |  |
| .git\objects\a1\15945ab1314462fda738cff6d78dc861a9be2a |  | 8785 | True | False | False | False | False |  |  |
| .git\objects\a2\18ad0134843cf9e5e6031eb42c573cfa41d0dd |  | 637 | True | False | False | False | False |  |  |
| .git\objects\a2\269eefe9a1b229b5aaa18f6116891125538f7d |  | 24242 | True | False | False | False | False |  |  |
| .git\objects\a3\2f620691c4aec55c6c4f6db9f3351f1650a525 |  | 63985 | True | False | False | False | False |  |  |
| .git\objects\a3\d612d55f77f05759cfff3310b6b9bd5fb6b343 |  | 8258 | True | False | False | False | False |  |  |
| .git\objects\a3\e09a6340fa3c6b690462732243d8b943982ec6 |  | 3565 | True | False | False | False | False |  |  |
| .git\objects\a4\1352fa50a582aa009e8417d88858952541bb33 |  | 3564 | True | False | False | False | False |  |  |
| .git\objects\a4\599cdbbbdf9dc05dd33872976b993881ab8168 |  | 23823 | True | False | False | False | False |  |  |
| .git\objects\a6\87e0ddb6f266cd0778ee124f1b47d7103f845b |  | 993 | True | False | False | False | False |  |  |
| .git\objects\a6\d582d83340f512fab76e76ce7d75daaa5249e0 |  | 1134 | True | False | False | False | False |  |  |
| .git\objects\a7\2a536c9e003ba5135f2448a36dcc614cce27f9 |  | 4469 | True | False | False | False | False |  |  |
| .git\objects\a7\390c224d5e0e687bc26102fb08dba244c07c0c |  | 10402 | True | False | False | False | False |  |  |
| .git\objects\a7\e958860c5a146de15f68be2bec98335887d92f |  | 1443 | True | False | False | False | False |  |  |
| .git\objects\aa\a58f8969aef7e734ec3c9995ad2c03174796ba |  | 16114 | True | False | False | False | False |  |  |
| .git\objects\ab\950027829affcdffdae9772c9c75eec5c2ce6f |  | 716 | True | False | False | False | False |  |  |
| .git\objects\ab\cf763cc4bfd924a8ca8beb0fca3e7a6edb0c83 |  | 1305 | True | False | False | False | False |  |  |
| .git\objects\ab\e99f4e572d2e8f8f3f9a58ced5286e4e91200c |  | 1558 | True | False | False | False | False |  |  |
| .git\objects\ac\46c7caf95338de49eeb7a498cab425f97e1fc4 |  | 2327 | True | False | False | False | False |  |  |
| .git\objects\ac\8ea5c8379bc7f5f9dd08e70650bf351212917e |  | 2663 | True | False | False | False | False |  |  |
| .git\objects\ad\4827129f8b52e0cc89cf5ce3da1873d90e538e |  | 2232 | True | False | False | False | False |  |  |
| .git\objects\ae\51eb53f1de187dc192e863be559ae4a09d5c76 |  | 16017 | True | False | False | False | False |  |  |
| .git\objects\ae\6d80caa2f8425e089e0ee54f284c399e754e58 |  | 12482 | True | False | False | False | False |  |  |
| .git\objects\ae\ad7dcd55683513832863ec7f6acf99bd373eae |  | 125350 | True | False | False | False | False |  |  |
| .git\objects\ae\f652234659775cc8e0ddc619e23839b7917aac |  | 164 | True | False | False | False | False |  |  |
| .git\objects\af\6ebed7ba6ad7caf31a26f97f3f8b71da7935ed |  | 396 | True | False | False | False | False |  |  |
| .git\objects\af\ec974591a65946f98f315a675ff1889986e115 |  | 7965 | True | False | False | False | False |  |  |
| .git\objects\b1\1f46ab92936152c4446c2e88a36affcbda6a82 |  | 18124 | True | False | False | False | False |  |  |
| .git\objects\b1\23aa5fc06a3b96148f6ac48ceba79cb78a189a |  | 751 | True | False | False | False | False |  |  |
| .git\objects\b1\5d79794491f5b3811ffaa3f1cef472bf0c7716 |  | 1613 | True | False | False | False | False |  |  |
| .git\objects\b1\68e975d81fe835d19ab932d70de0c51785bc9a |  | 4361 | True | False | False | False | False |  |  |
| .git\objects\b1\9fb40680ceabc9e0c71a69c54d71aaa55624be |  | 8039 | True | False | False | False | False |  |  |
| .git\objects\b2\6c74bc03c45ed91d44ea917521b86f413f8b9f |  | 17797 | True | False | False | False | False |  |  |
| .git\objects\b2\f5008178c7da7bb1d6179aa10396f40b05471f |  | 3218 | True | False | False | False | False |  |  |
| .git\objects\b3\0f0520142c18ae24df23a8f20034b05a848c3d |  | 706 | True | False | False | False | False |  |  |
| .git\objects\b4\ac554dcefb1c07866e1c9e8634b2a11fd67a70 |  | 114 | True | False | False | False | False |  |  |
| .git\objects\b6\5cfb2256a4199e628ea232d8dc1da69cdead5e |  | 2539 | True | False | False | False | False |  |  |
| .git\objects\b6\5cff5a16bfceb207397de8d8ac5a1882aa4438 |  | 3108 | True | False | False | False | False |  |  |
| .git\objects\b7\bc2b8c2fbfffa958056744b9ddad33cf8fcbf7 |  | 459 | True | False | False | False | False |  |  |
| .git\objects\b8\491c1b58333a4be5efddfef986c55b5e75ffdb |  | 5449 | True | False | False | False | False |  |  |
| .git\objects\b8\7e1b972e26f32540c78693bbb8b904efcdff40 |  | 350 | True | False | False | False | False |  |  |
| .git\objects\b8\908128d7858faedebbdf7bf3efe6fc9a377b1c |  | 2501 | True | False | False | False | False |  |  |
| .git\objects\b8\d7b0edd413b8bd457ee8392cee50475601e557 |  | 259 | True | False | False | False | False |  |  |
| .git\objects\b8\f92b7f5abb4bc5a139949c7eee123978d7096e |  | 943 | True | False | False | False | False |  |  |
| .git\objects\b9\1a0a422d4619aa1849dcb851583aa1c784e710 |  | 124 | True | False | False | False | False |  |  |
| .git\objects\ba\399321aaec37d677b88d89b7201e1b0d962a26 |  | 4752 | True | False | False | False | False |  |  |
| .git\objects\ba\76a44f8a964e7a132f0835d2d98870afc15052 |  | 5031 | True | False | False | False | False |  |  |
| .git\objects\ba\cb0fb526b1ce9c6ddebfc7c4302ee97c9a29b8 |  | 954 | True | False | False | False | False |  |  |
| .git\objects\ba\eb4da408c3286b54ce020a5c71b39a214c4398 |  | 171319 | True | False | False | False | False |  |  |
| .git\objects\ba\eb8ce952c338ddfdef00578b3906631b808a28 |  | 24282 | True | False | False | False | False |  |  |
| .git\objects\bb\05d2d92397eab9e99807011fd1c75cabc82314 |  | 923 | True | False | False | False | False |  |  |
| .git\objects\bb\71caf25dad75d39b187dd21059c286f587f46d |  | 169 | True | False | False | False | False |  |  |
| .git\objects\bb\a256cff949b9b3096bc9013988d52d366d33d3 |  | 23078 | True | False | False | False | False |  |  |
| .git\objects\bd\498bb1fe234dbbeb51d00c1155df1891af82a1 |  | 2529 | True | False | False | False | False |  |  |
| .git\objects\bd\9076e1ba5a1bf47a2e9ca9805e75e51bb8be25 |  | 942 | True | False | False | False | False |  |  |
| .git\objects\bf\706238ce69807bf78dd374d6c76fb8b17b4d70 |  | 2767 | True | False | False | False | False |  |  |
| .git\objects\bf\8417b6f827237b4ddf3689fb82f8a2e0b2188a |  | 27393 | True | False | False | False | False |  |  |
| .git\objects\c0\0baad38d1af145df930f0af3582f17ce42374a |  | 391 | True | False | False | False | False |  |  |
| .git\objects\c0\1259f07b5460fe034964938c2bae20f57d2704 |  | 10959 | True | False | False | False | False |  |  |
| .git\objects\c1\3b9eaa23bb3b16136e2bf41b9cdf2a6c3a9f49 |  | 50 | True | False | False | False | False |  |  |
| .git\objects\c1\d8d152352b511d95c846649d7e0ea3df198e03 |  | 610 | True | False | False | False | False |  |  |
| .git\objects\c2\b5355aa955f83f43927726022dc272c1383e14 |  | 20668 | True | False | False | False | False |  |  |
| .git\objects\c3\0126346e884c54e3fb4d6256912d2595bcb7e4 |  | 743 | True | False | False | False | False |  |  |
| .git\objects\c3\98e3e5e734a91cc5c056510905e0dd67d491e5 |  | 4287 | True | False | False | False | False |  |  |
| .git\objects\c3\9dc61eef7081c173a6e943123ad1e388e17ea0 |  | 250 | True | False | False | False | False |  |  |
| .git\objects\c4\1f675dfd1e82f186f6e6266c416a5dd98e0517 |  | 902 | True | False | False | False | False |  |  |
| .git\objects\c5\14f2f6b62c7842401c1ee5cddfbf3cad0264b7 |  | 23396 | True | False | False | False | False |  |  |
| .git\objects\c5\97d05f3fbee71bf0b750afefd7a16c6330c169 |  | 3243 | True | False | False | False | False |  |  |
| .git\objects\c6\695e67baaa981a9a9a5ecec813a3011b231af4 |  | 6622 | True | False | False | False | False |  |  |
| .git\objects\c7\ebccab55261440ebc8a60ca4fda8cb11e3b40f |  | 5529 | True | False | False | False | False |  |  |
| .git\objects\c8\1977ccc25d82fcc5b0b5065ed1210aca23afd6 |  | 4279 | True | False | False | False | False |  |  |
| .git\objects\c9\1e3bda34dbc28c4b787724664a5930637b0516 |  | 367 | True | False | False | False | False |  |  |
| .git\objects\c9\621a164f7c1d352bdfa7d09ec6011b97d16a15 |  | 932 | True | False | False | False | False |  |  |
| .git\objects\c9\a70d966992e0b6ac36655fb8a0b6689f057b99 |  | 76 | True | False | False | False | False |  |  |
| .git\objects\ca\269fb539f636d02aced3fbe8ea8ab7158a4527 |  | 31938 | True | False | False | False | False |  |  |
| .git\objects\ca\284ee21645059528d619f26281c0bf02573454 |  | 2821 | True | False | False | False | False |  |  |
| .git\objects\ca\76ddfcfe94d8956768896d2101bb0873692bac |  | 236 | True | False | False | False | False |  |  |
| .git\objects\ca\8d1f89702b906d2f3a1575ad415b3243cd1135 |  | 2725 | True | False | False | False | False |  |  |
| .git\objects\ca\cb6c37e5ae5a386928b613c542308c7985e3db |  | 725 | True | False | False | False | False |  |  |
| .git\objects\ca\f02727c5a6eb369df8b2bc9ab1bc06aa527d28 |  | 3148 | True | False | False | False | False |  |  |
| .git\objects\cb\7b2c21076f75d42aafd5fcfab43390f500174f |  | 1106 | True | False | False | False | False |  |  |
| .git\objects\cb\942c03b73fe37dd5497e8b76e48bb26daa45b2 |  | 23783 | True | False | False | False | False |  |  |
| .git\objects\cb\a5d163b07879eaa6a35b9a8b8a72b17695a21e |  | 19495 | True | False | False | False | False |  |  |
| .git\objects\cb\da1a1296c4271ea9c819facc7c252e5c05f2a0 |  | 820 | True | False | False | False | False |  |  |
| .git\objects\cc\35ab268106227cb0076e1e0f50908896ca1822 |  | 1571 | True | False | False | False | False |  |  |
| .git\objects\cc\450329997583d00f98377258e77ec68b944323 |  | 2441 | True | False | False | False | False |  |  |
| .git\objects\cd\62f006222839ae6dda9e588c0a09a2329fade8 |  | 146 | True | False | False | False | False |  |  |
| .git\objects\cd\827e162ef2739cbab1d17fc719bf11eddc7850 |  | 352 | True | False | False | False | False |  |  |
| .git\objects\ce\800c6599b2e35241e3389efbbef3de72b85bbb |  | 4741 | True | False | False | False | False |  |  |
| .git\objects\ce\a3fa23c85dfdf12b0dca87711893cf6634cd25 |  | 186 | True | False | False | False | False |  |  |
| .git\objects\ce\cbccd75f8a31b4e9a45681c8eaa90314435635 |  | 6608 | True | False | False | False | False |  |  |
| .git\objects\cf\3bda69f29ca64fa3ac66c9dde1fd132d69aebc |  | 968 | True | False | False | False | False |  |  |
| .git\objects\cf\6a6c243704e39b776a209590fbb570dd232e18 |  | 124 | True | False | False | False | False |  |  |
| .git\objects\cf\79bf90ee4d728f5866e3f9f90f059d35b050a7 |  | 22 | True | False | False | False | False |  |  |
| .git\objects\cf\9c7c2c815988575d9bc9af5fbc5f37c89d2e86 |  | 341 | True | False | False | False | False |  |  |
| .git\objects\cf\c60020a4da876e8c1c1c52561ce2272822b4b5 |  | 532 | True | False | False | False | False |  |  |
| .git\objects\d0\1869cec8ce81d3efb403c16f3ed11132381a81 |  | 1355 | True | False | False | False | False |  |  |
| .git\objects\d0\6143c577dc1417625dd3c7f27ee1f3281d31f4 |  | 9384 | True | False | False | False | False |  |  |
| .git\objects\d1\5bed1c8340853210fd4661108f2aebaf660397 |  | 701 | True | False | False | False | False |  |  |
| .git\objects\d2\856e057ba4c344ea98ef8344f0859a6d3b5239 |  | 1456 | True | False | False | False | False |  |  |
| .git\objects\d2\a92398a686ea0f9c9bb0f9e531982edfadbabf |  | 2326635 | True | False | False | False | False |  |  |
| .git\objects\d3\7449ddfda2e33c661089df71253ffb55191206 |  | 7194 | True | False | False | False | False |  |  |
| .git\objects\d3\b1fe8f70137e6648bb6d1f09f95fbbac146aa0 |  | 1881 | True | False | False | False | False |  |  |
| .git\objects\d3\db55bade24d8f749d88e5b114d6e8d72d16c8b |  | 273 | True | False | False | False | False |  |  |
| .git\objects\d4\fe530e9e80392f2790fe60001d9c02530a6508 |  | 2832 | True | False | False | False | False |  |  |
| .git\objects\d6\0dd396c6ef505c509b1af5190548c3a7bcc5c0 |  | 27424 | True | False | False | False | False |  |  |
| .git\objects\d6\5361453624fe4c5a92faf3331aed596813a99c |  | 41504 | True | False | False | False | False |  |  |
| .git\objects\d6\5576e2d47399e282b81aa777c8ca2ff8e1980a |  | 803 | True | False | False | False | False |  |  |
| .git\objects\d6\b7ef32c8478a48c3994dcadc86837f4371184d |  | 30 | True | False | False | False | False |  |  |
| .git\objects\d6\d39bf44ae90b4a050c0e732d6732a9a4e8c38d |  | 1005 | True | False | False | False | False |  |  |
| .git\objects\d7\0a0119b53555aed4e06cb9dd140aca3d9e1c9d |  | 621 | True | False | False | False | False |  |  |
| .git\objects\d8\0d474efd7fba2b576acd6f85234b6ae6c9900a |  | 600 | True | False | False | False | False |  |  |
| .git\objects\db\9a1ef5b02c9149786c102eabd53ba2b1493fe2 |  | 1008 | True | False | False | False | False |  |  |
| .git\objects\dc\5092a0cf2f5eb9c42b650518ad66893fdd7539 |  | 4331 | True | False | False | False | False |  |  |
| .git\objects\dc\e502aa02f447b267bb87a8d7b3567eae1e9d45 |  | 1304 | True | False | False | False | False |  |  |
| .git\objects\dd\3e6e6d6e1a15b930936835128a58da3cbd1754 |  | 16398 | True | False | False | False | False |  |  |
| .git\objects\de\6d1b457cb8bec325012c10531f92b0ab7754ad |  | 3565 | True | False | False | False | False |  |  |
| .git\objects\de\d216d7e171fcbc2a140f31c95a0827379faf9f |  | 525 | True | False | False | False | False |  |  |
| .git\objects\de\e237546033e51c44128ecb60b3f70698fb4c31 |  | 350 | True | False | False | False | False |  |  |
| .git\objects\df\2e52b0e943f87ad71e914778ed8a6e0f494704 |  | 1308 | True | False | False | False | False |  |  |
| .git\objects\df\6361fe54e846de57cdfb9f3515dbe85695729e |  | 2500 | True | False | False | False | False |  |  |
| .git\objects\df\dbd53767aa6f35714e6963a26d7b5257d5a349 |  | 360 | True | False | False | False | False |  |  |
| .git\objects\df\f54d53ced7437fc358729cd854be725e25bb31 |  | 18118 | True | False | False | False | False |  |  |
| .git\objects\e1\3473faac93d49633bb83d44df46b7633578b45 |  | 97263 | True | False | False | False | False |  |  |
| .git\objects\e1\351085b334f8cd032da0b9c57ea0a8fc461327 |  | 2385 | True | False | False | False | False |  |  |
| .git\objects\e1\5d2ef087c885fcc96f2844db56f5a784c59e4d |  | 23138 | True | False | False | False | False |  |  |
| .git\objects\e1\8fd545fa42bd2c4a5f3a7e3de182f8c45de305 |  | 52 | True | False | False | False | False |  |  |
| .git\objects\e1\a762297396b8d17e192137ba3c6ab37f49c2d0 |  | 4306 | True | False | False | False | False |  |  |
| .git\objects\e1\c74846bdfc3253b788c1300d133d2d5a6d9ad1 |  | 30181 | True | False | False | False | False |  |  |
| .git\objects\e2\1b1d7bd612025542a62b3105a9da4ca07c6b30 |  | 14968 | True | False | False | False | False |  |  |
| .git\objects\e2\d87755403c7d9869b6de4f0902ef6aa7ac7740 |  | 15514 | True | False | False | False | False |  |  |
| .git\objects\e4\048b72bf8b2e20d002e0d41b3445bb76f0edf8 |  | 3322 | True | False | False | False | False |  |  |
| .git\objects\e4\43cfe525ea48184f25879025f0295a9026899b |  | 27527 | True | False | False | False | False |  |  |
| .git\objects\e5\772913779567d3d1b4b9419e12dba7e1a9270e |  | 3595 | True | False | False | False | False |  |  |
| .git\objects\e5\e96b47c75aa322765aecf228e6a34706e88d3e |  | 9225 | True | False | False | False | False |  |  |
| .git\objects\e5\fe036c45723dbcd476aeb87e72ed079281bdf4 |  | 22549 | True | False | False | False | False |  |  |
| .git\objects\e6\170f7171f6320d9a31b134475f43bde8556a3b |  | 16182 | True | False | False | False | False |  |  |
| .git\objects\e6\5d459b22783355c40e87f7876c726f7381c806 |  | 426 | True | False | False | False | False |  |  |
| .git\objects\e6\9cb6b14054ddbb406f93b61b5678242ab29fc8 |  | 15589 | True | False | False | False | False |  |  |
| .git\objects\e6\9de29bb2d1d6434b8b29ae775ad8c2e48c5391 |  | 15 | True | False | False | False | False |  |  |
| .git\objects\e6\b58b0dbe522cd2678a503272190f0c88b921ae |  | 755 | True | False | False | False | False |  |  |
| .git\objects\e6\cb44d79627e2a3d426cb50d5d5e1c4b3a3cc91 |  | 2801 | True | False | False | False | False |  |  |
| .git\objects\e7\70a1a265f8e6f1c775e68e07ba3f1c1f9b3271 |  | 766 | True | False | False | False | False |  |  |
| .git\objects\e7\e59d2b67a4a149ad5e2e3606664871d5223dcd |  | 768 | True | False | False | False | False |  |  |
| .git\objects\e8\e4d279c193d1a6564af86ab39e5d4ffd377a1d |  | 389 | True | False | False | False | False |  |  |
| .git\objects\e9\763f50c261214ec9fdd6e6e8dfa53a264a2593 |  | 15218 | True | False | False | False | False |  |  |
| .git\objects\e9\ddc2cf9589c507ce6857068cb7440eaf52c950 |  | 533 | True | False | False | False | False |  |  |
| .git\objects\e9\e1c1202aae9ace9f861a8bbd0f3a7702ec4e4f |  | 14895 | True | False | False | False | False |  |  |
| .git\objects\ea\34516420a2f9cd4e9c19f3d73fd99076607225 |  | 4562 | True | False | False | False | False |  |  |
| .git\objects\ea\97ab9e6f630b9abb0423b402fbf8ec1627325d |  | 123 | True | False | False | False | False |  |  |
| .git\objects\eb\dfd093af853e3d3e317c578ed049cb95407c51 |  | 6766 | True | False | False | False | False |  |  |
| .git\objects\ec\0c26c92f623899ccc42f415c81855d56cb42de |  | 473 | True | False | False | False | False |  |  |
| .git\objects\ec\5f8cbca4749151cc112367ea0c5cda668b7ff4 |  | 3361 | True | False | False | False | False |  |  |
| .git\objects\ef\46da9c817b80bd68fb8f8fd9fa06b9495202c0 |  | 11201 | True | False | False | False | False |  |  |
| .git\objects\ef\b96446b686998b3882b3d7608cedb261f6ea10 |  | 2138 | True | False | False | False | False |  |  |
| .git\objects\ef\f9a7a3f3cf5bd66f4e880239fcc8239f1c1140 |  | 286 | True | False | False | False | False |  |  |
| .git\objects\f0\1fff9b45549bcd07db498b7f413792a4bc51b3 |  | 17267 | True | False | False | False | False |  |  |
| .git\objects\f0\8bfd482790476e8394acc031914fe378d49d8a |  | 8834 | True | False | False | False | False |  |  |
| .git\objects\f1\310a14e6164cb0114c2ed27305132c5c937a34 |  | 16140 | True | False | False | False | False |  |  |
| .git\objects\f1\6532090d38d19808ca174574d97be098250846 |  | 3574 | True | False | False | False | False |  |  |
| .git\objects\f1\66cc57b2783565bc48e8999103c572fca4c0e4 |  | 10848 | True | False | False | False | False |  |  |
| .git\objects\f2\7399a042d95c4708af3a8c74d35d338763cf8f |  | 651 | True | False | False | False | False |  |  |
| .git\objects\f3\28a89c7218105340c688694601e5327d97ad12 |  | 3245 | True | False | False | False | False |  |  |
| .git\objects\f3\5e54646447fa4a2d9b39a6a1218a8a0dd0d795 |  | 20337 | True | False | False | False | False |  |  |
| .git\objects\f3\6ef670372a3497e94432181ecfe089194d8627 |  | 191 | True | False | False | False | False |  |  |
| .git\objects\f3\ef975ae8b61c9beb87b91c6f5c56a95f82d9f4 |  | 225 | True | False | False | False | False |  |  |
| .git\objects\f4\e9bce56025c9bd23942ca91816e5dd1888f770 |  | 132 | True | False | False | False | False |  |  |
| .git\objects\f5\bbc7dc0e729aa045aa976d19c84f14140b148a |  | 14388 | True | False | False | False | False |  |  |
| .git\objects\f6\6d05bc41074a088d7adf23c4e14c01579030fb |  | 8845 | True | False | False | False | False |  |  |
| .git\objects\f6\eb7a0e4d9dce2a20da3b4c888626bd851f0b7c |  | 12472 | True | False | False | False | False |  |  |
| .git\objects\f7\95580a143e0d3dc66db51bf24cd9f783c0766b |  | 873 | True | False | False | False | False |  |  |
| .git\objects\f8\da1f296834424eecb177709455af04dbcf22a0 |  | 1550 | True | False | False | False | False |  |  |
| .git\objects\f9\0e4b3f6f6ba5047ee35d86165f8b7145c1d97a |  | 2783 | True | False | False | False | False |  |  |
| .git\objects\f9\1e12f3d856e834aee3ecabeea77201f934ef82 |  | 6832 | True | False | False | False | False |  |  |
| .git\objects\f9\7f56e18752d168687e4b0259eadfc2655dcffd |  | 46 | True | False | False | False | False |  |  |
| .git\objects\f9\8b53d601b5fe0cfbe13339f1901607f0cfe38b |  | 918 | True | False | False | False | False |  |  |
| .git\objects\f9\a50e08173fdc39bf51b456aaf10c3618f3e93f |  | 29782 | True | False | False | False | False |  |  |
| .git\objects\fa\1839076d911d3ff98302cef0709ac9ee9c47cd |  | 1832 | True | False | False | False | False |  |  |
| .git\objects\fa\69507550e10b99d89c70de65829039efb07d73 |  | 973 | True | False | False | False | False |  |  |
| .git\objects\fa\8c2c175865355a6c1e5786d240e517778a4da1 |  | 692 | True | False | False | False | False |  |  |
| .git\objects\fb\2084847057e3cfbefdac9d7b6eeb980ad0e0c2 |  | 28827 | True | False | False | False | False |  |  |
| .git\objects\fb\3c7830958ccc170a039489d83719c9a84e6db0 |  | 7005 | True | False | False | False | False |  |  |
| .git\objects\fb\b044b39eec433dc174d0c91dbd93364f8e9091 |  | 214425 | True | False | False | False | False |  |  |
| .git\objects\fc\1cb8f8eb9dcf334f44d54ffcc0f5be46f09001 |  | 2499 | True | False | False | False | False |  |  |
| .git\objects\fc\4dc50809264cd427bb29dffe546ec5f6226d31 |  | 683 | True | False | False | False | False |  |  |
| .git\objects\fc\8407327dd7a151b37b825035d010c144986b3f |  | 413 | True | False | False | False | False |  |  |
| .git\objects\fc\93c18b2627d836fa0b0a9aaf33afcaaa17b641 |  | 1834 | True | False | False | False | False |  |  |
| .git\objects\fc\b62187bcf9ce86d00913aae4dc8c900b6419d4 |  | 327 | True | False | False | False | False |  |  |
| .git\objects\fd\1f01eb784f42fa9740563a665763bb34b20fdc |  | 3742 | True | False | False | False | False |  |  |
| .git\objects\fd\2e091fe53a0cdbbab9241ed765156dc49a8e2a |  | 277 | True | False | False | False | False |  |  |
| .git\objects\fd\6c359c790856e18e92d1763df74aab4cc1c622 |  | 20507 | True | False | False | False | False |  |  |
| .git\objects\fd\93ed5d4a61d4a6bc041346ff3efcf0f6e4cec2 |  | 12459 | True | False | False | False | False |  |  |
| .git\objects\fd\f7ccb9318d0fd024335e94bad0cf18d197314d |  | 2368 | True | False | False | False | False |  |  |
| .git\ORIG_HEAD |  | 41 | True | False | False | False | False |  |  |
| .git\REBASE_HEAD |  | 41 | True | False | False | False | False |  |  |
| .git\refs\heads\main |  | 41 | True | False | False | False | False |  |  |
| .git\refs\remotes\origin\HEAD |  | 30 | True | False | False | False | False |  |  |
| .git\refs\remotes\origin\main |  | 41 | True | False | False | False | False |  |  |
| .gitignore | .gitignore | 79 | True | False | False | False | False |  |  |
| .venv\.gitignore | .gitignore | 1 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\__init__.py | .py | 353 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\__main__.py | .py | 854 | True | False | False | True | False | INSERT |  |
| .venv\Lib\site-packages\pip\__pip-runner__.py | .py | 1450 | True | False | False | True | False | INSERT |  |
| .venv\Lib\site-packages\pip\__pycache__\__init__.cpython-312.pyc | .pyc | 637 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\__pycache__\__main__.cpython-312.pyc | .pyc | 829 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\__pycache__\__pip-runner__.cpython-312.pyc | .pyc | 2193 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\__init__.py | .py | 511 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\__pycache__\__init__.cpython-312.pyc | .pyc | 739 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\__pycache__\build_env.cpython-312.pyc | .pyc | 18161 | True | False | False | True | False | DROP |  |
| .venv\Lib\site-packages\pip\_internal\__pycache__\cache.cpython-312.pyc | .pyc | 12356 | True | False | False | True | False | INSERT |  |
| .venv\Lib\site-packages\pip\_internal\__pycache__\configuration.cpython-312.pyc | .pyc | 18226 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\__pycache__\exceptions.cpython-312.pyc | .pyc | 40559 | True | False | False | True | False | UPDATE |  |
| .venv\Lib\site-packages\pip\_internal\__pycache__\main.cpython-312.pyc | .pyc | 622 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\__pycache__\pyproject.cpython-312.pyc | .pyc | 4074 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\__pycache__\self_outdated_check.cpython-312.pyc | .pyc | 10241 | True | False | False | True | False | REPLACE, UPDATE |  |
| .venv\Lib\site-packages\pip\_internal\__pycache__\wheel_builder.cpython-312.pyc | .pyc | 10675 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\build_env.py | .py | 14201 | True | False | True | True | True | REPLACE, DROP, UPDATE, SELECT |  |
| .venv\Lib\site-packages\pip\_internal\cache.py | .py | 10345 | True | False | False | True | False | INSERT |  |
| .venv\Lib\site-packages\pip\_internal\cli\__init__.py | .py | 131 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\cli\__pycache__\__init__.cpython-312.pyc | .pyc | 263 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\cli\__pycache__\autocompletion.cpython-312.pyc | .pyc | 9085 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\cli\__pycache__\base_command.cpython-312.pyc | .pyc | 10569 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\cli\__pycache__\cmdoptions.cpython-312.pyc | .pyc | 30311 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\cli\__pycache__\command_context.cpython-312.pyc | .pyc | 1801 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\cli\__pycache__\index_command.cpython-312.pyc | .pyc | 7185 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\cli\__pycache__\main.cpython-312.pyc | .pyc | 2247 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\cli\__pycache__\main_parser.cpython-312.pyc | .pyc | 4822 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\cli\__pycache__\parser.cpython-312.pyc | .pyc | 14794 | True | False | False | True | False | INSERT |  |
| .venv\Lib\site-packages\pip\_internal\cli\__pycache__\progress_bars.cpython-312.pyc | .pyc | 6055 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\cli\__pycache__\req_command.cpython-312.pyc | .pyc | 13805 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_internal\cli\__pycache__\spinners.cpython-312.pyc | .pyc | 11236 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\cli\__pycache__\status_codes.cpython-312.pyc | .pyc | 363 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\cli\autocompletion.py | .py | 7193 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\cli\base_command.py | .py | 8716 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\cli\cmdoptions.py | .py | 31025 | True | False | False | False | True |  |  |
| .venv\Lib\site-packages\pip\_internal\cli\command_context.py | .py | 817 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\cli\index_command.py | .py | 5717 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\cli\main.py | .py | 2815 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\cli\main_parser.py | .py | 4329 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_internal\cli\parser.py | .py | 10916 | True | False | False | True | True | INSERT, REPLACE |  |
| .venv\Lib\site-packages\pip\_internal\cli\progress_bars.py | .py | 4668 | True | False | False | True | False | UPDATE |  |
| .venv\Lib\site-packages\pip\_internal\cli\req_command.py | .py | 13799 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_internal\cli\spinners.py | .py | 7362 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\cli\status_codes.py | .py | 116 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\commands\__init__.py | .py | 4026 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_internal\commands\__pycache__\__init__.cpython-312.pyc | .pyc | 4105 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_internal\commands\__pycache__\cache.cpython-312.pyc | .pyc | 10150 | True | False | True | False | False | SELECT |  |
| .venv\Lib\site-packages\pip\_internal\commands\__pycache__\check.cpython-312.pyc | .pyc | 2544 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\commands\__pycache__\completion.cpython-312.pyc | .pyc | 5414 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\commands\__pycache__\configuration.cpython-312.pyc | .pyc | 13315 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\commands\__pycache__\debug.cpython-312.pyc | .pyc | 9949 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\commands\__pycache__\download.cpython-312.pyc | .pyc | 7244 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\commands\__pycache__\freeze.cpython-312.pyc | .pyc | 4273 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\commands\__pycache__\hash.cpython-312.pyc | .pyc | 2921 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\commands\__pycache__\help.cpython-312.pyc | .pyc | 1626 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\commands\__pycache__\index.cpython-312.pyc | .pyc | 7243 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_internal\commands\__pycache__\inspect.cpython-312.pyc | .pyc | 3937 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\commands\__pycache__\install.cpython-312.pyc | .pyc | 29733 | True | False | False | True | False | REPLACE |  |
| .venv\Lib\site-packages\pip\_internal\commands\__pycache__\list.cpython-312.pyc | .pyc | 17004 | True | False | True | True | False | SELECT, CREATE |  |
| .venv\Lib\site-packages\pip\_internal\commands\__pycache__\lock.cpython-312.pyc | .pyc | 7880 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\commands\__pycache__\search.cpython-312.pyc | .pyc | 7594 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\commands\__pycache__\show.cpython-312.pyc | .pyc | 11176 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\commands\__pycache__\uninstall.cpython-312.pyc | .pyc | 4662 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\commands\__pycache__\wheel.cpython-312.pyc | .pyc | 8335 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\commands\cache.py | .py | 8230 | True | False | True | False | True | SELECT |  |
| .venv\Lib\site-packages\pip\_internal\commands\check.py | .py | 2244 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\commands\completion.py | .py | 4530 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\commands\configuration.py | .py | 10105 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\commands\debug.py | .py | 6805 | True | False | False | True | False | REPLACE |  |
| .venv\Lib\site-packages\pip\_internal\commands\download.py | .py | 5075 | True | False | False | True | False | DELETE |  |
| .venv\Lib\site-packages\pip\_internal\commands\freeze.py | .py | 3099 | True | False | False | True | False | UPDATE |  |
| .venv\Lib\site-packages\pip\_internal\commands\hash.py | .py | 1679 | True | False | False | True | False | UPDATE |  |
| .venv\Lib\site-packages\pip\_internal\commands\help.py | .py | 1108 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\commands\index.py | .py | 5243 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_internal\commands\inspect.py | .py | 3177 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\commands\install.py | .py | 30472 | True | False | False | True | False | REPLACE, CREATE, DELETE |  |
| .venv\Lib\site-packages\pip\_internal\commands\list.py | .py | 13514 | True | False | True | True | False | SELECT, CREATE, UPDATE, INSERT |  |
| .venv\Lib\site-packages\pip\_internal\commands\lock.py | .py | 5797 | True | False | False | True | False | DELETE |  |
| .venv\Lib\site-packages\pip\_internal\commands\search.py | .py | 5782 | True | False | False | True | False | REPLACE |  |
| .venv\Lib\site-packages\pip\_internal\commands\show.py | .py | 8066 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\commands\uninstall.py | .py | 3868 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\commands\wheel.py | .py | 6013 | True | False | False | True | True | DELETE |  |
| .venv\Lib\site-packages\pip\_internal\configuration.py | .py | 14568 | True | False | False | True | False | REPLACE, UPDATE, CREATE |  |
| .venv\Lib\site-packages\pip\_internal\distributions\__init__.py | .py | 858 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_internal\distributions\__pycache__\__init__.cpython-312.pyc | .pyc | 929 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\distributions\__pycache__\base.cpython-312.pyc | .pyc | 2907 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_internal\distributions\__pycache__\installed.cpython-312.pyc | .pyc | 1757 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\distributions\__pycache__\sdist.cpython-312.pyc | .pyc | 8310 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\distributions\__pycache__\wheel.cpython-312.pyc | .pyc | 2306 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\distributions\base.py | .py | 1830 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_internal\distributions\installed.py | .py | 929 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\distributions\sdist.py | .py | 6627 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\distributions\wheel.py | .py | 1364 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\exceptions.py | .py | 29592 | True | False | False | True | False | UPDATE |  |
| .venv\Lib\site-packages\pip\_internal\index\__init__.py | .py | 29 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\index\__pycache__\__init__.cpython-312.pyc | .pyc | 217 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\index\__pycache__\collector.cpython-312.pyc | .pyc | 21206 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_internal\index\__pycache__\package_finder.cpython-312.pyc | .pyc | 42093 | True | False | False | True | False | UPDATE, CREATE |  |
| .venv\Lib\site-packages\pip\_internal\index\__pycache__\sources.cpython-312.pyc | .pyc | 12295 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\index\collector.py | .py | 16185 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_internal\index\package_finder.py | .py | 38835 | True | False | False | True | False | UPDATE, CREATE, DROP |  |
| .venv\Lib\site-packages\pip\_internal\index\sources.py | .py | 8639 | True | False | False | False | True |  |  |
| .venv\Lib\site-packages\pip\_internal\locations\__init__.py | .py | 14185 | True | False | False | True | False | REPLACE, CREATE | , ; )
        )
        if skip_cpython_build:
            continue

        warning_contexts.append((old_v, new_v, f |
| .venv\Lib\site-packages\pip\_internal\locations\__pycache__\__init__.cpython-312.pyc | .pyc | 15299 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_internal\locations\__pycache__\_distutils.cpython-312.pyc | .pyc | 6758 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\locations\__pycache__\_sysconfig.cpython-312.pyc | .pyc | 7908 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\locations\__pycache__\base.cpython-312.pyc | .pyc | 3696 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\locations\_distutils.py | .py | 5975 | True | False | False | True | False | CREATE, UPDATE | ,
             |
| .venv\Lib\site-packages\pip\_internal\locations\_sysconfig.py | .py | 7716 | True | False | False | True | False | UPDATE | ] = os.path.join(base, ; , ; ], dist_name),
        scripts=paths[ |
| .venv\Lib\site-packages\pip\_internal\locations\base.py | .py | 2550 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\main.py | .py | 338 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\metadata\__init__.py | .py | 5824 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\metadata\__pycache__\__init__.cpython-312.pyc | .pyc | 6749 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\metadata\__pycache__\_json.cpython-312.pyc | .pyc | 2861 | True | False | False | True | False | REPLACE |  |
| .venv\Lib\site-packages\pip\_internal\metadata\__pycache__\base.cpython-312.pyc | .pyc | 34423 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\metadata\__pycache__\pkg_resources.cpython-312.pyc | .pyc | 15772 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\metadata\_json.py | .py | 2711 | True | False | False | True | False | REPLACE |  |
| .venv\Lib\site-packages\pip\_internal\metadata\base.py | .py | 25420 | True | False | False | True | False | REPLACE |  |
| .venv\Lib\site-packages\pip\_internal\metadata\importlib\__init__.py | .py | 135 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\metadata\importlib\__pycache__\__init__.cpython-312.pyc | .pyc | 346 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\metadata\importlib\__pycache__\_compat.cpython-312.pyc | .pyc | 4225 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\metadata\importlib\__pycache__\_dists.cpython-312.pyc | .pyc | 12797 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\metadata\importlib\__pycache__\_envs.cpython-312.pyc | .pyc | 7995 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\metadata\importlib\_compat.py | .py | 2804 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\metadata\importlib\_dists.py | .py | 8420 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\metadata\importlib\_envs.py | .py | 5333 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\metadata\pkg_resources.py | .py | 10544 | True | False | False | True | False | UPDATE |  |
| .venv\Lib\site-packages\pip\_internal\models\__init__.py | .py | 62 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\models\__pycache__\__init__.cpython-312.pyc | .pyc | 251 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\models\__pycache__\candidate.cpython-312.pyc | .pyc | 1592 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\models\__pycache__\direct_url.cpython-312.pyc | .pyc | 10491 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\models\__pycache__\format_control.cpython-312.pyc | .pyc | 4112 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\models\__pycache__\index.cpython-312.pyc | .pyc | 1682 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\models\__pycache__\installation_report.cpython-312.pyc | .pyc | 2278 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\models\__pycache__\link.cpython-312.pyc | .pyc | 26644 | True | False | False | True | False | DROP |  |
| .venv\Lib\site-packages\pip\_internal\models\__pycache__\pylock.cpython-312.pyc | .pyc | 7849 | True | False | False | True | False | REPLACE |  |
| .venv\Lib\site-packages\pip\_internal\models\__pycache__\scheme.cpython-312.pyc | .pyc | 1011 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\models\__pycache__\search_scope.cpython-312.pyc | .pyc | 4940 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_internal\models\__pycache__\selection_prefs.cpython-312.pyc | .pyc | 1866 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_internal\models\__pycache__\target_python.cpython-312.pyc | .pyc | 4842 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\models\__pycache__\wheel.cpython-312.pyc | .pyc | 4657 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\models\candidate.py | .py | 753 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\models\direct_url.py | .py | 6555 | True | False | False | False | True |  |  |
| .venv\Lib\site-packages\pip\_internal\models\format_control.py | .py | 2471 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\models\index.py | .py | 1030 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\models\installation_report.py | .py | 2839 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\models\link.py | .py | 21793 | True | False | False | True | False | REPLACE, DROP |  |
| .venv\Lib\site-packages\pip\_internal\models\pylock.py | .py | 6211 | True | False | False | True | False | REPLACE |  |
| .venv\Lib\site-packages\pip\_internal\models\scheme.py | .py | 575 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\models\search_scope.py | .py | 4507 | True | False | False | True | False | CREATE | 
                         |
| .venv\Lib\site-packages\pip\_internal\models\selection_prefs.py | .py | 2016 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_internal\models\target_python.py | .py | 4243 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\models\wheel.py | .py | 2920 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\network\__init__.py | .py | 49 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\network\__pycache__\__init__.cpython-312.pyc | .pyc | 239 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\network\__pycache__\auth.cpython-312.pyc | .pyc | 21451 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\network\__pycache__\cache.cpython-312.pyc | .pyc | 7919 | True | False | False | True | False | REPLACE |  |
| .venv\Lib\site-packages\pip\_internal\network\__pycache__\download.cpython-312.pyc | .pyc | 16044 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\network\__pycache__\lazy_wheel.cpython-312.pyc | .pyc | 11550 | True | False | False | True | False | TRUNCATE |  |
| .venv\Lib\site-packages\pip\_internal\network\__pycache__\session.cpython-312.pyc | .pyc | 19186 | True | False | False | True | False | UPDATE |  |
| .venv\Lib\site-packages\pip\_internal\network\__pycache__\utils.cpython-312.pyc | .pyc | 2240 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\network\__pycache__\xmlrpc.cpython-312.pyc | .pyc | 2918 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\network\auth.py | .py | 20681 | True | False | False | False | True |  |  |
| .venv\Lib\site-packages\pip\_internal\network\cache.py | .py | 4862 | True | False | False | True | False | REPLACE, DELETE |  |
| .venv\Lib\site-packages\pip\_internal\network\download.py | .py | 12682 | True | False | False | True | True | DELETE, TRUNCATE |  |
| .venv\Lib\site-packages\pip\_internal\network\lazy_wheel.py | .py | 7646 | True | False | False | True | True | TRUNCATE |  |
| .venv\Lib\site-packages\pip\_internal\network\session.py | .py | 19188 | True | False | False | True | False | CREATE, UPDATE |  |
| .venv\Lib\site-packages\pip\_internal\network\utils.py | .py | 4091 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\network\xmlrpc.py | .py | 1830 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\operations\__init__.py | .py | 0 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\operations\__pycache__\__init__.cpython-312.pyc | .pyc | 185 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\operations\__pycache__\check.cpython-312.pyc | .pyc | 7145 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\operations\__pycache__\freeze.cpython-312.pyc | .pyc | 10183 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\operations\__pycache__\prepare.cpython-312.pyc | .pyc | 26551 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\operations\build\__init__.py | .py | 0 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\operations\build\__pycache__\__init__.cpython-312.pyc | .pyc | 191 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\operations\build\__pycache__\build_tracker.cpython-312.pyc | .pyc | 7566 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\operations\build\__pycache__\metadata.cpython-312.pyc | .pyc | 1840 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\operations\build\__pycache__\metadata_editable.cpython-312.pyc | .pyc | 1894 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\operations\build\__pycache__\wheel.cpython-312.pyc | .pyc | 1706 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\operations\build\__pycache__\wheel_editable.cpython-312.pyc | .pyc | 2045 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\operations\build\build_tracker.py | .py | 4771 | True | False | False | True | True | DELETE |  |
| .venv\Lib\site-packages\pip\_internal\operations\build\metadata.py | .py | 1421 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\operations\build\metadata_editable.py | .py | 1509 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\operations\build\wheel.py | .py | 1136 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\operations\build\wheel_editable.py | .py | 1478 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\operations\check.py | .py | 5894 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_internal\operations\freeze.py | .py | 9854 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\operations\install\__init__.py | .py | 50 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\operations\install\__pycache__\__init__.cpython-312.pyc | .pyc | 251 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\operations\install\__pycache__\wheel.cpython-312.pyc | .pyc | 34060 | True | False | False | True | False | REPLACE |  |
| .venv\Lib\site-packages\pip\_internal\operations\install\wheel.py | .py | 27956 | True | False | False | True | True | REPLACE, CREATE, DELETE |  |
| .venv\Lib\site-packages\pip\_internal\operations\prepare.py | .py | 28914 | True | False | False | True | True | DELETE |  |
| .venv\Lib\site-packages\pip\_internal\pyproject.py | .py | 4555 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\req\__init__.py | .py | 3041 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\req\__pycache__\__init__.cpython-312.pyc | .pyc | 3976 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\req\__pycache__\constructors.cpython-312.pyc | .pyc | 21651 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_internal\req\__pycache__\req_dependency_group.cpython-312.pyc | .pyc | 3986 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\req\__pycache__\req_file.cpython-312.pyc | .pyc | 23735 | True | False | False | True | False | REPLACE |  |
| .venv\Lib\site-packages\pip\_internal\req\__pycache__\req_install.cpython-312.pyc | .pyc | 34589 | True | False | False | True | False | CREATE, DELETE, UPDATE, REPLACE |  |
| .venv\Lib\site-packages\pip\_internal\req\__pycache__\req_set.cpython-312.pyc | .pyc | 5420 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_internal\req\__pycache__\req_uninstall.cpython-312.pyc | .pyc | 31590 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_internal\req\constructors.py | .py | 18581 | True | False | False | True | True | CREATE, DROP |  |
| .venv\Lib\site-packages\pip\_internal\req\req_dependency_group.py | .py | 2618 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\req\req_file.py | .py | 20130 | True | False | False | True | True | REPLACE, UPDATE |  |
| .venv\Lib\site-packages\pip\_internal\req\req_install.py | .py | 31273 | True | False | False | True | True | CREATE, DELETE, UPDATE, REPLACE |  |
| .venv\Lib\site-packages\pip\_internal\req\req_set.py | .py | 2828 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_internal\req\req_uninstall.py | .py | 24099 | True | False | False | True | True | CREATE, UPDATE, REPLACE |  |
| .venv\Lib\site-packages\pip\_internal\resolution\__init__.py | .py | 0 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\resolution\__pycache__\__init__.cpython-312.pyc | .pyc | 185 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\resolution\__pycache__\base.cpython-312.pyc | .pyc | 1156 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\resolution\base.py | .py | 577 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\resolution\legacy\__init__.py | .py | 0 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\resolution\legacy\__pycache__\__init__.cpython-312.pyc | .pyc | 192 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\resolution\legacy\__pycache__\resolver.cpython-312.pyc | .pyc | 22502 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\resolution\legacy\resolver.py | .py | 24060 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_internal\resolution\resolvelib\__init__.py | .py | 0 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\resolution\resolvelib\__pycache__\__init__.cpython-312.pyc | .pyc | 196 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\resolution\resolvelib\__pycache__\base.cpython-312.pyc | .pyc | 7971 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\resolution\resolvelib\__pycache__\candidates.cpython-312.pyc | .pyc | 29420 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\resolution\resolvelib\__pycache__\factory.cpython-312.pyc | .pyc | 33772 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\resolution\resolvelib\__pycache__\found_candidates.cpython-312.pyc | .pyc | 6719 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_internal\resolution\resolvelib\__pycache__\provider.cpython-312.pyc | .pyc | 11616 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\resolution\resolvelib\__pycache__\reporter.cpython-312.pyc | .pyc | 5788 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\resolution\resolvelib\__pycache__\requirements.cpython-312.pyc | .pyc | 14746 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\resolution\resolvelib\__pycache__\resolver.cpython-312.pyc | .pyc | 12351 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\resolution\resolvelib\base.py | .py | 5047 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\resolution\resolvelib\candidates.py | .py | 20454 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\resolution\resolvelib\factory.py | .py | 33628 | True | False | False | True | False | UPDATE |  |
| .venv\Lib\site-packages\pip\_internal\resolution\resolvelib\found_candidates.py | .py | 6018 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_internal\resolution\resolvelib\provider.py | .py | 11441 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\resolution\resolvelib\reporter.py | .py | 3909 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\resolution\resolvelib\requirements.py | .py | 8076 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\resolution\resolvelib\resolver.py | .py | 13437 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\self_outdated_check.py | .py | 8471 | True | False | False | True | False | REPLACE, UPDATE, CREATE |  |
| .venv\Lib\site-packages\pip\_internal\utils\__init__.py | .py | 0 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\utils\__pycache__\__init__.cpython-312.pyc | .pyc | 180 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\utils\__pycache__\_jaraco_text.cpython-312.pyc | .pyc | 4515 | True | False | False | True | False | DROP |  |
| .venv\Lib\site-packages\pip\_internal\utils\__pycache__\_log.cpython-312.pyc | .pyc | 1851 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\utils\__pycache__\appdirs.cpython-312.pyc | .pyc | 2440 | True | False | False | True | False | DROP |  |
| .venv\Lib\site-packages\pip\_internal\utils\__pycache__\compat.cpython-312.pyc | .pyc | 3007 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\utils\__pycache__\compatibility_tags.cpython-312.pyc | .pyc | 6632 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\utils\__pycache__\datetime.cpython-312.pyc | .pyc | 664 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\utils\__pycache__\deprecation.cpython-312.pyc | .pyc | 4194 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\utils\__pycache__\direct_url_helpers.cpython-312.pyc | .pyc | 3524 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\utils\__pycache__\egg_link.cpython-312.pyc | .pyc | 3129 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\utils\__pycache__\entrypoints.cpython-312.pyc | .pyc | 4040 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\utils\__pycache__\filesystem.cpython-312.pyc | .pyc | 7951 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\utils\__pycache__\filetypes.cpython-312.pyc | .pyc | 1114 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\utils\__pycache__\glibc.cpython-312.pyc | .pyc | 2335 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\utils\__pycache__\hashes.cpython-312.pyc | .pyc | 7407 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\utils\__pycache__\logging.cpython-312.pyc | .pyc | 13849 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\utils\__pycache__\misc.cpython-312.pyc | .pyc | 32677 | True | False | False | True | False | REPLACE, UPDATE |  |
| .venv\Lib\site-packages\pip\_internal\utils\__pycache__\packaging.cpython-312.pyc | .pyc | 1870 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\utils\__pycache__\retry.cpython-312.pyc | .pyc | 1957 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\utils\__pycache__\subprocess.cpython-312.pyc | .pyc | 8492 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_internal\utils\__pycache__\temp_dir.cpython-312.pyc | .pyc | 11894 | True | False | False | True | False | DELETE, CREATE |  |
| .venv\Lib\site-packages\pip\_internal\utils\__pycache__\unpacking.cpython-312.pyc | .pyc | 14296 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\utils\__pycache__\urls.cpython-312.pyc | .pyc | 2066 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\utils\__pycache__\virtualenv.cpython-312.pyc | .pyc | 4365 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\utils\__pycache__\wheel.cpython-312.pyc | .pyc | 5851 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\utils\_jaraco_text.py | .py | 3350 | True | False | False | True | False | DROP |  |
| .venv\Lib\site-packages\pip\_internal\utils\_log.py | .py | 1015 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\utils\appdirs.py | .py | 1681 | True | False | False | True | False | DROP |  |
| .venv\Lib\site-packages\pip\_internal\utils\compat.py | .py | 2514 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\utils\compatibility_tags.py | .py | 6630 | True | False | False | True | False | UPDATE |  |
| .venv\Lib\site-packages\pip\_internal\utils\datetime.py | .py | 241 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\utils\deprecation.py | .py | 3696 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\utils\direct_url_helpers.py | .py | 3200 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\utils\egg_link.py | .py | 2459 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\utils\entrypoints.py | .py | 3324 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\utils\filesystem.py | .py | 5497 | True | False | True | True | True | DELETE, REPLACE, SELECT |  |
| .venv\Lib\site-packages\pip\_internal\utils\filetypes.py | .py | 689 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\utils\glibc.py | .py | 3726 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\utils\hashes.py | .py | 4998 | True | False | False | True | False | UPDATE |  |
| .venv\Lib\site-packages\pip\_internal\utils\logging.py | .py | 12108 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\utils\misc.py | .py | 23374 | True | False | False | True | False | REPLACE, UPDATE |  |
| .venv\Lib\site-packages\pip\_internal\utils\packaging.py | .py | 1601 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\utils\retry.py | .py | 1461 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\utils\subprocess.py | .py | 8983 | True | False | False | True | True | CREATE, UPDATE |  |
| .venv\Lib\site-packages\pip\_internal\utils\temp_dir.py | .py | 9307 | True | False | False | True | True | DELETE, CREATE |  |
| .venv\Lib\site-packages\pip\_internal\utils\unpacking.py | .py | 12974 | True | False | False | True | False | REPLACE, UPDATE |  |
| .venv\Lib\site-packages\pip\_internal\utils\urls.py | .py | 1601 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\utils\virtualenv.py | .py | 3455 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\utils\wheel.py | .py | 4468 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\vcs\__init__.py | .py | 596 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\vcs\__pycache__\__init__.cpython-312.pyc | .pyc | 519 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_internal\vcs\__pycache__\bazaar.cpython-312.pyc | .pyc | 5138 | True | False | False | True | False | UPDATE |  |
| .venv\Lib\site-packages\pip\_internal\vcs\__pycache__\git.cpython-312.pyc | .pyc | 19896 | True | False | False | True | False | REPLACE |  |
| .venv\Lib\site-packages\pip\_internal\vcs\__pycache__\mercurial.cpython-312.pyc | .pyc | 7745 | True | False | False | True | False | UPDATE |  |
| .venv\Lib\site-packages\pip\_internal\vcs\__pycache__\subversion.cpython-312.pyc | .pyc | 12325 | True | False | False | True | False | UPDATE |  |
| .venv\Lib\site-packages\pip\_internal\vcs\__pycache__\versioncontrol.cpython-312.pyc | .pyc | 28715 | True | False | False | True | False | REPLACE, UPDATE |  |
| .venv\Lib\site-packages\pip\_internal\vcs\bazaar.py | .py | 3734 | True | False | False | True | False | UPDATE, CREATE |  |
| .venv\Lib\site-packages\pip\_internal\vcs\git.py | .py | 19144 | True | False | False | True | False | REPLACE, UPDATE |  |
| .venv\Lib\site-packages\pip\_internal\vcs\mercurial.py | .py | 5575 | True | False | False | True | False | UPDATE |  |
| .venv\Lib\site-packages\pip\_internal\vcs\subversion.py | .py | 11787 | True | False | False | True | False | UPDATE |  |
| .venv\Lib\site-packages\pip\_internal\vcs\versioncontrol.py | .py | 22502 | True | False | False | True | False | REPLACE, UPDATE |  |
| .venv\Lib\site-packages\pip\_internal\wheel_builder.py | .py | 9010 | True | False | False | True | False | REPLACE, UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\__init__.py | .py | 4907 | True | False | False | True | True | CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\__pycache__\__init__.cpython-312.pyc | .pyc | 4582 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\cachecontrol\__init__.py | .py | 677 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\cachecontrol\__pycache__\__init__.cpython-312.pyc | .pyc | 891 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\cachecontrol\__pycache__\_cmd.cpython-312.pyc | .pyc | 2635 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\cachecontrol\__pycache__\adapter.cpython-312.pyc | .pyc | 6700 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\cachecontrol\__pycache__\cache.cpython-312.pyc | .pyc | 3776 | True | False | False | True | False | DELETE |  |
| .venv\Lib\site-packages\pip\_vendor\cachecontrol\__pycache__\controller.cpython-312.pyc | .pyc | 16442 | True | False | False | True | False | CREATE, UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\cachecontrol\__pycache__\filewrapper.cpython-312.pyc | .pyc | 4336 | True | False | False | True | False | DELETE |  |
| .venv\Lib\site-packages\pip\_vendor\cachecontrol\__pycache__\heuristics.cpython-312.pyc | .pyc | 6686 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\cachecontrol\__pycache__\serialize.cpython-312.pyc | .pyc | 5250 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\cachecontrol\__pycache__\wrapper.cpython-312.pyc | .pyc | 1663 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\cachecontrol\_cmd.py | .py | 1737 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\cachecontrol\adapter.py | .py | 6599 | True | False | False | True | False | DELETE, UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\cachecontrol\cache.py | .py | 1953 | True | False | False | True | False | DELETE, UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\cachecontrol\caches\__init__.py | .py | 303 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\cachecontrol\caches\__pycache__\__init__.cpython-312.pyc | .pyc | 424 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\cachecontrol\caches\__pycache__\file_cache.cpython-312.pyc | .pyc | 7023 | True | False | False | True | False | REPLACE, DELETE |  |
| .venv\Lib\site-packages\pip\_vendor\cachecontrol\caches\__pycache__\redis_cache.cpython-312.pyc | .pyc | 2722 | True | False | False | True | False | DELETE |  |
| .venv\Lib\site-packages\pip\_vendor\cachecontrol\caches\file_cache.py | .py | 4117 | True | False | False | True | False | REPLACE, DELETE |  |
| .venv\Lib\site-packages\pip\_vendor\cachecontrol\caches\redis_cache.py | .py | 1386 | True | False | False | True | False | REPLACE, DELETE |  |
| .venv\Lib\site-packages\pip\_vendor\cachecontrol\controller.py | .py | 19101 | True | False | False | True | False | CREATE, DELETE, UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\cachecontrol\filewrapper.py | .py | 4291 | True | False | False | True | False | DELETE |  |
| .venv\Lib\site-packages\pip\_vendor\cachecontrol\heuristics.py | .py | 4881 | True | False | False | True | False | UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\cachecontrol\LICENSE.txt | .txt | 558 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\cachecontrol\py.typed | .typed | 0 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\cachecontrol\serialize.py | .py | 5163 | True | False | False | True | False | UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\cachecontrol\wrapper.py | .py | 1417 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\certifi\__init__.py | .py | 94 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\certifi\__main__.py | .py | 255 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\certifi\__pycache__\__init__.cpython-312.pyc | .pyc | 307 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\certifi\__pycache__\__main__.cpython-312.pyc | .pyc | 634 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\certifi\__pycache__\core.cpython-312.pyc | .pyc | 2071 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\certifi\cacert.pem | .pem | 291366 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\certifi\core.py | .py | 3442 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\certifi\LICENSE |  | 989 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\certifi\py.typed | .typed | 0 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\dependency_groups\__init__.py | .py | 250 | True | False | False | False | False |  | ,
     |
| .venv\Lib\site-packages\pip\_vendor\dependency_groups\__main__.py | .py | 1709 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\dependency_groups\__pycache__\__init__.cpython-312.pyc | .pyc | 366 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\dependency_groups\__pycache__\__main__.cpython-312.pyc | .pyc | 2672 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\dependency_groups\__pycache__\_implementation.cpython-312.pyc | .pyc | 9614 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\dependency_groups\__pycache__\_lint_dependency_groups.cpython-312.pyc | .pyc | 2836 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\dependency_groups\__pycache__\_pip_wrapper.cpython-312.pyc | .pyc | 3406 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\dependency_groups\__pycache__\_toml_compat.cpython-312.pyc | .pyc | 467 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\dependency_groups\_implementation.py | .py | 8041 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\dependency_groups\_lint_dependency_groups.py | .py | 1710 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\dependency_groups\_pip_wrapper.py | .py | 1865 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\dependency_groups\_toml_compat.py | .py | 285 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\dependency_groups\LICENSE.txt | .txt | 1099 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\dependency_groups\py.typed | .typed | 0 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\distlib\__init__.py | .py | 625 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\distlib\__pycache__\__init__.cpython-312.pyc | .pyc | 1258 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\distlib\__pycache__\compat.cpython-312.pyc | .pyc | 45515 | True | False | False | True | True | UPDATE, CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\distlib\__pycache__\resources.cpython-312.pyc | .pyc | 17301 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\distlib\__pycache__\scripts.cpython-312.pyc | .pyc | 19746 | True | False | False | True | False | CREATE, REPLACE |  |
| .venv\Lib\site-packages\pip\_vendor\distlib\__pycache__\util.cpython-312.pyc | .pyc | 88024 | True | False | False | True | False | CREATE, UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\distlib\compat.py | .py | 41467 | True | False | False | True | True | REPLACE, UPDATE, INSERT, CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\distlib\LICENSE.txt | .txt | 14531 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\distlib\resources.py | .py | 10820 | True | False | False | True | False | INSERT |  |
| .venv\Lib\site-packages\pip\_vendor\distlib\scripts.py | .py | 18612 | True | False | False | True | True | CREATE, REPLACE |  |
| .venv\Lib\site-packages\pip\_vendor\distlib\t32.exe | .exe | 97792 | False | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\distlib\t64.exe | .exe | 108032 | False | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\distlib\t64-arm.exe | .exe | 182784 | False | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\distlib\util.py | .py | 66682 | True | False | False | True | True | REPLACE, CREATE, UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\distlib\w32.exe | .exe | 91648 | False | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\distlib\w64.exe | .exe | 101888 | False | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\distlib\w64-arm.exe | .exe | 168448 | False | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\distro\__init__.py | .py | 981 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\distro\__main__.py | .py | 64 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\distro\__pycache__\__init__.cpython-312.pyc | .pyc | 949 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\distro\__pycache__\__main__.cpython-312.pyc | .pyc | 281 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\distro\__pycache__\distro.cpython-312.pyc | .pyc | 53781 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\distro\distro.py | .py | 49430 | True | False | False | True | False | CREATE, REPLACE, INSERT, UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\distro\LICENSE |  | 11325 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\distro\py.typed | .typed | 0 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\idna\__init__.py | .py | 868 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\idna\__pycache__\__init__.cpython-312.pyc | .pyc | 875 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\idna\__pycache__\codec.cpython-312.pyc | .pyc | 4965 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\idna\__pycache__\compat.cpython-312.pyc | .pyc | 879 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\idna\__pycache__\core.cpython-312.pyc | .pyc | 16110 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\idna\__pycache__\idnadata.cpython-312.pyc | .pyc | 99465 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\idna\__pycache__\intranges.cpython-312.pyc | .pyc | 2622 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\idna\__pycache__\package_data.cpython-312.pyc | .pyc | 206 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\idna\__pycache__\uts46data.cpython-312.pyc | .pyc | 158835 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\idna\codec.py | .py | 3422 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\idna\compat.py | .py | 316 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\idna\core.py | .py | 13239 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\idna\idnadata.py | .py | 78306 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\idna\intranges.py | .py | 1898 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\idna\LICENSE.md | .md | 1541 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\idna\package_data.py | .py | 21 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\idna\py.typed | .typed | 0 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\idna\uts46data.py | .py | 239289 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\msgpack\__init__.py | .py | 1109 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\msgpack\__pycache__\__init__.cpython-312.pyc | .pyc | 1728 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\msgpack\__pycache__\exceptions.cpython-312.pyc | .pyc | 2014 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\msgpack\__pycache__\ext.cpython-312.pyc | .pyc | 8282 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\msgpack\__pycache__\fallback.cpython-312.pyc | .pyc | 41479 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\msgpack\COPYING |  | 614 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\msgpack\exceptions.py | .py | 1081 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\msgpack\ext.py | .py | 5726 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\msgpack\fallback.py | .py | 32390 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\packaging\__init__.py | .py | 494 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\packaging\__pycache__\__init__.cpython-312.pyc | .pyc | 547 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\packaging\__pycache__\_elffile.cpython-312.pyc | .pyc | 5005 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\packaging\__pycache__\_manylinux.cpython-312.pyc | .pyc | 9695 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\packaging\__pycache__\_musllinux.cpython-312.pyc | .pyc | 4543 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\packaging\__pycache__\_parser.cpython-312.pyc | .pyc | 13972 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\packaging\__pycache__\_structures.cpython-312.pyc | .pyc | 3230 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\packaging\__pycache__\_tokenizer.cpython-312.pyc | .pyc | 7941 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\packaging\__pycache__\markers.cpython-312.pyc | .pyc | 12755 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\packaging\__pycache__\metadata.cpython-312.pyc | .pyc | 27197 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\packaging\__pycache__\requirements.cpython-312.pyc | .pyc | 4399 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\packaging\__pycache__\specifiers.cpython-312.pyc | .pyc | 39028 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\packaging\__pycache__\tags.cpython-312.pyc | .pyc | 24662 | True | False | False | True | False | REPLACE, INSERT |  |
| .venv\Lib\site-packages\pip\_vendor\packaging\__pycache__\utils.cpython-312.pyc | .pyc | 6624 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\packaging\__pycache__\version.cpython-312.pyc | .pyc | 20475 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\packaging\_elffile.py | .py | 3286 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\packaging\_manylinux.py | .py | 9596 | True | False | False | True | False | UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\packaging\_musllinux.py | .py | 2694 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\packaging\_parser.py | .py | 10221 | True | False | False | True | False | REPLACE |  |
| .venv\Lib\site-packages\pip\_vendor\packaging\_structures.py | .py | 1431 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\packaging\_tokenizer.py | .py | 5310 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\packaging\LICENSE |  | 197 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\packaging\LICENSE.APACHE | .apache | 10174 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\packaging\LICENSE.BSD | .bsd | 1344 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\packaging\licenses\__init__.py | .py | 5727 | True | False | False | True | False | REPLACE, CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\packaging\licenses\__pycache__\__init__.cpython-312.pyc | .pyc | 4109 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\packaging\licenses\__pycache__\_spdx.cpython-312.pyc | .pyc | 47353 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\packaging\licenses\_spdx.py | .py | 48398 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\packaging\markers.py | .py | 12049 | True | False | False | True | False | CREATE, UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\packaging\metadata.py | .py | 34739 | True | False | False | True | True | CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\packaging\py.typed | .typed | 0 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\packaging\requirements.py | .py | 2947 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\packaging\specifiers.py | .py | 40079 | True | False | False | True | False | CREATE, INSERT |  |
| .venv\Lib\site-packages\pip\_vendor\packaging\tags.py | .py | 22745 | True | False | False | True | False | REPLACE, INSERT |  |
| .venv\Lib\site-packages\pip\_vendor\packaging\utils.py | .py | 5050 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\packaging\version.py | .py | 16688 | True | False | False | True | False | DROP |  |
| .venv\Lib\site-packages\pip\_vendor\pkg_resources\__init__.py | .py | 124451 | True | False | False | True | True | UPDATE, REPLACE, CREATE, INSERT, DELETE | ,
     |
| .venv\Lib\site-packages\pip\_vendor\pkg_resources\__pycache__\__init__.cpython-312.pyc | .pyc | 161238 | True | False | False | True | False | UPDATE, CREATE, REPLACE, DELETE, INSERT |  |
| .venv\Lib\site-packages\pip\_vendor\pkg_resources\LICENSE |  | 1023 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\platformdirs\__init__.py | .py | 22344 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\platformdirs\__main__.py | .py | 1505 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\platformdirs\__pycache__\__init__.cpython-312.pyc | .pyc | 19836 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\platformdirs\__pycache__\__main__.cpython-312.pyc | .pyc | 1937 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\platformdirs\__pycache__\android.cpython-312.pyc | .pyc | 10674 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\platformdirs\__pycache__\api.cpython-312.pyc | .pyc | 13329 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\platformdirs\__pycache__\macos.cpython-312.pyc | .pyc | 8992 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\platformdirs\__pycache__\unix.cpython-312.pyc | .pyc | 14740 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\platformdirs\__pycache__\version.cpython-312.pyc | .pyc | 792 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\platformdirs\__pycache__\windows.cpython-312.pyc | .pyc | 13663 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\platformdirs\android.py | .py | 9013 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\platformdirs\api.py | .py | 9281 | True | False | False | True | True | CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\platformdirs\LICENSE |  | 1089 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\platformdirs\macos.py | .py | 6322 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\platformdirs\py.typed | .typed | 0 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\platformdirs\unix.py | .py | 10458 | True | False | False | True | False | REPLACE |  |
| .venv\Lib\site-packages\pip\_vendor\platformdirs\version.py | .py | 704 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\platformdirs\windows.py | .py | 10125 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\__init__.py | .py | 2983 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\__main__.py | .py | 353 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\__pycache__\__init__.cpython-312.pyc | .pyc | 3478 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\__pycache__\__main__.cpython-312.pyc | .pyc | 724 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\__pycache__\console.cpython-312.pyc | .pyc | 2618 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\__pycache__\filter.cpython-312.pyc | .pyc | 3211 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\__pycache__\formatter.cpython-312.pyc | .pyc | 4710 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\__pycache__\lexer.cpython-312.pyc | .pyc | 38351 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\__pycache__\modeline.cpython-312.pyc | .pyc | 1549 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\__pycache__\plugin.cpython-312.pyc | .pyc | 2598 | True | False | True | False | False | SELECT |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\__pycache__\regexopt.cpython-312.pyc | .pyc | 4067 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\__pycache__\scanner.cpython-312.pyc | .pyc | 4746 | True | False | False | True | False | UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\__pycache__\sphinxext.cpython-312.pyc | .pyc | 12088 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\__pycache__\style.cpython-312.pyc | .pyc | 6683 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\__pycache__\token.cpython-312.pyc | .pyc | 8179 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\__pycache__\unistring.cpython-312.pyc | .pyc | 32962 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\__pycache__\util.cpython-312.pyc | .pyc | 14059 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\console.py | .py | 1718 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\filter.py | .py | 1910 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\filters\__init__.py | .py | 40392 | True | False | False | True | False | REPLACE |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\filters\__pycache__\__init__.cpython-312.pyc | .pyc | 37901 | True | False | False | True | False | REPLACE |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\formatter.py | .py | 4390 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\formatters\__init__.py | .py | 5385 | True | False | False | True | False | UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\formatters\__pycache__\__init__.cpython-312.pyc | .pyc | 6892 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\formatters\__pycache__\_mapping.cpython-312.pyc | .pyc | 4205 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\formatters\_mapping.py | .py | 4176 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\lexer.py | .py | 35349 | True | False | False | True | False | REPLACE, CREATE, UPDATE, INSERT, DROP | , ; state |
| .venv\Lib\site-packages\pip\_vendor\pygments\lexers\__init__.py | .py | 12115 | True | False | False | True | False | UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\lexers\__pycache__\__init__.cpython-312.pyc | .pyc | 14611 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\lexers\__pycache__\_mapping.cpython-312.pyc | .pyc | 69834 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\lexers\__pycache__\python.cpython-312.pyc | .pyc | 42962 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\lexers\_mapping.py | .py | 77602 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\lexers\python.py | .py | 53853 | True | False | True | True | False | DROP, DELETE, INSERT, SELECT | keywords; soft-keywords; expr; numbers; expr-keywords; builtins; magicfuncs; magicvars; name; expr; expr; magicfuncs; rfstringescape; stringescape; bytesescape; fstrings-double; fstrings-single; strings-double; strings-single; fstrings-double; fstrings-single; strings-double; strings-single; keywords; builtins; magicfuncs; magicvars; backtick; name; numbers; magicfuncs; strings-double; strings-single; strings-double; strings-single; keywords; builtins; backtick; name; numbers; , ; strings; strings; strings; nl; strings; nl; , ; ,
        ; , ; ,
         |
| .venv\Lib\site-packages\pip\_vendor\pygments\LICENSE |  | 1331 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\modeline.py | .py | 1005 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\plugin.py | .py | 1891 | True | False | True | False | False | SELECT |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\regexopt.py | .py | 3072 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\scanner.py | .py | 3092 | True | False | False | True | False | UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\sphinxext.py | .py | 7981 | True | False | False | True | False | REPLACE |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\style.py | .py | 6420 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\styles\__init__.py | .py | 2042 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\styles\__pycache__\__init__.cpython-312.pyc | .pyc | 2654 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\styles\__pycache__\_mapping.cpython-312.pyc | .pyc | 3638 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\styles\_mapping.py | .py | 3312 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\token.py | .py | 6226 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\unistring.py | .py | 63208 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pygments\util.py | .py | 10031 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pyproject_hooks\__init__.py | .py | 691 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pyproject_hooks\__pycache__\__init__.cpython-312.pyc | .pyc | 737 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pyproject_hooks\__pycache__\_impl.cpython-312.pyc | .pyc | 18036 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pyproject_hooks\_impl.py | .py | 14936 | True | False | False | True | True | UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\pyproject_hooks\_in_process\__init__.py | .py | 557 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pyproject_hooks\_in_process\__pycache__\__init__.cpython-312.pyc | .pyc | 1066 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pyproject_hooks\_in_process\__pycache__\_in_process.cpython-312.pyc | .pyc | 15304 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pyproject_hooks\_in_process\_in_process.py | .py | 12216 | True | False | False | True | True | INSERT |  |
| .venv\Lib\site-packages\pip\_vendor\pyproject_hooks\LICENSE |  | 1081 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\pyproject_hooks\py.typed | .typed | 0 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\README.rst | .rst | 9394 | True | False | False | True | False | REPLACE, UPDATE, DELETE |  |
| .venv\Lib\site-packages\pip\_vendor\requests\__init__.py | .py | 5057 | True | False | False | True | False | DELETE |  |
| .venv\Lib\site-packages\pip\_vendor\requests\__pycache__\__init__.cpython-312.pyc | .pyc | 5243 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\requests\__pycache__\__version__.cpython-312.pyc | .pyc | 574 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\requests\__pycache__\_internal_utils.cpython-312.pyc | .pyc | 2014 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\requests\__pycache__\adapters.cpython-312.pyc | .pyc | 27856 | True | False | True | True | False | SELECT, ALTER |  |
| .venv\Lib\site-packages\pip\_vendor\requests\__pycache__\api.cpython-312.pyc | .pyc | 7181 | True | False | False | True | False | DELETE |  |
| .venv\Lib\site-packages\pip\_vendor\requests\__pycache__\auth.cpython-312.pyc | .pyc | 13911 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\requests\__pycache__\certs.cpython-312.pyc | .pyc | 668 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\requests\__pycache__\compat.cpython-312.pyc | .pyc | 1975 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\requests\__pycache__\cookies.cpython-312.pyc | .pyc | 25188 | True | False | False | True | False | CREATE, UPDATE, INSERT, REPLACE |  |
| .venv\Lib\site-packages\pip\_vendor\requests\__pycache__\exceptions.cpython-312.pyc | .pyc | 7588 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\requests\__pycache__\help.cpython-312.pyc | .pyc | 4218 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\requests\__pycache__\hooks.cpython-312.pyc | .pyc | 1041 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\requests\__pycache__\models.cpython-312.pyc | .pyc | 35513 | True | False | False | True | False | REPLACE |  |
| .venv\Lib\site-packages\pip\_vendor\requests\__pycache__\packages.cpython-312.pyc | .pyc | 1256 | True | False | False | True | False | REPLACE |  |
| .venv\Lib\site-packages\pip\_vendor\requests\__pycache__\sessions.cpython-312.pyc | .pyc | 27844 | True | False | False | True | False | DELETE, CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\requests\__pycache__\status_codes.cpython-312.pyc | .pyc | 6013 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\requests\__pycache__\structures.cpython-312.pyc | .pyc | 5613 | True | False | False | True | False | UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\requests\__pycache__\utils.cpython-312.pyc | .pyc | 36104 | True | False | True | True | False | UPDATE, CREATE, INSERT, REPLACE, SELECT |  |
| .venv\Lib\site-packages\pip\_vendor\requests\__version__.py | .py | 435 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\requests\_internal_utils.py | .py | 1495 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\requests\adapters.py | .py | 26429 | True | False | True | True | False | SELECT, ALTER |  |
| .venv\Lib\site-packages\pip\_vendor\requests\api.py | .py | 6449 | True | False | False | True | False | DELETE |  |
| .venv\Lib\site-packages\pip\_vendor\requests\auth.py | .py | 10186 | True | False | False | False | True |  |  |
| .venv\Lib\site-packages\pip\_vendor\requests\certs.py | .py | 441 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\requests\compat.py | .py | 1822 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\requests\cookies.py | .py | 18590 | True | False | False | True | True | CREATE, REPLACE, UPDATE, INSERT |  |
| .venv\Lib\site-packages\pip\_vendor\requests\exceptions.py | .py | 4272 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\requests\help.py | .py | 3813 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\requests\hooks.py | .py | 733 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\requests\LICENSE |  | 10142 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\requests\models.py | .py | 35575 | True | False | False | True | True | CREATE, UPDATE, REPLACE |  |
| .venv\Lib\site-packages\pip\_vendor\requests\packages.py | .py | 1057 | True | False | False | True | False | REPLACE |  |
| .venv\Lib\site-packages\pip\_vendor\requests\sessions.py | .py | 30503 | True | False | False | True | True | UPDATE, CREATE, DELETE, INSERT |  |
| .venv\Lib\site-packages\pip\_vendor\requests\status_codes.py | .py | 4322 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\requests\structures.py | .py | 2912 | True | False | False | True | True | UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\requests\utils.py | .py | 33225 | True | False | True | True | True | REPLACE, UPDATE, CREATE, INSERT, SELECT |  |
| .venv\Lib\site-packages\pip\_vendor\resolvelib\__init__.py | .py | 541 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\resolvelib\__pycache__\__init__.cpython-312.pyc | .pyc | 624 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\resolvelib\__pycache__\providers.cpython-312.pyc | .pyc | 10115 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\resolvelib\__pycache__\reporters.cpython-312.pyc | .pyc | 3279 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\resolvelib\__pycache__\structs.cpython-312.pyc | .pyc | 12434 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\resolvelib\LICENSE |  | 751 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\resolvelib\providers.py | .py | 8914 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\resolvelib\py.typed | .typed | 0 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\resolvelib\reporters.py | .py | 2037 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\resolvelib\resolvers\__init__.py | .py | 640 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\resolvelib\resolvers\__pycache__\__init__.cpython-312.pyc | .pyc | 729 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\resolvelib\resolvers\__pycache__\abstract.cpython-312.pyc | .pyc | 2435 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\resolvelib\resolvers\__pycache__\criterion.cpython-312.pyc | .pyc | 3266 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\resolvelib\resolvers\__pycache__\exceptions.cpython-312.pyc | .pyc | 4073 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\resolvelib\resolvers\__pycache__\resolution.cpython-312.pyc | .pyc | 25135 | True | False | False | True | False | UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\resolvelib\resolvers\abstract.py | .py | 1543 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\resolvelib\resolvers\criterion.py | .py | 1768 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\resolvelib\resolvers\exceptions.py | .py | 1768 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\resolvelib\resolvers\resolution.py | .py | 24212 | True | False | False | True | True | UPDATE, CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\resolvelib\structs.py | .py | 6420 | True | False | False | False | True |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__init__.py | .py | 6090 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__main__.py | .py | 7896 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\__init__.cpython-312.pyc | .pyc | 7005 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\__main__.cpython-312.pyc | .pyc | 9528 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\_cell_widths.cpython-312.pyc | .pyc | 7862 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\_emoji_codes.cpython-312.pyc | .pyc | 205966 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\_emoji_replace.cpython-312.pyc | .pyc | 1719 | True | False | False | True | False | REPLACE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\_export_format.cpython-312.pyc | .pyc | 2339 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\_extension.cpython-312.pyc | .pyc | 527 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\_fileno.cpython-312.pyc | .pyc | 845 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\_inspect.cpython-312.pyc | .pyc | 12013 | True | False | False | True | False | REPLACE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\_log_render.cpython-312.pyc | .pyc | 4137 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\_loop.cpython-312.pyc | .pyc | 1860 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\_null_file.cpython-312.pyc | .pyc | 3619 | True | False | False | True | False | TRUNCATE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\_palettes.cpython-312.pyc | .pyc | 5150 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\_pick.cpython-312.pyc | .pyc | 711 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\_ratio.cpython-312.pyc | .pyc | 6413 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\_spinners.cpython-312.pyc | .pyc | 13169 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\_stack.cpython-312.pyc | .pyc | 955 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\_timer.cpython-312.pyc | .pyc | 855 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\_win32_console.cpython-312.pyc | .pyc | 28801 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\_windows.cpython-312.pyc | .pyc | 2480 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\_windows_renderer.cpython-312.pyc | .pyc | 3553 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\_wrap.cpython-312.pyc | .pyc | 3316 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\abc.cpython-312.pyc | .pyc | 1598 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\align.cpython-312.pyc | .pyc | 12234 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\ansi.cpython-312.pyc | .pyc | 9071 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\bar.cpython-312.pyc | .pyc | 4262 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\box.cpython-312.pyc | .pyc | 11681 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\cells.cpython-312.pyc | .pyc | 5550 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\color.cpython-312.pyc | .pyc | 26542 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\color_triplet.cpython-312.pyc | .pyc | 1691 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\columns.cpython-312.pyc | .pyc | 8574 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\console.cpython-312.pyc | .pyc | 115032 | True | False | False | True | False | UPDATE, REPLACE, INSERT | 
 |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\constrain.cpython-312.pyc | .pyc | 2248 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\containers.cpython-312.pyc | .pyc | 9200 | True | False | False | True | False | INSERT |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\control.cpython-312.pyc | .pyc | 10786 | True | False | False | True | False | REPLACE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\default_styles.cpython-312.pyc | .pyc | 10511 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\diagnose.cpython-312.pyc | .pyc | 1511 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\emoji.cpython-312.pyc | .pyc | 4071 | True | False | False | True | False | REPLACE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\errors.cpython-312.pyc | .pyc | 1835 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\file_proxy.cpython-312.pyc | .pyc | 3561 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\filesize.cpython-312.pyc | .pyc | 3037 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\highlighter.cpython-312.pyc | .pyc | 9878 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\json.cpython-312.pyc | .pyc | 6025 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\jupyter.cpython-312.pyc | .pyc | 5198 | True | False | False | True | False | REPLACE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\layout.cpython-312.pyc | .pyc | 20148 | True | False | False | True | False | UPDATE, CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\live.cpython-312.pyc | .pyc | 19983 | True | False | False | True | False | UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\live_render.cpython-312.pyc | .pyc | 4735 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\logging.cpython-312.pyc | .pyc | 14064 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\markup.cpython-312.pyc | .pyc | 9557 | True | False | False | True | False | REPLACE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\measure.cpython-312.pyc | .pyc | 6368 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\padding.cpython-312.pyc | .pyc | 6923 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\pager.cpython-312.pyc | .pyc | 1801 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\palette.cpython-312.pyc | .pyc | 5287 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\panel.cpython-312.pyc | .pyc | 12714 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\pretty.cpython-312.pyc | .pyc | 40601 | True | False | False | True | False | INSERT |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\progress.cpython-312.pyc | .pyc | 74929 | True | False | False | True | False | UPDATE, INSERT, CREATE, DELETE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\progress_bar.cpython-312.pyc | .pyc | 10367 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\prompt.cpython-312.pyc | .pyc | 15995 | True | False | True | False | False | SELECT |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\protocol.cpython-312.pyc | .pyc | 1782 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\region.cpython-312.pyc | .pyc | 557 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\repr.cpython-312.pyc | .pyc | 6603 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\rule.cpython-312.pyc | .pyc | 6558 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\scope.cpython-312.pyc | .pyc | 3815 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\screen.cpython-312.pyc | .pyc | 2469 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\segment.cpython-312.pyc | .pyc | 28527 | True | False | False | True | False | REPLACE, INSERT | 
; 
; 
; 
 |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\spinner.cpython-312.pyc | .pyc | 5913 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\status.cpython-312.pyc | .pyc | 6051 | True | False | False | True | False | UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\style.cpython-312.pyc | .pyc | 33407 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\styled.cpython-312.pyc | .pyc | 2129 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\syntax.cpython-312.pyc | .pyc | 40953 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\table.cpython-312.pyc | .pyc | 43827 | True | False | False | True | False | REPLACE, UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\terminal_theme.cpython-312.pyc | .pyc | 3338 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\text.cpython-312.pyc | .pyc | 61200 | True | False | False | True | False | CREATE, TRUNCATE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\theme.cpython-312.pyc | .pyc | 6317 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\themes.cpython-312.pyc | .pyc | 304 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\traceback.cpython-312.pyc | .pyc | 36163 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\__pycache__\tree.cpython-312.pyc | .pyc | 11783 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\_cell_widths.py | .py | 10209 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\_emoji_codes.py | .py | 140235 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\_emoji_replace.py | .py | 1064 | True | False | False | True | False | REPLACE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\_export_format.py | .py | 2128 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\_extension.py | .py | 265 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\_fileno.py | .py | 799 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\_inspect.py | .py | 9656 | True | False | False | True | True | REPLACE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\_log_render.py | .py | 3225 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\_loop.py | .py | 1236 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\_null_file.py | .py | 1394 | True | False | False | True | False | TRUNCATE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\_palettes.py | .py | 7063 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\_pick.py | .py | 423 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\_ratio.py | .py | 5325 | True | False | False | True | False | REPLACE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\_spinners.py | .py | 19919 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\_stack.py | .py | 351 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\_timer.py | .py | 417 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\_win32_console.py | .py | 22755 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\_windows.py | .py | 1925 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\_windows_renderer.py | .py | 2783 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\_wrap.py | .py | 3404 | True | False | False | True | False | INSERT |  |
| .venv\Lib\site-packages\pip\_vendor\rich\abc.py | .py | 890 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\align.py | .py | 10324 | True | False | False | True | False | UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\ansi.py | .py | 6921 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\bar.py | .py | 3263 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\box.py | .py | 10686 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\cells.py | .py | 5130 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\color.py | .py | 18211 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\color_triplet.py | .py | 1054 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\columns.py | .py | 7131 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\console.py | .py | 100849 | True | False | False | True | True | UPDATE, REPLACE, INSERT | \n |
| .venv\Lib\site-packages\pip\_vendor\rich\constrain.py | .py | 1288 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\containers.py | .py | 5502 | True | False | False | True | False | INSERT, TRUNCATE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\control.py | .py | 6487 | True | False | False | True | False | REPLACE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\default_styles.py | .py | 8257 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\diagnose.py | .py | 1025 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\emoji.py | .py | 2367 | True | False | False | True | False | REPLACE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\errors.py | .py | 642 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\file_proxy.py | .py | 1683 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\filesize.py | .py | 2484 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\highlighter.py | .py | 9586 | True | False | False | False | True |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\json.py | .py | 5031 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\jupyter.py | .py | 3252 | True | False | False | True | False | REPLACE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\layout.py | .py | 14004 | True | False | False | True | False | UPDATE, CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\LICENSE |  | 1056 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\live.py | .py | 15180 | True | False | False | True | False | UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\live_render.py | .py | 3521 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\logging.py | .py | 12468 | True | False | False | True | False | DELETE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\markup.py | .py | 8451 | True | False | False | True | False | REPLACE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\measure.py | .py | 5305 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\padding.py | .py | 4908 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\pager.py | .py | 828 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\palette.py | .py | 3396 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\panel.py | .py | 11157 | True | False | False | True | True | REPLACE, TRUNCATE, UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\pretty.py | .py | 36391 | True | False | False | True | False | REPLACE, INSERT |  |
| .venv\Lib\site-packages\pip\_vendor\rich\progress.py | .py | 60408 | True | False | False | True | True | UPDATE, INSERT, CREATE, DELETE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\progress_bar.py | .py | 8162 | True | False | False | True | False | UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\prompt.py | .py | 12447 | True | False | True | False | True | SELECT |  |
| .venv\Lib\site-packages\pip\_vendor\rich\protocol.py | .py | 1391 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\py.typed | .typed | 0 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\region.py | .py | 166 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\repr.py | .py | 4431 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\rule.py | .py | 4602 | True | False | False | True | False | REPLACE, TRUNCATE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\scope.py | .py | 2843 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\screen.py | .py | 1591 | True | False | False | True | False | UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\segment.py | .py | 24743 | True | False | False | True | False | REPLACE, INSERT | \n; \n; \n; \n |
| .venv\Lib\site-packages\pip\_vendor\rich\spinner.py | .py | 4214 | True | False | False | True | False | UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\status.py | .py | 4424 | True | False | False | True | False | UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\style.py | .py | 26990 | True | False | False | True | True | CREATE, UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\styled.py | .py | 1258 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\syntax.py | .py | 36371 | True | False | False | True | False | UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\table.py | .py | 40049 | True | False | False | True | True | REPLACE, UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\terminal_theme.py | .py | 3370 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\text.py | .py | 47552 | True | False | False | True | True | CREATE, INSERT, UPDATE, TRUNCATE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\theme.py | .py | 3771 | True | False | False | True | True | UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\themes.py | .py | 102 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\rich\traceback.py | .py | 35861 | True | False | False | True | False | REPLACE, CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\rich\tree.py | .py | 9451 | True | False | False | True | False | UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\tomli\__init__.py | .py | 314 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\tomli\__pycache__\__init__.cpython-312.pyc | .pyc | 328 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\tomli\__pycache__\_parser.cpython-312.pyc | .pyc | 29403 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\tomli\__pycache__\_re.cpython-312.pyc | .pyc | 4066 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\tomli\__pycache__\_types.cpython-312.pyc | .pyc | 356 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\tomli\_parser.py | .py | 25778 | True | False | False | True | False | REPLACE, CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\tomli\_re.py | .py | 3235 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\tomli\_types.py | .py | 254 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\tomli\LICENSE |  | 1072 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\tomli\py.typed | .typed | 26 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\tomli_w\__init__.py | .py | 169 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\tomli_w\__pycache__\__init__.cpython-312.pyc | .pyc | 317 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\tomli_w\__pycache__\_writer.cpython-312.pyc | .pyc | 10339 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\tomli_w\_writer.py | .py | 6961 | True | False | False | True | False | REPLACE |  |
| .venv\Lib\site-packages\pip\_vendor\tomli_w\LICENSE |  | 1072 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\tomli_w\py.typed | .typed | 26 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\truststore\__init__.py | .py | 1320 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\truststore\__pycache__\__init__.cpython-312.pyc | .pyc | 1442 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\truststore\__pycache__\_api.cpython-312.pyc | .pyc | 17522 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\truststore\__pycache__\_macos.cpython-312.pyc | .pyc | 18977 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\truststore\__pycache__\_openssl.cpython-312.pyc | .pyc | 2246 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\truststore\__pycache__\_ssl_constants.cpython-312.pyc | .pyc | 1089 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\truststore\__pycache__\_windows.cpython-312.pyc | .pyc | 15755 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\truststore\_api.py | .py | 11413 | True | False | False | True | False | REPLACE |  |
| .venv\Lib\site-packages\pip\_vendor\truststore\_macos.py | .py | 20503 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\truststore\_openssl.py | .py | 2412 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\truststore\_ssl_constants.py | .py | 1130 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\truststore\_windows.py | .py | 17993 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\truststore\LICENSE |  | 1086 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\truststore\py.typed | .typed | 0 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\__init__.py | .py | 3333 | True | False | False | True | False | DELETE |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\__pycache__\__init__.cpython-312.pyc | .pyc | 3395 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\__pycache__\_collections.cpython-312.pyc | .pyc | 16354 | True | False | False | True | False | UPDATE, CREATE, INSERT |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\__pycache__\_version.cpython-312.pyc | .pyc | 208 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\__pycache__\connection.cpython-312.pyc | .pyc | 20393 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\__pycache__\connectionpool.cpython-312.pyc | .pyc | 36528 | True | False | False | True | False | CREATE, REPLACE |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\__pycache__\exceptions.cpython-312.pyc | .pyc | 13483 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\__pycache__\fields.cpython-312.pyc | .pyc | 10392 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\__pycache__\filepost.cpython-312.pyc | .pyc | 4002 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\__pycache__\poolmanager.cpython-312.pyc | .pyc | 20419 | True | False | False | True | False | CREATE, UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\__pycache__\request.cpython-312.pyc | .pyc | 7284 | True | False | False | True | False | DELETE, DROP |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\__pycache__\response.cpython-312.pyc | .pyc | 33933 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\_collections.py | .py | 11372 | True | False | False | True | True | INSERT, UPDATE, CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\_version.py | .py | 64 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\connection.py | .py | 20314 | True | False | False | True | True | UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\connectionpool.py | .py | 40408 | True | False | False | True | True | CREATE, REPLACE, UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\contrib\__init__.py | .py | 0 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\contrib\__pycache__\__init__.cpython-312.pyc | .pyc | 188 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\contrib\__pycache__\_appengine_environ.cpython-312.pyc | .pyc | 1838 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\contrib\__pycache__\appengine.cpython-312.pyc | .pyc | 11554 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\contrib\__pycache__\ntlmpool.cpython-312.pyc | .pyc | 5704 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\contrib\__pycache__\pyopenssl.cpython-312.pyc | .pyc | 24438 | True | False | True | False | False | SELECT |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\contrib\__pycache__\securetransport.cpython-312.pyc | .pyc | 35491 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\contrib\__pycache__\socks.cpython-312.pyc | .pyc | 7501 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\contrib\_appengine_environ.py | .py | 957 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\contrib\_securetransport\__init__.py | .py | 0 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\contrib\_securetransport\__pycache__\__init__.cpython-312.pyc | .pyc | 205 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\contrib\_securetransport\__pycache__\bindings.cpython-312.pyc | .pyc | 17417 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\contrib\_securetransport\__pycache__\low_level.cpython-312.pyc | .pyc | 14753 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\contrib\_securetransport\bindings.py | .py | 17632 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\contrib\_securetransport\low_level.py | .py | 13922 | True | False | False | True | False | CREATE, REPLACE, INSERT |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\contrib\appengine.py | .py | 11036 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\contrib\ntlmpool.py | .py | 4528 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\contrib\pyopenssl.py | .py | 17081 | True | False | True | False | False | SELECT |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\contrib\securetransport.py | .py | 34446 | True | False | True | True | False | SELECT, CREATE, UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\contrib\socks.py | .py | 7097 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\exceptions.py | .py | 8217 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\fields.py | .py | 8579 | True | False | False | True | False | REPLACE, UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\filepost.py | .py | 2440 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\LICENSE.txt | .txt | 1115 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\packages\__init__.py | .py | 0 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\packages\__pycache__\__init__.cpython-312.pyc | .pyc | 189 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\packages\__pycache__\six.cpython-312.pyc | .pyc | 41245 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\packages\backports\__init__.py | .py | 0 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\packages\backports\__pycache__\__init__.cpython-312.pyc | .pyc | 199 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\packages\backports\__pycache__\makefile.cpython-312.pyc | .pyc | 1815 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\packages\backports\__pycache__\weakref_finalize.cpython-312.pyc | .pyc | 7326 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\packages\backports\makefile.py | .py | 1417 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\packages\backports\weakref_finalize.py | .py | 5343 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\packages\six.py | .py | 34665 | True | False | False | True | True | CREATE, REPLACE, UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\poolmanager.py | .py | 19990 | True | False | False | True | True | CREATE, ALTER, UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\request.py | .py | 6691 | True | False | False | True | False | DELETE, DROP, UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\response.py | .py | 30641 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\util\__init__.py | .py | 1155 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\util\__pycache__\__init__.cpython-312.pyc | .pyc | 1136 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\util\__pycache__\connection.cpython-312.pyc | .pyc | 4737 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\util\__pycache__\proxy.cpython-312.pyc | .pyc | 1542 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\util\__pycache__\queue.cpython-312.pyc | .pyc | 1342 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\util\__pycache__\request.cpython-312.pyc | .pyc | 4173 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\util\__pycache__\response.cpython-312.pyc | .pyc | 2982 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\util\__pycache__\retry.cpython-312.pyc | .pyc | 21712 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\util\__pycache__\ssl_.cpython-312.pyc | .pyc | 15354 | True | False | True | False | False | SELECT |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\util\__pycache__\ssl_match_hostname.cpython-312.pyc | .pyc | 5041 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\util\__pycache__\ssltransport.cpython-312.pyc | .pyc | 10743 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\util\__pycache__\timeout.cpython-312.pyc | .pyc | 11129 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\util\__pycache__\url.cpython-312.pyc | .pyc | 15775 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\util\__pycache__\wait.cpython-312.pyc | .pyc | 4393 | True | False | True | False | False | SELECT |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\util\connection.py | .py | 4901 | True | False | True | False | False | SELECT |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\util\proxy.py | .py | 1605 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\util\queue.py | .py | 498 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\util\request.py | .py | 3997 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\util\response.py | .py | 3510 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\util\retry.py | .py | 22050 | True | False | False | True | False | CREATE, DELETE, UPDATE |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\util\ssl_.py | .py | 17460 | True | False | True | True | False | REPLACE, SELECT |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\util\ssl_match_hostname.py | .py | 5758 | True | False | False | True | False | REPLACE |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\util\ssltransport.py | .py | 6895 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\util\timeout.py | .py | 10168 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\util\url.py | .py | 14296 | True | False | False | True | False | INSERT |  |
| .venv\Lib\site-packages\pip\_vendor\urllib3\util\wait.py | .py | 5403 | True | False | True | False | False | SELECT |  |
| .venv\Lib\site-packages\pip\_vendor\vendor.txt | .txt | 343 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip\py.typed | .typed | 286 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip-25.3.dist-info\entry_points.txt | .txt | 84 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip-25.3.dist-info\INSTALLER |  | 4 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip-25.3.dist-info\licenses\AUTHORS.txt | .txt | 11503 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip-25.3.dist-info\licenses\LICENSE.txt | .txt | 1093 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip-25.3.dist-info\licenses\src\pip\_vendor\cachecontrol\LICENSE.txt | .txt | 558 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip-25.3.dist-info\licenses\src\pip\_vendor\certifi\LICENSE |  | 989 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip-25.3.dist-info\licenses\src\pip\_vendor\dependency_groups\LICENSE.txt | .txt | 1099 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip-25.3.dist-info\licenses\src\pip\_vendor\distlib\LICENSE.txt | .txt | 14531 | True | False | False | True | False | CREATE |  |
| .venv\Lib\site-packages\pip-25.3.dist-info\licenses\src\pip\_vendor\distro\LICENSE |  | 11325 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip-25.3.dist-info\licenses\src\pip\_vendor\idna\LICENSE.md | .md | 1541 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip-25.3.dist-info\licenses\src\pip\_vendor\msgpack\COPYING |  | 614 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip-25.3.dist-info\licenses\src\pip\_vendor\packaging\LICENSE |  | 197 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip-25.3.dist-info\licenses\src\pip\_vendor\packaging\LICENSE.APACHE | .apache | 10174 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip-25.3.dist-info\licenses\src\pip\_vendor\packaging\LICENSE.BSD | .bsd | 1344 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip-25.3.dist-info\licenses\src\pip\_vendor\pkg_resources\LICENSE |  | 1023 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip-25.3.dist-info\licenses\src\pip\_vendor\platformdirs\LICENSE |  | 1089 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip-25.3.dist-info\licenses\src\pip\_vendor\pygments\LICENSE |  | 1331 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip-25.3.dist-info\licenses\src\pip\_vendor\pyproject_hooks\LICENSE |  | 1081 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip-25.3.dist-info\licenses\src\pip\_vendor\requests\LICENSE |  | 10142 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip-25.3.dist-info\licenses\src\pip\_vendor\resolvelib\LICENSE |  | 751 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip-25.3.dist-info\licenses\src\pip\_vendor\rich\LICENSE |  | 1056 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip-25.3.dist-info\licenses\src\pip\_vendor\tomli\LICENSE |  | 1072 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip-25.3.dist-info\licenses\src\pip\_vendor\tomli_w\LICENSE |  | 1072 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip-25.3.dist-info\licenses\src\pip\_vendor\truststore\LICENSE |  | 1086 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip-25.3.dist-info\licenses\src\pip\_vendor\urllib3\LICENSE.txt | .txt | 1115 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip-25.3.dist-info\METADATA |  | 4672 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip-25.3.dist-info\RECORD |  | 68273 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip-25.3.dist-info\REQUESTED |  | 0 | True | False | False | False | False |  |  |
| .venv\Lib\site-packages\pip-25.3.dist-info\WHEEL |  | 82 | True | False | False | False | False |  |  |
| .venv\pyvenv.cfg | .cfg | 318 | True | False | False | False | False |  |  |
| .venv\Scripts\activate |  | 2060 | True | False | False | False | False |  |  |
| .venv\Scripts\activate.bat | .bat | 1004 | True | False | False | True | False | UPDATE |  |
| .venv\Scripts\Activate.ps1 | .ps1 | 26199 | True | False | False | False | False |  |  |
| .venv\Scripts\deactivate.bat | .bat | 393 | True | False | False | False | False |  |  |
| .venv\Scripts\pip.exe | .exe | 108411 | False | False | False | False | False |  |  |
| .venv\Scripts\pip3.12.exe | .exe | 108411 | False | False | False | False | False |  |  |
| .venv\Scripts\pip3.exe | .exe | 108411 | False | False | False | False | False |  |  |
| .venv\Scripts\python.exe | .exe | 270104 | False | False | False | False | False |  |  |
| .venv\Scripts\pythonw.exe | .exe | 258840 | False | False | False | False | False |  |  |

## 6) Notes on Detection Method
- This overview is generated by recursively reading every file in the workspace and running static pattern extraction.
- Dynamic runtime includes, generated SQL strings, or query-builder abstractions may not always expose exact SQL verbs in static text.
- Vendor and framework files are included in inventory to satisfy full coverage.

## 7) Role-Wise Options, Click Actions, and Source-to-Target Connections
- Scope: extracted from all PHP files by scanning links, forms, submit buttons, and role guards.
- Meaning of "what happens on click": route target + target behavior classification (read/write DB, redirects, JSON, file download, file I/O).

### Role: system_admin
| From Page | Option/Button | Click Type | Method | Goes To | Target Role Scope | What Happens |
|---|---|---|---|---|---|---|
| system_admin_activity_feed.php | $scope,'entry'=>(int)$row['id'],'start_date'=>$startDate,'end_date'=>$endDate,'activity_action'=>$actionFilter,'activity_preset'=>$activityPreset]).'#entry-'.(int)$row['id']); ?>">Open | link | GET | <?php echo htmlspecialchars(build_feed_url([ | external_or_dynamic | route_or_ui_only |
| system_admin_activity_feed.php | $scope,'start_date'=>$startDate,'end_date'=>$endDate,'activity_action'=>$actionFilter,'activity_preset'=>$activityPreset]).'#entry-'.(int)$row['id']); ?>">Close | link | GET | <?php echo htmlspecialchars(build_feed_url([ | external_or_dynamic | route_or_ui_only |
| system_admin_activity_feed.php | $scope])); ?>">Clear | link | GET | <?php echo htmlspecialchars(build_feed_url([ | external_or_dynamic | route_or_ui_only |
| system_admin_activity_feed.php | Activity Feed | link | GET | system_admin_activity_feed.php | system_admin | reads_db, returns_json, redirects |
| system_admin_activity_feed.php | Apply | button_submit | GET | system_admin_activity_feed.php | system_admin | reads_db, returns_json, redirects |
| system_admin_activity_feed.php | Backup & Restore | link | GET | system_admin_export_sql.php | system_admin | reads_db, writes_db, redirects, downloads_file, file_io |
| system_admin_activity_feed.php | Dashboard | link | GET | system_admin_dashboard.php | system_admin | reads_db, returns_json, redirects |
| system_admin_activity_feed.php | Logout | link | GET | logout.php | shared_or_unscoped | returns_json |
| system_admin_activity_feed.php | 'today','activity_preset'=>'active_logins'])); ?>"> Today Active | link | GET | <?php echo htmlspecialchars(build_feed_url([ | external_or_dynamic | route_or_ui_only |
| system_admin_activity_feed.php | 'today','activity_preset'=>'failed_logins'])); ?>"> Failed Logins | link | GET | <?php echo htmlspecialchars(build_feed_url([ | external_or_dynamic | route_or_ui_only |
| system_admin_activity_feed.php | 'today','activity_preset'=>'sensitive_actions'])); ?>"> Sensitive Actions | link | GET | <?php echo htmlspecialchars(build_feed_url([ | external_or_dynamic | route_or_ui_only |
| system_admin_activity_feed.php | Unlock | button_submit | POST | system_admin_activity_feed.php | system_admin | reads_db, returns_json, redirects |
| system_admin_activity_feed.php | 'week','activity_preset'=>'active_logins'])); ?>"> Week Active | link | GET | <?php echo htmlspecialchars(build_feed_url([ | external_or_dynamic | route_or_ui_only |
| system_admin_dashboard.php | Activity Feed | link | GET | system_admin_activity_feed.php | system_admin | reads_db, returns_json, redirects |
| system_admin_dashboard.php | Backup & Restore | link | GET | system_admin_export_sql.php | system_admin | reads_db, writes_db, redirects, downloads_file, file_io |
| system_admin_dashboard.php | Dashboard | link | GET | system_admin_dashboard.php | system_admin | reads_db, returns_json, redirects |
| system_admin_dashboard.php | Failed Logins | link | GET | system_admin_activity_feed.php?scope=today&activity_preset=failed_logins | system_admin | reads_db, returns_json, redirects |
| system_admin_dashboard.php | Failed Logins Today | link | GET | system_admin_activity_feed.php?scope=today&activity_preset=failed_logins | system_admin | reads_db, returns_json, redirects |
| system_admin_dashboard.php | Full Audit Log | link | GET | system_admin_activity_feed.php?scope=all | system_admin | reads_db, returns_json, redirects |
| system_admin_dashboard.php | Logout | link | GET | logout.php | shared_or_unscoped | returns_json |
| system_admin_dashboard.php | Sensitive Actions | link | GET | system_admin_activity_feed.php?scope=today&activity_preset=sensitive_actions | system_admin | reads_db, returns_json, redirects |
| system_admin_dashboard.php | Sensitive Actions Today | link | GET | system_admin_activity_feed.php?scope=today&activity_preset=sensitive_actions | system_admin | reads_db, returns_json, redirects |
| system_admin_dashboard.php | Today Active | link | GET | system_admin_activity_feed.php?scope=today&activity_preset=active_logins | system_admin | reads_db, returns_json, redirects |
| system_admin_dashboard.php | Week Active | link | GET | system_admin_activity_feed.php?scope=week&activity_preset=active_logins | system_admin | reads_db, returns_json, redirects |
| system_admin_export_sql.php | Activity Feed | link | GET | system_admin_activity_feed.php | system_admin | reads_db, returns_json, redirects |
| system_admin_export_sql.php | Backup & Restore | link | GET | system_admin_export_sql.php | system_admin | reads_db, writes_db, redirects, downloads_file, file_io |
| system_admin_export_sql.php | Dashboard | link | GET | system_admin_dashboard.php | system_admin | reads_db, returns_json, redirects |
| system_admin_export_sql.php | Download Backup SQL | button_submit | POST | system_admin_export_sql.php | system_admin | reads_db, writes_db, redirects, downloads_file, file_io |
| system_admin_export_sql.php | Logout | link | GET | logout.php | shared_or_unscoped | returns_json |
| system_admin_export_sql.php | Restore Database | button_submit | POST | system_admin_export_sql.php | system_admin | reads_db, writes_db, redirects, downloads_file, file_io |

### Role: admin
| From Page | Option/Button | Click Type | Method | Goes To | Target Role Scope | What Happens |
|---|---|---|---|---|---|---|
| admin_dashboard.php | Academic Calendar | link | GET | manage_academic_calendar.php | admin | reads_db, writes_db, redirects |
| admin_dashboard.php | Add Students | link | GET | bulk_add_students.php | admin | reads_db, writes_db, returns_json, redirects, file_io |
| admin_dashboard.php | Assign Teachers | link | GET | assign_teachers.php | admin | reads_db, writes_db, returns_json, redirects |
| admin_dashboard.php | Change Roles | link | GET | change_roles.php | admin | reads_db, writes_db, redirects |
| admin_dashboard.php | Create Classes | link | GET | create_classes.php | admin | reads_db, writes_db, returns_json, redirects |
| admin_dashboard.php | Create Subjects | link | GET | create_subjects.php | admin | reads_db, writes_db, returns_json, redirects |
| admin_dashboard.php | Dashboard | link | GET | admin_dashboard.php | admin | writes_db, redirects |
| admin_dashboard.php | Logout | link | GET | logout.php | shared_or_unscoped | returns_json |
| admin_dashboard.php | Manage Electives | link | GET | manage_electives.php | admin | reads_db, writes_db, redirects |
| admin_dashboard.php | Manage Teachers | link | GET | manage_teachers.php | admin | reads_db, writes_db, redirects |
| admin_dashboard.php | Manual Mailing | link | GET | test_mail.php | admin | reads_db, writes_db, returns_json, redirects |
| admin_login.php | Faculty/Student Login | link | GET | login.php | shared_or_unscoped | reads_db, writes_db, redirects |
| admin_login.php | Go back to standard login | link | GET | login.php | shared_or_unscoped | reads_db, writes_db, redirects |
| admin_login.php | Login | button_submit | POST | admin_login.php | admin | reads_db, writes_db, redirects |
| admin_login.php | Portal Home | link | GET | index.php | shared_or_unscoped | page_render_or_ui_action |
| assign_teachers.php | Academic Calendar | link | GET | manage_academic_calendar.php | admin | reads_db, writes_db, redirects |
| assign_teachers.php | Add Students | link | GET | bulk_add_students.php | admin | reads_db, writes_db, returns_json, redirects, file_io |
| assign_teachers.php | Assign | button_submit | POST | assign_teachers.php | admin | reads_db, writes_db, returns_json, redirects |
| assign_teachers.php | Assign Teachers | link | GET | assign_teachers.php | admin | reads_db, writes_db, returns_json, redirects |
| assign_teachers.php | Change Roles | link | GET | change_roles.php | admin | reads_db, writes_db, redirects |
| assign_teachers.php | Create Classes | link | GET | create_classes.php | admin | reads_db, writes_db, returns_json, redirects |
| assign_teachers.php | Create Subjects | link | GET | create_subjects.php | admin | reads_db, writes_db, returns_json, redirects |
| assign_teachers.php | Dashboard | link | GET | admin_dashboard.php | admin | writes_db, redirects |
| assign_teachers.php | Logout | link | GET | logout.php | shared_or_unscoped | returns_json |
| assign_teachers.php | Manage Electives | link | GET | manage_electives.php | admin | reads_db, writes_db, redirects |
| assign_teachers.php | Manage Teachers | link | GET | manage_teachers.php | admin | reads_db, writes_db, redirects |
| assign_teachers.php | Manual Mailing | link | GET | test_mail.php | admin | reads_db, writes_db, returns_json, redirects |
| bulk_add_students.php | " target="_blank" rel="noopener" class="class-action-btn class-action-btn--ghost">View latest | link | GET | <?php echo htmlspecialchars($latest_timetable[ | external_or_dynamic | route_or_ui_only |
| bulk_add_students.php | &section_id= " class="class-action-btn class-action-btn--secondary"> Download class CSV | link | GET | download_class_students_template.php?class_id=<?php echo (int)$card[ | admin | reads_db, downloads_file, file_io |
| bulk_add_students.php | >Upload for All Classes | button_submit | POST | bulk_add_students.php | admin | reads_db, writes_db, returns_json, redirects, file_io |
| bulk_add_students.php | Academic Calendar | link | GET | manage_academic_calendar.php | admin | reads_db, writes_db, redirects |
| bulk_add_students.php | Add Student | button_submit | POST | bulk_add_students.php | admin | reads_db, writes_db, returns_json, redirects, file_io |
| bulk_add_students.php | Add Students | link | GET | bulk_add_students.php | admin | reads_db, writes_db, returns_json, redirects, file_io |
| bulk_add_students.php | Assign Teachers | link | GET | assign_teachers.php | admin | reads_db, writes_db, returns_json, redirects |
| bulk_add_students.php | Change Roles | link | GET | change_roles.php | admin | reads_db, writes_db, redirects |
| bulk_add_students.php | Copy Students | button_submit | POST | bulk_add_students.php | admin | reads_db, writes_db, returns_json, redirects, file_io |
| bulk_add_students.php | Create Classes | link | GET | create_classes.php | admin | reads_db, writes_db, returns_json, redirects |
| bulk_add_students.php | Create Subjects | link | GET | create_subjects.php | admin | reads_db, writes_db, returns_json, redirects |
| bulk_add_students.php | Dashboard | link | GET | admin_dashboard.php | admin | writes_db, redirects |
| bulk_add_students.php | Delete | button_submit | POST | bulk_add_students.php | admin | reads_db, writes_db, returns_json, redirects, file_io |
| bulk_add_students.php | Delete timetable | button_submit | POST | bulk_add_students.php | admin | reads_db, writes_db, returns_json, redirects, file_io |
| bulk_add_students.php | Download Template | link | GET | download_template.php | shared_or_unscoped | downloads_file |
| bulk_add_students.php | Link | link | GET | <?php echo htmlspecialchars($filePath); ?> | external_or_dynamic | route_or_ui_only |
| bulk_add_students.php | Logout | link | GET | logout.php | shared_or_unscoped | returns_json |
| bulk_add_students.php | Manage Electives | link | GET | manage_electives.php | admin | reads_db, writes_db, redirects |
| bulk_add_students.php | Manage Teachers | link | GET | manage_teachers.php | admin | reads_db, writes_db, redirects |
| bulk_add_students.php | Manual Mailing | link | GET | test_mail.php | admin | reads_db, writes_db, returns_json, redirects |
| bulk_add_students.php | Upload Students | button_submit | POST | bulk_add_students.php | admin | reads_db, writes_db, returns_json, redirects, file_io |
| change_roles.php | Academic Calendar | link | GET | manage_academic_calendar.php | admin | reads_db, writes_db, redirects |
| change_roles.php | Add Students | link | GET | bulk_add_students.php | admin | reads_db, writes_db, returns_json, redirects, file_io |
| change_roles.php | Assign Teachers | link | GET | assign_teachers.php | admin | reads_db, writes_db, returns_json, redirects |
| change_roles.php | Change Role | button_submit | POST | change_roles.php | admin | reads_db, writes_db, redirects |
| change_roles.php | Change Roles | link | GET | change_roles.php | admin | reads_db, writes_db, redirects |
| change_roles.php | Create Classes | link | GET | create_classes.php | admin | reads_db, writes_db, returns_json, redirects |
| change_roles.php | Create Subjects | link | GET | create_subjects.php | admin | reads_db, writes_db, returns_json, redirects |
| change_roles.php | Dashboard | link | GET | admin_dashboard.php | admin | writes_db, redirects |
| change_roles.php | Logout | link | GET | logout.php | shared_or_unscoped | returns_json |
| change_roles.php | Manage Electives | link | GET | manage_electives.php | admin | reads_db, writes_db, redirects |
| change_roles.php | Manage Teachers | link | GET | manage_teachers.php | admin | reads_db, writes_db, redirects |
| change_roles.php | Manual Mailing | link | GET | test_mail.php | admin | reads_db, writes_db, returns_json, redirects |
| create_classes.php | Academic Calendar | link | GET | manage_academic_calendar.php | admin | reads_db, writes_db, redirects |
| create_classes.php | Add Students | link | GET | bulk_add_students.php | admin | reads_db, writes_db, returns_json, redirects, file_io |
| create_classes.php | Assign Teachers | link | GET | assign_teachers.php | admin | reads_db, writes_db, returns_json, redirects |
| create_classes.php | Change Roles | link | GET | change_roles.php | admin | reads_db, writes_db, redirects |
| create_classes.php | Create Class | button_submit | POST | create_classes.php | admin | reads_db, writes_db, returns_json, redirects |
| create_classes.php | Create Classes | link | GET | create_classes.php | admin | reads_db, writes_db, returns_json, redirects |
| create_classes.php | Create Subjects | link | GET | create_subjects.php | admin | reads_db, writes_db, returns_json, redirects |
| create_classes.php | Dashboard | link | GET | admin_dashboard.php | admin | writes_db, redirects |
| create_classes.php | Logout | link | GET | logout.php | shared_or_unscoped | returns_json |
| create_classes.php | Manage Electives | link | GET | manage_electives.php | admin | reads_db, writes_db, redirects |
| create_classes.php | Manage Teachers | link | GET | manage_teachers.php | admin | reads_db, writes_db, redirects |
| create_classes.php | Manual Mailing | link | GET | test_mail.php | admin | reads_db, writes_db, returns_json, redirects |
| create_classes.php | Save Changes | button_submit | POST | create_classes.php | admin | reads_db, writes_db, returns_json, redirects |
| create_classes.php | Update | button_submit | POST | create_classes.php | admin | reads_db, writes_db, returns_json, redirects |
| create_subjects.php | Academic Calendar | link | GET | manage_academic_calendar.php | admin | reads_db, writes_db, redirects |
| create_subjects.php | Add Students | link | GET | bulk_add_students.php | admin | reads_db, writes_db, returns_json, redirects, file_io |
| create_subjects.php | Assign Teachers | link | GET | assign_teachers.php | admin | reads_db, writes_db, returns_json, redirects |
| create_subjects.php | Change Roles | link | GET | change_roles.php | admin | reads_db, writes_db, redirects |
| create_subjects.php | Create Classes | link | GET | create_classes.php | admin | reads_db, writes_db, returns_json, redirects |
| create_subjects.php | Create Subject | button_submit | POST | create_subjects.php | admin | reads_db, writes_db, returns_json, redirects |
| create_subjects.php | Create Subjects | link | GET | create_subjects.php | admin | reads_db, writes_db, returns_json, redirects |
| create_subjects.php | Dashboard | link | GET | admin_dashboard.php | admin | writes_db, redirects |
| create_subjects.php | Logout | link | GET | logout.php | shared_or_unscoped | returns_json |
| create_subjects.php | Manage Electives | link | GET | manage_electives.php | admin | reads_db, writes_db, redirects |
| create_subjects.php | Manage Teachers | link | GET | manage_teachers.php | admin | reads_db, writes_db, redirects |
| create_subjects.php | Manual Mailing | link | GET | test_mail.php | admin | reads_db, writes_db, returns_json, redirects |
| manage_academic_calendar.php | Academic Calendar | link | GET | manage_academic_calendar.php | admin | reads_db, writes_db, redirects |
| manage_academic_calendar.php | Add Students | link | GET | bulk_add_students.php | admin | reads_db, writes_db, returns_json, redirects, file_io |
| manage_academic_calendar.php | Assign Teachers | link | GET | assign_teachers.php | admin | reads_db, writes_db, returns_json, redirects |
| manage_academic_calendar.php | Change Roles | link | GET | change_roles.php | admin | reads_db, writes_db, redirects |
| manage_academic_calendar.php | Create Classes | link | GET | create_classes.php | admin | reads_db, writes_db, returns_json, redirects |
| manage_academic_calendar.php | Create Subjects | link | GET | create_subjects.php | admin | reads_db, writes_db, returns_json, redirects |
| manage_academic_calendar.php | Dashboard | link | GET | admin_dashboard.php | admin | writes_db, redirects |
| manage_academic_calendar.php | Logout | link | GET | logout.php | shared_or_unscoped | returns_json |
| manage_academic_calendar.php | Manage Electives | link | GET | manage_electives.php | admin | reads_db, writes_db, redirects |
| manage_academic_calendar.php | Manage Teachers | link | GET | manage_teachers.php | admin | reads_db, writes_db, redirects |
| manage_academic_calendar.php | Manual Mailing | link | GET | test_mail.php | admin | reads_db, writes_db, returns_json, redirects |
| manage_academic_calendar.php | Save Academic Calendar | button_submit | POST | manage_academic_calendar.php | admin | reads_db, writes_db, redirects |
| manage_electives.php | "> students | link | GET | <?php echo htmlspecialchars($electiveLink); ?> | external_or_dynamic | route_or_ui_only |
| manage_electives.php | 0 ? '' : 'disabled'; ?>>Load Students | button_submit | GET | manage_electives.php | admin | reads_db, writes_db, redirects |
| manage_electives.php | Academic Calendar | link | GET | manage_academic_calendar.php | admin | reads_db, writes_db, redirects |
| manage_electives.php | Add Students | link | GET | bulk_add_students.php | admin | reads_db, writes_db, returns_json, redirects, file_io |
| manage_electives.php | Assign Teachers | link | GET | assign_teachers.php | admin | reads_db, writes_db, returns_json, redirects |
| manage_electives.php | Change Roles | link | GET | change_roles.php | admin | reads_db, writes_db, redirects |
| manage_electives.php | Create Classes | link | GET | create_classes.php | admin | reads_db, writes_db, returns_json, redirects |
| manage_electives.php | Create Subjects | link | GET | create_subjects.php | admin | reads_db, writes_db, returns_json, redirects |
| manage_electives.php | Dashboard | link | GET | admin_dashboard.php | admin | writes_db, redirects |
| manage_electives.php | Logout | link | GET | logout.php | shared_or_unscoped | returns_json |
| manage_electives.php | Manage Electives | link | GET | manage_electives.php | admin | reads_db, writes_db, redirects |
| manage_electives.php | Manage Teachers | link | GET | manage_teachers.php | admin | reads_db, writes_db, redirects |
| manage_electives.php | Manual Mailing | link | GET | test_mail.php | admin | reads_db, writes_db, returns_json, redirects |
| manage_electives.php | Reset | link | GET | <?php echo htmlspecialchars($resetLink); ?> | external_or_dynamic | route_or_ui_only |
| manage_electives.php | Save Assignments | button_submit | POST | manage_electives.php | admin | reads_db, writes_db, redirects |
| manage_sections.php | Academic Calendar | link | GET | manage_academic_calendar.php | admin | reads_db, writes_db, redirects |
| manage_sections.php | Add Division | button_submit | POST | manage_sections.php | admin | reads_db, writes_db, redirects |
| manage_sections.php | Add Students | link | GET | bulk_add_students.php | admin | reads_db, writes_db, returns_json, redirects, file_io |
| manage_sections.php | Assign Teachers | link | GET | assign_teachers.php | admin | reads_db, writes_db, returns_json, redirects |
| manage_sections.php | Change Roles | link | GET | change_roles.php | admin | reads_db, writes_db, redirects |
| manage_sections.php | Create Classes | link | GET | create_classes.php | admin | reads_db, writes_db, returns_json, redirects |
| manage_sections.php | Create Subjects | link | GET | create_subjects.php | admin | reads_db, writes_db, returns_json, redirects |
| manage_sections.php | Dashboard | link | GET | admin_dashboard.php | admin | writes_db, redirects |
| manage_sections.php | Logout | link | GET | logout.php | shared_or_unscoped | returns_json |
| manage_sections.php | Manage Divisions | link | GET | manage_sections.php | admin | reads_db, writes_db, redirects |
| manage_sections.php | Manage Electives | link | GET | manage_electives.php | admin | reads_db, writes_db, redirects |
| manage_sections.php | Manage Teachers | link | GET | manage_teachers.php | admin | reads_db, writes_db, redirects |
| manage_sections.php | Manual Mailing | link | GET | test_mail.php | admin | reads_db, writes_db, returns_json, redirects |
| manage_teachers.php | Academic Calendar | link | GET | manage_academic_calendar.php | admin | reads_db, writes_db, redirects |
| manage_teachers.php | Add Students | link | GET | bulk_add_students.php | admin | reads_db, writes_db, returns_json, redirects, file_io |
| manage_teachers.php | Add Teacher | button_submit | POST | manage_teachers.php | admin | reads_db, writes_db, redirects |
| manage_teachers.php | Assign Teachers | link | GET | assign_teachers.php | admin | reads_db, writes_db, returns_json, redirects |
| manage_teachers.php | Change Roles | link | GET | change_roles.php | admin | reads_db, writes_db, redirects |
| manage_teachers.php | Create Classes | link | GET | create_classes.php | admin | reads_db, writes_db, returns_json, redirects |
| manage_teachers.php | Create Subjects | link | GET | create_subjects.php | admin | reads_db, writes_db, returns_json, redirects |
| manage_teachers.php | Dashboard | link | GET | admin_dashboard.php | admin | writes_db, redirects |
| manage_teachers.php | Deactivate Account | button_submit | POST | manage_teachers.php | admin | reads_db, writes_db, redirects |
| manage_teachers.php | Logout | link | GET | logout.php | shared_or_unscoped | returns_json |
| manage_teachers.php | Manage Electives | link | GET | manage_electives.php | admin | reads_db, writes_db, redirects |
| manage_teachers.php | Manage Teachers | link | GET | manage_teachers.php | admin | reads_db, writes_db, redirects |
| manage_teachers.php | Manual Mailing | link | GET | test_mail.php | admin | reads_db, writes_db, returns_json, redirects |
| manage_teachers.php | Reset Password | button_submit | POST | manage_teachers.php | admin | reads_db, writes_db, redirects |
| manage_teachers.php | Save Changes | button_submit | POST | manage_teachers.php | admin | reads_db, writes_db, redirects |
| test_mail.php | Academic Calendar | link | GET | manage_academic_calendar.php | admin | reads_db, writes_db, redirects |
| test_mail.php | Add Students | link | GET | bulk_add_students.php | admin | reads_db, writes_db, returns_json, redirects, file_io |
| test_mail.php | Assign Teachers | link | GET | assign_teachers.php | admin | reads_db, writes_db, returns_json, redirects |
| test_mail.php | Change Roles | link | GET | change_roles.php | admin | reads_db, writes_db, redirects |
| test_mail.php | Create Classes | link | GET | create_classes.php | admin | reads_db, writes_db, returns_json, redirects |
| test_mail.php | Create Subjects | link | GET | create_subjects.php | admin | reads_db, writes_db, returns_json, redirects |
| test_mail.php | Dashboard | link | GET | admin_dashboard.php | admin | writes_db, redirects |
| test_mail.php | Logout | link | GET | logout.php | shared_or_unscoped | returns_json |
| test_mail.php | Manage Electives | link | GET | manage_electives.php | admin | reads_db, writes_db, redirects |
| test_mail.php | Manage Teachers | link | GET | manage_teachers.php | admin | reads_db, writes_db, redirects |
| test_mail.php | Send Test Mail | button_submit | POST | test_mail.php | admin | reads_db, writes_db, returns_json, redirects |
| test_mail.php | Test Mail | link | GET | test_mail.php | admin | reads_db, writes_db, returns_json, redirects |

### Role: program_chair
| From Page | Option/Button | Click Type | Method | Goes To | Target Role Scope | What Happens |
|---|---|---|---|---|---|---|
| course_progress.php | Alerts | link | GET | send_alerts.php | program_chair | reads_db, writes_db, redirects |
| course_progress.php | Apply Filters | button_submit | GET | course_progress.php | program_chair | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| course_progress.php | Courses | link | GET | course_progress.php | program_chair | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| course_progress.php | 'csv']))); ?>" class="theme-toggle" style="text-decoration:none; display:inline-flex; align-items:center;"> Export CSV | link | GET | <?php echo htmlspecialchars($_SERVER[ | external_or_dynamic | route_or_ui_only |
| course_progress.php | Dashboard | link | GET | program_dashboard.php | program_chair | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| course_progress.php | Edit Profile | link | GET | edit_profile.php | shared_or_unscoped | reads_db, writes_db, redirects |
| course_progress.php | Logout | link | GET | logout.php | shared_or_unscoped | returns_json |
| course_progress.php | Reports | link | GET | program_reports.php | program_chair | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| course_progress.php | Reset | link | GET | course_progress.php | program_chair | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| course_progress.php | Settings | link | GET | settings.php | program_chair | reads_db, writes_db, redirects |
| course_progress.php | Students | link | GET | student_progress.php | program_chair | reads_db, returns_json, redirects, downloads_file, file_io |
| course_progress.php | Switch to Teacher | link | GET | login_as.php?role=teacher | shared_or_unscoped | reads_db, writes_db, redirects |
| course_progress.php | Teachers | link | GET | teacher_progress.php | program_chair | reads_db, writes_db, returns_json, redirects |
| program_dashboard.php | "> Active Teachers | link | GET | teacher_progress.php<?php echo $card_link_query !== | external_or_dynamic | route_or_ui_only |
| program_dashboard.php | Academic Week | link | GET | <?php echo htmlspecialchars($week_link); ?> | external_or_dynamic | route_or_ui_only |
| program_dashboard.php | Alerts | link | GET | send_alerts.php | program_chair | reads_db, writes_db, redirects |
| program_dashboard.php | Apply Filters | button_submit | GET | program_dashboard.php | program_chair | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| program_dashboard.php | Avg Syllabus % | link | GET | <?php echo htmlspecialchars($course_progress_link); ?> | external_or_dynamic | route_or_ui_only |
| program_dashboard.php | Courses | link | GET | course_progress.php | program_chair | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| program_dashboard.php | Dashboard | link | GET | program_dashboard.php | program_chair | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| program_dashboard.php | Edit Profile | link | GET | edit_profile.php | shared_or_unscoped | reads_db, writes_db, redirects |
| program_dashboard.php | Logout | link | GET | logout.php | shared_or_unscoped | returns_json |
| program_dashboard.php | Low Performing | link | GET | <?php echo htmlspecialchars($student_at_risk_link); ?> | external_or_dynamic | route_or_ui_only |
| program_dashboard.php | Pending Alerts | link | GET | <?php echo htmlspecialchars($alerts_link); ?> | external_or_dynamic | route_or_ui_only |
| program_dashboard.php | Reports | link | GET | program_reports.php | program_chair | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| program_dashboard.php | Settings | link | GET | settings.php | program_chair | reads_db, writes_db, redirects |
| program_dashboard.php | Students | link | GET | student_progress.php | program_chair | reads_db, returns_json, redirects, downloads_file, file_io |
| program_dashboard.php | Students In Scope | link | GET | <?php echo htmlspecialchars($students_page_link); ?> | external_or_dynamic | route_or_ui_only |
| program_dashboard.php | Switch to Teacher | link | GET | login_as.php?role=teacher | shared_or_unscoped | reads_db, writes_db, redirects |
| program_dashboard.php | Teachers | link | GET | teacher_progress.php | program_chair | reads_db, writes_db, returns_json, redirects |
| program_dashboard.php | Total Courses | link | GET | <?php echo htmlspecialchars($course_progress_link); ?> | external_or_dynamic | route_or_ui_only |
| program_reports.php | " data-teacher-name=" ">View | link | GET | # | external_or_dynamic | route_or_ui_only |
| program_reports.php | Alerts | link | GET | send_alerts.php | program_chair | reads_db, writes_db, redirects |
| program_reports.php | Apply Filters | button_submit | GET | program_reports.php | program_chair | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| program_reports.php | Courses | link | GET | course_progress.php | program_chair | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| program_reports.php | Dashboard | link | GET | program_dashboard.php | program_chair | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| program_reports.php | Download CSV | link | GET | <?php echo htmlspecialchars($csv_export_url); ?> | external_or_dynamic | route_or_ui_only |
| program_reports.php | Download PDF | link | GET | <?php echo htmlspecialchars($pdf_export_url); ?> | external_or_dynamic | route_or_ui_only |
| program_reports.php | Edit Profile | link | GET | edit_profile.php | shared_or_unscoped | reads_db, writes_db, redirects |
| program_reports.php | Logout | link | GET | logout.php | shared_or_unscoped | returns_json |
| program_reports.php | Reports | link | GET | program_reports.php | program_chair | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| program_reports.php | Settings | link | GET | settings.php | program_chair | reads_db, writes_db, redirects |
| program_reports.php | Students | link | GET | student_progress.php | program_chair | reads_db, returns_json, redirects, downloads_file, file_io |
| program_reports.php | Switch to Teacher | link | GET | login_as.php?role=teacher | shared_or_unscoped | reads_db, writes_db, redirects |
| program_reports.php | Teachers | link | GET | teacher_progress.php | program_chair | reads_db, writes_db, returns_json, redirects |
| send_alerts.php | Alerts | link | GET | send_alerts.php | program_chair | reads_db, writes_db, redirects |
| send_alerts.php | Back to Dashboard | link | GET | program_dashboard.php | program_chair | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| send_alerts.php | Courses | link | GET | course_progress.php | program_chair | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| send_alerts.php | Dashboard | link | GET | program_dashboard.php | program_chair | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| send_alerts.php | Edit Profile | link | GET | edit_profile.php | shared_or_unscoped | reads_db, writes_db, redirects |
| send_alerts.php | Logout | link | GET | logout.php | shared_or_unscoped | returns_json |
| send_alerts.php | Reports | link | GET | program_reports.php | program_chair | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| send_alerts.php | Send Alert | button_submit | POST | send_alerts.php | program_chair | reads_db, writes_db, redirects |
| send_alerts.php | Settings | link | GET | settings.php | program_chair | reads_db, writes_db, redirects |
| send_alerts.php | Students | link | GET | student_progress.php | program_chair | reads_db, returns_json, redirects, downloads_file, file_io |
| send_alerts.php | Switch to Teacher | link | GET | login_as.php?role=teacher | shared_or_unscoped | reads_db, writes_db, redirects |
| send_alerts.php | Teachers | link | GET | teacher_progress.php | program_chair | reads_db, writes_db, returns_json, redirects |
| settings.php | Alerts | link | GET | send_alerts.php | program_chair | reads_db, writes_db, redirects |
| settings.php | Courses | link | GET | course_progress.php | program_chair | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| settings.php | Dashboard | link | GET | program_dashboard.php | program_chair | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| settings.php | Edit Profile | link | GET | edit_profile.php | shared_or_unscoped | reads_db, writes_db, redirects |
| settings.php | Logout | link | GET | logout.php | shared_or_unscoped | returns_json |
| settings.php | Reports | link | GET | program_reports.php | program_chair | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| settings.php | Save Settings | button_submit | POST | settings.php | program_chair | reads_db, writes_db, redirects |
| settings.php | Settings | link | GET | settings.php | program_chair | reads_db, writes_db, redirects |
| settings.php | Students | link | GET | student_progress.php | program_chair | reads_db, returns_json, redirects, downloads_file, file_io |
| settings.php | Switch to Teacher | link | GET | login_as.php?role=teacher | shared_or_unscoped | reads_db, writes_db, redirects |
| settings.php | Teachers | link | GET | teacher_progress.php | program_chair | reads_db, writes_db, returns_json, redirects |
| student_progress.php | Alerts | link | GET | send_alerts.php | program_chair | reads_db, writes_db, redirects |
| student_progress.php | Apply Filters | button_submit | GET | student_progress.php | program_chair | reads_db, returns_json, redirects, downloads_file, file_io |
| student_progress.php | Courses | link | GET | course_progress.php | program_chair | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| student_progress.php | Dashboard | link | GET | program_dashboard.php | program_chair | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| student_progress.php | Edit Profile | link | GET | edit_profile.php | shared_or_unscoped | reads_db, writes_db, redirects |
| student_progress.php | Logout | link | GET | logout.php | shared_or_unscoped | returns_json |
| student_progress.php | Reports | link | GET | program_reports.php | program_chair | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| student_progress.php | Save Changes | button_submit | POST | edit_student.php | admin | writes_db, redirects |
| student_progress.php | Settings | link | GET | settings.php | program_chair | reads_db, writes_db, redirects |
| student_progress.php | Students | link | GET | student_progress.php | program_chair | reads_db, returns_json, redirects, downloads_file, file_io |
| student_progress.php | Switch to Teacher | link | GET | login_as.php?role=teacher | shared_or_unscoped | reads_db, writes_db, redirects |
| student_progress.php | Teachers | link | GET | teacher_progress.php | program_chair | reads_db, writes_db, returns_json, redirects |
| teacher_progress.php | " data-teacher-name=" ">View | link | GET | # | external_or_dynamic | route_or_ui_only |
| teacher_progress.php | Alerts | link | GET | send_alerts.php | program_chair | reads_db, writes_db, redirects |
| teacher_progress.php | Apply filters | button_submit | GET | teacher_progress.php | program_chair | reads_db, writes_db, returns_json, redirects |
| teacher_progress.php | Courses | link | GET | course_progress.php | program_chair | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| teacher_progress.php | Dashboard | link | GET | program_dashboard.php | program_chair | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| teacher_progress.php | Edit Profile | link | GET | edit_profile.php | shared_or_unscoped | reads_db, writes_db, redirects |
| teacher_progress.php | Logout | link | GET | logout.php | shared_or_unscoped | returns_json |
| teacher_progress.php | Reports | link | GET | program_reports.php | program_chair | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| teacher_progress.php | Reset filters | link | GET | teacher_progress.php | program_chair | reads_db, writes_db, returns_json, redirects |
| teacher_progress.php | Settings | link | GET | settings.php | program_chair | reads_db, writes_db, redirects |
| teacher_progress.php | Students | link | GET | student_progress.php | program_chair | reads_db, returns_json, redirects, downloads_file, file_io |
| teacher_progress.php | Switch to Teacher | link | GET | login_as.php?role=teacher | shared_or_unscoped | reads_db, writes_db, redirects |
| teacher_progress.php | Teachers | link | GET | teacher_progress.php | program_chair | reads_db, writes_db, returns_json, redirects |
| teacher_progress.php | View | link | GET | ${t.file_path} | external_or_dynamic | route_or_ui_only |
| track_teachers.php | "> | link | GET | ?teacher_id=<?php echo (int)$teacher[ | external_or_dynamic | route_or_ui_only |
| track_teachers.php | Back to Dashboard | link | GET | program_dashboard.php | program_chair | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |

### Role: teacher
| From Page | Option/Button | Click Type | Method | Goes To | Target Role Scope | What Happens |
|---|---|---|---|---|---|---|
| alerts.php | Back to Dashboard | link | GET | teacher_dashboard.php | teacher | reads_db, writes_db, returns_json, redirects |
| alerts.php | Respond | button_submit | POST | alerts.php | teacher | reads_db, writes_db, redirects |
| assignments.php | " target="_blank">Feedback file | link | GET | <?php echo htmlspecialchars($submission[ | external_or_dynamic | route_or_ui_only |
| assignments.php | " target="_blank">Student file | link | GET | <?php echo htmlspecialchars($submission[ | external_or_dynamic | route_or_ui_only |
| assignments.php | Assignments | link | GET | assignments.php | teacher | reads_db, writes_db, returns_json, redirects, file_io |
| assignments.php | Dashboard | link | GET | teacher_dashboard.php | teacher | reads_db, writes_db, returns_json, redirects |
| assignments.php | Download brief | link | GET | <?php echo htmlspecialchars($instructionsFile); ?> | external_or_dynamic | route_or_ui_only |
| assignments.php | Edit Profile | link | GET | edit_profile.php | shared_or_unscoped | reads_db, writes_db, redirects |
| assignments.php | ICA Components | link | GET | create_ica_components.php | teacher | reads_db, writes_db, returns_json, redirects |
| assignments.php | Logout | link | GET | logout.php | shared_or_unscoped | returns_json |
| assignments.php | Manage ICA Marks | link | GET | manage_ica_marks.php | teacher | reads_db, writes_db, redirects, downloads_file, file_io |
| assignments.php | Publish Assignment | button_submit | POST | assignments.php | teacher | reads_db, writes_db, returns_json, redirects, file_io |
| assignments.php | Save Changes | button_submit | POST | assignments.php | teacher | reads_db, writes_db, returns_json, redirects, file_io |
| assignments.php | Switch to Program Chair | link | GET | login_as.php?role=program_chair | shared_or_unscoped | reads_db, writes_db, redirects |
| assignments.php | Timetable | link | GET | timetable.php | teacher | reads_db, writes_db, redirects, file_io |
| assignments.php | Update | button_submit | POST | assignments.php | teacher | reads_db, writes_db, returns_json, redirects, file_io |
| assignments.php | Update Progress | link | GET | update_progress.php | teacher | reads_db, writes_db, returns_json, redirects |
| assignments.php | View | link | GET | assignments.php?assignment_id=<?php echo $rowId; ?> | teacher | reads_db, writes_db, returns_json, redirects, file_io |
| assignments.php | View Alerts | link | GET | view_alerts.php | teacher | reads_db, writes_db, redirects |
| assignments.php | View Reports | link | GET | view_reports.php | teacher | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| create_ica_components.php | Assignments | link | GET | assignments.php | teacher | reads_db, writes_db, returns_json, redirects, file_io |
| create_ica_components.php | Copy Components | button_submit | POST | create_ica_components.php | teacher | reads_db, writes_db, returns_json, redirects |
| create_ica_components.php | Dashboard | link | GET | teacher_dashboard.php | teacher | reads_db, writes_db, returns_json, redirects |
| create_ica_components.php | Edit Components | link | GET | # | external_or_dynamic | route_or_ui_only |
| create_ica_components.php | Edit Profile | link | GET | edit_profile.php | shared_or_unscoped | reads_db, writes_db, redirects |
| create_ica_components.php | ICA Components | link | GET | create_ica_components.php | teacher | reads_db, writes_db, returns_json, redirects |
| create_ica_components.php | Logout | link | GET | logout.php | shared_or_unscoped | returns_json |
| create_ica_components.php | Manage ICA Marks | link | GET | manage_ica_marks.php | teacher | reads_db, writes_db, redirects, downloads_file, file_io |
| create_ica_components.php | Save All Components | button_submit | POST | create_ica_components.php | teacher | reads_db, writes_db, returns_json, redirects |
| create_ica_components.php | Switch to Program Chair | link | GET | login_as.php?role=program_chair | shared_or_unscoped | reads_db, writes_db, redirects |
| create_ica_components.php | Timetable | link | GET | timetable.php | teacher | reads_db, writes_db, redirects, file_io |
| create_ica_components.php | Update Progress | link | GET | update_progress.php | teacher | reads_db, writes_db, returns_json, redirects |
| create_ica_components.php | View Alerts | link | GET | view_alerts.php | teacher | reads_db, writes_db, redirects |
| create_ica_components.php | View Reports | link | GET | view_reports.php | teacher | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| manage_ica_marks.php | Assignments | link | GET | assignments.php | teacher | reads_db, writes_db, returns_json, redirects, file_io |
| manage_ica_marks.php | Dashboard | link | GET | teacher_dashboard.php | teacher | reads_db, writes_db, returns_json, redirects |
| manage_ica_marks.php | Edit Profile | link | GET | edit_profile.php | shared_or_unscoped | reads_db, writes_db, redirects |
| manage_ica_marks.php | ICA Components | link | GET | create_ica_components.php | teacher | reads_db, writes_db, returns_json, redirects |
| manage_ica_marks.php | Logout | link | GET | logout.php | shared_or_unscoped | returns_json |
| manage_ica_marks.php | Manage ICA Marks | link | GET | manage_ica_marks.php | teacher | reads_db, writes_db, redirects, downloads_file, file_io |
| manage_ica_marks.php | Save Marks | button_submit | POST | manage_ica_marks.php | teacher | reads_db, writes_db, redirects, downloads_file, file_io |
| manage_ica_marks.php | Switch to Program Chair | link | GET | login_as.php?role=program_chair | shared_or_unscoped | reads_db, writes_db, redirects |
| manage_ica_marks.php | Timetable | link | GET | timetable.php | teacher | reads_db, writes_db, redirects, file_io |
| manage_ica_marks.php | Update Progress | link | GET | update_progress.php | teacher | reads_db, writes_db, returns_json, redirects |
| manage_ica_marks.php | Upload CSV | button_submit | POST | manage_ica_marks.php | teacher | reads_db, writes_db, redirects, downloads_file, file_io |
| manage_ica_marks.php | View Alerts | link | GET | view_alerts.php | teacher | reads_db, writes_db, redirects |
| manage_ica_marks.php | View Reports | link | GET | view_reports.php | teacher | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| reports.php | Assignments | link | GET | assignments.php | teacher | reads_db, writes_db, returns_json, redirects, file_io |
| reports.php | Dashboard | link | GET | teacher_dashboard.php | teacher | reads_db, writes_db, returns_json, redirects |
| reports.php | Edit Profile | link | GET | edit_profile.php | shared_or_unscoped | reads_db, writes_db, redirects |
| reports.php | Export to PDF | link | GET | ?export=pdf | external_or_dynamic | route_or_ui_only |
| reports.php | ICA Components | link | GET | create_ica_components.php | teacher | reads_db, writes_db, returns_json, redirects |
| reports.php | Logout | link | GET | logout.php | shared_or_unscoped | returns_json |
| reports.php | Manage ICA Marks | link | GET | manage_ica_marks.php | teacher | reads_db, writes_db, redirects, downloads_file, file_io |
| reports.php | Timetable | link | GET | timetable.php | teacher | reads_db, writes_db, redirects, file_io |
| reports.php | Update Progress | link | GET | update_progress.php | teacher | reads_db, writes_db, returns_json, redirects |
| reports.php | View Alerts | link | GET | view_alerts.php | teacher | reads_db, writes_db, redirects |
| reports.php | View Reports | link | GET | view_reports.php | teacher | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| teacher_dashboard.php | " style="display:block; text-decoration:none; color:inherit;"> Received on | link | GET | view_alerts.php?id=<?php echo (int)$alert[ | teacher | reads_db, writes_db, redirects |
| teacher_dashboard.php | Assignments | link | GET | assignments.php | teacher | reads_db, writes_db, returns_json, redirects, file_io |
| teacher_dashboard.php | Dashboard | link | GET | teacher_dashboard.php | teacher | reads_db, writes_db, returns_json, redirects |
| teacher_dashboard.php | Edit Profile | link | GET | edit_profile.php | shared_or_unscoped | reads_db, writes_db, redirects |
| teacher_dashboard.php | ICA Components | link | GET | create_ica_components.php | teacher | reads_db, writes_db, returns_json, redirects |
| teacher_dashboard.php | Logout | link | GET | logout.php | shared_or_unscoped | returns_json |
| teacher_dashboard.php | Manage ICA Marks | link | GET | manage_ica_marks.php | teacher | reads_db, writes_db, redirects, downloads_file, file_io |
| teacher_dashboard.php | Switch to Program Chair | link | GET | login_as.php?role=program_chair | shared_or_unscoped | reads_db, writes_db, redirects |
| teacher_dashboard.php | Timetable | link | GET | timetable.php | teacher | reads_db, writes_db, redirects, file_io |
| teacher_dashboard.php | Update Progress | link | GET | update_progress.php | teacher | reads_db, writes_db, returns_json, redirects |
| teacher_dashboard.php | Update Progress | link | GET | update_progress.php?subject=${encodeURIComponent(subjectNameRaw)}&class_id=${classId}&section_id=${sectionId}&assignment_key=${encodeURIComponent(assignmentKey)} | teacher | reads_db, writes_db, returns_json, redirects |
| teacher_dashboard.php | View Alerts | link | GET | view_alerts.php | teacher | reads_db, writes_db, redirects |
| teacher_dashboard.php | View all | link | GET | view_alerts.php | teacher | reads_db, writes_db, redirects |
| teacher_dashboard.php | View Reports | link | GET | view_reports.php | teacher | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| timetable.php | " class="btn" style="background-color: #0d6efd;">Edit | link | GET | timetable.php?edit_id=<?php echo (int)$row[ | teacher | reads_db, writes_db, redirects, file_io |
| timetable.php | " class="btn" style="background-color: #dc3545;" onclick="return confirm('Are you sure you want to delete this timetable?');">Delete | link | GET | timetable.php?delete_id=<?php echo (int)$row[ | teacher | reads_db, writes_db, redirects, file_io |
| timetable.php | " target="_blank" class="btn">View | link | GET | <?php echo htmlspecialchars($row[ | external_or_dynamic | route_or_ui_only |
| timetable.php | Assignments | link | GET | assignments.php | teacher | reads_db, writes_db, returns_json, redirects, file_io |
| timetable.php | Cancel | link | GET | timetable.php | teacher | reads_db, writes_db, redirects, file_io |
| timetable.php | Dashboard | link | GET | teacher_dashboard.php | teacher | reads_db, writes_db, returns_json, redirects |
| timetable.php | Edit Profile | link | GET | edit_profile.php | shared_or_unscoped | reads_db, writes_db, redirects |
| timetable.php | ICA Components | link | GET | create_ica_components.php | teacher | reads_db, writes_db, returns_json, redirects |
| timetable.php | Logout | link | GET | logout.php | shared_or_unscoped | returns_json |
| timetable.php | Manage ICA Marks | link | GET | manage_ica_marks.php | teacher | reads_db, writes_db, redirects, downloads_file, file_io |
| timetable.php | Save Changes | button_submit | POST | timetable.php | teacher | reads_db, writes_db, redirects, file_io |
| timetable.php | Switch to Program Chair | link | GET | login_as.php?role=program_chair | shared_or_unscoped | reads_db, writes_db, redirects |
| timetable.php | Timetable | link | GET | timetable.php | teacher | reads_db, writes_db, redirects, file_io |
| timetable.php | Update Progress | link | GET | update_progress.php | teacher | reads_db, writes_db, returns_json, redirects |
| timetable.php | Upload | button_submit | POST | timetable.php | teacher | reads_db, writes_db, redirects, file_io |
| timetable.php | View Alerts | link | GET | view_alerts.php | teacher | reads_db, writes_db, redirects |
| timetable.php | View Reports | link | GET | view_reports.php | teacher | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| update_progress.php | >Update Progress | button_submit | POST | update_progress.php | teacher | reads_db, writes_db, returns_json, redirects |
| update_progress.php | Assignments | link | GET | assignments.php | teacher | reads_db, writes_db, returns_json, redirects, file_io |
| update_progress.php | Dashboard | link | GET | teacher_dashboard.php | teacher | reads_db, writes_db, returns_json, redirects |
| update_progress.php | Edit Profile | link | GET | edit_profile.php | shared_or_unscoped | reads_db, writes_db, redirects |
| update_progress.php | ICA Components | link | GET | create_ica_components.php | teacher | reads_db, writes_db, returns_json, redirects |
| update_progress.php | Logout | link | GET | logout.php | shared_or_unscoped | returns_json |
| update_progress.php | Manage ICA Marks | link | GET | manage_ica_marks.php | teacher | reads_db, writes_db, redirects, downloads_file, file_io |
| update_progress.php | Switch to Program Chair | link | GET | login_as.php?role=program_chair | shared_or_unscoped | reads_db, writes_db, redirects |
| update_progress.php | Timetable | link | GET | timetable.php | teacher | reads_db, writes_db, redirects, file_io |
| update_progress.php | Update Progress | link | GET | update_progress.php | teacher | reads_db, writes_db, returns_json, redirects |
| update_progress.php | View Alerts | link | GET | view_alerts.php | teacher | reads_db, writes_db, redirects |
| update_progress.php | View Reports | link | GET | view_reports.php | teacher | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| view_alerts.php | Assignments | link | GET | assignments.php | teacher | reads_db, writes_db, returns_json, redirects, file_io |
| view_alerts.php | Back to Dashboard | link | GET | teacher_dashboard.php | teacher | reads_db, writes_db, returns_json, redirects |
| view_alerts.php | Dashboard | link | GET | teacher_dashboard.php | teacher | reads_db, writes_db, returns_json, redirects |
| view_alerts.php | Edit Profile | link | GET | edit_profile.php | shared_or_unscoped | reads_db, writes_db, redirects |
| view_alerts.php | ICA Components | link | GET | create_ica_components.php | teacher | reads_db, writes_db, returns_json, redirects |
| view_alerts.php | Logout | link | GET | logout.php | shared_or_unscoped | returns_json |
| view_alerts.php | Manage ICA Marks | link | GET | manage_ica_marks.php | teacher | reads_db, writes_db, redirects, downloads_file, file_io |
| view_alerts.php | Respond | button_submit | POST | view_alerts.php | teacher | reads_db, writes_db, redirects |
| view_alerts.php | Switch to Program Chair | link | GET | login_as.php?role=program_chair | shared_or_unscoped | reads_db, writes_db, redirects |
| view_alerts.php | Timetable | link | GET | timetable.php | teacher | reads_db, writes_db, redirects, file_io |
| view_alerts.php | Update Progress | link | GET | update_progress.php | teacher | reads_db, writes_db, returns_json, redirects |
| view_alerts.php | View Alerts | link | GET | view_alerts.php | teacher | reads_db, writes_db, redirects |
| view_alerts.php | View Reports | link | GET | view_reports.php | teacher | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| view_reports.php | ">Show all students | link | GET | <?php echo htmlspecialchars(removeQueryParams([ | external_or_dynamic | route_or_ui_only |
| view_reports.php | Assignments | link | GET | assignments.php | teacher | reads_db, writes_db, returns_json, redirects, file_io |
| view_reports.php | Dashboard | link | GET | teacher_dashboard.php | teacher | reads_db, writes_db, returns_json, redirects |
| view_reports.php | Download CSV | link | GET | ?export=csv_summary&subject_id=<?php echo $selected_subject; ?>&class_id=<?php echo $selected_class; ?> | external_or_dynamic | route_or_ui_only |
| view_reports.php | Edit Profile | link | GET | edit_profile.php | shared_or_unscoped | reads_db, writes_db, redirects |
| view_reports.php | ICA Components | link | GET | create_ica_components.php | teacher | reads_db, writes_db, returns_json, redirects |
| view_reports.php | Logout | link | GET | logout.php | shared_or_unscoped | returns_json |
| view_reports.php | Manage ICA Marks | link | GET | manage_ica_marks.php | teacher | reads_db, writes_db, redirects, downloads_file, file_io |
| view_reports.php | Switch to Program Chair | link | GET | login_as.php?role=program_chair | shared_or_unscoped | reads_db, writes_db, redirects |
| view_reports.php | Timetable | link | GET | timetable.php | teacher | reads_db, writes_db, redirects, file_io |
| view_reports.php | Update Progress | link | GET | update_progress.php | teacher | reads_db, writes_db, returns_json, redirects |
| view_reports.php | View Alerts | link | GET | view_alerts.php | teacher | reads_db, writes_db, redirects |
| view_reports.php | View Reports | link | GET | view_reports.php | teacher | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |

### Role: student
| From Page | Option/Button | Click Type | Method | Goes To | Target Role Scope | What Happens |
|---|---|---|---|---|---|---|
| student_dashboard.php | " download>Download | link | GET | <?php echo htmlspecialchars($file[ | external_or_dynamic | route_or_ui_only |
| student_dashboard.php | 0 ? '' : ' success'; ?>" title="Open Subject Comparison"> At-risk subjects 0 ? 'Check announcements.' : 'All subjects on track.'; ?> | link | GET | subject_comparison.php | student | reads_db, writes_db, returns_json, redirects |
| student_dashboard.php | Assignments | link | GET | view_assignment_marks.php | student | reads_db, writes_db, redirects, file_io |
| student_dashboard.php | Assignments completed Keep going. | link | GET | view_assignment_marks.php | student | reads_db, writes_db, redirects, file_io |
| student_dashboard.php | Dashboard | link | GET | student_dashboard.php | student | reads_db, returns_json, redirects |
| student_dashboard.php | Edit Profile | link | GET | edit_profile.php | shared_or_unscoped | reads_db, writes_db, redirects |
| student_dashboard.php | Logout | link | GET | logout.php | shared_or_unscoped | returns_json |
| student_dashboard.php | Marks | link | GET | view_marks.php | student | reads_db, writes_db, redirects |
| student_dashboard.php | Open full view | link | GET | subject_comparison.php | student | reads_db, writes_db, returns_json, redirects |
| student_dashboard.php | Overall performance % components evaluated. | link | GET | view_marks.php | student | reads_db, writes_db, redirects |
| student_dashboard.php | Subject Comparison | link | GET | subject_comparison.php | student | reads_db, writes_db, returns_json, redirects |
| student_dashboard.php | Subjects enrolled pending. | link | GET | view_progress.php | student | reads_db, writes_db, returns_json, redirects |
| student_dashboard.php | Syllabus Progress | link | GET | view_progress.php | student | reads_db, writes_db, returns_json, redirects |
| student_dashboard.php | Timetable | link | GET | view_timetable.php | shared_or_unscoped | reads_db, redirects |
| student_dashboard.php | View all | link | GET | view_assignment_marks.php | student | reads_db, writes_db, redirects, file_io |
| student_dashboard.php | View all | link | GET | view_timetable.php | shared_or_unscoped | reads_db, redirects |
| student_dashboard.php | View assignments | link | GET | view_assignment_marks.php | student | reads_db, writes_db, redirects, file_io |
| subject_comparison.php | Apply Filter | button_submit | GET | subject_comparison.php | student | reads_db, writes_db, returns_json, redirects |
| subject_comparison.php | Assignments | link | GET | view_assignment_marks.php | student | reads_db, writes_db, redirects, file_io |
| subject_comparison.php | Back to dashboard | link | GET | student_dashboard.php | student | reads_db, returns_json, redirects |
| subject_comparison.php | Clear Filters | link | GET | subject_comparison.php | student | reads_db, writes_db, returns_json, redirects |
| subject_comparison.php | Dashboard | link | GET | student_dashboard.php | student | reads_db, returns_json, redirects |
| subject_comparison.php | Edit Profile | link | GET | edit_profile.php | shared_or_unscoped | reads_db, writes_db, redirects |
| subject_comparison.php | Logout | link | GET | logout.php | shared_or_unscoped | returns_json |
| subject_comparison.php | Marks | link | GET | view_marks.php | student | reads_db, writes_db, redirects |
| subject_comparison.php | Subject Comparison | link | GET | subject_comparison.php | student | reads_db, writes_db, returns_json, redirects |
| subject_comparison.php | Syllabus Progress | link | GET | view_progress.php | student | reads_db, writes_db, returns_json, redirects |
| subject_comparison.php | Timetable | link | GET | view_timetable.php | shared_or_unscoped | reads_db, redirects |
| view_assignment_marks.php | " target="_blank">Download | link | GET | <?php echo htmlspecialchars($assignment[ | external_or_dynamic | route_or_ui_only |
| view_assignment_marks.php | " target="_blank">View submission | link | GET | <?php echo htmlspecialchars($assignment[ | external_or_dynamic | route_or_ui_only |
| view_assignment_marks.php | Assignments | link | GET | view_assignment_marks.php | student | reads_db, writes_db, redirects, file_io |
| view_assignment_marks.php | Dashboard | link | GET | student_dashboard.php | student | reads_db, returns_json, redirects |
| view_assignment_marks.php | Edit Profile | link | GET | edit_profile.php | shared_or_unscoped | reads_db, writes_db, redirects |
| view_assignment_marks.php | Logout | link | GET | logout.php | shared_or_unscoped | returns_json |
| view_assignment_marks.php | Marks | link | GET | view_marks.php | student | reads_db, writes_db, redirects |
| view_assignment_marks.php | Subject Comparison | link | GET | subject_comparison.php | student | reads_db, writes_db, returns_json, redirects |
| view_assignment_marks.php | Submit | button_submit | POST | view_assignment_marks.php | student | reads_db, writes_db, redirects, file_io |
| view_assignment_marks.php | Syllabus Progress | link | GET | view_progress.php | student | reads_db, writes_db, returns_json, redirects |
| view_assignment_marks.php | Timetable | link | GET | view_timetable.php | shared_or_unscoped | reads_db, redirects |
| view_marks.php | Assignments | link | GET | view_assignment_marks.php | student | reads_db, writes_db, redirects, file_io |
| view_marks.php | Dashboard | link | GET | student_dashboard.php | student | reads_db, returns_json, redirects |
| view_marks.php | Edit Profile | link | GET | edit_profile.php | shared_or_unscoped | reads_db, writes_db, redirects |
| view_marks.php | Logout | link | GET | logout.php | shared_or_unscoped | returns_json |
| view_marks.php | Marks | link | GET | view_marks.php | student | reads_db, writes_db, redirects |
| view_marks.php | Subject Comparison | link | GET | subject_comparison.php | student | reads_db, writes_db, returns_json, redirects |
| view_marks.php | Syllabus Progress | link | GET | view_progress.php | student | reads_db, writes_db, returns_json, redirects |
| view_marks.php | Timetable | link | GET | view_timetable.php | shared_or_unscoped | reads_db, redirects |
| view_progress.php | Assignments | link | GET | view_assignment_marks.php | student | reads_db, writes_db, redirects, file_io |
| view_progress.php | Dashboard | link | GET | student_dashboard.php | student | reads_db, returns_json, redirects |
| view_progress.php | Edit Profile | link | GET | edit_profile.php | shared_or_unscoped | reads_db, writes_db, redirects |
| view_progress.php | Logout | link | GET | logout.php | shared_or_unscoped | returns_json |
| view_progress.php | Marks | link | GET | view_marks.php | student | reads_db, writes_db, redirects |
| view_progress.php | Subject Comparison | link | GET | subject_comparison.php | student | reads_db, writes_db, returns_json, redirects |
| view_progress.php | Syllabus Progress | link | GET | view_progress.php | student | reads_db, writes_db, returns_json, redirects |
| view_progress.php | Timetable | link | GET | view_timetable.php | shared_or_unscoped | reads_db, redirects |

### Role: shared_or_unscoped
| From Page | Option/Button | Click Type | Method | Goes To | Target Role Scope | What Happens |
|---|---|---|---|---|---|---|
| change_password.php | Update Password | button_submit | POST | change_password.php | shared_or_unscoped | reads_db, writes_db, redirects |
| edit_profile.php | Alerts | link | GET | send_alerts.php | program_chair | reads_db, writes_db, redirects |
| edit_profile.php | Assignments | link | GET | assignments.php | teacher | reads_db, writes_db, returns_json, redirects, file_io |
| edit_profile.php | Assignments | link | GET | view_assignment_marks.php | student | reads_db, writes_db, redirects, file_io |
| edit_profile.php | Courses | link | GET | course_progress.php | program_chair | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| edit_profile.php | Dashboard | link | GET | program_dashboard.php | program_chair | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| edit_profile.php | Dashboard | link | GET | student_dashboard.php | student | reads_db, returns_json, redirects |
| edit_profile.php | Dashboard | link | GET | teacher_dashboard.php | teacher | reads_db, writes_db, returns_json, redirects |
| edit_profile.php | Edit Profile | link | GET | edit_profile.php | shared_or_unscoped | reads_db, writes_db, redirects |
| edit_profile.php | ICA Components | link | GET | create_ica_components.php | teacher | reads_db, writes_db, returns_json, redirects |
| edit_profile.php | Logout | link | GET | logout.php | shared_or_unscoped | returns_json |
| edit_profile.php | Manage ICA Marks | link | GET | manage_ica_marks.php | teacher | reads_db, writes_db, redirects, downloads_file, file_io |
| edit_profile.php | Marks | link | GET | view_marks.php | student | reads_db, writes_db, redirects |
| edit_profile.php | Reports | link | GET | program_reports.php | program_chair | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| edit_profile.php | Save Changes | button_submit | POST | edit_profile.php | shared_or_unscoped | reads_db, writes_db, redirects |
| edit_profile.php | Settings | link | GET | settings.php | program_chair | reads_db, writes_db, redirects |
| edit_profile.php | Students | link | GET | student_progress.php | program_chair | reads_db, returns_json, redirects, downloads_file, file_io |
| edit_profile.php | Subject Comparison | link | GET | subject_comparison.php | student | reads_db, writes_db, returns_json, redirects |
| edit_profile.php | Switch to Program Chair | link | GET | login_as.php?role=program_chair | shared_or_unscoped | reads_db, writes_db, redirects |
| edit_profile.php | Switch to Teacher | link | GET | login_as.php?role=teacher | shared_or_unscoped | reads_db, writes_db, redirects |
| edit_profile.php | Syllabus Progress | link | GET | view_progress.php | student | reads_db, writes_db, returns_json, redirects |
| edit_profile.php | Teachers | link | GET | teacher_progress.php | program_chair | reads_db, writes_db, returns_json, redirects |
| edit_profile.php | Timetable | link | GET | timetable.php | teacher | reads_db, writes_db, redirects, file_io |
| edit_profile.php | Timetable | link | GET | view_timetable.php | shared_or_unscoped | reads_db, redirects |
| edit_profile.php | Update Progress | link | GET | update_progress.php | teacher | reads_db, writes_db, returns_json, redirects |
| edit_profile.php | View Alerts | link | GET | view_alerts.php | teacher | reads_db, writes_db, redirects |
| edit_profile.php | View Reports | link | GET | view_reports.php | teacher | reads_db, writes_db, returns_json, redirects, downloads_file, file_io |
| forgot_password.php | Back to Login | link | GET | login.php | shared_or_unscoped | reads_db, writes_db, redirects |
| forgot_password.php | Send Reset Link | button_submit | POST | forgot_password.php | shared_or_unscoped | reads_db, writes_db |
| index.php | 9392123577 | link | GET | tel:+919392123577 | external_or_dynamic | route_or_ui_only |
| index.php | About | link | GET | #about | external_or_dynamic | route_or_ui_only |
| index.php | Admin | link | GET | admin_login.php | admin | reads_db, writes_db, redirects |
| index.php | Admin Login | link | GET | admin_login.php | admin | reads_db, writes_db, redirects |
| index.php | Contact | link | GET | #contact | external_or_dynamic | route_or_ui_only |
| index.php | Get Started | link | GET | login.php | shared_or_unscoped | reads_db, writes_db, redirects |
| index.php | Home | link | GET | index.php | shared_or_unscoped | page_render_or_ui_action |
| index.php | KUCHURUSAI.KRISHNA34@nmims.in | link | GET | mailto:KUCHURUSAI.KRISHNA34@nmims.in | external_or_dynamic | route_or_ui_only |
| index.php | Link | link | GET | index.php | shared_or_unscoped | page_render_or_ui_action |
| index.php | Login | link | GET | login.php | shared_or_unscoped | reads_db, writes_db, redirects |
| index.php | Program Chair | link | GET | login.php | shared_or_unscoped | reads_db, writes_db, redirects |
| index.php | sherymounika.reddy32@nmims.in | link | GET | mailto:sherymounika.reddy32@nmims.in | external_or_dynamic | route_or_ui_only |
| index.php | shiva.ganesh21@nmims.in | link | GET | mailto:shiva.ganesh21@nmims.in | external_or_dynamic | route_or_ui_only |
| index.php | Student | link | GET | login.php | shared_or_unscoped | reads_db, writes_db, redirects |
| index.php | Teacher | link | GET | login.php | shared_or_unscoped | reads_db, writes_db, redirects |
| login.php | Contact | link | GET | http://localhost/ica_tracker/index.php#contact | external_or_dynamic | route_or_ui_only |
| login.php | Forgot Password? | link | GET | forgot_password.php | shared_or_unscoped | reads_db, writes_db |
| login.php | Login | button_submit | POST | login.php | shared_or_unscoped | reads_db, writes_db, redirects |
| login.php | Portal Home | link | GET | index.php | shared_or_unscoped | page_render_or_ui_action |
| login_as.php | Login as Program Chair | button_submit | POST | login_as.php | shared_or_unscoped | reads_db, writes_db, redirects |
| login_as.php | Login as Teacher | button_submit | POST | login_as.php | shared_or_unscoped | reads_db, writes_db, redirects |
| PHPMailer\get_oauth_token.php | Continue | input_submit | POST | get_oauth_token.php | shared_or_unscoped | reads_db, redirects |
| reset_password.php | Back to Login | link | GET | login.php | shared_or_unscoped | reads_db, writes_db, redirects |
| reset_password.php | Request New Reset Link | link | GET | forgot_password.php | shared_or_unscoped | reads_db, writes_db |
| reset_password.php | Set Password | button_submit | POST | reset_password.php | shared_or_unscoped | reads_db, writes_db, redirects |
| signup.php | Login | link | GET | login.php | shared_or_unscoped | reads_db, writes_db, redirects |
| signup.php | Sign Up | button_submit | POST | signup.php | shared_or_unscoped | reads_db, writes_db |
| vendor\phpmailer\phpmailer\get_oauth_token.php | Continue | input_submit | POST | get_oauth_token.php | shared_or_unscoped | reads_db, redirects |
| vendor\phpmailer\phpmailer\test\PHPMailer\Html2TextTest.php | https://github.com/PHPMailer/PHPMailer | link | GET | https://github.com/PHPMailer/PHPMailer | external_or_dynamic | route_or_ui_only |
| vendor\phpmailer\phpmailer\test\PHPMailer\Html2TextTest.php | Other text with &#39; and &quot; | link | GET | #fragment | external_or_dynamic | route_or_ui_only |
| vendor\phpmailer\phpmailer\test\PHPMailer\Html2TextTest.php | with | link | GET | https://example.com/ | external_or_dynamic | route_or_ui_only |
| vendor\phpmailer\phpmailer\test\PHPMailer\PHPMailerTest.php | https://github.com/PHPMailer/PHPMailer/ | link | GET | https://github.com/PHPMailer/PHPMailer/ | external_or_dynamic | route_or_ui_only |
| vendor\phpmailer\phpmailer\test\SendTestCase.php | Run Test | input_submit | GET | SendTestCase.php | shared_or_unscoped | page_render_or_ui_action |
| view_timetable.php | "> Download | link | GET | <?php echo $safePath; ?> | external_or_dynamic | route_or_ui_only |
| view_timetable.php | Assignments | link | GET | view_assignment_marks.php | student | reads_db, writes_db, redirects, file_io |
| view_timetable.php | Dashboard | link | GET | student_dashboard.php | student | reads_db, returns_json, redirects |
| view_timetable.php | Edit Profile | link | GET | edit_profile.php | shared_or_unscoped | reads_db, writes_db, redirects |
| view_timetable.php | Logout | link | GET | logout.php | shared_or_unscoped | returns_json |
| view_timetable.php | Marks | link | GET | view_marks.php | student | reads_db, writes_db, redirects |
| view_timetable.php | Subject Comparison | link | GET | subject_comparison.php | student | reads_db, writes_db, returns_json, redirects |
| view_timetable.php | Syllabus Progress | link | GET | view_progress.php | student | reads_db, writes_db, returns_json, redirects |
| view_timetable.php | Timetable | link | GET | view_timetable.php | shared_or_unscoped | reads_db, redirects |

### Cross-Role Switch and Session Context Actions
- login_as.php: switches session role between program_chair and teacher, then redirects to corresponding dashboard.
- set_academic_context.php: updates active academic term context used by role dashboards/reports.
- logout.php: destroys session and redirects to login page.

### Notes
- Dynamic JavaScript-created controls and runtime-computed URLs are represented when static attributes are visible in source.
- For form submissions without explicit action, target defaults to same file (self-submit), and behavior is derived from that file code path.

## 8) Application Role Option Matrix (Clean App-Only View)
- This section excludes vendor/test files and focuses on actual ICA Tracker role options and button actions.

### system_admin
| Option | From | Click | To | Connected Target | On Click, What Happens |
|---|---|---|---|---|---|
| $scope,'entry'=>(int)$row['id'],'start_date'=>$startDate,'end_date'=>$endDate,'activity_action'=>$actionFilter,'activity_preset'=>$activityPreset]).'#entry-'.(int)$row['id']); ?>">Open | system_admin_activity_feed.php | link GET | <?php echo htmlspecialchars(build_feed_url([ | dynamic_or_external | open_route_or_trigger_ui |
| $scope,'start_date'=>$startDate,'end_date'=>$endDate,'activity_action'=>$actionFilter,'activity_preset'=>$activityPreset]).'#entry-'.(int)$row['id']); ?>">Close | system_admin_activity_feed.php | link GET | <?php echo htmlspecialchars(build_feed_url([ | dynamic_or_external | open_route_or_trigger_ui |
| $scope])); ?>">Clear | system_admin_activity_feed.php | link GET | <?php echo htmlspecialchars(build_feed_url([ | dynamic_or_external | open_route_or_trigger_ui |
| Activity Feed | system_admin_activity_feed.php | link GET | system_admin_activity_feed.php | system_admin_activity_feed.php | read_db,json_response,redirect |
| Apply | system_admin_activity_feed.php | button GET | system_admin_activity_feed.php | system_admin_activity_feed.php | read_db,json_response,redirect |
| Backup & Restore | system_admin_activity_feed.php | link GET | system_admin_export_sql.php | system_admin_export_sql.php | read_db,write_db,redirect,download,file_io |
| Dashboard | system_admin_activity_feed.php | link GET | system_admin_dashboard.php | system_admin_dashboard.php | read_db,json_response,redirect |
| Logout | system_admin_activity_feed.php | link GET | logout.php | logout.php | json_response |
| 'today','activity_preset'=>'active_logins'])); ?>"> Today Active | system_admin_activity_feed.php | link GET | <?php echo htmlspecialchars(build_feed_url([ | dynamic_or_external | open_route_or_trigger_ui |
| 'today','activity_preset'=>'failed_logins'])); ?>"> Failed Logins | system_admin_activity_feed.php | link GET | <?php echo htmlspecialchars(build_feed_url([ | dynamic_or_external | open_route_or_trigger_ui |
| 'today','activity_preset'=>'sensitive_actions'])); ?>"> Sensitive Actions | system_admin_activity_feed.php | link GET | <?php echo htmlspecialchars(build_feed_url([ | dynamic_or_external | open_route_or_trigger_ui |
| Unlock | system_admin_activity_feed.php | button POST | system_admin_activity_feed.php | system_admin_activity_feed.php | read_db,json_response,redirect |
| 'week','activity_preset'=>'active_logins'])); ?>"> Week Active | system_admin_activity_feed.php | link GET | <?php echo htmlspecialchars(build_feed_url([ | dynamic_or_external | open_route_or_trigger_ui |
| Activity Feed | system_admin_dashboard.php | link GET | system_admin_activity_feed.php | system_admin_activity_feed.php | read_db,json_response,redirect |
| Backup & Restore | system_admin_dashboard.php | link GET | system_admin_export_sql.php | system_admin_export_sql.php | read_db,write_db,redirect,download,file_io |
| Dashboard | system_admin_dashboard.php | link GET | system_admin_dashboard.php | system_admin_dashboard.php | read_db,json_response,redirect |
| Failed Logins | system_admin_dashboard.php | link GET | system_admin_activity_feed.php?scope=today&activity_preset=failed_logins | system_admin_activity_feed.php | read_db,json_response,redirect |
| Failed Logins Today | system_admin_dashboard.php | link GET | system_admin_activity_feed.php?scope=today&activity_preset=failed_logins | system_admin_activity_feed.php | read_db,json_response,redirect |
| Full Audit Log | system_admin_dashboard.php | link GET | system_admin_activity_feed.php?scope=all | system_admin_activity_feed.php | read_db,json_response,redirect |
| Logout | system_admin_dashboard.php | link GET | logout.php | logout.php | json_response |
| Sensitive Actions | system_admin_dashboard.php | link GET | system_admin_activity_feed.php?scope=today&activity_preset=sensitive_actions | system_admin_activity_feed.php | read_db,json_response,redirect |
| Sensitive Actions Today | system_admin_dashboard.php | link GET | system_admin_activity_feed.php?scope=today&activity_preset=sensitive_actions | system_admin_activity_feed.php | read_db,json_response,redirect |
| Today Active | system_admin_dashboard.php | link GET | system_admin_activity_feed.php?scope=today&activity_preset=active_logins | system_admin_activity_feed.php | read_db,json_response,redirect |
| Week Active | system_admin_dashboard.php | link GET | system_admin_activity_feed.php?scope=week&activity_preset=active_logins | system_admin_activity_feed.php | read_db,json_response,redirect |
| Activity Feed | system_admin_export_sql.php | link GET | system_admin_activity_feed.php | system_admin_activity_feed.php | read_db,json_response,redirect |
| Backup & Restore | system_admin_export_sql.php | link GET | system_admin_export_sql.php | system_admin_export_sql.php | read_db,write_db,redirect,download,file_io |
| Dashboard | system_admin_export_sql.php | link GET | system_admin_dashboard.php | system_admin_dashboard.php | read_db,json_response,redirect |
| Download Backup SQL | system_admin_export_sql.php | button POST | system_admin_export_sql.php | system_admin_export_sql.php | read_db,write_db,redirect,download,file_io |
| Logout | system_admin_export_sql.php | link GET | logout.php | logout.php | json_response |
| Restore Database | system_admin_export_sql.php | button POST | system_admin_export_sql.php | system_admin_export_sql.php | read_db,write_db,redirect,download,file_io |

### admin
| Option | From | Click | To | Connected Target | On Click, What Happens |
|---|---|---|---|---|---|
| Academic Calendar | admin_dashboard.php | link GET | manage_academic_calendar.php | manage_academic_calendar.php | read_db,write_db,redirect |
| Add Students | admin_dashboard.php | link GET | bulk_add_students.php | bulk_add_students.php | read_db,write_db,json_response,redirect,file_io |
| Assign Teachers | admin_dashboard.php | link GET | assign_teachers.php | assign_teachers.php | read_db,write_db,json_response,redirect |
| Change Roles | admin_dashboard.php | link GET | change_roles.php | change_roles.php | read_db,write_db,redirect |
| Create Classes | admin_dashboard.php | link GET | create_classes.php | create_classes.php | read_db,write_db,json_response,redirect |
| Create Subjects | admin_dashboard.php | link GET | create_subjects.php | create_subjects.php | read_db,write_db,json_response,redirect |
| Dashboard | admin_dashboard.php | link GET | admin_dashboard.php | admin_dashboard.php | write_db,redirect |
| Logout | admin_dashboard.php | link GET | logout.php | logout.php | json_response |
| Manage Electives | admin_dashboard.php | link GET | manage_electives.php | manage_electives.php | read_db,write_db,redirect |
| Manage Teachers | admin_dashboard.php | link GET | manage_teachers.php | manage_teachers.php | read_db,write_db,redirect |
| Manual Mailing | admin_dashboard.php | link GET | test_mail.php | test_mail.php | read_db,write_db,json_response,redirect |
| Faculty/Student Login | admin_login.php | link GET | login.php | login.php | read_db,write_db,redirect |
| Go back to standard login | admin_login.php | link GET | login.php | login.php | read_db,write_db,redirect |
| Login | admin_login.php | button POST | admin_login.php | admin_login.php | read_db,write_db,redirect |
| Portal Home | admin_login.php | link GET | index.php | index.php | render_page |
| Academic Calendar | assign_teachers.php | link GET | manage_academic_calendar.php | manage_academic_calendar.php | read_db,write_db,redirect |
| Add Students | assign_teachers.php | link GET | bulk_add_students.php | bulk_add_students.php | read_db,write_db,json_response,redirect,file_io |
| Assign | assign_teachers.php | button POST | assign_teachers.php | assign_teachers.php | read_db,write_db,json_response,redirect |
| Assign Teachers | assign_teachers.php | link GET | assign_teachers.php | assign_teachers.php | read_db,write_db,json_response,redirect |
| Change Roles | assign_teachers.php | link GET | change_roles.php | change_roles.php | read_db,write_db,redirect |
| Create Classes | assign_teachers.php | link GET | create_classes.php | create_classes.php | read_db,write_db,json_response,redirect |
| Create Subjects | assign_teachers.php | link GET | create_subjects.php | create_subjects.php | read_db,write_db,json_response,redirect |
| Dashboard | assign_teachers.php | link GET | admin_dashboard.php | admin_dashboard.php | write_db,redirect |
| Logout | assign_teachers.php | link GET | logout.php | logout.php | json_response |
| Manage Electives | assign_teachers.php | link GET | manage_electives.php | manage_electives.php | read_db,write_db,redirect |
| Manage Teachers | assign_teachers.php | link GET | manage_teachers.php | manage_teachers.php | read_db,write_db,redirect |
| Manual Mailing | assign_teachers.php | link GET | test_mail.php | test_mail.php | read_db,write_db,json_response,redirect |
| " target="_blank" rel="noopener" class="class-action-btn class-action-btn--ghost">View latest | bulk_add_students.php | link GET | <?php echo htmlspecialchars($latest_timetable[ | dynamic_or_external | open_route_or_trigger_ui |
| &section_id= " class="class-action-btn class-action-btn--secondary"> Download class CSV | bulk_add_students.php | link GET | download_class_students_template.php?class_id=<?php echo (int)$card[ | download_class_students_template.php | read_db,download,file_io |
| >Upload for All Classes | bulk_add_students.php | button POST | bulk_add_students.php | bulk_add_students.php | read_db,write_db,json_response,redirect,file_io |
| Academic Calendar | bulk_add_students.php | link GET | manage_academic_calendar.php | manage_academic_calendar.php | read_db,write_db,redirect |
| Add Student | bulk_add_students.php | button POST | bulk_add_students.php | bulk_add_students.php | read_db,write_db,json_response,redirect,file_io |
| Add Students | bulk_add_students.php | link GET | bulk_add_students.php | bulk_add_students.php | read_db,write_db,json_response,redirect,file_io |
| Assign Teachers | bulk_add_students.php | link GET | assign_teachers.php | assign_teachers.php | read_db,write_db,json_response,redirect |
| Change Roles | bulk_add_students.php | link GET | change_roles.php | change_roles.php | read_db,write_db,redirect |
| Copy Students | bulk_add_students.php | button POST | bulk_add_students.php | bulk_add_students.php | read_db,write_db,json_response,redirect,file_io |
| Create Classes | bulk_add_students.php | link GET | create_classes.php | create_classes.php | read_db,write_db,json_response,redirect |
| Create Subjects | bulk_add_students.php | link GET | create_subjects.php | create_subjects.php | read_db,write_db,json_response,redirect |
| Dashboard | bulk_add_students.php | link GET | admin_dashboard.php | admin_dashboard.php | write_db,redirect |
| Delete | bulk_add_students.php | button POST | bulk_add_students.php | bulk_add_students.php | read_db,write_db,json_response,redirect,file_io |
| Delete timetable | bulk_add_students.php | button POST | bulk_add_students.php | bulk_add_students.php | read_db,write_db,json_response,redirect,file_io |
| Download Template | bulk_add_students.php | link GET | download_template.php | download_template.php | download |
| Logout | bulk_add_students.php | link GET | logout.php | logout.php | json_response |
| Manage Electives | bulk_add_students.php | link GET | manage_electives.php | manage_electives.php | read_db,write_db,redirect |
| Manage Teachers | bulk_add_students.php | link GET | manage_teachers.php | manage_teachers.php | read_db,write_db,redirect |
| Manual Mailing | bulk_add_students.php | link GET | test_mail.php | test_mail.php | read_db,write_db,json_response,redirect |
| Upload Students | bulk_add_students.php | button POST | bulk_add_students.php | bulk_add_students.php | read_db,write_db,json_response,redirect,file_io |
| Academic Calendar | change_roles.php | link GET | manage_academic_calendar.php | manage_academic_calendar.php | read_db,write_db,redirect |
| Add Students | change_roles.php | link GET | bulk_add_students.php | bulk_add_students.php | read_db,write_db,json_response,redirect,file_io |
| Assign Teachers | change_roles.php | link GET | assign_teachers.php | assign_teachers.php | read_db,write_db,json_response,redirect |
| Change Role | change_roles.php | button POST | change_roles.php | change_roles.php | read_db,write_db,redirect |
| Change Roles | change_roles.php | link GET | change_roles.php | change_roles.php | read_db,write_db,redirect |
| Create Classes | change_roles.php | link GET | create_classes.php | create_classes.php | read_db,write_db,json_response,redirect |
| Create Subjects | change_roles.php | link GET | create_subjects.php | create_subjects.php | read_db,write_db,json_response,redirect |
| Dashboard | change_roles.php | link GET | admin_dashboard.php | admin_dashboard.php | write_db,redirect |
| Logout | change_roles.php | link GET | logout.php | logout.php | json_response |
| Manage Electives | change_roles.php | link GET | manage_electives.php | manage_electives.php | read_db,write_db,redirect |
| Manage Teachers | change_roles.php | link GET | manage_teachers.php | manage_teachers.php | read_db,write_db,redirect |
| Manual Mailing | change_roles.php | link GET | test_mail.php | test_mail.php | read_db,write_db,json_response,redirect |
| Academic Calendar | create_classes.php | link GET | manage_academic_calendar.php | manage_academic_calendar.php | read_db,write_db,redirect |
| Add Students | create_classes.php | link GET | bulk_add_students.php | bulk_add_students.php | read_db,write_db,json_response,redirect,file_io |
| Assign Teachers | create_classes.php | link GET | assign_teachers.php | assign_teachers.php | read_db,write_db,json_response,redirect |
| Change Roles | create_classes.php | link GET | change_roles.php | change_roles.php | read_db,write_db,redirect |
| Create Class | create_classes.php | button POST | create_classes.php | create_classes.php | read_db,write_db,json_response,redirect |
| Create Classes | create_classes.php | link GET | create_classes.php | create_classes.php | read_db,write_db,json_response,redirect |
| Create Subjects | create_classes.php | link GET | create_subjects.php | create_subjects.php | read_db,write_db,json_response,redirect |
| Dashboard | create_classes.php | link GET | admin_dashboard.php | admin_dashboard.php | write_db,redirect |
| Logout | create_classes.php | link GET | logout.php | logout.php | json_response |
| Manage Electives | create_classes.php | link GET | manage_electives.php | manage_electives.php | read_db,write_db,redirect |
| Manage Teachers | create_classes.php | link GET | manage_teachers.php | manage_teachers.php | read_db,write_db,redirect |
| Manual Mailing | create_classes.php | link GET | test_mail.php | test_mail.php | read_db,write_db,json_response,redirect |
| Save Changes | create_classes.php | button POST | create_classes.php | create_classes.php | read_db,write_db,json_response,redirect |
| Update | create_classes.php | button POST | create_classes.php | create_classes.php | read_db,write_db,json_response,redirect |
| Academic Calendar | create_subjects.php | link GET | manage_academic_calendar.php | manage_academic_calendar.php | read_db,write_db,redirect |
| Add Students | create_subjects.php | link GET | bulk_add_students.php | bulk_add_students.php | read_db,write_db,json_response,redirect,file_io |
| Assign Teachers | create_subjects.php | link GET | assign_teachers.php | assign_teachers.php | read_db,write_db,json_response,redirect |
| Change Roles | create_subjects.php | link GET | change_roles.php | change_roles.php | read_db,write_db,redirect |
| Create Classes | create_subjects.php | link GET | create_classes.php | create_classes.php | read_db,write_db,json_response,redirect |
| Create Subject | create_subjects.php | button POST | create_subjects.php | create_subjects.php | read_db,write_db,json_response,redirect |
| Create Subjects | create_subjects.php | link GET | create_subjects.php | create_subjects.php | read_db,write_db,json_response,redirect |
| Dashboard | create_subjects.php | link GET | admin_dashboard.php | admin_dashboard.php | write_db,redirect |
| Logout | create_subjects.php | link GET | logout.php | logout.php | json_response |
| Manage Electives | create_subjects.php | link GET | manage_electives.php | manage_electives.php | read_db,write_db,redirect |
| Manage Teachers | create_subjects.php | link GET | manage_teachers.php | manage_teachers.php | read_db,write_db,redirect |
| Manual Mailing | create_subjects.php | link GET | test_mail.php | test_mail.php | read_db,write_db,json_response,redirect |
| Academic Calendar | manage_academic_calendar.php | link GET | manage_academic_calendar.php | manage_academic_calendar.php | read_db,write_db,redirect |
| Add Students | manage_academic_calendar.php | link GET | bulk_add_students.php | bulk_add_students.php | read_db,write_db,json_response,redirect,file_io |
| Assign Teachers | manage_academic_calendar.php | link GET | assign_teachers.php | assign_teachers.php | read_db,write_db,json_response,redirect |
| Change Roles | manage_academic_calendar.php | link GET | change_roles.php | change_roles.php | read_db,write_db,redirect |
| Create Classes | manage_academic_calendar.php | link GET | create_classes.php | create_classes.php | read_db,write_db,json_response,redirect |
| Create Subjects | manage_academic_calendar.php | link GET | create_subjects.php | create_subjects.php | read_db,write_db,json_response,redirect |
| Dashboard | manage_academic_calendar.php | link GET | admin_dashboard.php | admin_dashboard.php | write_db,redirect |
| Logout | manage_academic_calendar.php | link GET | logout.php | logout.php | json_response |
| Manage Electives | manage_academic_calendar.php | link GET | manage_electives.php | manage_electives.php | read_db,write_db,redirect |
| Manage Teachers | manage_academic_calendar.php | link GET | manage_teachers.php | manage_teachers.php | read_db,write_db,redirect |
| Manual Mailing | manage_academic_calendar.php | link GET | test_mail.php | test_mail.php | read_db,write_db,json_response,redirect |
| Save Academic Calendar | manage_academic_calendar.php | button POST | manage_academic_calendar.php | manage_academic_calendar.php | read_db,write_db,redirect |
| "> students | manage_electives.php | link GET | <?php echo htmlspecialchars($electiveLink); ?> | dynamic_or_external | open_route_or_trigger_ui |
| 0 ? '' : 'disabled'; ?>>Load Students | manage_electives.php | button GET | manage_electives.php | manage_electives.php | read_db,write_db,redirect |
| Academic Calendar | manage_electives.php | link GET | manage_academic_calendar.php | manage_academic_calendar.php | read_db,write_db,redirect |
| Add Students | manage_electives.php | link GET | bulk_add_students.php | bulk_add_students.php | read_db,write_db,json_response,redirect,file_io |
| Assign Teachers | manage_electives.php | link GET | assign_teachers.php | assign_teachers.php | read_db,write_db,json_response,redirect |
| Change Roles | manage_electives.php | link GET | change_roles.php | change_roles.php | read_db,write_db,redirect |
| Create Classes | manage_electives.php | link GET | create_classes.php | create_classes.php | read_db,write_db,json_response,redirect |
| Create Subjects | manage_electives.php | link GET | create_subjects.php | create_subjects.php | read_db,write_db,json_response,redirect |
| Dashboard | manage_electives.php | link GET | admin_dashboard.php | admin_dashboard.php | write_db,redirect |
| Logout | manage_electives.php | link GET | logout.php | logout.php | json_response |
| Manage Electives | manage_electives.php | link GET | manage_electives.php | manage_electives.php | read_db,write_db,redirect |
| Manage Teachers | manage_electives.php | link GET | manage_teachers.php | manage_teachers.php | read_db,write_db,redirect |
| Manual Mailing | manage_electives.php | link GET | test_mail.php | test_mail.php | read_db,write_db,json_response,redirect |
| Reset | manage_electives.php | link GET | <?php echo htmlspecialchars($resetLink); ?> | dynamic_or_external | open_route_or_trigger_ui |
| Save Assignments | manage_electives.php | button POST | manage_electives.php | manage_electives.php | read_db,write_db,redirect |
| Academic Calendar | manage_sections.php | link GET | manage_academic_calendar.php | manage_academic_calendar.php | read_db,write_db,redirect |
| Add Division | manage_sections.php | button POST | manage_sections.php | manage_sections.php | read_db,write_db,redirect |
| Add Students | manage_sections.php | link GET | bulk_add_students.php | bulk_add_students.php | read_db,write_db,json_response,redirect,file_io |
| Assign Teachers | manage_sections.php | link GET | assign_teachers.php | assign_teachers.php | read_db,write_db,json_response,redirect |
| Change Roles | manage_sections.php | link GET | change_roles.php | change_roles.php | read_db,write_db,redirect |
| Create Classes | manage_sections.php | link GET | create_classes.php | create_classes.php | read_db,write_db,json_response,redirect |
| Create Subjects | manage_sections.php | link GET | create_subjects.php | create_subjects.php | read_db,write_db,json_response,redirect |
| Dashboard | manage_sections.php | link GET | admin_dashboard.php | admin_dashboard.php | write_db,redirect |
| Logout | manage_sections.php | link GET | logout.php | logout.php | json_response |
| Manage Divisions | manage_sections.php | link GET | manage_sections.php | manage_sections.php | read_db,write_db,redirect |
| Manage Electives | manage_sections.php | link GET | manage_electives.php | manage_electives.php | read_db,write_db,redirect |
| Manage Teachers | manage_sections.php | link GET | manage_teachers.php | manage_teachers.php | read_db,write_db,redirect |
| Manual Mailing | manage_sections.php | link GET | test_mail.php | test_mail.php | read_db,write_db,json_response,redirect |
| Academic Calendar | manage_teachers.php | link GET | manage_academic_calendar.php | manage_academic_calendar.php | read_db,write_db,redirect |
| Add Students | manage_teachers.php | link GET | bulk_add_students.php | bulk_add_students.php | read_db,write_db,json_response,redirect,file_io |
| Add Teacher | manage_teachers.php | button POST | manage_teachers.php | manage_teachers.php | read_db,write_db,redirect |
| Assign Teachers | manage_teachers.php | link GET | assign_teachers.php | assign_teachers.php | read_db,write_db,json_response,redirect |
| Change Roles | manage_teachers.php | link GET | change_roles.php | change_roles.php | read_db,write_db,redirect |
| Create Classes | manage_teachers.php | link GET | create_classes.php | create_classes.php | read_db,write_db,json_response,redirect |
| Create Subjects | manage_teachers.php | link GET | create_subjects.php | create_subjects.php | read_db,write_db,json_response,redirect |
| Dashboard | manage_teachers.php | link GET | admin_dashboard.php | admin_dashboard.php | write_db,redirect |
| Deactivate Account | manage_teachers.php | button POST | manage_teachers.php | manage_teachers.php | read_db,write_db,redirect |
| Logout | manage_teachers.php | link GET | logout.php | logout.php | json_response |
| Manage Electives | manage_teachers.php | link GET | manage_electives.php | manage_electives.php | read_db,write_db,redirect |
| Manage Teachers | manage_teachers.php | link GET | manage_teachers.php | manage_teachers.php | read_db,write_db,redirect |
| Manual Mailing | manage_teachers.php | link GET | test_mail.php | test_mail.php | read_db,write_db,json_response,redirect |
| Reset Password | manage_teachers.php | button POST | manage_teachers.php | manage_teachers.php | read_db,write_db,redirect |
| Save Changes | manage_teachers.php | button POST | manage_teachers.php | manage_teachers.php | read_db,write_db,redirect |
| Academic Calendar | test_mail.php | link GET | manage_academic_calendar.php | manage_academic_calendar.php | read_db,write_db,redirect |
| Add Students | test_mail.php | link GET | bulk_add_students.php | bulk_add_students.php | read_db,write_db,json_response,redirect,file_io |
| Assign Teachers | test_mail.php | link GET | assign_teachers.php | assign_teachers.php | read_db,write_db,json_response,redirect |
| Change Roles | test_mail.php | link GET | change_roles.php | change_roles.php | read_db,write_db,redirect |
| Create Classes | test_mail.php | link GET | create_classes.php | create_classes.php | read_db,write_db,json_response,redirect |
| Create Subjects | test_mail.php | link GET | create_subjects.php | create_subjects.php | read_db,write_db,json_response,redirect |
| Dashboard | test_mail.php | link GET | admin_dashboard.php | admin_dashboard.php | write_db,redirect |
| Logout | test_mail.php | link GET | logout.php | logout.php | json_response |
| Manage Electives | test_mail.php | link GET | manage_electives.php | manage_electives.php | read_db,write_db,redirect |
| Manage Teachers | test_mail.php | link GET | manage_teachers.php | manage_teachers.php | read_db,write_db,redirect |
| Send Test Mail | test_mail.php | button POST | test_mail.php | test_mail.php | read_db,write_db,json_response,redirect |
| Test Mail | test_mail.php | link GET | test_mail.php | test_mail.php | read_db,write_db,json_response,redirect |

### program_chair
| Option | From | Click | To | Connected Target | On Click, What Happens |
|---|---|---|---|---|---|
| Alerts | course_progress.php | link GET | send_alerts.php | send_alerts.php | read_db,write_db,redirect |
| Apply Filters | course_progress.php | button GET | course_progress.php | course_progress.php | read_db,write_db,json_response,redirect,download,file_io |
| Courses | course_progress.php | link GET | course_progress.php | course_progress.php | read_db,write_db,json_response,redirect,download,file_io |
| 'csv']))); ?>" class="theme-toggle" style="text-decoration:none; display:inline-flex; align-items:center;"> Export CSV | course_progress.php | link GET | <?php echo htmlspecialchars($_SERVER[ | dynamic_or_external | open_route_or_trigger_ui |
| Dashboard | course_progress.php | link GET | program_dashboard.php | program_dashboard.php | read_db,write_db,json_response,redirect,download,file_io |
| Edit Profile | course_progress.php | link GET | edit_profile.php | edit_profile.php | read_db,write_db,redirect |
| Logout | course_progress.php | link GET | logout.php | logout.php | json_response |
| Reports | course_progress.php | link GET | program_reports.php | program_reports.php | read_db,write_db,json_response,redirect,download,file_io |
| Reset | course_progress.php | link GET | course_progress.php | course_progress.php | read_db,write_db,json_response,redirect,download,file_io |
| Settings | course_progress.php | link GET | settings.php | settings.php | read_db,write_db,redirect |
| Students | course_progress.php | link GET | student_progress.php | student_progress.php | read_db,json_response,redirect,download,file_io |
| Switch to Teacher | course_progress.php | link GET | login_as.php?role=teacher | login_as.php | read_db,write_db,redirect |
| Teachers | course_progress.php | link GET | teacher_progress.php | teacher_progress.php | read_db,write_db,json_response,redirect |
| "> Active Teachers | program_dashboard.php | link GET | teacher_progress.php<?php echo $card_link_query !== | dynamic_or_external | open_route_or_trigger_ui |
| Academic Week | program_dashboard.php | link GET | <?php echo htmlspecialchars($week_link); ?> | dynamic_or_external | open_route_or_trigger_ui |
| Alerts | program_dashboard.php | link GET | send_alerts.php | send_alerts.php | read_db,write_db,redirect |
| Apply Filters | program_dashboard.php | button GET | program_dashboard.php | program_dashboard.php | read_db,write_db,json_response,redirect,download,file_io |
| Avg Syllabus % | program_dashboard.php | link GET | <?php echo htmlspecialchars($course_progress_link); ?> | dynamic_or_external | open_route_or_trigger_ui |
| Courses | program_dashboard.php | link GET | course_progress.php | course_progress.php | read_db,write_db,json_response,redirect,download,file_io |
| Dashboard | program_dashboard.php | link GET | program_dashboard.php | program_dashboard.php | read_db,write_db,json_response,redirect,download,file_io |
| Edit Profile | program_dashboard.php | link GET | edit_profile.php | edit_profile.php | read_db,write_db,redirect |
| Logout | program_dashboard.php | link GET | logout.php | logout.php | json_response |
| Low Performing | program_dashboard.php | link GET | <?php echo htmlspecialchars($student_at_risk_link); ?> | dynamic_or_external | open_route_or_trigger_ui |
| Pending Alerts | program_dashboard.php | link GET | <?php echo htmlspecialchars($alerts_link); ?> | dynamic_or_external | open_route_or_trigger_ui |
| Reports | program_dashboard.php | link GET | program_reports.php | program_reports.php | read_db,write_db,json_response,redirect,download,file_io |
| Settings | program_dashboard.php | link GET | settings.php | settings.php | read_db,write_db,redirect |
| Students | program_dashboard.php | link GET | student_progress.php | student_progress.php | read_db,json_response,redirect,download,file_io |
| Students In Scope | program_dashboard.php | link GET | <?php echo htmlspecialchars($students_page_link); ?> | dynamic_or_external | open_route_or_trigger_ui |
| Switch to Teacher | program_dashboard.php | link GET | login_as.php?role=teacher | login_as.php | read_db,write_db,redirect |
| Teachers | program_dashboard.php | link GET | teacher_progress.php | teacher_progress.php | read_db,write_db,json_response,redirect |
| Total Courses | program_dashboard.php | link GET | <?php echo htmlspecialchars($course_progress_link); ?> | dynamic_or_external | open_route_or_trigger_ui |
| " data-teacher-name=" ">View | program_reports.php | link GET | # | dynamic_or_external | open_route_or_trigger_ui |
| Alerts | program_reports.php | link GET | send_alerts.php | send_alerts.php | read_db,write_db,redirect |
| Apply Filters | program_reports.php | button GET | program_reports.php | program_reports.php | read_db,write_db,json_response,redirect,download,file_io |
| Courses | program_reports.php | link GET | course_progress.php | course_progress.php | read_db,write_db,json_response,redirect,download,file_io |
| Dashboard | program_reports.php | link GET | program_dashboard.php | program_dashboard.php | read_db,write_db,json_response,redirect,download,file_io |
| Download CSV | program_reports.php | link GET | <?php echo htmlspecialchars($csv_export_url); ?> | dynamic_or_external | open_route_or_trigger_ui |
| Download PDF | program_reports.php | link GET | <?php echo htmlspecialchars($pdf_export_url); ?> | dynamic_or_external | open_route_or_trigger_ui |
| Edit Profile | program_reports.php | link GET | edit_profile.php | edit_profile.php | read_db,write_db,redirect |
| Logout | program_reports.php | link GET | logout.php | logout.php | json_response |
| Reports | program_reports.php | link GET | program_reports.php | program_reports.php | read_db,write_db,json_response,redirect,download,file_io |
| Settings | program_reports.php | link GET | settings.php | settings.php | read_db,write_db,redirect |
| Students | program_reports.php | link GET | student_progress.php | student_progress.php | read_db,json_response,redirect,download,file_io |
| Switch to Teacher | program_reports.php | link GET | login_as.php?role=teacher | login_as.php | read_db,write_db,redirect |
| Teachers | program_reports.php | link GET | teacher_progress.php | teacher_progress.php | read_db,write_db,json_response,redirect |
| Alerts | send_alerts.php | link GET | send_alerts.php | send_alerts.php | read_db,write_db,redirect |
| Back to Dashboard | send_alerts.php | link GET | program_dashboard.php | program_dashboard.php | read_db,write_db,json_response,redirect,download,file_io |
| Courses | send_alerts.php | link GET | course_progress.php | course_progress.php | read_db,write_db,json_response,redirect,download,file_io |
| Dashboard | send_alerts.php | link GET | program_dashboard.php | program_dashboard.php | read_db,write_db,json_response,redirect,download,file_io |
| Edit Profile | send_alerts.php | link GET | edit_profile.php | edit_profile.php | read_db,write_db,redirect |
| Logout | send_alerts.php | link GET | logout.php | logout.php | json_response |
| Reports | send_alerts.php | link GET | program_reports.php | program_reports.php | read_db,write_db,json_response,redirect,download,file_io |
| Send Alert | send_alerts.php | button POST | send_alerts.php | send_alerts.php | read_db,write_db,redirect |
| Settings | send_alerts.php | link GET | settings.php | settings.php | read_db,write_db,redirect |
| Students | send_alerts.php | link GET | student_progress.php | student_progress.php | read_db,json_response,redirect,download,file_io |
| Switch to Teacher | send_alerts.php | link GET | login_as.php?role=teacher | login_as.php | read_db,write_db,redirect |
| Teachers | send_alerts.php | link GET | teacher_progress.php | teacher_progress.php | read_db,write_db,json_response,redirect |
| Alerts | settings.php | link GET | send_alerts.php | send_alerts.php | read_db,write_db,redirect |
| Courses | settings.php | link GET | course_progress.php | course_progress.php | read_db,write_db,json_response,redirect,download,file_io |
| Dashboard | settings.php | link GET | program_dashboard.php | program_dashboard.php | read_db,write_db,json_response,redirect,download,file_io |
| Edit Profile | settings.php | link GET | edit_profile.php | edit_profile.php | read_db,write_db,redirect |
| Logout | settings.php | link GET | logout.php | logout.php | json_response |
| Reports | settings.php | link GET | program_reports.php | program_reports.php | read_db,write_db,json_response,redirect,download,file_io |
| Save Settings | settings.php | button POST | settings.php | settings.php | read_db,write_db,redirect |
| Settings | settings.php | link GET | settings.php | settings.php | read_db,write_db,redirect |
| Students | settings.php | link GET | student_progress.php | student_progress.php | read_db,json_response,redirect,download,file_io |
| Switch to Teacher | settings.php | link GET | login_as.php?role=teacher | login_as.php | read_db,write_db,redirect |
| Teachers | settings.php | link GET | teacher_progress.php | teacher_progress.php | read_db,write_db,json_response,redirect |
| Alerts | student_progress.php | link GET | send_alerts.php | send_alerts.php | read_db,write_db,redirect |
| Apply Filters | student_progress.php | button GET | student_progress.php | student_progress.php | read_db,json_response,redirect,download,file_io |
| Courses | student_progress.php | link GET | course_progress.php | course_progress.php | read_db,write_db,json_response,redirect,download,file_io |
| Dashboard | student_progress.php | link GET | program_dashboard.php | program_dashboard.php | read_db,write_db,json_response,redirect,download,file_io |
| Edit Profile | student_progress.php | link GET | edit_profile.php | edit_profile.php | read_db,write_db,redirect |
| Logout | student_progress.php | link GET | logout.php | logout.php | json_response |
| Reports | student_progress.php | link GET | program_reports.php | program_reports.php | read_db,write_db,json_response,redirect,download,file_io |
| Save Changes | student_progress.php | button POST | edit_student.php | edit_student.php | write_db,redirect |
| Settings | student_progress.php | link GET | settings.php | settings.php | read_db,write_db,redirect |
| Students | student_progress.php | link GET | student_progress.php | student_progress.php | read_db,json_response,redirect,download,file_io |
| Switch to Teacher | student_progress.php | link GET | login_as.php?role=teacher | login_as.php | read_db,write_db,redirect |
| Teachers | student_progress.php | link GET | teacher_progress.php | teacher_progress.php | read_db,write_db,json_response,redirect |
| " data-teacher-name=" ">View | teacher_progress.php | link GET | # | dynamic_or_external | open_route_or_trigger_ui |
| Alerts | teacher_progress.php | link GET | send_alerts.php | send_alerts.php | read_db,write_db,redirect |
| Apply filters | teacher_progress.php | button GET | teacher_progress.php | teacher_progress.php | read_db,write_db,json_response,redirect |
| Courses | teacher_progress.php | link GET | course_progress.php | course_progress.php | read_db,write_db,json_response,redirect,download,file_io |
| Dashboard | teacher_progress.php | link GET | program_dashboard.php | program_dashboard.php | read_db,write_db,json_response,redirect,download,file_io |
| Edit Profile | teacher_progress.php | link GET | edit_profile.php | edit_profile.php | read_db,write_db,redirect |
| Logout | teacher_progress.php | link GET | logout.php | logout.php | json_response |
| Reports | teacher_progress.php | link GET | program_reports.php | program_reports.php | read_db,write_db,json_response,redirect,download,file_io |
| Reset filters | teacher_progress.php | link GET | teacher_progress.php | teacher_progress.php | read_db,write_db,json_response,redirect |
| Settings | teacher_progress.php | link GET | settings.php | settings.php | read_db,write_db,redirect |
| Students | teacher_progress.php | link GET | student_progress.php | student_progress.php | read_db,json_response,redirect,download,file_io |
| Switch to Teacher | teacher_progress.php | link GET | login_as.php?role=teacher | login_as.php | read_db,write_db,redirect |
| Teachers | teacher_progress.php | link GET | teacher_progress.php | teacher_progress.php | read_db,write_db,json_response,redirect |
| View | teacher_progress.php | link GET | ${t.file_path} | dynamic_or_external | open_route_or_trigger_ui |
| "> | track_teachers.php | link GET | ?teacher_id=<?php echo (int)$teacher[ | dynamic_or_external | open_route_or_trigger_ui |
| Back to Dashboard | track_teachers.php | link GET | program_dashboard.php | program_dashboard.php | read_db,write_db,json_response,redirect,download,file_io |

### teacher
| Option | From | Click | To | Connected Target | On Click, What Happens |
|---|---|---|---|---|---|
| Back to Dashboard | alerts.php | link GET | teacher_dashboard.php | teacher_dashboard.php | read_db,write_db,json_response,redirect |
| Respond | alerts.php | button POST | alerts.php | alerts.php | read_db,write_db,redirect |
| " target="_blank">Feedback file | assignments.php | link GET | <?php echo htmlspecialchars($submission[ | dynamic_or_external | open_route_or_trigger_ui |
| " target="_blank">Student file | assignments.php | link GET | <?php echo htmlspecialchars($submission[ | dynamic_or_external | open_route_or_trigger_ui |
| Assignments | assignments.php | link GET | assignments.php | assignments.php | read_db,write_db,json_response,redirect,file_io |
| Dashboard | assignments.php | link GET | teacher_dashboard.php | teacher_dashboard.php | read_db,write_db,json_response,redirect |
| Download brief | assignments.php | link GET | <?php echo htmlspecialchars($instructionsFile); ?> | dynamic_or_external | open_route_or_trigger_ui |
| Edit Profile | assignments.php | link GET | edit_profile.php | edit_profile.php | read_db,write_db,redirect |
| ICA Components | assignments.php | link GET | create_ica_components.php | create_ica_components.php | read_db,write_db,json_response,redirect |
| Logout | assignments.php | link GET | logout.php | logout.php | json_response |
| Manage ICA Marks | assignments.php | link GET | manage_ica_marks.php | manage_ica_marks.php | read_db,write_db,redirect,download,file_io |
| Publish Assignment | assignments.php | button POST | assignments.php | assignments.php | read_db,write_db,json_response,redirect,file_io |
| Save Changes | assignments.php | button POST | assignments.php | assignments.php | read_db,write_db,json_response,redirect,file_io |
| Switch to Program Chair | assignments.php | link GET | login_as.php?role=program_chair | login_as.php | read_db,write_db,redirect |
| Timetable | assignments.php | link GET | timetable.php | timetable.php | read_db,write_db,redirect,file_io |
| Update | assignments.php | button POST | assignments.php | assignments.php | read_db,write_db,json_response,redirect,file_io |
| Update Progress | assignments.php | link GET | update_progress.php | update_progress.php | read_db,write_db,json_response,redirect |
| View | assignments.php | link GET | assignments.php?assignment_id=<?php echo $rowId; ?> | assignments.php | read_db,write_db,json_response,redirect,file_io |
| View Alerts | assignments.php | link GET | view_alerts.php | view_alerts.php | read_db,write_db,redirect |
| View Reports | assignments.php | link GET | view_reports.php | view_reports.php | read_db,write_db,json_response,redirect,download,file_io |
| Assignments | create_ica_components.php | link GET | assignments.php | assignments.php | read_db,write_db,json_response,redirect,file_io |
| Copy Components | create_ica_components.php | button POST | create_ica_components.php | create_ica_components.php | read_db,write_db,json_response,redirect |
| Dashboard | create_ica_components.php | link GET | teacher_dashboard.php | teacher_dashboard.php | read_db,write_db,json_response,redirect |
| Edit Components | create_ica_components.php | link GET | # | dynamic_or_external | open_route_or_trigger_ui |
| Edit Profile | create_ica_components.php | link GET | edit_profile.php | edit_profile.php | read_db,write_db,redirect |
| ICA Components | create_ica_components.php | link GET | create_ica_components.php | create_ica_components.php | read_db,write_db,json_response,redirect |
| Logout | create_ica_components.php | link GET | logout.php | logout.php | json_response |
| Manage ICA Marks | create_ica_components.php | link GET | manage_ica_marks.php | manage_ica_marks.php | read_db,write_db,redirect,download,file_io |
| Save All Components | create_ica_components.php | button POST | create_ica_components.php | create_ica_components.php | read_db,write_db,json_response,redirect |
| Switch to Program Chair | create_ica_components.php | link GET | login_as.php?role=program_chair | login_as.php | read_db,write_db,redirect |
| Timetable | create_ica_components.php | link GET | timetable.php | timetable.php | read_db,write_db,redirect,file_io |
| Update Progress | create_ica_components.php | link GET | update_progress.php | update_progress.php | read_db,write_db,json_response,redirect |
| View Alerts | create_ica_components.php | link GET | view_alerts.php | view_alerts.php | read_db,write_db,redirect |
| View Reports | create_ica_components.php | link GET | view_reports.php | view_reports.php | read_db,write_db,json_response,redirect,download,file_io |
| Assignments | manage_ica_marks.php | link GET | assignments.php | assignments.php | read_db,write_db,json_response,redirect,file_io |
| Dashboard | manage_ica_marks.php | link GET | teacher_dashboard.php | teacher_dashboard.php | read_db,write_db,json_response,redirect |
| Edit Profile | manage_ica_marks.php | link GET | edit_profile.php | edit_profile.php | read_db,write_db,redirect |
| ICA Components | manage_ica_marks.php | link GET | create_ica_components.php | create_ica_components.php | read_db,write_db,json_response,redirect |
| Logout | manage_ica_marks.php | link GET | logout.php | logout.php | json_response |
| Manage ICA Marks | manage_ica_marks.php | link GET | manage_ica_marks.php | manage_ica_marks.php | read_db,write_db,redirect,download,file_io |
| Save Marks | manage_ica_marks.php | button POST | manage_ica_marks.php | manage_ica_marks.php | read_db,write_db,redirect,download,file_io |
| Switch to Program Chair | manage_ica_marks.php | link GET | login_as.php?role=program_chair | login_as.php | read_db,write_db,redirect |
| Timetable | manage_ica_marks.php | link GET | timetable.php | timetable.php | read_db,write_db,redirect,file_io |
| Update Progress | manage_ica_marks.php | link GET | update_progress.php | update_progress.php | read_db,write_db,json_response,redirect |
| Upload CSV | manage_ica_marks.php | button POST | manage_ica_marks.php | manage_ica_marks.php | read_db,write_db,redirect,download,file_io |
| View Alerts | manage_ica_marks.php | link GET | view_alerts.php | view_alerts.php | read_db,write_db,redirect |
| View Reports | manage_ica_marks.php | link GET | view_reports.php | view_reports.php | read_db,write_db,json_response,redirect,download,file_io |
| Assignments | reports.php | link GET | assignments.php | assignments.php | read_db,write_db,json_response,redirect,file_io |
| Dashboard | reports.php | link GET | teacher_dashboard.php | teacher_dashboard.php | read_db,write_db,json_response,redirect |
| Edit Profile | reports.php | link GET | edit_profile.php | edit_profile.php | read_db,write_db,redirect |
| Export to PDF | reports.php | link GET | ?export=pdf | dynamic_or_external | open_route_or_trigger_ui |
| ICA Components | reports.php | link GET | create_ica_components.php | create_ica_components.php | read_db,write_db,json_response,redirect |
| Logout | reports.php | link GET | logout.php | logout.php | json_response |
| Manage ICA Marks | reports.php | link GET | manage_ica_marks.php | manage_ica_marks.php | read_db,write_db,redirect,download,file_io |
| Timetable | reports.php | link GET | timetable.php | timetable.php | read_db,write_db,redirect,file_io |
| Update Progress | reports.php | link GET | update_progress.php | update_progress.php | read_db,write_db,json_response,redirect |
| View Alerts | reports.php | link GET | view_alerts.php | view_alerts.php | read_db,write_db,redirect |
| View Reports | reports.php | link GET | view_reports.php | view_reports.php | read_db,write_db,json_response,redirect,download,file_io |
| " style="display:block; text-decoration:none; color:inherit;"> Received on | teacher_dashboard.php | link GET | view_alerts.php?id=<?php echo (int)$alert[ | view_alerts.php | read_db,write_db,redirect |
| Assignments | teacher_dashboard.php | link GET | assignments.php | assignments.php | read_db,write_db,json_response,redirect,file_io |
| Dashboard | teacher_dashboard.php | link GET | teacher_dashboard.php | teacher_dashboard.php | read_db,write_db,json_response,redirect |
| Edit Profile | teacher_dashboard.php | link GET | edit_profile.php | edit_profile.php | read_db,write_db,redirect |
| ICA Components | teacher_dashboard.php | link GET | create_ica_components.php | create_ica_components.php | read_db,write_db,json_response,redirect |
| Logout | teacher_dashboard.php | link GET | logout.php | logout.php | json_response |
| Manage ICA Marks | teacher_dashboard.php | link GET | manage_ica_marks.php | manage_ica_marks.php | read_db,write_db,redirect,download,file_io |
| Switch to Program Chair | teacher_dashboard.php | link GET | login_as.php?role=program_chair | login_as.php | read_db,write_db,redirect |
| Timetable | teacher_dashboard.php | link GET | timetable.php | timetable.php | read_db,write_db,redirect,file_io |
| Update Progress | teacher_dashboard.php | link GET | update_progress.php | update_progress.php | read_db,write_db,json_response,redirect |
| Update Progress | teacher_dashboard.php | link GET | update_progress.php?subject=${encodeURIComponent(subjectNameRaw)}&class_id=${classId}&section_id=${sectionId}&assignment_key=${encodeURIComponent(assignmentKey)} | update_progress.php | read_db,write_db,json_response,redirect |
| View Alerts | teacher_dashboard.php | link GET | view_alerts.php | view_alerts.php | read_db,write_db,redirect |
| View all | teacher_dashboard.php | link GET | view_alerts.php | view_alerts.php | read_db,write_db,redirect |
| View Reports | teacher_dashboard.php | link GET | view_reports.php | view_reports.php | read_db,write_db,json_response,redirect,download,file_io |
| " class="btn" style="background-color: #0d6efd;">Edit | timetable.php | link GET | timetable.php?edit_id=<?php echo (int)$row[ | timetable.php | read_db,write_db,redirect,file_io |
| " class="btn" style="background-color: #dc3545;" onclick="return confirm('Are you sure you want to delete this timetable?');">Delete | timetable.php | link GET | timetable.php?delete_id=<?php echo (int)$row[ | timetable.php | read_db,write_db,redirect,file_io |
| " target="_blank" class="btn">View | timetable.php | link GET | <?php echo htmlspecialchars($row[ | dynamic_or_external | open_route_or_trigger_ui |
| Assignments | timetable.php | link GET | assignments.php | assignments.php | read_db,write_db,json_response,redirect,file_io |
| Cancel | timetable.php | link GET | timetable.php | timetable.php | read_db,write_db,redirect,file_io |
| Dashboard | timetable.php | link GET | teacher_dashboard.php | teacher_dashboard.php | read_db,write_db,json_response,redirect |
| Edit Profile | timetable.php | link GET | edit_profile.php | edit_profile.php | read_db,write_db,redirect |
| ICA Components | timetable.php | link GET | create_ica_components.php | create_ica_components.php | read_db,write_db,json_response,redirect |
| Logout | timetable.php | link GET | logout.php | logout.php | json_response |
| Manage ICA Marks | timetable.php | link GET | manage_ica_marks.php | manage_ica_marks.php | read_db,write_db,redirect,download,file_io |
| Save Changes | timetable.php | button POST | timetable.php | timetable.php | read_db,write_db,redirect,file_io |
| Switch to Program Chair | timetable.php | link GET | login_as.php?role=program_chair | login_as.php | read_db,write_db,redirect |
| Timetable | timetable.php | link GET | timetable.php | timetable.php | read_db,write_db,redirect,file_io |
| Update Progress | timetable.php | link GET | update_progress.php | update_progress.php | read_db,write_db,json_response,redirect |
| Upload | timetable.php | button POST | timetable.php | timetable.php | read_db,write_db,redirect,file_io |
| View Alerts | timetable.php | link GET | view_alerts.php | view_alerts.php | read_db,write_db,redirect |
| View Reports | timetable.php | link GET | view_reports.php | view_reports.php | read_db,write_db,json_response,redirect,download,file_io |
| >Update Progress | update_progress.php | button POST | update_progress.php | update_progress.php | read_db,write_db,json_response,redirect |
| Assignments | update_progress.php | link GET | assignments.php | assignments.php | read_db,write_db,json_response,redirect,file_io |
| Dashboard | update_progress.php | link GET | teacher_dashboard.php | teacher_dashboard.php | read_db,write_db,json_response,redirect |
| Edit Profile | update_progress.php | link GET | edit_profile.php | edit_profile.php | read_db,write_db,redirect |
| ICA Components | update_progress.php | link GET | create_ica_components.php | create_ica_components.php | read_db,write_db,json_response,redirect |
| Logout | update_progress.php | link GET | logout.php | logout.php | json_response |
| Manage ICA Marks | update_progress.php | link GET | manage_ica_marks.php | manage_ica_marks.php | read_db,write_db,redirect,download,file_io |
| Switch to Program Chair | update_progress.php | link GET | login_as.php?role=program_chair | login_as.php | read_db,write_db,redirect |
| Timetable | update_progress.php | link GET | timetable.php | timetable.php | read_db,write_db,redirect,file_io |
| Update Progress | update_progress.php | link GET | update_progress.php | update_progress.php | read_db,write_db,json_response,redirect |
| View Alerts | update_progress.php | link GET | view_alerts.php | view_alerts.php | read_db,write_db,redirect |
| View Reports | update_progress.php | link GET | view_reports.php | view_reports.php | read_db,write_db,json_response,redirect,download,file_io |
| Assignments | view_alerts.php | link GET | assignments.php | assignments.php | read_db,write_db,json_response,redirect,file_io |
| Back to Dashboard | view_alerts.php | link GET | teacher_dashboard.php | teacher_dashboard.php | read_db,write_db,json_response,redirect |
| Dashboard | view_alerts.php | link GET | teacher_dashboard.php | teacher_dashboard.php | read_db,write_db,json_response,redirect |
| Edit Profile | view_alerts.php | link GET | edit_profile.php | edit_profile.php | read_db,write_db,redirect |
| ICA Components | view_alerts.php | link GET | create_ica_components.php | create_ica_components.php | read_db,write_db,json_response,redirect |
| Logout | view_alerts.php | link GET | logout.php | logout.php | json_response |
| Manage ICA Marks | view_alerts.php | link GET | manage_ica_marks.php | manage_ica_marks.php | read_db,write_db,redirect,download,file_io |
| Respond | view_alerts.php | button POST | view_alerts.php | view_alerts.php | read_db,write_db,redirect |
| Switch to Program Chair | view_alerts.php | link GET | login_as.php?role=program_chair | login_as.php | read_db,write_db,redirect |
| Timetable | view_alerts.php | link GET | timetable.php | timetable.php | read_db,write_db,redirect,file_io |
| Update Progress | view_alerts.php | link GET | update_progress.php | update_progress.php | read_db,write_db,json_response,redirect |
| View Alerts | view_alerts.php | link GET | view_alerts.php | view_alerts.php | read_db,write_db,redirect |
| View Reports | view_alerts.php | link GET | view_reports.php | view_reports.php | read_db,write_db,json_response,redirect,download,file_io |
| ">Show all students | view_reports.php | link GET | <?php echo htmlspecialchars(removeQueryParams([ | dynamic_or_external | open_route_or_trigger_ui |
| Assignments | view_reports.php | link GET | assignments.php | assignments.php | read_db,write_db,json_response,redirect,file_io |
| Dashboard | view_reports.php | link GET | teacher_dashboard.php | teacher_dashboard.php | read_db,write_db,json_response,redirect |
| Download CSV | view_reports.php | link GET | ?export=csv_summary&subject_id=<?php echo $selected_subject; ?>&class_id=<?php echo $selected_class; ?> | dynamic_or_external | open_route_or_trigger_ui |
| Edit Profile | view_reports.php | link GET | edit_profile.php | edit_profile.php | read_db,write_db,redirect |
| ICA Components | view_reports.php | link GET | create_ica_components.php | create_ica_components.php | read_db,write_db,json_response,redirect |
| Logout | view_reports.php | link GET | logout.php | logout.php | json_response |
| Manage ICA Marks | view_reports.php | link GET | manage_ica_marks.php | manage_ica_marks.php | read_db,write_db,redirect,download,file_io |
| Switch to Program Chair | view_reports.php | link GET | login_as.php?role=program_chair | login_as.php | read_db,write_db,redirect |
| Timetable | view_reports.php | link GET | timetable.php | timetable.php | read_db,write_db,redirect,file_io |
| Update Progress | view_reports.php | link GET | update_progress.php | update_progress.php | read_db,write_db,json_response,redirect |
| View Alerts | view_reports.php | link GET | view_alerts.php | view_alerts.php | read_db,write_db,redirect |
| View Reports | view_reports.php | link GET | view_reports.php | view_reports.php | read_db,write_db,json_response,redirect,download,file_io |

### student
| Option | From | Click | To | Connected Target | On Click, What Happens |
|---|---|---|---|---|---|
| " download>Download | student_dashboard.php | link GET | <?php echo htmlspecialchars($file[ | dynamic_or_external | open_route_or_trigger_ui |
| 0 ? '' : ' success'; ?>" title="Open Subject Comparison"> At-risk subjects 0 ? 'Check announcements.' : 'All subjects on track.'; ?> | student_dashboard.php | link GET | subject_comparison.php | subject_comparison.php | read_db,write_db,json_response,redirect |
| Assignments | student_dashboard.php | link GET | view_assignment_marks.php | view_assignment_marks.php | read_db,write_db,redirect,file_io |
| Assignments completed Keep going. | student_dashboard.php | link GET | view_assignment_marks.php | view_assignment_marks.php | read_db,write_db,redirect,file_io |
| Dashboard | student_dashboard.php | link GET | student_dashboard.php | student_dashboard.php | read_db,json_response,redirect |
| Edit Profile | student_dashboard.php | link GET | edit_profile.php | edit_profile.php | read_db,write_db,redirect |
| Logout | student_dashboard.php | link GET | logout.php | logout.php | json_response |
| Marks | student_dashboard.php | link GET | view_marks.php | view_marks.php | read_db,write_db,redirect |
| Open full view | student_dashboard.php | link GET | subject_comparison.php | subject_comparison.php | read_db,write_db,json_response,redirect |
| Overall performance % components evaluated. | student_dashboard.php | link GET | view_marks.php | view_marks.php | read_db,write_db,redirect |
| Subject Comparison | student_dashboard.php | link GET | subject_comparison.php | subject_comparison.php | read_db,write_db,json_response,redirect |
| Subjects enrolled pending. | student_dashboard.php | link GET | view_progress.php | view_progress.php | read_db,write_db,json_response,redirect |
| Syllabus Progress | student_dashboard.php | link GET | view_progress.php | view_progress.php | read_db,write_db,json_response,redirect |
| Timetable | student_dashboard.php | link GET | view_timetable.php | view_timetable.php | read_db,redirect |
| View all | student_dashboard.php | link GET | view_assignment_marks.php | view_assignment_marks.php | read_db,write_db,redirect,file_io |
| View all | student_dashboard.php | link GET | view_timetable.php | view_timetable.php | read_db,redirect |
| View assignments | student_dashboard.php | link GET | view_assignment_marks.php | view_assignment_marks.php | read_db,write_db,redirect,file_io |
| Apply Filter | subject_comparison.php | button GET | subject_comparison.php | subject_comparison.php | read_db,write_db,json_response,redirect |
| Assignments | subject_comparison.php | link GET | view_assignment_marks.php | view_assignment_marks.php | read_db,write_db,redirect,file_io |
| Back to dashboard | subject_comparison.php | link GET | student_dashboard.php | student_dashboard.php | read_db,json_response,redirect |
| Clear Filters | subject_comparison.php | link GET | subject_comparison.php | subject_comparison.php | read_db,write_db,json_response,redirect |
| Dashboard | subject_comparison.php | link GET | student_dashboard.php | student_dashboard.php | read_db,json_response,redirect |
| Edit Profile | subject_comparison.php | link GET | edit_profile.php | edit_profile.php | read_db,write_db,redirect |
| Logout | subject_comparison.php | link GET | logout.php | logout.php | json_response |
| Marks | subject_comparison.php | link GET | view_marks.php | view_marks.php | read_db,write_db,redirect |
| Subject Comparison | subject_comparison.php | link GET | subject_comparison.php | subject_comparison.php | read_db,write_db,json_response,redirect |
| Syllabus Progress | subject_comparison.php | link GET | view_progress.php | view_progress.php | read_db,write_db,json_response,redirect |
| Timetable | subject_comparison.php | link GET | view_timetable.php | view_timetable.php | read_db,redirect |
| " target="_blank">Download | view_assignment_marks.php | link GET | <?php echo htmlspecialchars($assignment[ | dynamic_or_external | open_route_or_trigger_ui |
| " target="_blank">View submission | view_assignment_marks.php | link GET | <?php echo htmlspecialchars($assignment[ | dynamic_or_external | open_route_or_trigger_ui |
| Assignments | view_assignment_marks.php | link GET | view_assignment_marks.php | view_assignment_marks.php | read_db,write_db,redirect,file_io |
| Dashboard | view_assignment_marks.php | link GET | student_dashboard.php | student_dashboard.php | read_db,json_response,redirect |
| Edit Profile | view_assignment_marks.php | link GET | edit_profile.php | edit_profile.php | read_db,write_db,redirect |
| Logout | view_assignment_marks.php | link GET | logout.php | logout.php | json_response |
| Marks | view_assignment_marks.php | link GET | view_marks.php | view_marks.php | read_db,write_db,redirect |
| Subject Comparison | view_assignment_marks.php | link GET | subject_comparison.php | subject_comparison.php | read_db,write_db,json_response,redirect |
| Submit | view_assignment_marks.php | button POST | view_assignment_marks.php | view_assignment_marks.php | read_db,write_db,redirect,file_io |
| Syllabus Progress | view_assignment_marks.php | link GET | view_progress.php | view_progress.php | read_db,write_db,json_response,redirect |
| Timetable | view_assignment_marks.php | link GET | view_timetable.php | view_timetable.php | read_db,redirect |
| Assignments | view_marks.php | link GET | view_assignment_marks.php | view_assignment_marks.php | read_db,write_db,redirect,file_io |
| Dashboard | view_marks.php | link GET | student_dashboard.php | student_dashboard.php | read_db,json_response,redirect |
| Edit Profile | view_marks.php | link GET | edit_profile.php | edit_profile.php | read_db,write_db,redirect |
| Logout | view_marks.php | link GET | logout.php | logout.php | json_response |
| Marks | view_marks.php | link GET | view_marks.php | view_marks.php | read_db,write_db,redirect |
| Subject Comparison | view_marks.php | link GET | subject_comparison.php | subject_comparison.php | read_db,write_db,json_response,redirect |
| Syllabus Progress | view_marks.php | link GET | view_progress.php | view_progress.php | read_db,write_db,json_response,redirect |
| Timetable | view_marks.php | link GET | view_timetable.php | view_timetable.php | read_db,redirect |
| Assignments | view_progress.php | link GET | view_assignment_marks.php | view_assignment_marks.php | read_db,write_db,redirect,file_io |
| Dashboard | view_progress.php | link GET | student_dashboard.php | student_dashboard.php | read_db,json_response,redirect |
| Edit Profile | view_progress.php | link GET | edit_profile.php | edit_profile.php | read_db,write_db,redirect |
| Logout | view_progress.php | link GET | logout.php | logout.php | json_response |
| Marks | view_progress.php | link GET | view_marks.php | view_marks.php | read_db,write_db,redirect |
| Subject Comparison | view_progress.php | link GET | subject_comparison.php | subject_comparison.php | read_db,write_db,json_response,redirect |
| Syllabus Progress | view_progress.php | link GET | view_progress.php | view_progress.php | read_db,write_db,json_response,redirect |
| Timetable | view_progress.php | link GET | view_timetable.php | view_timetable.php | read_db,redirect |
| "> Download | view_timetable.php | link GET | <?php echo $safePath; ?> | dynamic_or_external | open_route_or_trigger_ui |
| Assignments | view_timetable.php | link GET | view_assignment_marks.php | view_assignment_marks.php | read_db,write_db,redirect,file_io |
| Dashboard | view_timetable.php | link GET | student_dashboard.php | student_dashboard.php | read_db,json_response,redirect |
| Edit Profile | view_timetable.php | link GET | edit_profile.php | edit_profile.php | read_db,write_db,redirect |
| Logout | view_timetable.php | link GET | logout.php | logout.php | json_response |
| Marks | view_timetable.php | link GET | view_marks.php | view_marks.php | read_db,write_db,redirect |
| Subject Comparison | view_timetable.php | link GET | subject_comparison.php | subject_comparison.php | read_db,write_db,json_response,redirect |
| Syllabus Progress | view_timetable.php | link GET | view_progress.php | view_progress.php | read_db,write_db,json_response,redirect |
| Timetable | view_timetable.php | link GET | view_timetable.php | view_timetable.php | read_db,redirect |

### shared_or_unscoped
| Option | From | Click | To | Connected Target | On Click, What Happens |
|---|---|---|---|---|---|
| Update Password | change_password.php | button POST | change_password.php | change_password.php | read_db,write_db,redirect |
| Alerts | edit_profile.php | link GET | send_alerts.php | send_alerts.php | read_db,write_db,redirect |
| Assignments | edit_profile.php | link GET | assignments.php | assignments.php | read_db,write_db,json_response,redirect,file_io |
| Assignments | edit_profile.php | link GET | view_assignment_marks.php | view_assignment_marks.php | read_db,write_db,redirect,file_io |
| Courses | edit_profile.php | link GET | course_progress.php | course_progress.php | read_db,write_db,json_response,redirect,download,file_io |
| Dashboard | edit_profile.php | link GET | program_dashboard.php | program_dashboard.php | read_db,write_db,json_response,redirect,download,file_io |
| Dashboard | edit_profile.php | link GET | student_dashboard.php | student_dashboard.php | read_db,json_response,redirect |
| Dashboard | edit_profile.php | link GET | teacher_dashboard.php | teacher_dashboard.php | read_db,write_db,json_response,redirect |
| Edit Profile | edit_profile.php | link GET | edit_profile.php | edit_profile.php | read_db,write_db,redirect |
| ICA Components | edit_profile.php | link GET | create_ica_components.php | create_ica_components.php | read_db,write_db,json_response,redirect |
| Logout | edit_profile.php | link GET | logout.php | logout.php | json_response |
| Manage ICA Marks | edit_profile.php | link GET | manage_ica_marks.php | manage_ica_marks.php | read_db,write_db,redirect,download,file_io |
| Marks | edit_profile.php | link GET | view_marks.php | view_marks.php | read_db,write_db,redirect |
| Reports | edit_profile.php | link GET | program_reports.php | program_reports.php | read_db,write_db,json_response,redirect,download,file_io |
| Save Changes | edit_profile.php | button POST | edit_profile.php | edit_profile.php | read_db,write_db,redirect |
| Settings | edit_profile.php | link GET | settings.php | settings.php | read_db,write_db,redirect |
| Students | edit_profile.php | link GET | student_progress.php | student_progress.php | read_db,json_response,redirect,download,file_io |
| Subject Comparison | edit_profile.php | link GET | subject_comparison.php | subject_comparison.php | read_db,write_db,json_response,redirect |
| Switch to Program Chair | edit_profile.php | link GET | login_as.php?role=program_chair | login_as.php | read_db,write_db,redirect |
| Switch to Teacher | edit_profile.php | link GET | login_as.php?role=teacher | login_as.php | read_db,write_db,redirect |
| Syllabus Progress | edit_profile.php | link GET | view_progress.php | view_progress.php | read_db,write_db,json_response,redirect |
| Teachers | edit_profile.php | link GET | teacher_progress.php | teacher_progress.php | read_db,write_db,json_response,redirect |
| Timetable | edit_profile.php | link GET | timetable.php | timetable.php | read_db,write_db,redirect,file_io |
| Timetable | edit_profile.php | link GET | view_timetable.php | view_timetable.php | read_db,redirect |
| Update Progress | edit_profile.php | link GET | update_progress.php | update_progress.php | read_db,write_db,json_response,redirect |
| View Alerts | edit_profile.php | link GET | view_alerts.php | view_alerts.php | read_db,write_db,redirect |
| View Reports | edit_profile.php | link GET | view_reports.php | view_reports.php | read_db,write_db,json_response,redirect,download,file_io |
| Back to Login | forgot_password.php | link GET | login.php | login.php | read_db,write_db,redirect |
| Send Reset Link | forgot_password.php | button POST | forgot_password.php | forgot_password.php | read_db,write_db |
| 9392123577 | index.php | link GET | tel:+919392123577 | dynamic_or_external | open_route_or_trigger_ui |
| About | index.php | link GET | #about | dynamic_or_external | open_route_or_trigger_ui |
| Admin | index.php | link GET | admin_login.php | admin_login.php | read_db,write_db,redirect |
| Admin Login | index.php | link GET | admin_login.php | admin_login.php | read_db,write_db,redirect |
| Contact | index.php | link GET | #contact | dynamic_or_external | open_route_or_trigger_ui |
| Get Started | index.php | link GET | login.php | login.php | read_db,write_db,redirect |
| Home | index.php | link GET | index.php | index.php | render_page |
| KUCHURUSAI.KRISHNA34@nmims.in | index.php | link GET | mailto:KUCHURUSAI.KRISHNA34@nmims.in | dynamic_or_external | open_route_or_trigger_ui |
| Login | index.php | link GET | login.php | login.php | read_db,write_db,redirect |
| Program Chair | index.php | link GET | login.php | login.php | read_db,write_db,redirect |
| sherymounika.reddy32@nmims.in | index.php | link GET | mailto:sherymounika.reddy32@nmims.in | dynamic_or_external | open_route_or_trigger_ui |
| shiva.ganesh21@nmims.in | index.php | link GET | mailto:shiva.ganesh21@nmims.in | dynamic_or_external | open_route_or_trigger_ui |
| Student | index.php | link GET | login.php | login.php | read_db,write_db,redirect |
| Teacher | index.php | link GET | login.php | login.php | read_db,write_db,redirect |
| Contact | login.php | link GET | http://localhost/ica_tracker/index.php#contact | dynamic_or_external | open_route_or_trigger_ui |
| Forgot Password? | login.php | link GET | forgot_password.php | forgot_password.php | read_db,write_db |
| Login | login.php | button POST | login.php | login.php | read_db,write_db,redirect |
| Portal Home | login.php | link GET | index.php | index.php | render_page |
| Login as Program Chair | login_as.php | button POST | login_as.php | login_as.php | read_db,write_db,redirect |
| Login as Teacher | login_as.php | button POST | login_as.php | login_as.php | read_db,write_db,redirect |
| Back to Login | reset_password.php | link GET | login.php | login.php | read_db,write_db,redirect |
| Request New Reset Link | reset_password.php | link GET | forgot_password.php | forgot_password.php | read_db,write_db |
| Set Password | reset_password.php | button POST | reset_password.php | reset_password.php | read_db,write_db,redirect |
| Login | signup.php | link GET | login.php | login.php | read_db,write_db,redirect |
| Sign Up | signup.php | button POST | signup.php | signup.php | read_db,write_db |

### User Intent Interpretation of Click Outcomes
- read_db: page/API fetches records from database to display data.
- write_db: click leads to insert/update/delete workflow (directly or via submitted form).
- redirect: request changes route using Location header.
- json_response: endpoint returns JSON for AJAX modal/table updates.
- download: click triggers file export/download (CSV/PDF/attachment).
- file_io: request reads/writes local files such as uploads/templates/logs.
- render_page: click mainly opens UI view without explicit SQL/I-O action in that file.
