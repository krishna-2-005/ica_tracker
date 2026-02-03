<?php
session_start();
include 'db_connect.php';
require_once __DIR__ . '/includes/academic_context.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';
$adminNameRaw = isset($_SESSION['name']) ? trim((string)$_SESSION['name']) : '';
$adminNameDisplay = $adminNameRaw !== '' ? format_person_display($adminNameRaw) : 'ADMIN';

function get_academic_year_options(): array
{
    $currentYear = (int)date('Y');
    $startYear = $currentYear - 5;
    $endYear = $currentYear + 6;
    $options = [];
    for ($year = $startYear; $year <= $endYear; $year++) {
        $options[] = $year . '-' . ($year + 1);
    }
    return $options;
}

function build_calendar_metadata(string $semester, string $start_date, string $end_date): array
{
    $semester_clean = strtolower(trim($semester));
    $semester_number = null;
    if ($semester_clean === 'odd') {
        $semester_number = 1;
    } elseif ($semester_clean === 'even') {
        $semester_number = 2;
    }

    $start_ts = strtotime($start_date);
    $end_ts = strtotime($end_date);
    $academic_year = '';
    if ($start_ts && $end_ts) {
        $start_year = (int)date('Y', $start_ts);
        $end_year = (int)date('Y', $end_ts);
        $display_end_year = ($end_year === $start_year) ? $start_year + 1 : $end_year;
        $academic_year = $start_year . '-' . $display_end_year;
    }

    return [
        'semester_number' => $semester_number,
        'academic_year' => $academic_year,
    ];
}

// Helper function to save the calendar for a specific semester
function save_calendar($conn, $school_name, $semester, $start_date, $end_date, ?string $academic_year_override = null) {
    ensureAcademicCalendarSchema($conn);

    $meta = build_calendar_metadata($semester, $start_date, $end_date);
    $semester_number = $meta['semester_number'];
    $academic_year = $academic_year_override !== null && $academic_year_override !== '' ? $academic_year_override : $meta['academic_year'];

    if ($semester_number === null) {
        $insert_query = "INSERT INTO academic_calendar (school_name, semester_term, start_date, end_date, academic_year, semester_number) VALUES (?, ?, ?, ?, ?, NULL)";
        $stmt_insert = mysqli_prepare($conn, $insert_query);
        if (!$stmt_insert) {
            return false;
        }
        mysqli_stmt_bind_param($stmt_insert, "sssss", $school_name, $semester, $start_date, $end_date, $academic_year);
    } else {
        $insert_query = "INSERT INTO academic_calendar (school_name, semester_term, start_date, end_date, academic_year, semester_number) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_insert = mysqli_prepare($conn, $insert_query);
        if (!$stmt_insert) {
            return false;
        }
        mysqli_stmt_bind_param($stmt_insert, "sssssi", $school_name, $semester, $start_date, $end_date, $academic_year, $semester_number);
    }

    $result = mysqli_stmt_execute($stmt_insert);
    mysqli_stmt_close($stmt_insert);
    return $result;
}

function update_calendar($conn, int $calendar_id, string $school_name, string $semester, string $start_date, string $end_date, ?string $academic_year_override = null): bool
{
    ensureAcademicCalendarSchema($conn);

    $meta = build_calendar_metadata($semester, $start_date, $end_date);
    $semester_number = $meta['semester_number'];
    $academic_year = $academic_year_override !== null && $academic_year_override !== '' ? $academic_year_override : $meta['academic_year'];

    if ($semester_number === null) {
        $update_query = "UPDATE academic_calendar SET school_name = ?, semester_term = ?, start_date = ?, end_date = ?, academic_year = ?, semester_number = NULL WHERE id = ?";
        $stmt_update = mysqli_prepare($conn, $update_query);
        if (!$stmt_update) {
            return false;
        }
        mysqli_stmt_bind_param($stmt_update, "sssssi", $school_name, $semester, $start_date, $end_date, $academic_year, $calendar_id);
    } else {
        $update_query = "UPDATE academic_calendar SET school_name = ?, semester_term = ?, start_date = ?, end_date = ?, academic_year = ?, semester_number = ? WHERE id = ?";
        $stmt_update = mysqli_prepare($conn, $update_query);
        if (!$stmt_update) {
            return false;
        }
        mysqli_stmt_bind_param($stmt_update, "sssssii", $school_name, $semester, $start_date, $end_date, $academic_year, $semester_number, $calendar_id);
    }

    $result = mysqli_stmt_execute($stmt_update);
    mysqli_stmt_close($stmt_update);
    return $result;
}

