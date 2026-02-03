   <?php
   if (defined('DB_CONNECT_INCLUDED')) {
       return;
   }
   define('DB_CONNECT_INCLUDED', true);

   $servername = "localhost";
   $username = "root";
   $password = "";
   $dbname = "ica_tracker";

   $conn = mysqli_connect($servername, $username, $password, $dbname);

   if (!$conn || mysqli_connect_errno()) {
       $error_message = "Database connection failed: " . mysqli_connect_error();
       error_log($error_message, 3, 'C:\xampp\php\logs\php_error_log');
       http_response_code(500);
       die("Connection failed. Please contact the administrator.");
   }

   mysqli_set_charset($conn, "utf8mb4");

   if (!function_exists('format_class_label')) {
       function format_class_label(?string $className, ?string $division, ?string $semester, ?string $school = ''): string {
           $className = $className !== null ? trim($className) : '';
           $division = $division !== null ? trim($division) : '';
           $semester = $semester !== null ? trim($semester) : '';
           $school = $school !== null ? trim($school) : '';

           $toUpper = static function (string $value): string {
               $trimmed = trim($value);
               if ($trimmed === '') {
                   return '';
               }
               if (function_exists('mb_strtoupper')) {
                   return mb_strtoupper($trimmed, 'UTF-8');
               }
               return strtoupper($trimmed);
           };

           $segments = [];
           $classSegment = $className !== '' ? $toUpper($className) : '';
           if ($classSegment !== '') {
               $segments[] = $classSegment;
           }

           $metaParts = [];
           if ($semester !== '') {
               $cleanSemester = preg_replace('/^sem\s*/i', '', $semester);
               $cleanSemester = $cleanSemester !== '' ? $cleanSemester : $semester;
               $metaParts[] = 'SEM: ' . $toUpper($cleanSemester);
           }
           if ($school !== '') {
               $metaParts[] = 'SCHOOL: ' . $toUpper($school);
           }
           if (!empty($metaParts)) {
               $segments[] = '(' . implode(' - ', $metaParts) . ')';
           }

           if ($division !== '') {
               $divisionParts = array_filter(array_map(static function ($piece) use ($toUpper) {
                   $normalized = preg_replace('/\s+/', ' ', trim((string)$piece));
                   if ($normalized === '') {
                       return '';
                   }
                   if (stripos($normalized, 'DIV') !== 0) {
                       $normalized = 'DIV ' . $normalized;
                   }
                   return $toUpper($normalized);
               }, explode('/', $division)));
               if (!empty($divisionParts)) {
                   $segments[] = implode(' / ', $divisionParts);
               }
           }

           if (empty($segments)) {
               return '';
           }

           return implode(' - ', $segments);
       }
   }

   if (!function_exists('format_subject_display')) {
       function format_subject_display(?string $name): string {
           if ($name === null) {
               return '';
           }
           $trimmed = trim($name);
           if ($trimmed === '') {
               return '';
           }
           if (function_exists('mb_strtoupper')) {
               return mb_strtoupper($trimmed, 'UTF-8');
           }
           return strtoupper($trimmed);
       }
   }

   if (!function_exists('derive_subject_short_name')) {
       function derive_subject_short_name(?string $name): string {
           if ($name === null) {
               return '';
           }

           $trimmed = trim($name);
           if ($trimmed === '') {
               return '';
           }

           $hyphenPos = strpos($trimmed, '-');
           if ($hyphenPos !== false) {
               $candidate = substr($trimmed, 0, $hyphenPos);
               $candidate = preg_replace('/[^A-Za-z0-9]/', '', strtoupper(trim($candidate)));
               if ($candidate !== '') {
                   return substr($candidate, 0, 12);
               }
           }

           $normalized = preg_replace('/[^A-Za-z0-9\s]/', ' ', $trimmed);
           $words = preg_split('/\s+/', $normalized, -1, PREG_SPLIT_NO_EMPTY);

           if (!$words) {
               $fallback = preg_replace('/[^A-Za-z0-9]/', '', strtoupper($trimmed));
               return substr($fallback, 0, 12);
           }

           $stopWords = ['and', 'of', 'for', 'the', 'a', 'an', 'to', 'in', 'on', 'with', 'by', 'or', 'from', 'into', 'at', 'as', 'per', 'vs', 'via'];
           $short = '';

           foreach ($words as $word) {
               $lower = strtolower($word);
               if (in_array($lower, $stopWords, true)) {
                   continue;
               }
               $short .= strtoupper(substr($word, 0, 1));
           }

           if ($short === '') {
               foreach ($words as $word) {
                   if ($word === '') {
                       continue;
                   }
                   $short .= strtoupper(substr($word, 0, 1));
               }
           }

           if ($short === '') {
               $fallback = preg_replace('/[^A-Za-z0-9]/', '', strtoupper($trimmed));
               $short = $fallback;
           }

           return substr($short, 0, 12);
       }
   }

   if (!function_exists('build_assignment_key')) {
       function build_assignment_key(string $subjectName, int $classId, ?int $sectionId = null): string {
           $payload = json_encode([
               'subject' => $subjectName,
               'class_id' => $classId,
               'section_id' => $sectionId !== null ? (int)$sectionId : 0
           ], JSON_UNESCAPED_UNICODE);
           if ($payload === false) {
               return '';
           }
           $encoded = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
           return $encoded;
       }
   }

   if (!function_exists('parse_assignment_key')) {
       function parse_assignment_key(string $key): ?array {
           if ($key === '') {
               return null;
           }
           $padded = strtr($key, '-_', '+/');
           $remainder = strlen($padded) % 4;
           if ($remainder) {
               $padded .= str_repeat('=', 4 - $remainder);
           }
           $decoded = base64_decode($padded, true);
           if ($decoded === false) {
               return null;
           }
           $data = json_decode($decoded, true);
           if (!is_array($data)) {
               return null;
           }
           $subject = isset($data['subject']) ? trim((string)$data['subject']) : '';
           $classId = isset($data['class_id']) ? (int)$data['class_id'] : 0;
           $sectionId = isset($data['section_id']) ? (int)$data['section_id'] : 0;
           if ($subject === '' || $classId <= 0) {
               return null;
           }
           return [
               'subject' => $subject,
               'class_id' => $classId,
               'section_id' => $sectionId
           ];
       }
   }

   if (!function_exists('derive_contact_hours_label')) {
       function derive_contact_hours_label(int $practicalHours, int $tutorialHours): string {
           $practical = max(0, (int)$practicalHours);
           $tutorial = max(0, (int)$tutorialHours);
           if ($practical > 0 && $tutorial > 0) {
               return 'Practical & Tutorial';
           }
           if ($practical > 0) {
               return 'Practical';
           }
           if ($tutorial > 0) {
               return 'Tutorial';
           }
           return 'Practical';
       }
   }

   if (!function_exists('format_contact_hours_summary')) {
       function format_contact_hours_summary(int $theoryHours, int $practicalHours, int $tutorialHours): string {
           $segments = [];
           if ($theoryHours > 0) {
               $segments[] = $theoryHours . ' theory';
           }
           if ($practicalHours > 0) {
               $segments[] = $practicalHours . ' practical';
           }
           if ($tutorialHours > 0) {
               $segments[] = $tutorialHours . ' tutorial';
           }
           if (empty($segments)) {
               return '0 hours';
           }
           return implode(' / ', $segments);
       }
   }

    if (!function_exists('format_person_display')) {
        function format_person_display(?string $name): string {
            return format_subject_display($name);
        }
    }
   ?>