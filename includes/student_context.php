<?php
if (!function_exists('fetchStudentProfilesBySap')) {
    function fetchStudentProfilesBySap(mysqli $conn, string $sapId): array
    {
        $sql = "SELECT s.id, s.sap_id, s.name, s.roll_number, s.class_id, s.section_id, s.college_email,\n                       c.class_name, c.semester, c.department, c.school, c.academic_term_id,\n                       sec.section_name,\n                       ac.semester_number AS term_semester_number, ac.semester_term AS term_semester_term,\n                       ac.start_date AS term_start, ac.end_date AS term_end\n                FROM students s\n                LEFT JOIN classes c ON s.class_id = c.id\n                LEFT JOIN sections sec ON s.section_id = sec.id\n                LEFT JOIN academic_calendar ac ON c.academic_term_id = ac.id\n                WHERE s.sap_id = ?\n                ORDER BY (ac.start_date IS NULL) ASC, ac.start_date DESC, s.id DESC";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return [];
        }
        mysqli_stmt_bind_param($stmt, 's', $sapId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (!$result) {
            mysqli_stmt_close($stmt);
            return [];
        }
        $profiles = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $profiles[] = [
                'id' => isset($row['id']) ? (int)$row['id'] : 0,
                'sap_id' => $row['sap_id'] ?? null,
                'name' => $row['name'] ?? '',
                'roll_number' => $row['roll_number'] ?? '',
                'class_id' => isset($row['class_id']) ? (int)$row['class_id'] : null,
                'section_id' => isset($row['section_id']) ? (int)$row['section_id'] : null,
                'class_name' => $row['class_name'] ?? '',
                'semester' => $row['semester'] ?? '',
                'department' => $row['department'] ?? '',
                'school' => $row['school'] ?? '',
                'section_name' => $row['section_name'] ?? '',
                'academic_term_id' => isset($row['academic_term_id']) ? (int)$row['academic_term_id'] : null,
                'term_semester_number' => isset($row['term_semester_number']) ? (int)$row['term_semester_number'] : null,
                'term_semester_term' => $row['term_semester_term'] ?? null,
                'term_start' => $row['term_start'] ?? null,
                'term_end' => $row['term_end'] ?? null,
                'college_email' => $row['college_email'] ?? null,
            ];
        }
        mysqli_free_result($result);
        mysqli_stmt_close($stmt);
        return $profiles;
    }
}

if (!function_exists('selectStudentProfileForTerm')) {
    function selectStudentProfileForTerm(array $profiles, ?int $termId): ?array
    {
        if ($termId === null) {
            return $profiles[0] ?? null;
        }
        foreach ($profiles as $profile) {
            if (isset($profile['academic_term_id']) && (int)$profile['academic_term_id'] === $termId) {
                return $profile;
            }
        }
        return null;
    }
}