// --- FINAL ROBUST PDF PARSING LOGIC ---
if (isset($_POST['upload_pdf']) && isset($_FILES['calendar_pdf'])) {
    $school_name = (string)$_POST['school'];
    $semester = (string)$_POST['semester'];
    
    if (empty($school_name) || empty($semester)) {
        $error = "Please select a school and a semester.";
    } elseif ($_FILES['calendar_pdf']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['calendar_pdf']['tmp_name'];
        
        $raw_text = shell_exec("pdftotext \"$file_tmp_path\" -layout -");

        if ($raw_text) {
            // Define extremely flexible, case-insensitive patterns that account for any whitespace, including newlines.
            // The 's' modifier makes '.' match newlines, and 'i' makes it case-insensitive.
            $odd_header_pattern = '/Odd.*?Semester.*?Term.*?I/is';
            $even_header_pattern = '/Even.*?Semester.*?Term.*?II/is';
            $next_year_pattern = '/Commencement of next Academic year/is';
            
            $semester_header_pattern = ($semester == 'odd') ? $odd_header_pattern : $even_header_pattern;
            $next_section_pattern = ($semester == 'odd') ? $even_header_pattern : $next_year_pattern;

            // Find the starting position of the selected semester's section
            if (preg_match($semester_header_pattern, $raw_text, $matches, PREG_OFFSET_CAPTURE)) {
                $start_pos = $matches[0][1];
                
                // Find the starting position of the *next* section to define the boundary
                if (preg_match($next_section_pattern, $raw_text, $next_matches, PREG_OFFSET_CAPTURE, $start_pos)) {
                    $end_pos = $next_matches[0][1];
                    $semester_text = substr($raw_text, $start_pos, $end_pos - $start_pos);
                } else {
                    // If the next section isn't found, just take the rest of the document
                    $semester_text = substr($raw_text, $start_pos);
                }

                // Now, find the "Commencement" and "Term End" dates within this isolated text
                if (preg_match('/Commencement of Term.*?([a-zA-Z]+\s+\d{1,2}(?:st|nd|rd|th)?,\s+\d{4})/is', $semester_text, $start_match) &&
                    preg_match('/Term End Exam.*?([a-zA-Z]+\s+\d{1,2}(?:st|nd|rd|th)?,\s+\d{4})/is', $semester_text, $end_match)) {
                    
                    $start_date = date('Y-m-d', strtotime($start_match[1]));
                    $end_date = date('Y-m-d', strtotime($end_match[1]));

                    $meta = build_calendar_metadata($semester, $start_date, $end_date);
                    $academic_year_auto = $meta['academic_year'] ?? '';
                    if (save_calendar($conn, $school_name, $semester, $start_date, $end_date, $academic_year_auto)) {
                        $success = "Calendar for ".ucfirst($semester)." Semester added! Start: <b>$start_date</b>, End: <b>$end_date</b>";
                    } else {
                        $error = "Database error after processing PDF.";
                    }
                } else {
                    $error = "Found the semester section, but could not automatically find the 'Commencement of Term' or 'Term End Exam' dates within it.";
                }
            } else {
                 $error = "Could not find the section header for the selected semester in the PDF. The text might be formatted unusually.";
            }
        } else {
            $error = "Failed to extract text from the PDF. Please ensure 'pdftotext' is installed and permissions are correct.";
        }
    } else {
        $error = "There was an error uploading the file.";
    }
}

// Handle manual form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_date']) && !isset($_POST['upload_pdf'])) {
    $calendar_action = isset($_POST['calendar_action']) ? trim((string)$_POST['calendar_action']) : 'create';
    $calendar_id = isset($_POST['calendar_id']) ? (int)$_POST['calendar_id'] : 0;
    $school_name = (string)$_POST['school'];
    $semester = (string)$_POST['semester'];
    $start_date = (string)$_POST['start_date'];
    $end_date = (string)$_POST['end_date'];
    $academic_year_input = isset($_POST['academic_year']) ? trim((string)$_POST['academic_year']) : '';
    $valid_academic_years = get_academic_year_options();

    if (empty($school_name) || empty($semester) || empty($start_date) || empty($end_date)) {
        $error = "All fields are required for manual entry.";
    } elseif ($academic_year_input === '' || !in_array($academic_year_input, $valid_academic_years, true)) {
        $error = "Please choose a valid academic year.";
    } elseif (strtotime($start_date) >= strtotime($end_date)) {
        $error = "Start date must be before end date.";
    } else {
        if ($calendar_action === 'update') {
            if ($calendar_id <= 0) {
                $error = "Missing calendar identifier for update.";
            } else {
                $check_stmt = mysqli_prepare($conn, "SELECT id FROM academic_calendar WHERE id = ? LIMIT 1");
                if ($check_stmt) {
                    mysqli_stmt_bind_param($check_stmt, "i", $calendar_id);
                    mysqli_stmt_execute($check_stmt);
                    mysqli_stmt_store_result($check_stmt);
                    $exists = mysqli_stmt_num_rows($check_stmt) > 0;
                    mysqli_stmt_close($check_stmt);
                } else {
                    $exists = false;
                }

                if (!$exists) {
                    $error = "The selected calendar entry no longer exists.";
                } else {
                    if (update_calendar($conn, $calendar_id, $school_name, $semester, $start_date, $end_date, $academic_year_input)) {
                        $success = "Academic calendar entry updated successfully.";
                    } else {
                        $error = "Failed to update the academic calendar entry.";
                    }
                }
            }
        } else {
            if (save_calendar($conn, $school_name, $semester, $start_date, $end_date, $academic_year_input)) {
                $success = "Academic calendar for ".ucfirst($semester)." Semester added successfully!";
            } else {
                $error = "Failed to add academic calendar entry.";
            }
        }
    }
}