if (!function_exists('buildStudentTermContext')) {
    function buildStudentTermContext(mysqli $conn, string $sapId, array $options = []): array
    {
        $context = [
            'error' => null,
            'profiles' => [],
            'default_profile' => null,
            'student_info' => null,
            'student_id' => null,
            'class_id' => null,
            'section_id' => null,
            'student_name' => $options['student_name'] ?? '',
            'roll_number' => '—',
            'school_label' => '',
            'semester_label' => '',
            'class_label' => 'N/A',
            'section_label' => 'N/A',
            'timeline_mismatch' => false,
            'academic_context' => null,
            'active_term' => null,
            'active_term_id' => null,
            'term_start_date' => null,
            'term_end_date' => null,
            'term_start_bound' => null,
            'term_end_bound' => null,
        ];

        $profiles = fetchStudentProfilesBySap($conn, $sapId);
        if (empty($profiles)) {
            $context['error'] = 'Student record could not be found. Please contact the administrator.';
            return $context;
        }

        $context['profiles'] = $profiles;
        $defaultProfile = $profiles[0];
        $context['default_profile'] = $defaultProfile;

        if (!empty($defaultProfile['name'])) {
            $context['student_name'] = $defaultProfile['name'];
        }
        if (!empty($defaultProfile['roll_number'])) {
            $context['roll_number'] = $defaultProfile['roll_number'];
        }
        $context['school_label'] = $defaultProfile['school'] ?? '';
        $context['semester_label'] = $defaultProfile['semester'] ?? '';
        $defaultClassLabel = format_class_label(
            $defaultProfile['class_name'] ?? '',
            $defaultProfile['section_name'] ?? '',
            $defaultProfile['semester'] ?? '',
            $defaultProfile['school'] ?? ''
        );
        $context['class_label'] = $defaultClassLabel !== '' ? $defaultClassLabel : 'N/A';
        $context['section_label'] = $defaultProfile['section_name'] !== '' ? format_subject_display($defaultProfile['section_name']) : 'N/A';

        require_once __DIR__ . '/academic_context.php';
        $context['academic_context'] = resolveAcademicContext($conn, [
            'school_name' => $context['school_label'],
            'default_semester' => $context['semester_label'],
        ]);

        $activeTerm = $context['academic_context']['active'] ?? null;
        $context['active_term'] = $activeTerm;
        $activeTermId = $activeTerm && isset($activeTerm['id']) ? (int)$activeTerm['id'] : null;
        $context['active_term_id'] = $activeTermId;

        $termDateFilter = $context['academic_context']['date_filter'] ?? null;
        $termStartDate = $termDateFilter['start'] ?? null;
        $termEndDate = $termDateFilter['end'] ?? null;
        $termStartBound = $termStartDate ? $termStartDate . ' 00:00:00' : null;
        $termEndBound = $termEndDate ? $termEndDate . ' 23:59:59' : null;
        if ($termEndBound !== null) {
            $currentTimestamp = date('Y-m-d H:i:s');
            if ($termEndBound < $currentTimestamp) {
                $termEndBound = $currentTimestamp;
            }
        }
        $context['term_start_date'] = $termStartDate;
        $context['term_end_date'] = $termEndDate;
        $context['term_start_bound'] = $termStartBound;
        $context['term_end_bound'] = $termEndBound;

        $studentInfo = selectStudentProfileForTerm($profiles, $activeTermId);
        if ($studentInfo) {
            $context['student_info'] = $studentInfo;
            $studentId = isset($studentInfo['id']) ? (int)$studentInfo['id'] : 0;
            $classId = isset($studentInfo['class_id']) ? (int)$studentInfo['class_id'] : 0;
            $sectionId = isset($studentInfo['section_id']) ? (int)$studentInfo['section_id'] : 0;
            $context['student_id'] = $studentId > 0 ? $studentId : null;
            $context['class_id'] = $classId > 0 ? $classId : null;
            $context['section_id'] = $sectionId > 0 ? $sectionId : null;
            if (!empty($studentInfo['name'])) {
                $context['student_name'] = $studentInfo['name'];
            }
            if (!empty($studentInfo['roll_number'])) {
                $context['roll_number'] = $studentInfo['roll_number'];
            }
            if (!empty($studentInfo['school'])) {
                $context['school_label'] = $studentInfo['school'];
            }
            $resolvedClassLabel = format_class_label(
                $studentInfo['class_name'] ?? '',
                $studentInfo['section_name'] ?? '',
                $studentInfo['semester'] ?? '',
                $studentInfo['school'] ?? ''
            );
            $context['class_label'] = $resolvedClassLabel !== '' ? $resolvedClassLabel : 'N/A';
            $context['section_label'] = $studentInfo['section_name'] !== '' ? format_subject_display($studentInfo['section_name']) : 'N/A';
            if (!empty($studentInfo['semester'])) {
                $context['semester_label'] = $studentInfo['semester'];
            }
        } else {
            if ($activeTermId !== null) {
                $context['timeline_mismatch'] = true;
            }
            $context['student_id'] = null;
            $context['class_id'] = null;
            $context['section_id'] = null;
            $context['class_label'] = 'No class assigned for this semester';
            $context['section_label'] = 'N/A';
        }

        return $context;
    }
}

if (!function_exists('getAssignedElectiveSubjectIds')) {
    function getAssignedElectiveSubjectIds(mysqli $conn, int $studentId, int $classId): array
    {
        if ($studentId <= 0 || $classId <= 0) {
            return [];
        }
        $subjectIds = [];
        $stmt = mysqli_prepare($conn, "SELECT subject_id FROM student_elective_choices WHERE student_id = ? AND class_id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ii', $studentId, $classId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($result && ($row = mysqli_fetch_assoc($result))) {
                $subjectId = isset($row['subject_id']) ? (int)$row['subject_id'] : 0;
                if ($subjectId > 0) {
                    $subjectIds[$subjectId] = true;
                }
            }
            if ($result) {
                mysqli_free_result($result);
            }
            mysqli_stmt_close($stmt);
        }
        return array_keys($subjectIds);
    }
}