ensureAcademicCalendarSchema($conn);
$calendar_entries = [];
$calendar_query = "SELECT id, school_name, semester_term, academic_year, semester_number, label_override, start_date, end_date, created_at FROM academic_calendar ORDER BY school_name, start_date DESC, id DESC";
$calendar_result = mysqli_query($conn, $calendar_query);
if ($calendar_result) {
    while ($row = mysqli_fetch_assoc($calendar_result)) {
        $calendar_entries[] = $row;
    }
    mysqli_free_result($calendar_result);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Academic Calendar - ICA Tracker</title>
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
    <link rel="stylesheet" href="ica_tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .card { background-color: #ffffff; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); margin: 20px 0; }
        body.dark-mode .card { background-color: #5a5a5a; color: #FFFFFF; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #333; }
        body.dark-mode .form-group label { color: #e0e0e0; }
        .form-group select, .form-group input[type="date"], .form-group input[type="file"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; }
        body.dark-mode .form-group select, body.dark-mode .form-group input[type="date"], body.dark-mode .form-group input[type="file"] { background-color: #444; color: #e0e0e0; border-color: #555; }
        .btn { background-color: #A6192E; color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-size: 1rem; transition: background-color 0.3s ease; }
        .btn:hover { background-color: #cc4b4b; }
        body.dark-mode .btn { background-color: #cc4b4b; }
        body.dark-mode .btn:hover { background-color: #e57373; }
        .error { color: #A6192E; font-weight: bold; margin-bottom: 15px; }
        .success { color: #388e3c; font-weight: bold; margin-bottom: 15px; }
        .btn-secondary { background-color: #6c757d; color: #fff; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-size: 1rem; transition: background-color 0.3s ease; }
        .btn-secondary:hover { background-color: #5a6268; }
        .calendar-table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        .calendar-table th, .calendar-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .calendar-table thead { background-color: #f5f5f5; }
        body.dark-mode .calendar-table thead { background-color: #3c3c3c; }
        body.dark-mode .calendar-table th, body.dark-mode .calendar-table td { border-color: #555; }
        .calendar-edit-note { margin-bottom: 12px; font-weight: 600; color: #A6192E; display: none; }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
    <link rel="icon" type="image/png" href="nmimsvertical.jpg">
    <link rel="apple-touch-icon" href="nmimsvertical.jpg">
            <h2>ICA Tracker</h2>
            <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
            <a href="manage_teachers.php"><i class="fas fa-chalkboard-teacher"></i> <span>Manage Teachers</span></a>
            <a href="create_classes.php"><i class="fas fa-layer-group"></i> <span>Create Classes</span></a>
            <a href="create_subjects.php"><i class="fas fa-book"></i> <span>Create Subjects</span></a>
            <a href="assign_teachers.php"><i class="fas fa-user-tag"></i> <span>Assign Teachers</span></a>
            <a href="manage_electives.php"><i class="fas fa-user-friends"></i> <span>Manage Electives</span></a>
            <a href="change_roles.php"><i class="fas fa-user-cog"></i> <span>Change Roles</span></a>
            <a href="bulk_add_students.php"><i class="fas fa-file-upload"></i> <span>Add Students</span></a>
                       <a href="manage_academic_calendar.php" class="active"><i class="fas fa-calendar-alt"></i> <span>Academic Calendar</span></a>

            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </div>
        <div class="main-content">
            <div class="header">
                <h2>Welcome, <?php echo htmlspecialchars($adminNameDisplay); ?>!</h2>
            </div>
           
                <div class="card">
                    <div class="card-header"><h5>Update Calendar Manually</h5></div>
                    <div class="card-body">
                        <?php if ($error) { echo "<div class=\"error\">$error</div>"; } ?>
                        <?php if ($success) { echo "<div class=\"success\">$success</div>"; } ?>
                        <p id="calendar-edit-note" class="calendar-edit-note">Editing existing timeline entry.</p>
                        <form method="POST" id="calendar-form">
                            <input type="hidden" name="calendar_action" id="calendar-action" value="create">
                            <input type="hidden" name="calendar_id" id="calendar-id" value="">
                            <div class="form-group">
                                <label for="school">Which School/Department is this calendar for? (e.g., STME)</label>
                                <select id="school" name="school" required>
                                    <option value="">-- Select a School --</option>
                                    <option value="STME">STME</option>
                                    <option value="SOL">SOL</option>
                                    <option value="SPTM">SPTM</option>
                                    <option value="SBM">SBM</option>
                                    <option value="SOC">SOC</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="semester_manual">Which Semester is this? (Odd = Term 1, Even = Term 2)</label>
                                <select id="semester_manual" name="semester" required>
                                    <option value="">-- Select Semester --</option>
                                    <option value="odd">Odd Semester / Term 1</option>
                                    <option value="even">Even Semester / Term 2</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="start_date">What is the Start Date of the Semester? (choose a date)</label>
                                <input type="date" id="start_date" name="start_date" required>
                            </div>
                            <div class="form-group">
                                <label for="end_date">What is the End Date of the Semester? (choose a date after the start date)</label>
                                <input type="date" id="end_date" name="end_date" required>
                            </div>
                            <div class="form-group">
                                <label for="academic-year-select">Which Academic Year does this timeline belong to?</label>
                                <select id="academic-year-select" name="academic_year" required>
                                    <option value="">Select academic year</option>
                                    <?php foreach (get_academic_year_options() as $yearOption) {
                                        $selectedAttr = '';
                                        if (isset($_POST['academic_year']) && $_POST['academic_year'] === $yearOption) {
                                            $selectedAttr = 'selected';
                                        }
                                        echo '<option value="' . htmlspecialchars($yearOption, ENT_QUOTES, 'UTF-8') . '" ' . $selectedAttr . '>' . htmlspecialchars($yearOption) . '</option>';
                                    } ?>
                                </select>
                            </div>
                            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                                <button type="submit" class="btn" id="calendar-submit">Save Academic Calendar</button>
                                <button type="button" class="btn-secondary" id="calendar-cancel" style="display:none;">Cancel Edit</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><h5>Existing Timelines</h5></div>
                    <div class="card-body">
                        <?php if (empty($calendar_entries)) { ?>
                            <p>No academic calendar entries recorded yet.</p>
                        <?php } else { ?>
                            <div style="overflow-x:auto;">
                                <table class="calendar-table">
                                    <thead>
                                        <tr>
                                            <th>School</th>
                                            <th>Timeline</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($calendar_entries as $entry) {
                                            $label_parts = [];
                                            if (!empty($entry['label_override'])) {
                                                $label_parts[] = $entry['label_override'];
                                            } else {
                                                if (!empty($entry['semester_number'])) {
                                                    $label_parts[] = 'Semester ' . (int)$entry['semester_number'];
                                                }
                                                if (!empty($entry['semester_term'])) {
                                                    $label_parts[] = ucfirst((string)$entry['semester_term']) . ' Term';
                                                }
                                                if (!empty($entry['academic_year'])) {
                                                    $label_parts[] = 'AY ' . $entry['academic_year'];
                                                }
                                            }
                                            $label_text = $label_parts ? implode(' • ', $label_parts) : 'Timeline';
                                            if (!empty($entry['start_date']) && !empty($entry['end_date'])) {
                                                $label_text .= ' • ' . date('d M Y', strtotime((string)$entry['start_date'])) . ' - ' . date('d M Y', strtotime((string)$entry['end_date']));
                                            }
                                            $created_at = isset($entry['created_at']) && $entry['created_at'] !== null ? date('d M Y H:i', strtotime((string)$entry['created_at'])) : 'N/A';
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($entry['school_name']); ?></td>
                                            <td><?php echo htmlspecialchars($label_text); ?></td>
                                            <td><?php echo htmlspecialchars(date('Y-m-d', strtotime((string)$entry['start_date']))); ?></td>
                                            <td><?php echo htmlspecialchars(date('Y-m-d', strtotime((string)$entry['end_date']))); ?></td>
                                            <td><?php echo htmlspecialchars($created_at); ?></td>
                                            <td>
                                                <button type="button" class="btn" data-calendar-edit
                                                    data-calendar-id="<?php echo (int)$entry['id']; ?>"
                                                    data-school="<?php echo htmlspecialchars($entry['school_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-semester="<?php echo htmlspecialchars($entry['semester_term'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-start="<?php echo htmlspecialchars(date('Y-m-d', strtotime((string)$entry['start_date']))); ?>"
                                                    data-end="<?php echo htmlspecialchars(date('Y-m-d', strtotime((string)$entry['end_date']))); ?>"
                                                    data-year="<?php echo htmlspecialchars($entry['academic_year'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                >Edit</button>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function toggleTheme() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
        }
        if (localStorage.getItem('theme') === 'dark') document.body.classList.add('dark-mode');

        (function(){
            const form = document.getElementById('calendar-form');
            if (!form) {
                return;
            }

            const actionField = document.getElementById('calendar-action');
            const idField = document.getElementById('calendar-id');
            const schoolField = document.getElementById('school');
            const semesterField = document.getElementById('semester_manual');
            const startField = document.getElementById('start_date');
            const endField = document.getElementById('end_date');
            const yearField = document.getElementById('academic-year-select');
            const submitButton = document.getElementById('calendar-submit');
            const cancelButton = document.getElementById('calendar-cancel');
            const editNote = document.getElementById('calendar-edit-note');

            function toISO(value) {
                if (!value) {
                    return '';
                }
                return value.slice(0, 10);
            }

            function enterEditMode(details) {
                if (!details) {
                    return;
                }
                actionField.value = 'update';
                idField.value = details.id;
                schoolField.value = details.school;
                semesterField.value = details.semester;
                startField.value = toISO(details.start);
                endField.value = toISO(details.end);
                if (yearField) {
                    yearField.value = details.year && details.year !== '' ? details.year : '';
                }
                submitButton.textContent = 'Update Academic Calendar';
                cancelButton.style.display = 'inline-block';
                editNote.style.display = 'block';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }

            function exitEditMode() {
                actionField.value = 'create';
                idField.value = '';
                form.reset();
                if (yearField) {
                    yearField.value = '';
                }
                submitButton.textContent = 'Save Academic Calendar';
                cancelButton.style.display = 'none';
                editNote.style.display = 'none';
            }

            document.querySelectorAll('[data-calendar-edit]').forEach(function(button){
                button.addEventListener('click', function(){
                    const payload = {
                        id: button.getAttribute('data-calendar-id'),
                        school: button.getAttribute('data-school') || '',
                        semester: button.getAttribute('data-semester') || '',
                        start: button.getAttribute('data-start') || '',
                        end: button.getAttribute('data-end') || '',
                        year: button.getAttribute('data-year') || ''
                    };
                    enterEditMode(payload);
                });
            });

            if (cancelButton) {
                cancelButton.addEventListener('click', function(){
                    exitEditMode();
                });
            }
        })();
    </script>
</body>
</html>
