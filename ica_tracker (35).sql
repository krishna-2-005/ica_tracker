-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 02, 2026 at 10:37 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ica_tracker`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_calendar`
--

CREATE TABLE `academic_calendar` (
  `id` int(11) NOT NULL,
  `school_name` varchar(100) NOT NULL,
  `semester_term` varchar(10) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `academic_year` varchar(20) NOT NULL DEFAULT '',
  `semester_number` int(11) DEFAULT NULL,
  `label_override` varchar(120) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academic_calendar`
--

INSERT INTO `academic_calendar` (`id`, `school_name`, `semester_term`, `start_date`, `end_date`, `created_at`, `academic_year`, `semester_number`, `label_override`) VALUES
(1, 'STME', 'odd', '2025-07-14', '2025-11-15', '2025-10-12 06:45:55', '2025-2026', 1, ''),
(2, 'STME', 'even', '2026-01-02', '2026-04-25', '2025-12-21 04:34:02', '2025-2026', 2, '');

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `actor_id` bigint(20) DEFAULT NULL,
  `target_user_id` bigint(20) DEFAULT NULL,
  `actor_role` varchar(50) DEFAULT NULL,
  `actor_unique_id` varchar(100) DEFAULT NULL,
  `actor_username` varchar(100) DEFAULT NULL,
  `actor_name` varchar(150) DEFAULT NULL,
  `target_role` varchar(50) DEFAULT NULL,
  `target_unique_id` varchar(100) DEFAULT NULL,
  `target_username` varchar(100) DEFAULT NULL,
  `target_name` varchar(150) DEFAULT NULL,
  `object_type` varchar(100) DEFAULT NULL,
  `object_id` varchar(100) DEFAULT NULL,
  `object_label` varchar(255) DEFAULT NULL,
  `action` varchar(150) NOT NULL,
  `event_label` varchar(255) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `actor_id`, `target_user_id`, `actor_role`, `actor_unique_id`, `actor_username`, `actor_name`, `target_role`, `target_unique_id`, `target_username`, `target_name`, `object_type`, `object_id`, `object_label`, `action`, `event_label`, `details`, `metadata`, `ip_address`, `user_agent`, `created_at`) VALUES
(7, NULL, 40004483, NULL, 'program_chair', '32100443', 'Chandrakant_Wani', 'Prof.Chandrakant Wani', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'logout', 'User logged out', 'Logout requested from logout modal.', '{\"session_id\":\"nch6hd26ceftikg0o5klru1un9\",\"request_method\":\"POST\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 00:35:28'),
(8, NULL, 40004483, NULL, 'program_chair', '32100443', 'Chandrakant_Wani', 'Prof.Chandrakant Wani', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'login_success', 'User logged in', 'Successful login via main portal.', '{\"identifier_used\":\"32100443\",\"role\":\"program_chair\",\"post_login_redirect\":\"login_as.php\",\"session_id\":\"lsrd4kcj3s11ukb2d928gp3usj\",\"force_password_change\":false,\"student_profile_id\":null,\"require_college_email\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 00:35:32'),
(9, NULL, 40004485, NULL, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'admin_login_success', 'Administrator logged in', 'Admin portal authentication succeeded.', '{\"identifier_used\":\"40004485\",\"teacher_unique_id\":\"40004485\",\"session_id\":\"vnh3dba49t52dk4mfi151kvinl\",\"force_password_change\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:25:09'),
(10, NULL, 40004485, NULL, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', NULL, NULL, NULL, NULL, 'subject', '65', 'CVT-Complex Variables and Transforms | 2ND YEAR CE (SEM: 4 - SCHOOL: STME)', 'subject_created', 'Subject created', 'Subject created via admin interface.', '{\"subject_id\":65,\"subject_name\":\"CVT-Complex Variables and Transforms\",\"short_name\":\"CVT\",\"subject_type\":\"regular\",\"school\":\"STME\",\"semester\":\"4\",\"class_id\":32,\"section_id\":0,\"class_label\":\"2ND YEAR CE (SEM: 4 - SCHOOL: STME)\",\"theory_hours\":45,\"practical_hours\":0,\"tutorial_hours\":15,\"total_planned_hours\":60,\"elective_category\":null,\"elective_number\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:27:56'),
(11, NULL, 40004485, NULL, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', NULL, NULL, NULL, NULL, 'subject', '66', 'COA-Computer Organization and Architecture | 2ND YEAR CE (SEM: 4 - SCHOOL: STME)', 'subject_created', 'Subject created', 'Subject created via admin interface.', '{\"subject_id\":66,\"subject_name\":\"COA-Computer Organization and Architecture\",\"short_name\":\"COA\",\"subject_type\":\"regular\",\"school\":\"STME\",\"semester\":\"4\",\"class_id\":32,\"section_id\":0,\"class_label\":\"2ND YEAR CE (SEM: 4 - SCHOOL: STME)\",\"theory_hours\":45,\"practical_hours\":0,\"tutorial_hours\":0,\"total_planned_hours\":45,\"elective_category\":null,\"elective_number\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:28:30'),
(12, NULL, 40004485, NULL, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', NULL, NULL, NULL, NULL, 'subject', '67', 'DAA-Design and Analysis of Algorithms | 2ND YEAR CE (SEM: 4 - SCHOOL: STME)', 'subject_created', 'Subject created', 'Subject created via admin interface.', '{\"subject_id\":67,\"subject_name\":\"DAA-Design and Analysis of Algorithms\",\"short_name\":\"DAA\",\"subject_type\":\"regular\",\"school\":\"STME\",\"semester\":\"4\",\"class_id\":32,\"section_id\":0,\"class_label\":\"2ND YEAR CE (SEM: 4 - SCHOOL: STME)\",\"theory_hours\":30,\"practical_hours\":30,\"tutorial_hours\":0,\"total_planned_hours\":60,\"elective_category\":null,\"elective_number\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:28:58'),
(13, NULL, 40004485, NULL, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', NULL, NULL, NULL, NULL, 'subject', '68', 'DBMS-Database Management Systems | 2ND YEAR CE (SEM: 4 - SCHOOL: STME)', 'subject_created', 'Subject created', 'Subject created via admin interface.', '{\"subject_id\":68,\"subject_name\":\"DBMS-Database Management Systems\",\"short_name\":\"DBMS\",\"subject_type\":\"regular\",\"school\":\"STME\",\"semester\":\"4\",\"class_id\":32,\"section_id\":0,\"class_label\":\"2ND YEAR CE (SEM: 4 - SCHOOL: STME)\",\"theory_hours\":30,\"practical_hours\":30,\"tutorial_hours\":0,\"total_planned_hours\":60,\"elective_category\":null,\"elective_number\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:29:26'),
(14, NULL, 40004485, NULL, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', NULL, NULL, NULL, NULL, 'subject', '69', 'MM-Microprocessor and Microcontroller | 2ND YEAR CE (SEM: 4 - SCHOOL: STME)', 'subject_created', 'Subject created', 'Subject created via admin interface.', '{\"subject_id\":69,\"subject_name\":\"MM-Microprocessor and Microcontroller\",\"short_name\":\"MM\",\"subject_type\":\"regular\",\"school\":\"STME\",\"semester\":\"4\",\"class_id\":32,\"section_id\":0,\"class_label\":\"2ND YEAR CE (SEM: 4 - SCHOOL: STME)\",\"theory_hours\":45,\"practical_hours\":30,\"tutorial_hours\":0,\"total_planned_hours\":75,\"elective_category\":null,\"elective_number\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:29:52'),
(15, NULL, 40004485, NULL, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', NULL, NULL, NULL, NULL, 'subject', '70', 'TCS-Theoretical Computer Science | 2ND YEAR CE (SEM: 4 - SCHOOL: STME)', 'subject_created', 'Subject created', 'Subject created via admin interface.', '{\"subject_id\":70,\"subject_name\":\"TCS-Theoretical Computer Science\",\"short_name\":\"TCS\",\"subject_type\":\"regular\",\"school\":\"STME\",\"semester\":\"4\",\"class_id\":32,\"section_id\":0,\"class_label\":\"2ND YEAR CE (SEM: 4 - SCHOOL: STME)\",\"theory_hours\":30,\"practical_hours\":0,\"tutorial_hours\":15,\"total_planned_hours\":45,\"elective_category\":null,\"elective_number\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:30:22'),
(16, NULL, 40004485, NULL, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', NULL, NULL, NULL, NULL, 'subject', '71', 'WP-Web Programming | 2ND YEAR CE (SEM: 4 - SCHOOL: STME)', 'subject_created', 'Subject created', 'Subject created via admin interface.', '{\"subject_id\":71,\"subject_name\":\"WP-Web Programming\",\"short_name\":\"WP\",\"subject_type\":\"regular\",\"school\":\"STME\",\"semester\":\"4\",\"class_id\":32,\"section_id\":0,\"class_label\":\"2ND YEAR CE (SEM: 4 - SCHOOL: STME)\",\"theory_hours\":30,\"practical_hours\":30,\"tutorial_hours\":0,\"total_planned_hours\":60,\"elective_category\":null,\"elective_number\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:30:48'),
(17, NULL, 40004485, NULL, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', NULL, NULL, NULL, NULL, 'subject', '72', 'OOPJ-Object Oriented Programming through JAVA | 2ND YEAR CE (SEM: 4 - SCHOOL: STME)', 'subject_created', 'Subject created', 'Subject created via admin interface.', '{\"subject_id\":72,\"subject_name\":\"OOPJ-Object Oriented Programming through JAVA\",\"short_name\":\"OOPJ\",\"subject_type\":\"regular\",\"school\":\"STME\",\"semester\":\"4\",\"class_id\":32,\"section_id\":0,\"class_label\":\"2ND YEAR CE (SEM: 4 - SCHOOL: STME)\",\"theory_hours\":0,\"practical_hours\":30,\"tutorial_hours\":0,\"total_planned_hours\":30,\"elective_category\":null,\"elective_number\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:31:21'),
(18, NULL, 40004485, NULL, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', NULL, NULL, NULL, NULL, 'subject', '73', 'DE1-Design Experience I | 2ND YEAR CE (SEM: 4 - SCHOOL: STME)', 'subject_created', 'Subject created', 'Subject created via admin interface.', '{\"subject_id\":73,\"subject_name\":\"DE1-Design Experience I\",\"short_name\":\"DE1\",\"subject_type\":\"regular\",\"school\":\"STME\",\"semester\":\"4\",\"class_id\":32,\"section_id\":0,\"class_label\":\"2ND YEAR CE (SEM: 4 - SCHOOL: STME)\",\"theory_hours\":0,\"practical_hours\":30,\"tutorial_hours\":0,\"total_planned_hours\":30,\"elective_category\":null,\"elective_number\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:31:51'),
(19, 2147483676, 40004485, 2147483676, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '40005592', 'Dibakar_Dey', 'Dr. Dibakar Dey	', 'teacher_subject_assignment', '65:32:0', 'CVT-Complex Variables and Transforms', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":2147483676,\"teacher_unique_id\":\"40005592\",\"subject_id\":65,\"subject_name\":\"CVT-Complex Variables and Transforms\",\"class_id\":32,\"section_id\":0,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":60,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:34:05'),
(20, 2147483671, 40004485, 2147483671, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '32100424', 'Rahul_Koshti', 'Dr. Rahul Koshti	', 'teacher_subject_assignment', '66:32:0', 'COA-Computer Organization and Architecture', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":2147483671,\"teacher_unique_id\":\"32100424\",\"subject_id\":66,\"subject_name\":\"COA-Computer Organization and Architecture\",\"class_id\":32,\"section_id\":0,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":45,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:34:18'),
(21, 40004481, 40004485, 40004481, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '40004803', 'Bhanu_sree', 'Dr.Bhanu Sree', 'teacher_subject_assignment', '67:32:0', 'DAA-Design and Analysis of Algorithms', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":40004481,\"teacher_unique_id\":\"40004803\",\"subject_id\":67,\"subject_name\":\"DAA-Design and Analysis of Algorithms\",\"class_id\":32,\"section_id\":0,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":60,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:34:37'),
(22, 2147483666, 40004485, 2147483666, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '40002868', 'Wasiha_Tasneem', 'Prof. Wasiha Tasneem', 'teacher_subject_assignment', '68:32:0', 'DBMS-Database Management Systems', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":2147483666,\"teacher_unique_id\":\"40002868\",\"subject_id\":68,\"subject_name\":\"DBMS-Database Management Systems\",\"class_id\":32,\"section_id\":0,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":60,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:35:36'),
(23, 2147483673, 40004485, 2147483673, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '40005572', 'Uday_Panwar', 'Dr. Uday Panwar	', 'teacher_subject_assignment', '69:32:0', 'MM-Microprocessor and Microcontroller', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":2147483673,\"teacher_unique_id\":\"40005572\",\"subject_id\":69,\"subject_name\":\"MM-Microprocessor and Microcontroller\",\"class_id\":32,\"section_id\":0,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":75,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:35:58'),
(24, 2147483667, 40004485, 2147483667, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '40005786', 'Ramesh_Munipala', 'Dr. Ramesh Munipala', 'teacher_subject_assignment', '70:32:0', 'TCS-Theoretical Computer Science', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":2147483667,\"teacher_unique_id\":\"40005786\",\"subject_id\":70,\"subject_name\":\"TCS-Theoretical Computer Science\",\"class_id\":32,\"section_id\":0,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":45,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:36:20'),
(25, 2147483684, 40004485, 2147483684, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '40005591', 'Nikita_Pande', 'Prof. Nikita Pande	', 'teacher_subject_assignment', '71:32:0', 'WP-Web Programming', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":2147483684,\"teacher_unique_id\":\"40005591\",\"subject_id\":71,\"subject_name\":\"WP-Web Programming\",\"class_id\":32,\"section_id\":0,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":60,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:36:38'),
(26, 2147483665, 40004485, 2147483665, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '40002405', 'Vinayak_Mukkawar', 'Prof. Vinayak Mukkawar', 'teacher_subject_assignment', '72:32:0', 'OOPJ-Object Oriented Programming through JAVA', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":2147483665,\"teacher_unique_id\":\"40002405\",\"subject_id\":72,\"subject_name\":\"OOPJ-Object Oriented Programming through JAVA\",\"class_id\":32,\"section_id\":0,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":30,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:37:26'),
(27, NULL, 40004485, NULL, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', NULL, NULL, NULL, NULL, 'subject', '74', 'SM-Statistical Methods | 2ND YEAR CSEDS (SEM: 4 - SCHOOL: STME) - DIV A', 'subject_created', 'Subject created', 'Subject created via admin interface.', '{\"subject_id\":74,\"subject_name\":\"SM-Statistical Methods\",\"short_name\":\"SM\",\"subject_type\":\"regular\",\"school\":\"STME\",\"semester\":\"4\",\"class_id\":31,\"section_id\":20,\"class_label\":\"2ND YEAR CSEDS (SEM: 4 - SCHOOL: STME) - DIV A\",\"theory_hours\":30,\"practical_hours\":30,\"tutorial_hours\":0,\"total_planned_hours\":60,\"elective_category\":null,\"elective_number\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:38:31'),
(28, NULL, 40004485, NULL, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', NULL, NULL, NULL, NULL, 'subject', '75', 'ML-Machine Learning | 2ND YEAR CSEDS (SEM: 4 - SCHOOL: STME) - DIV A', 'subject_created', 'Subject created', 'Subject created via admin interface.', '{\"subject_id\":75,\"subject_name\":\"ML-Machine Learning\",\"short_name\":\"ML\",\"subject_type\":\"regular\",\"school\":\"STME\",\"semester\":\"4\",\"class_id\":31,\"section_id\":20,\"class_label\":\"2ND YEAR CSEDS (SEM: 4 - SCHOOL: STME) - DIV A\",\"theory_hours\":15,\"practical_hours\":30,\"tutorial_hours\":0,\"total_planned_hours\":45,\"elective_category\":null,\"elective_number\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:39:32'),
(29, NULL, 40004485, NULL, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', NULL, NULL, NULL, NULL, 'subject', '76', 'IDS&IA-Introduction to Data, Signal, and Image Analysis | 2ND YEAR CSEDS (SEM: 4 - SCHOOL: STME) - DIV A', 'subject_created', 'Subject created', 'Subject created via admin interface.', '{\"subject_id\":76,\"subject_name\":\"IDS&IA-Introduction to Data, Signal, and Image Analysis\",\"short_name\":\"IDSIA\",\"subject_type\":\"regular\",\"school\":\"STME\",\"semester\":\"4\",\"class_id\":31,\"section_id\":20,\"class_label\":\"2ND YEAR CSEDS (SEM: 4 - SCHOOL: STME) - DIV A\",\"theory_hours\":45,\"practical_hours\":30,\"tutorial_hours\":0,\"total_planned_hours\":75,\"elective_category\":null,\"elective_number\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:40:06'),
(30, NULL, 40004485, NULL, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', NULL, NULL, NULL, NULL, 'subject', '77', 'DHV-Data Handling and Visualization | 2ND YEAR CSEDS (SEM: 4 - SCHOOL: STME) - DIV A', 'subject_created', 'Subject created', 'Subject created via admin interface.', '{\"subject_id\":77,\"subject_name\":\"DHV-Data Handling and Visualization\",\"short_name\":\"DHV\",\"subject_type\":\"regular\",\"school\":\"STME\",\"semester\":\"4\",\"class_id\":31,\"section_id\":20,\"class_label\":\"2ND YEAR CSEDS (SEM: 4 - SCHOOL: STME) - DIV A\",\"theory_hours\":15,\"practical_hours\":30,\"tutorial_hours\":0,\"total_planned_hours\":45,\"elective_category\":null,\"elective_number\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:40:39'),
(31, 40004495, 40004485, 40004495, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '40002404', 'Vidyasagar', 'Dr.Vidyasagar', 'teacher_subject_assignment', '74:31:20', 'SM-Statistical Methods', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":40004495,\"teacher_unique_id\":\"40002404\",\"subject_id\":74,\"subject_name\":\"SM-Statistical Methods\",\"class_id\":31,\"section_id\":20,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":60,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:41:17'),
(32, 40004494, 40004485, 40004494, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '40004479', 'Naresh_Vurukonda', 'Dr.Naresh Vurukonda', 'teacher_subject_assignment', '75:31:20', 'ML-Machine Learning', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":40004494,\"teacher_unique_id\":\"40004479\",\"subject_id\":75,\"subject_name\":\"ML-Machine Learning\",\"class_id\":31,\"section_id\":20,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":45,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:41:44'),
(33, 2147483673, 40004485, 2147483673, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '40005572', 'Uday_Panwar', 'Dr. Uday Panwar	', 'teacher_subject_assignment', '76:31:20', 'IDS&IA-Introduction to Data, Signal, and Image Analysis', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":2147483673,\"teacher_unique_id\":\"40005572\",\"subject_id\":76,\"subject_name\":\"IDS&IA-Introduction to Data, Signal, and Image Analysis\",\"class_id\":31,\"section_id\":20,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":75,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:43:04'),
(34, 2147483666, 40004485, 2147483666, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '40002868', 'Wasiha_Tasneem', 'Prof. Wasiha Tasneem', 'teacher_subject_assignment', '68:31:20', 'DBMS-Database Management Systems', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":2147483666,\"teacher_unique_id\":\"40002868\",\"subject_id\":68,\"subject_name\":\"DBMS-Database Management Systems\",\"class_id\":31,\"section_id\":20,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":60,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:43:30'),
(35, 2147483665, 40004485, 2147483665, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '40002405', 'Vinayak_Mukkawar', 'Prof. Vinayak Mukkawar', 'teacher_subject_assignment', '71:31:20', 'WP-Web Programming', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":2147483665,\"teacher_unique_id\":\"40002405\",\"subject_id\":71,\"subject_name\":\"WP-Web Programming\",\"class_id\":31,\"section_id\":20,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":60,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:43:50'),
(36, 2147483679, 40004485, 2147483679, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '40005825', 'Anshi_Bajaj', 'Prof. Anshi Bajaj	', 'teacher_subject_assignment', '77:31:20', 'DHV-Data Handling and Visualization', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":2147483679,\"teacher_unique_id\":\"40005825\",\"subject_id\":77,\"subject_name\":\"DHV-Data Handling and Visualization\",\"class_id\":31,\"section_id\":20,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":45,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:44:08'),
(37, 2147483699, 40004485, 2147483699, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '40005641', 'Amit_Saini ', 'Dr. Amit Kumar Saini ', 'teacher_subject_assignment', '74:31:21', 'SM-Statistical Methods', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":2147483699,\"teacher_unique_id\":\"40005641\",\"subject_id\":74,\"subject_name\":\"SM-Statistical Methods\",\"class_id\":31,\"section_id\":21,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":60,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:45:08'),
(38, 40004494, 40004485, 40004494, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '40004479', 'Naresh_Vurukonda', 'Dr.Naresh Vurukonda', 'teacher_subject_assignment', '75:31:21', 'ML-Machine Learning', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":40004494,\"teacher_unique_id\":\"40004479\",\"subject_id\":75,\"subject_name\":\"ML-Machine Learning\",\"class_id\":31,\"section_id\":21,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":45,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:45:22'),
(39, 2147483673, 40004485, 2147483673, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '40005572', 'Uday_Panwar', 'Dr. Uday Panwar	', 'teacher_subject_assignment', '76:31:21', 'IDS&IA-Introduction to Data, Signal, and Image Analysis', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":2147483673,\"teacher_unique_id\":\"40005572\",\"subject_id\":76,\"subject_name\":\"IDS&IA-Introduction to Data, Signal, and Image Analysis\",\"class_id\":31,\"section_id\":21,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":75,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:45:38'),
(40, 2147483679, 40004485, 2147483679, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '40005825', 'Anshi_Bajaj', 'Prof. Anshi Bajaj	', 'teacher_subject_assignment', '68:31:21', 'DBMS-Database Management Systems', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":2147483679,\"teacher_unique_id\":\"40005825\",\"subject_id\":68,\"subject_name\":\"DBMS-Database Management Systems\",\"class_id\":31,\"section_id\":21,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":60,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:46:01'),
(41, 2147483665, 40004485, 2147483665, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '40002405', 'Vinayak_Mukkawar', 'Prof. Vinayak Mukkawar', 'teacher_subject_assignment', '71:31:21', 'WP-Web Programming', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":2147483665,\"teacher_unique_id\":\"40002405\",\"subject_id\":71,\"subject_name\":\"WP-Web Programming\",\"class_id\":31,\"section_id\":21,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":60,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:46:18'),
(42, 2147483679, 40004485, 2147483679, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '40005825', 'Anshi_Bajaj', 'Prof. Anshi Bajaj	', 'teacher_subject_assignment', '77:31:21', 'DHV-Data Handling and Visualization', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":2147483679,\"teacher_unique_id\":\"40005825\",\"subject_id\":77,\"subject_name\":\"DHV-Data Handling and Visualization\",\"class_id\":31,\"section_id\":21,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":45,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:46:36'),
(43, NULL, 40004485, NULL, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', NULL, NULL, NULL, NULL, 'subject', '78', 'AAI-Applied Artificial Intelligence | 3RD YEAR CSEDS (SEM: 6 - SCHOOL: STME)', 'subject_created', 'Subject created', 'Subject created via admin interface.', '{\"subject_id\":78,\"subject_name\":\"AAI-Applied Artificial Intelligence\",\"short_name\":\"AAI\",\"subject_type\":\"regular\",\"school\":\"STME\",\"semester\":\"6\",\"class_id\":27,\"section_id\":0,\"class_label\":\"3RD YEAR CSEDS (SEM: 6 - SCHOOL: STME)\",\"theory_hours\":30,\"practical_hours\":30,\"tutorial_hours\":0,\"total_planned_hours\":60,\"elective_category\":null,\"elective_number\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:54:03'),
(44, NULL, 40004485, NULL, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', NULL, NULL, NULL, NULL, 'subject', '79', 'NNDL-Neural Networks and Deep Learning | 3RD YEAR CSEDS (SEM: 6 - SCHOOL: STME)', 'subject_created', 'Subject created', 'Subject created via admin interface.', '{\"subject_id\":79,\"subject_name\":\"NNDL-Neural Networks and Deep Learning\",\"short_name\":\"NNDL\",\"subject_type\":\"regular\",\"school\":\"STME\",\"semester\":\"6\",\"class_id\":27,\"section_id\":0,\"class_label\":\"3RD YEAR CSEDS (SEM: 6 - SCHOOL: STME)\",\"theory_hours\":30,\"practical_hours\":30,\"tutorial_hours\":0,\"total_planned_hours\":60,\"elective_category\":null,\"elective_number\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:54:32'),
(45, NULL, 40004485, NULL, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', NULL, NULL, NULL, NULL, 'subject', '80', 'ADSA-Advance Data Structure for Analytics | 3RD YEAR CSEDS (SEM: 6 - SCHOOL: STME)', 'subject_created', 'Subject created', 'Subject created via admin interface.', '{\"subject_id\":80,\"subject_name\":\"ADSA-Advance Data Structure for Analytics\",\"short_name\":\"ADSA\",\"subject_type\":\"regular\",\"school\":\"STME\",\"semester\":\"6\",\"class_id\":27,\"section_id\":0,\"class_label\":\"3RD YEAR CSEDS (SEM: 6 - SCHOOL: STME)\",\"theory_hours\":30,\"practical_hours\":30,\"tutorial_hours\":0,\"total_planned_hours\":60,\"elective_category\":null,\"elective_number\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:55:07'),
(46, NULL, 40004485, NULL, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', NULL, NULL, NULL, NULL, 'subject', '81', 'DE2-PA- Predictive Analysis | 3RD YEAR CSEDS (SEM: 6 - SCHOOL: STME)', 'subject_created', 'Subject created', 'Subject created via admin interface.', '{\"subject_id\":81,\"subject_name\":\"DE2-PA- Predictive Analysis\",\"short_name\":\"DE2\",\"subject_type\":\"regular\",\"school\":\"STME\",\"semester\":\"6\",\"class_id\":27,\"section_id\":0,\"class_label\":\"3RD YEAR CSEDS (SEM: 6 - SCHOOL: STME)\",\"theory_hours\":30,\"practical_hours\":30,\"tutorial_hours\":0,\"total_planned_hours\":60,\"elective_category\":null,\"elective_number\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:55:52'),
(47, NULL, 40004485, NULL, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', NULL, NULL, NULL, NULL, 'subject', '82', 'DE3-VCC- Virtualization and Cloud Computing | 3RD YEAR CSEDS (SEM: 6 - SCHOOL: STME)', 'subject_created', 'Subject created', 'Subject created via admin interface.', '{\"subject_id\":82,\"subject_name\":\"DE3-VCC- Virtualization and Cloud Computing\",\"short_name\":\"DE3\",\"subject_type\":\"elective\",\"school\":\"STME\",\"semester\":\"6\",\"class_id\":27,\"section_id\":0,\"class_label\":\"3RD YEAR CSEDS (SEM: 6 - SCHOOL: STME)\",\"theory_hours\":30,\"practical_hours\":30,\"tutorial_hours\":0,\"total_planned_hours\":60,\"elective_category\":\"departmental\",\"elective_number\":\"3\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 09:57:48'),
(48, NULL, 40004485, NULL, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', NULL, NULL, NULL, NULL, 'subject', '83', 'DE2-PA- Predictive Analysis | 3RD YEAR CSEDS (SEM: 6 - SCHOOL: STME)', 'subject_created', 'Subject created', 'Subject created via admin interface.', '{\"subject_id\":83,\"subject_name\":\"DE2-PA- Predictive Analysis\",\"short_name\":\"DE2\",\"subject_type\":\"elective\",\"school\":\"STME\",\"semester\":\"6\",\"class_id\":27,\"section_id\":0,\"class_label\":\"3RD YEAR CSEDS (SEM: 6 - SCHOOL: STME)\",\"theory_hours\":30,\"practical_hours\":30,\"tutorial_hours\":0,\"total_planned_hours\":60,\"elective_category\":\"departmental\",\"elective_number\":\"2\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:00:18'),
(49, NULL, 40004485, NULL, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', NULL, NULL, NULL, NULL, 'subject', '84', 'OE3-RM- Research Methodology | 3RD YEAR CSEDS (SEM: 6 - SCHOOL: STME)', 'subject_created', 'Subject created', 'Subject created via admin interface.', '{\"subject_id\":84,\"subject_name\":\"OE3-RM- Research Methodology\",\"short_name\":\"OE3\",\"subject_type\":\"elective\",\"school\":\"STME\",\"semester\":\"6\",\"class_id\":27,\"section_id\":0,\"class_label\":\"3RD YEAR CSEDS (SEM: 6 - SCHOOL: STME)\",\"theory_hours\":45,\"practical_hours\":0,\"tutorial_hours\":0,\"total_planned_hours\":45,\"elective_category\":\"open\",\"elective_number\":\"3\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:01:00'),
(50, NULL, 40004485, NULL, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', NULL, NULL, NULL, NULL, 'subject', '85', 'OE3-DT-DRONE TECHNOLOGY | 3RD YEAR CSEDS (SEM: 6 - SCHOOL: STME)', 'subject_created', 'Subject created', 'Subject created via admin interface.', '{\"subject_id\":85,\"subject_name\":\"OE3-DT-DRONE TECHNOLOGY\",\"short_name\":\"OE3\",\"subject_type\":\"elective\",\"school\":\"STME\",\"semester\":\"6\",\"class_id\":27,\"section_id\":0,\"class_label\":\"3RD YEAR CSEDS (SEM: 6 - SCHOOL: STME)\",\"theory_hours\":30,\"practical_hours\":30,\"tutorial_hours\":0,\"total_planned_hours\":60,\"elective_category\":\"open\",\"elective_number\":\"3\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:01:43'),
(51, NULL, 40004485, NULL, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', NULL, NULL, NULL, NULL, 'subject', '86', 'OE4-FIMIS - Financial Institutions, Markets, Instruments and Services | 3RD YEAR CSEDS (SEM: 6 - SCHOOL: STME)', 'subject_created', 'Subject created', 'Subject created via admin interface.', '{\"subject_id\":86,\"subject_name\":\"OE4-FIMIS - Financial Institutions, Markets, Instruments and Services\",\"short_name\":\"OE4\",\"subject_type\":\"elective\",\"school\":\"STME\",\"semester\":\"6\",\"class_id\":27,\"section_id\":0,\"class_label\":\"3RD YEAR CSEDS (SEM: 6 - SCHOOL: STME)\",\"theory_hours\":45,\"practical_hours\":0,\"tutorial_hours\":0,\"total_planned_hours\":45,\"elective_category\":\"open\",\"elective_number\":\"4\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:03:40'),
(52, NULL, 40004485, NULL, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', NULL, NULL, NULL, NULL, 'subject', '87', 'OE4-CEM-CREATIVITY AND ETHICS IN MARKETING | 3RD YEAR CSEDS (SEM: 6 - SCHOOL: STME)', 'subject_created', 'Subject created', 'Subject created via admin interface.', '{\"subject_id\":87,\"subject_name\":\"OE4-CEM-CREATIVITY AND ETHICS IN MARKETING\",\"short_name\":\"OE4\",\"subject_type\":\"elective\",\"school\":\"STME\",\"semester\":\"6\",\"class_id\":27,\"section_id\":0,\"class_label\":\"3RD YEAR CSEDS (SEM: 6 - SCHOOL: STME)\",\"theory_hours\":45,\"practical_hours\":0,\"tutorial_hours\":0,\"total_planned_hours\":45,\"elective_category\":\"open\",\"elective_number\":\"4\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:05:01'),
(53, NULL, 40004485, NULL, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', NULL, NULL, NULL, NULL, 'subject', '88', 'IS-Interpersonal Skills | 3RD YEAR CSEDS (SEM: 6 - SCHOOL: STME)', 'subject_created', 'Subject created', 'Subject created via admin interface.', '{\"subject_id\":88,\"subject_name\":\"IS-Interpersonal Skills\",\"short_name\":\"IS\",\"subject_type\":\"regular\",\"school\":\"STME\",\"semester\":\"6\",\"class_id\":27,\"section_id\":0,\"class_label\":\"3RD YEAR CSEDS (SEM: 6 - SCHOOL: STME)\",\"theory_hours\":0,\"practical_hours\":30,\"tutorial_hours\":0,\"total_planned_hours\":30,\"elective_category\":null,\"elective_number\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:05:29'),
(54, 2147483664, 40004485, 2147483664, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '51434700', 'Rajesh_Prabhakar', 'Prof. Rajesh Prabhakar', 'teacher_subject_assignment', '78:27:0', 'AAI-Applied Artificial Intelligence', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":2147483664,\"teacher_unique_id\":\"51434700\",\"subject_id\":78,\"subject_name\":\"AAI-Applied Artificial Intelligence\",\"class_id\":27,\"section_id\":0,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":60,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:09:55'),
(55, 2147483684, 40004485, 2147483684, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '40005591', 'Nikita_Pande', 'Prof. Nikita Pande	', 'teacher_subject_assignment', '79:27:0', 'NNDL-Neural Networks and Deep Learning', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":2147483684,\"teacher_unique_id\":\"40005591\",\"subject_id\":79,\"subject_name\":\"NNDL-Neural Networks and Deep Learning\",\"class_id\":27,\"section_id\":0,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":60,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:10:19'),
(56, 2147483666, 40004485, 2147483666, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '40002868', 'Wasiha_Tasneem', 'Prof. Wasiha Tasneem', 'teacher_subject_assignment', '80:27:0', 'ADSA-Advance Data Structure for Analytics', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":2147483666,\"teacher_unique_id\":\"40002868\",\"subject_id\":80,\"subject_name\":\"ADSA-Advance Data Structure for Analytics\",\"class_id\":27,\"section_id\":0,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":60,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:11:30'),
(57, 2147483664, 40004485, 2147483664, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '51434700', 'Rajesh_Prabhakar', 'Prof. Rajesh Prabhakar', 'teacher_subject_assignment', '83:27:0', 'DE2-PA- Predictive Analysis', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":2147483664,\"teacher_unique_id\":\"51434700\",\"subject_id\":83,\"subject_name\":\"DE2-PA- Predictive Analysis\",\"class_id\":27,\"section_id\":0,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":60,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:11:56'),
(58, 40004494, 40004485, 40004494, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '40004479', 'Naresh_Vurukonda', 'Dr.Naresh Vurukonda', 'teacher_subject_assignment', '82:27:0', 'DE3-VCC- Virtualization and Cloud Computing', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":40004494,\"teacher_unique_id\":\"40004479\",\"subject_id\":82,\"subject_name\":\"DE3-VCC- Virtualization and Cloud Computing\",\"class_id\":27,\"section_id\":0,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":60,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:12:29'),
(59, 40004483, 40004485, 40004483, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'program_chair', '32100443', 'Chandrakant_Wani', 'Prof.Chandrakant Wani', 'teacher_subject_assignment', '84:27:0', 'OE3-RM- Research Methodology', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":40004483,\"teacher_unique_id\":\"32100443\",\"subject_id\":84,\"subject_name\":\"OE3-RM- Research Methodology\",\"class_id\":27,\"section_id\":0,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":45,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:13:15'),
(60, 2147483671, 40004485, 2147483671, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '32100424', 'Rahul_Koshti', 'Dr. Rahul Koshti	', 'teacher_subject_assignment', '85:27:0', 'OE3-DT-DRONE TECHNOLOGY', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":2147483671,\"teacher_unique_id\":\"32100424\",\"subject_id\":85,\"subject_name\":\"OE3-DT-DRONE TECHNOLOGY\",\"class_id\":27,\"section_id\":0,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":60,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:13:52'),
(61, 2147483674, 40004485, 2147483674, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '40004014', 'Mubashir_Hassan', 'Dr. Mubashir Hassan	', 'teacher_subject_assignment', '86:27:0', 'OE4-FIMIS - Financial Institutions, Markets, Instruments and Services', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":2147483674,\"teacher_unique_id\":\"40004014\",\"subject_id\":86,\"subject_name\":\"OE4-FIMIS - Financial Institutions, Markets, Instruments and Services\",\"class_id\":27,\"section_id\":0,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":45,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:14:22'),
(62, 2147483675, 40004485, 2147483675, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '40005562', 'Akshita_Dwivedi', 'Dr. Akshita Dwivedi	', 'teacher_subject_assignment', '87:27:0', 'OE4-CEM-CREATIVITY AND ETHICS IN MARKETING', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":2147483675,\"teacher_unique_id\":\"40005562\",\"subject_id\":87,\"subject_name\":\"OE4-CEM-CREATIVITY AND ETHICS IN MARKETING\",\"class_id\":27,\"section_id\":0,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":45,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:15:24'),
(63, 2147483681, 40004485, 2147483681, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '40005593', 'Rohith', 'Dr. Rocharla Rohith	', 'teacher_subject_assignment', '88:27:0', 'IS-Interpersonal Skills', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":2147483681,\"teacher_unique_id\":\"40005593\",\"subject_id\":88,\"subject_name\":\"IS-Interpersonal Skills\",\"class_id\":27,\"section_id\":0,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":30,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:15:41'),
(64, NULL, 40004485, NULL, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', NULL, NULL, NULL, NULL, 'subject', '89', 'CS-Cyber Security | 3RD YEAR CSEDS (SEM: 6 - SCHOOL: STME)', 'subject_created', 'Subject created', 'Subject created via admin interface.', '{\"subject_id\":89,\"subject_name\":\"CS-Cyber Security\",\"short_name\":\"CS\",\"subject_type\":\"regular\",\"school\":\"STME\",\"semester\":\"6\",\"class_id\":27,\"section_id\":0,\"class_label\":\"3RD YEAR CSEDS (SEM: 6 - SCHOOL: STME)\",\"theory_hours\":30,\"practical_hours\":30,\"tutorial_hours\":0,\"total_planned_hours\":60,\"elective_category\":null,\"elective_number\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:16:24'),
(65, NULL, 40004485, NULL, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', NULL, NULL, NULL, NULL, 'subject', '90', 'ML-Machine Learning | 3RD YEAR CSEDS (SEM: 6 - SCHOOL: STME)', 'subject_created', 'Subject created', 'Subject created via admin interface.', '{\"subject_id\":90,\"subject_name\":\"ML-Machine Learning\",\"short_name\":\"ML\",\"subject_type\":\"regular\",\"school\":\"STME\",\"semester\":\"6\",\"class_id\":27,\"section_id\":0,\"class_label\":\"3RD YEAR CSEDS (SEM: 6 - SCHOOL: STME)\",\"theory_hours\":30,\"practical_hours\":30,\"tutorial_hours\":0,\"total_planned_hours\":60,\"elective_category\":null,\"elective_number\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:16:55'),
(66, NULL, 40004485, NULL, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', NULL, NULL, NULL, NULL, 'subject', '91', 'DC-Distributed Computing | 3RD YEAR CE (SEM: 6 - SCHOOL: STME)', 'subject_created', 'Subject created', 'Subject created via admin interface.', '{\"subject_id\":91,\"subject_name\":\"DC-Distributed Computing\",\"short_name\":\"DC\",\"subject_type\":\"regular\",\"school\":\"STME\",\"semester\":\"6\",\"class_id\":34,\"section_id\":0,\"class_label\":\"3RD YEAR CE (SEM: 6 - SCHOOL: STME)\",\"theory_hours\":30,\"practical_hours\":30,\"tutorial_hours\":0,\"total_planned_hours\":60,\"elective_category\":null,\"elective_number\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:18:05'),
(67, NULL, 40004485, NULL, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', NULL, NULL, NULL, NULL, 'subject', '92', 'DE2-CV-Computer Vision | 3RD YEAR CE (SEM: 6 - SCHOOL: STME)', 'subject_created', 'Subject created', 'Subject created via admin interface.', '{\"subject_id\":92,\"subject_name\":\"DE2-CV-Computer Vision\",\"short_name\":\"DE2\",\"subject_type\":\"regular\",\"school\":\"STME\",\"semester\":\"6\",\"class_id\":34,\"section_id\":0,\"class_label\":\"3RD YEAR CE (SEM: 6 - SCHOOL: STME)\",\"theory_hours\":30,\"practical_hours\":30,\"tutorial_hours\":0,\"total_planned_hours\":60,\"elective_category\":null,\"elective_number\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:18:40'),
(68, NULL, 40004481, NULL, 'teacher', '40004803', 'Bhanu_sree', 'Dr.Bhanu Sree', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'login_success', 'User logged in', 'Successful login via main portal.', '{\"identifier_used\":\"40004803\",\"role\":\"teacher\",\"post_login_redirect\":\"teacher_dashboard.php\",\"session_id\":\"4vllpbhgllup313vhreg56vfli\",\"force_password_change\":false,\"student_profile_id\":null,\"require_college_email\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:27:15'),
(69, NULL, 2147483706, NULL, 'student', '70572300006', '70572300006', 'MAKKENA LAHARI', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'login_success_auto_account', 'Auto-created student login', 'Student account generated from SAP sign-in and login initiated.', '{\"identifier_used\":\"70572300006\",\"session_id\":\"t9f4a6l9n8dadlfgb6clj7i9d6\",\"auto_account_created\":true,\"student_record_id\":1000000148}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:39:06'),
(70, NULL, 40004485, NULL, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', NULL, NULL, NULL, NULL, 'subject', '93', 'DE3-SQA-Software Quality Assurance | 3RD YEAR CE (SEM: 6 - SCHOOL: STME)', 'subject_created', 'Subject created', 'Subject created via admin interface.', '{\"subject_id\":93,\"subject_name\":\"DE3-SQA-Software Quality Assurance\",\"short_name\":\"DE3\",\"subject_type\":\"elective\",\"school\":\"STME\",\"semester\":\"6\",\"class_id\":34,\"section_id\":0,\"class_label\":\"3RD YEAR CE (SEM: 6 - SCHOOL: STME)\",\"theory_hours\":30,\"practical_hours\":30,\"tutorial_hours\":0,\"total_planned_hours\":60,\"elective_category\":\"departmental\",\"elective_number\":\"3\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 10:43:51');
INSERT INTO `activity_logs` (`id`, `user_id`, `actor_id`, `target_user_id`, `actor_role`, `actor_unique_id`, `actor_username`, `actor_name`, `target_role`, `target_unique_id`, `target_username`, `target_name`, `object_type`, `object_id`, `object_label`, `action`, `event_label`, `details`, `metadata`, `ip_address`, `user_agent`, `created_at`) VALUES
(71, 40004483, 40004485, 40004483, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'program_chair', '32100443', 'Chandrakant_Wani', 'Prof.Chandrakant Wani', 'teacher_subject_assignment', '23', 'RM-Research Methodology | 3rd YEAR CSEDS (Sem 6 - STME)', 'assignment_deleted', 'Teacher assignment deleted', 'Teacher assignment removed via admin interface.', '{\"assignment_id\":23,\"teacher_id\":40004483,\"teacher_unique_id\":\"32100443\",\"teacher_name\":\"PROF.CHANDRAKANT WANI (PROGRAM CHAIR)\",\"subject_id\":24,\"subject_name\":\"RM-Research Methodology\",\"class_id\":27,\"section_id\":0,\"class_label\":\"3rd YEAR CSEDS (Sem 6 - STME)\",\"class_school\":\"STME\",\"class_semester\":\"6\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 11:16:07'),
(72, 2147483665, 40004485, 2147483665, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '40002405', 'Vinayak_Mukkawar', 'Prof. Vinayak Mukkawar', 'teacher_subject_assignment', '89:34:0', 'CS-CYBER SECURITY', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":2147483665,\"teacher_unique_id\":\"40002405\",\"subject_id\":89,\"subject_name\":\"CS-CYBER SECURITY\",\"class_id\":34,\"section_id\":0,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":60,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 11:26:32'),
(73, 2147483684, 40004485, 2147483684, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '40005591', 'Nikita_Pande', 'Prof. Nikita Pande	', 'teacher_subject_assignment', '90:34:0', 'ML-MACHINE LEARNING', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":2147483684,\"teacher_unique_id\":\"40005591\",\"subject_id\":90,\"subject_name\":\"ML-MACHINE LEARNING\",\"class_id\":34,\"section_id\":0,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":60,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 11:26:47'),
(74, 2147483667, 40004485, 2147483667, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '40005786', 'Ramesh_Munipala', 'Dr. Ramesh Munipala', 'teacher_subject_assignment', '91:34:0', 'DC-Distributed Computing', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":2147483667,\"teacher_unique_id\":\"40005786\",\"subject_id\":91,\"subject_name\":\"DC-Distributed Computing\",\"class_id\":34,\"section_id\":0,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":60,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 11:27:06'),
(75, 2147483684, 40004485, 2147483684, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '40005591', 'Nikita_Pande', 'Prof. Nikita Pande	', 'teacher_subject_assignment', '92:34:0', 'DE2-CV-COMPUTER VISION', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":2147483684,\"teacher_unique_id\":\"40005591\",\"subject_id\":92,\"subject_name\":\"DE2-CV-COMPUTER VISION\",\"class_id\":34,\"section_id\":0,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":60,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 11:27:39'),
(76, 2147483679, 40004485, 2147483679, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '40005825', 'Anshi_Bajaj', 'Prof. Anshi Bajaj	', 'teacher_subject_assignment', '93:34:0', 'DE3-SQA-Software Quality Assurance', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":2147483679,\"teacher_unique_id\":\"40005825\",\"subject_id\":93,\"subject_name\":\"DE3-SQA-Software Quality Assurance\",\"class_id\":34,\"section_id\":0,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":60,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 11:27:59'),
(77, 40004483, 40004485, 40004483, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'program_chair', '32100443', 'Chandrakant_Wani', 'Prof.Chandrakant Wani', 'teacher_subject_assignment', '84:34:0', 'OE3-RM- Research Methodology', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":40004483,\"teacher_unique_id\":\"32100443\",\"subject_id\":84,\"subject_name\":\"OE3-RM- Research Methodology\",\"class_id\":34,\"section_id\":0,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":45,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 11:28:17'),
(78, 2147483671, 40004485, 2147483671, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '32100424', 'Rahul_Koshti', 'Dr. Rahul Koshti	', 'teacher_subject_assignment', '85:34:0', 'OE3-DT-DRONE TECHNOLOGY', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":2147483671,\"teacher_unique_id\":\"32100424\",\"subject_id\":85,\"subject_name\":\"OE3-DT-DRONE TECHNOLOGY\",\"class_id\":34,\"section_id\":0,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":60,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 11:28:44'),
(79, 2147483674, 40004485, 2147483674, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '40004014', 'Mubashir_Hassan', 'Dr. Mubashir Hassan	', 'teacher_subject_assignment', '86:34:0', 'OE4-FIMIS - Financial Institutions, Markets, Instruments and Services', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":2147483674,\"teacher_unique_id\":\"40004014\",\"subject_id\":86,\"subject_name\":\"OE4-FIMIS - Financial Institutions, Markets, Instruments and Services\",\"class_id\":34,\"section_id\":0,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":45,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 11:29:07'),
(80, 2147483675, 40004485, 2147483675, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '40005562', 'Akshita_Dwivedi', 'Dr. Akshita Dwivedi	', 'teacher_subject_assignment', '87:34:0', 'OE4-CEM-CREATIVITY AND ETHICS IN MARKETING', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":2147483675,\"teacher_unique_id\":\"40005562\",\"subject_id\":87,\"subject_name\":\"OE4-CEM-CREATIVITY AND ETHICS IN MARKETING\",\"class_id\":34,\"section_id\":0,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":45,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 11:29:20'),
(81, 2147483681, 40004485, 2147483681, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', 'teacher', '40005593', 'Rohith', 'Dr. Rocharla Rohith	', 'teacher_subject_assignment', '88:34:0', 'IS-Interpersonal Skills', 'assignment_created', 'Teacher assignment created', 'Teacher assigned to subject and class.', '{\"teacher_id\":2147483681,\"teacher_unique_id\":\"40005593\",\"subject_id\":88,\"subject_name\":\"IS-Interpersonal Skills\",\"class_id\":34,\"section_id\":0,\"class_label\":null,\"school\":null,\"semester\":null,\"total_planned_hours\":30,\"active_term_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 11:29:33'),
(82, NULL, 2147483706, NULL, 'student', '70572300006', '70572300006', 'MAKKENA LAHARI', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'logout', 'User logged out', 'Logout requested from logout modal.', '{\"session_id\":\"t9f4a6l9n8dadlfgb6clj7i9d6\",\"request_method\":\"POST\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 11:48:23'),
(83, NULL, 2147483707, NULL, 'student', '70572300022', '70572300022', 'GUMUDAVELLI VIKRAM', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'login_success_auto_account', 'Auto-created student login', 'Student account generated from SAP sign-in and login initiated.', '{\"identifier_used\":\"70572300022\",\"session_id\":\"t9f4a6l9n8dadlfgb6clj7i9d6\",\"auto_account_created\":true,\"student_record_id\":1000000155}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 11:48:35'),
(84, NULL, 2147483686, NULL, 'teacher', '40001918', 'NMS_Desai', 'NMS Desai', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'login_success', 'User logged in', 'Successful login via main portal.', '{\"identifier_used\":\"40001918\",\"role\":\"teacher\",\"post_login_redirect\":\"teacher_dashboard.php\",\"session_id\":\"q3ec1lle1qil7ebqcqai0hbjij\",\"force_password_change\":true,\"student_profile_id\":null,\"require_college_email\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 13:20:44'),
(85, NULL, 2147483698, NULL, 'student', '70572300021', '70572300021', 'BRUNGI SHIVA GANESH', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'login_success', 'User logged in', 'Successful login via main portal.', '{\"identifier_used\":\"70572300021\",\"role\":\"student\",\"post_login_redirect\":\"student_dashboard.php\",\"session_id\":\"cocjnsrenqcdk2bq0tn2o5a3pa\",\"force_password_change\":true,\"student_profile_id\":1000000154,\"require_college_email\":true}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 13:21:39'),
(86, NULL, 40004485, NULL, 'admin', 'Raja_Govind', 'Raja_Govind', 'Raja Govind', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'admin_login_success', 'Administrator logged in', 'Admin portal authentication succeeded.', '{\"identifier_used\":\"40004485\",\"teacher_unique_id\":\"40004485\",\"session_id\":\"ioej5v76tv1rb0erecbqv9mgng\",\"force_password_change\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 14:56:46'),
(87, NULL, 40004481, NULL, 'teacher', '40004803', 'Bhanu_sree', 'Dr.Bhanu Sree', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'logout', 'User logged out', 'Logout requested from logout modal.', '{\"session_id\":\"4vllpbhgllup313vhreg56vfli\",\"request_method\":\"POST\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 15:03:24'),
(88, NULL, 40004483, NULL, 'program_chair', '32100443', 'Chandrakant_Wani', 'Prof.Chandrakant Wani', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'login_success', 'User logged in', 'Successful login via main portal.', '{\"identifier_used\":\"32100443\",\"role\":\"program_chair\",\"post_login_redirect\":\"login_as.php\",\"session_id\":\"shu7ipl2i77ln31j4unat9qi13\",\"force_password_change\":false,\"student_profile_id\":null,\"require_college_email\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 15:03:29'),
(89, NULL, 40004483, NULL, 'program_chair', '32100443', 'Chandrakant_Wani', 'Prof.Chandrakant Wani', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'logout', 'User logged out', 'Logout requested from logout modal.', '{\"session_id\":\"shu7ipl2i77ln31j4unat9qi13\",\"request_method\":\"POST\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 15:04:17'),
(90, NULL, 2147483696, NULL, 'student', '70572300033', '70572300033', 'MD RAYYAN', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'login_success', 'User logged in', 'Successful login via main portal.', '{\"identifier_used\":\"70572300033\",\"role\":\"student\",\"post_login_redirect\":\"student_dashboard.php\",\"session_id\":\"e7k4gv8gu8eb105g1phnac5bt8\",\"force_password_change\":true,\"student_profile_id\":1000000161,\"require_college_email\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 15:04:29'),
(91, NULL, 2147483696, NULL, 'student', '70572300033', '70572300033', 'MD RAYYAN', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'logout', 'User logged out', 'Logout requested from logout modal.', '{\"session_id\":\"e7k4gv8gu8eb105g1phnac5bt8\",\"request_method\":\"POST\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 15:07:35');

-- --------------------------------------------------------

--
-- Table structure for table `alerts`
--

CREATE TABLE `alerts` (
  `id` int(11) NOT NULL,
  `teacher_id` bigint(20) NOT NULL,
  `message` text NOT NULL,
  `status` enum('pending','responded','resolved') DEFAULT 'pending',
  `response` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `responded_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `id` int(11) NOT NULL,
  `teacher_id` bigint(20) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `subject` varchar(100) NOT NULL,
  `assignment_type` varchar(50) DEFAULT NULL,
  `assignment_number` varchar(50) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_at` datetime DEFAULT NULL,
  `due_at` datetime DEFAULT NULL,
  `max_marks` decimal(6,2) DEFAULT NULL,
  `instructions_file` varchar(255) DEFAULT NULL,
  `deadline` date NOT NULL,
  `status` enum('pending','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`id`, `teacher_id`, `class_id`, `section_id`, `subject_id`, `subject`, `assignment_type`, `assignment_number`, `title`, `description`, `start_at`, `due_at`, `max_marks`, `instructions_file`, `deadline`, `status`, `created_at`, `updated_at`) VALUES
(3, 40004495, NULL, NULL, NULL, 'Calculus', NULL, NULL, 'Calculus Assignment 1', 'Solve problems on limits and derivatives', NULL, '2025-09-20 23:59:00', NULL, NULL, '2025-09-20', 'pending', '2025-09-16 04:47:53', '2025-12-21 20:03:16'),
(4, 40004495, NULL, NULL, NULL, 'Calculus', NULL, NULL, 'Calculus Assignment 2', 'Integration problems', NULL, '2025-09-25 23:59:00', NULL, NULL, '2025-09-25', 'pending', '2025-09-16 04:47:53', '2025-12-21 20:03:16'),
(5, 40004494, NULL, NULL, NULL, 'ML', NULL, NULL, 'ML Assignment 1', 'Implement a basic ML model', NULL, '2025-09-22 23:59:00', NULL, NULL, '2025-09-22', 'pending', '2025-09-16 04:47:53', '2025-12-21 20:03:16'),
(17, 40004481, 21, 0, 13, 'Software Engineering', 'Lab Work', '2', 'Class Diagram', 'Draw Class diagrams', '2025-12-22 01:37:00', '2025-12-24 23:59:00', 10.00, NULL, '2025-12-24', 'pending', '2025-12-21 20:09:14', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `class_name` varchar(50) NOT NULL,
  `semester` varchar(50) NOT NULL,
  `school` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `teacher_id` bigint(20) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `academic_term_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `class_name`, `semester`, `school`, `description`, `teacher_id`, `department`, `academic_term_id`) VALUES
(21, '3rd Year CSEDS', '5', 'STME', '', NULL, NULL, 1),
(22, '2nd Year CSEDS', '3', 'STME', '', NULL, NULL, 1),
(23, '1st Year CSEDS ', '1', 'STME', '', NULL, NULL, 1),
(24, '1st Year CE', '1', 'STME', '', NULL, NULL, 1),
(25, '4th Year CSEDS', '7', 'STME', '', NULL, NULL, 1),
(26, '3rd Year CE', '5', 'STME', '', NULL, NULL, 1),
(27, '3rd Year CSEDS', '6', 'STME', '', NULL, NULL, 2),
(28, '1st Year CSEDS', '2', 'STME', '', NULL, NULL, 2),
(29, '2nd Year CE', '3', 'STME', '', NULL, NULL, 1),
(30, '1st Year CE', '2', 'STME', '', NULL, NULL, 2),
(31, '2ND YEAR CSEDS', '4', 'STME', '', NULL, NULL, 2),
(32, '2ND YEAR CE', '4', 'STME', '', NULL, NULL, 2),
(33, '4TH YEAR CSEDS', '8', 'STME', '', NULL, NULL, 2),
(34, '3RD YEAR CE', '6', 'STME', '', NULL, NULL, 2);

-- --------------------------------------------------------

--
-- Table structure for table `class_timetables`
--

CREATE TABLE `class_timetables` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_timetables`
--

INSERT INTO `class_timetables` (`id`, `class_id`, `file_name`, `file_path`, `uploaded_at`) VALUES
(1, 21, 'Third Year_01-09-2025.pdf', 'uploads/class_timetables/class_21_20251112061511_438e4495.pdf', '2025-11-12 05:15:11'),
(2, 30, 'STME_Hyd_Timetable_Term_II AY2025-26_2Jan2026 to 25Apr2026.xlsx', 'uploads/class_timetables/class_30_20260102103549_d266a909.xlsx', '2026-01-02 09:35:49'),
(3, 28, 'STME_Hyd_Timetable_Term_II AY2025-26_2Jan2026 to 25Apr2026.xlsx', 'uploads/class_timetables/class_28_20260102103602_da3346ac.xlsx', '2026-01-02 09:36:02'),
(4, 32, 'STME_Hyd_Timetable_Term_II AY2025-26_2Jan2026 to 25Apr2026.xlsx', 'uploads/class_timetables/class_32_20260102103613_d8d62f05.xlsx', '2026-01-02 09:36:13'),
(5, 31, 'STME_Hyd_Timetable_Term_II AY2025-26_2Jan2026 to 25Apr2026.xlsx', 'uploads/class_timetables/class_31_20260102103625_11afb4e0.xlsx', '2026-01-02 09:36:25'),
(6, 34, 'STME_Hyd_Timetable_Term_II AY2025-26_2Jan2026 to 25Apr2026.xlsx', 'uploads/class_timetables/class_34_20260102103635_7866e386.xlsx', '2026-01-02 09:36:35'),
(7, 27, 'STME_Hyd_Timetable_Term_II AY2025-26_2Jan2026 to 25Apr2026.xlsx', 'uploads/class_timetables/class_27_20260102103645_315defbf.xlsx', '2026-01-02 09:36:45'),
(8, 33, 'STME_Hyd_Timetable_Term_II AY2025-26_2Jan2026 to 25Apr2026.xlsx', 'uploads/class_timetables/class_33_20260102103654_47a30f85.xlsx', '2026-01-02 09:36:54');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ica_components`
--

CREATE TABLE `ica_components` (
  `id` int(11) NOT NULL,
  `teacher_id` bigint(20) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `course_type` varchar(50) NOT NULL,
  `component_name` varchar(100) NOT NULL,
  `instances` int(11) NOT NULL DEFAULT 1,
  `marks_per_instance` decimal(5,2) NOT NULL,
  `total_marks` decimal(5,2) NOT NULL,
  `scaled_total_marks` decimal(5,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ica_components`
--

INSERT INTO `ica_components` (`id`, `teacher_id`, `subject_id`, `class_id`, `course_type`, `component_name`, `instances`, `marks_per_instance`, `total_marks`, `scaled_total_marks`, `created_at`) VALUES
(54, 40004481, 13, 21, 'Credit + Term End', 'Mid Exam ', 2, 10.00, 20.00, 10.00, '2025-11-09 11:43:42'),
(55, 40004481, 13, 21, 'Credit + Term End', 'Presentation', 1, 10.00, 10.00, 10.00, '2025-11-09 11:43:42'),
(56, 40004481, 13, 21, 'Credit + Term End', 'Project', 1, 30.00, 30.00, 30.00, '2025-11-09 11:43:42'),
(57, 40004494, 14, NULL, 'Credit + ICA', 'Mid Exam 1', 1, 10.00, 10.00, 10.00, '2025-11-10 08:46:03'),
(58, 40004494, 14, NULL, 'Credit + ICA', 'Mid Exam 2', 1, 10.00, 10.00, 10.00, '2025-11-10 08:46:03'),
(59, 40004494, 14, NULL, 'Credit + ICA', 'Major Project ', 1, 20.00, 20.00, 20.00, '2025-11-10 08:46:03'),
(60, 40004494, 14, NULL, 'Credit + ICA', 'Mini Project', 1, 10.00, 10.00, 10.00, '2025-11-10 08:46:03'),
(61, 40004481, 23, NULL, 'Credit + ICA', 'Paper ', 1, 30.00, 30.00, 30.00, '2025-11-10 16:29:52'),
(62, 40004481, 23, NULL, 'Credit + ICA', 'Case Study', 1, 50.00, 50.00, 20.00, '2025-11-10 16:29:52'),
(73, 40004481, 23, 25, 'Credit + ICA', 'Mid Exam', 2, 10.00, 20.00, 20.00, '2025-11-14 16:44:18'),
(74, 40004481, 23, 25, 'Credit + ICA', 'Quiz', 3, 10.00, 30.00, 20.00, '2025-11-14 16:44:18'),
(77, 40004481, 23, 25, 'Credit + ICA', 'Research Paper', 1, 10.00, 10.00, 10.00, '2025-12-15 16:52:49'),
(78, 40004481, 13, 26, 'Credit + Term End', 'Mid Exam ', 2, 10.00, 20.00, 10.00, '2025-12-17 13:19:36'),
(79, 40004481, 13, 26, 'Credit + Term End', 'Presentation', 1, 10.00, 10.00, 10.00, '2025-12-17 13:19:36'),
(80, 40004481, 13, 26, 'Credit + Term End', 'Project', 1, 30.00, 30.00, 30.00, '2025-12-17 13:19:36');

-- --------------------------------------------------------

--
-- Table structure for table `ica_marks`
--

CREATE TABLE `ica_marks` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `subject` varchar(50) NOT NULL,
  `timeline` varchar(20) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `component_type` varchar(50) NOT NULL,
  `marks` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ica_student_marks`
--

CREATE TABLE `ica_student_marks` (
  `id` int(11) NOT NULL,
  `teacher_id` bigint(20) NOT NULL,
  `student_id` int(11) NOT NULL,
  `component_id` int(11) NOT NULL,
  `instance_number` int(11) NOT NULL,
  `marks` decimal(5,2) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ica_student_marks`
--

INSERT INTO `ica_student_marks` (`id`, `teacher_id`, `student_id`, `component_id`, `instance_number`, `marks`, `updated_at`) VALUES
(992, 40004481, 1000000147, 54, 1, 3.00, '2025-11-15 10:30:48'),
(993, 40004481, 1000000148, 54, 1, 4.00, '2025-11-09 12:51:08'),
(994, 40004481, 1000000149, 54, 1, NULL, '2025-11-15 10:30:48'),
(995, 40004481, 1000000150, 54, 1, 6.00, '2025-11-09 12:51:08'),
(996, 40004481, 1000000151, 54, 1, 7.00, '2025-11-09 12:51:08'),
(997, 40004481, 1000000152, 54, 1, 8.00, '2025-11-09 11:47:44'),
(998, 40004481, 1000000153, 54, 1, 9.00, '2025-11-09 12:51:08'),
(999, 40004481, 1000000154, 54, 1, 3.00, '2025-11-09 12:51:08'),
(1000, 40004481, 1000000155, 54, 1, 4.00, '2025-11-09 12:51:08'),
(1001, 40004481, 1000000156, 54, 1, NULL, '2025-11-09 12:51:08'),
(1002, 40004481, 1000000157, 54, 1, 3.00, '2025-11-09 12:51:08'),
(1003, 40004481, 1000000159, 54, 1, 5.00, '2025-11-09 11:47:44'),
(1004, 40004481, 1000000160, 54, 1, 6.00, '2025-11-09 11:47:44'),
(1005, 40004481, 1000000161, 54, 1, 7.00, '2025-11-09 12:51:08'),
(1006, 40004481, 1000000162, 54, 1, 3.00, '2025-11-09 12:51:08'),
(1007, 40004481, 1000000163, 54, 1, 4.00, '2025-11-09 12:51:08'),
(1008, 40004481, 1000000164, 54, 1, 5.00, '2025-11-09 12:51:08'),
(1009, 40004481, 1000000165, 54, 1, 6.00, '2025-11-09 12:51:08'),
(1010, 40004481, 1000000166, 54, 1, 7.00, '2025-11-09 12:51:08'),
(1011, 40004481, 1000000167, 54, 1, 8.00, '2025-11-09 12:51:08'),
(1012, 40004481, 1000000168, 54, 1, 9.00, '2025-11-09 12:51:08'),
(1013, 40004481, 1000000169, 54, 1, 3.00, '2025-11-09 12:51:08'),
(1014, 40004481, 1000000170, 54, 1, 4.00, '2025-11-09 12:51:08'),
(1015, 40004481, 1000000171, 54, 1, 5.00, '2025-11-09 12:51:08'),
(1016, 40004481, 1000000172, 54, 1, 3.00, '2025-11-09 12:51:08'),
(1017, 40004481, 1000000173, 54, 1, 4.00, '2025-11-09 12:51:08'),
(1018, 40004481, 1000000174, 54, 1, 3.00, '2025-11-09 12:51:08'),
(1019, 40004481, 1000000175, 54, 1, 4.00, '2025-11-09 12:51:08'),
(1020, 40004481, 1000000176, 54, 1, 5.00, '2025-11-09 12:51:08'),
(1021, 40004481, 1000000177, 54, 1, 6.00, '2025-11-09 12:51:08'),
(1022, 40004481, 1000000178, 54, 1, 7.00, '2025-11-09 11:47:44'),
(1023, 40004481, 1000000180, 54, 1, 9.00, '2025-11-09 12:51:08'),
(1024, 40004481, 1000000179, 54, 1, 8.00, '2025-11-15 10:30:48'),
(1036, 40004481, 1000000158, 54, 1, 4.00, '2025-11-09 12:51:08'),
(1059, 40004481, 1000000147, 56, 1, 17.00, '2025-11-09 12:49:09'),
(1060, 40004481, 1000000148, 56, 1, 18.00, '2025-11-09 12:49:09'),
(1061, 40004481, 1000000149, 56, 1, 19.00, '2025-11-09 12:49:09'),
(1062, 40004481, 1000000150, 56, 1, 20.00, '2025-11-09 12:49:09'),
(1063, 40004481, 1000000151, 56, 1, 21.00, '2025-11-09 12:49:09'),
(1064, 40004481, 1000000152, 56, 1, 22.00, '2025-11-09 12:49:09'),
(1065, 40004481, 1000000153, 56, 1, 23.00, '2025-11-09 12:49:09'),
(1066, 40004481, 1000000154, 56, 1, 24.00, '2025-11-09 12:49:09'),
(1067, 40004481, 1000000155, 56, 1, 25.00, '2025-11-09 12:49:09'),
(1068, 40004481, 1000000156, 56, 1, 26.00, '2025-11-09 12:49:09'),
(1069, 40004481, 1000000157, 56, 1, 27.00, '2025-11-09 12:49:09'),
(1070, 40004481, 1000000158, 56, 1, 28.00, '2025-11-09 12:49:09'),
(1071, 40004481, 1000000159, 56, 1, 29.00, '2025-11-09 12:49:09'),
(1072, 40004481, 1000000160, 56, 1, 17.00, '2025-11-09 12:49:09'),
(1073, 40004481, 1000000161, 56, 1, 18.00, '2025-11-09 12:49:09'),
(1074, 40004481, 1000000162, 56, 1, 19.00, '2025-11-09 12:49:09'),
(1075, 40004481, 1000000163, 56, 1, 20.00, '2025-11-09 12:49:09'),
(1076, 40004481, 1000000164, 56, 1, 21.00, '2025-11-09 12:49:09'),
(1077, 40004481, 1000000165, 56, 1, 22.00, '2025-11-09 12:49:09'),
(1078, 40004481, 1000000166, 56, 1, 23.00, '2025-11-09 12:49:09'),
(1079, 40004481, 1000000167, 56, 1, 24.00, '2025-11-09 12:49:09'),
(1080, 40004481, 1000000168, 56, 1, 25.00, '2025-11-09 12:49:09'),
(1081, 40004481, 1000000169, 56, 1, 26.00, '2025-11-09 12:49:09'),
(1082, 40004481, 1000000170, 56, 1, 27.00, '2025-11-09 12:49:09'),
(1083, 40004481, 1000000171, 56, 1, 28.00, '2025-11-09 12:49:09'),
(1084, 40004481, 1000000172, 56, 1, 29.00, '2025-11-09 12:49:09'),
(1085, 40004481, 1000000173, 56, 1, 17.00, '2025-11-09 12:49:09'),
(1086, 40004481, 1000000174, 56, 1, 18.00, '2025-11-09 12:49:09'),
(1087, 40004481, 1000000175, 56, 1, 19.00, '2025-11-09 12:49:09'),
(1088, 40004481, 1000000176, 56, 1, 20.00, '2025-11-09 12:49:09'),
(1089, 40004481, 1000000177, 56, 1, 21.00, '2025-11-09 12:49:09'),
(1090, 40004481, 1000000178, 56, 1, 22.00, '2025-11-09 12:49:09'),
(1091, 40004481, 1000000180, 56, 1, 24.00, '2025-11-09 12:49:09'),
(1092, 40004481, 1000000179, 56, 1, 23.00, '2025-11-09 12:49:09'),
(1127, 40004481, 1000000147, 55, 1, 9.00, '2025-11-09 13:20:17'),
(1128, 40004481, 1000000148, 55, 1, 10.00, '2025-11-09 13:20:17'),
(1129, 40004481, 1000000149, 55, 1, NULL, '2025-11-09 13:20:17'),
(1130, 40004481, 1000000150, 55, 1, 6.00, '2025-11-09 13:20:17'),
(1131, 40004481, 1000000151, 55, 1, 7.00, '2025-11-09 13:20:17'),
(1132, 40004481, 1000000152, 55, 1, 8.00, '2025-11-09 13:20:17'),
(1133, 40004481, 1000000153, 55, 1, 9.00, '2025-11-09 13:20:17'),
(1134, 40004481, 1000000154, 55, 1, 3.00, '2025-11-09 13:20:17'),
(1135, 40004481, 1000000155, 55, 1, 4.00, '2025-11-09 13:20:17'),
(1136, 40004481, 1000000156, 55, 1, NULL, '2025-11-09 13:20:17'),
(1137, 40004481, 1000000157, 55, 1, 3.00, '2025-11-09 13:20:17'),
(1138, 40004481, 1000000158, 55, 1, 4.00, '2025-11-09 13:20:17'),
(1139, 40004481, 1000000159, 55, 1, 5.00, '2025-11-09 13:20:17'),
(1140, 40004481, 1000000160, 55, 1, 6.00, '2025-11-09 13:20:17'),
(1141, 40004481, 1000000161, 55, 1, 7.00, '2025-11-09 13:20:17'),
(1142, 40004481, 1000000162, 55, 1, 3.00, '2025-11-09 13:20:17'),
(1143, 40004481, 1000000163, 55, 1, 4.00, '2025-11-09 13:20:17'),
(1144, 40004481, 1000000164, 55, 1, 5.00, '2025-11-09 13:20:17'),
(1145, 40004481, 1000000165, 55, 1, 6.00, '2025-11-09 13:20:17'),
(1146, 40004481, 1000000166, 55, 1, 7.00, '2025-11-09 13:20:17'),
(1147, 40004481, 1000000167, 55, 1, 8.00, '2025-11-09 13:20:17'),
(1148, 40004481, 1000000168, 55, 1, 9.00, '2025-11-09 13:20:17'),
(1149, 40004481, 1000000169, 55, 1, 3.00, '2025-11-09 13:20:17'),
(1150, 40004481, 1000000170, 55, 1, 4.00, '2025-11-09 13:20:17'),
(1151, 40004481, 1000000171, 55, 1, 5.00, '2025-11-09 13:20:17'),
(1152, 40004481, 1000000172, 55, 1, 3.00, '2025-11-09 13:20:17'),
(1153, 40004481, 1000000173, 55, 1, 4.00, '2025-11-09 13:20:17'),
(1154, 40004481, 1000000174, 55, 1, 3.00, '2025-11-09 13:20:17'),
(1155, 40004481, 1000000175, 55, 1, 4.00, '2025-11-09 13:20:17'),
(1156, 40004481, 1000000176, 55, 1, 5.00, '2025-11-09 13:20:17'),
(1157, 40004481, 1000000177, 55, 1, 6.00, '2025-11-09 13:20:17'),
(1158, 40004481, 1000000178, 55, 1, 7.00, '2025-11-09 13:20:17'),
(1159, 40004481, 1000000180, 55, 1, 9.00, '2025-11-09 13:20:17'),
(1160, 40004481, 1000000179, 55, 1, 8.00, '2025-11-09 13:20:17'),
(1229, 40004481, 1000000424, 54, 1, 9.00, '2025-11-12 06:22:31'),
(1230, 40004481, 1000000425, 54, 1, 9.00, '2025-11-12 06:22:31'),
(1231, 40004481, 1000000426, 54, 1, 8.00, '2025-11-12 06:22:31'),
(1232, 40004481, 1000000427, 54, 1, 7.00, '2025-11-12 06:22:31'),
(1233, 40004481, 1000000428, 54, 1, 7.00, '2025-11-12 06:22:31'),
(1234, 40004481, 1000000429, 54, 1, 7.00, '2025-11-12 06:22:31'),
(1235, 40004481, 1000000430, 54, 1, 8.00, '2025-11-12 06:22:31'),
(1236, 40004481, 1000000424, 55, 1, 9.00, '2025-11-12 06:22:47'),
(1237, 40004481, 1000000425, 55, 1, 8.00, '2025-11-12 06:22:47'),
(1238, 40004481, 1000000426, 55, 1, 9.00, '2025-11-12 06:22:47'),
(1239, 40004481, 1000000427, 55, 1, 9.00, '2025-11-12 06:22:47'),
(1240, 40004481, 1000000428, 55, 1, 9.00, '2025-11-12 06:22:47'),
(1241, 40004481, 1000000429, 55, 1, 7.00, '2025-11-12 06:22:47'),
(1242, 40004481, 1000000430, 55, 1, 8.00, '2025-11-12 06:22:47'),
(1243, 40004481, 1000000424, 56, 1, 14.00, '2025-11-12 06:24:11'),
(1244, 40004481, 1000000425, 56, 1, 17.00, '2025-11-12 06:24:11'),
(1245, 40004481, 1000000426, 56, 1, 18.00, '2025-11-12 06:24:11'),
(1246, 40004481, 1000000427, 56, 1, 18.00, '2025-11-12 06:24:11'),
(1247, 40004481, 1000000428, 56, 1, 19.00, '2025-11-12 06:24:11'),
(1248, 40004481, 1000000429, 56, 1, 18.00, '2025-11-12 06:24:11'),
(1249, 40004481, 1000000430, 56, 1, 20.00, '2025-11-12 06:24:11'),
(1461, 40004481, 1000000431, 73, 1, 6.00, '2025-12-16 14:17:14'),
(1462, 40004481, 1000000432, 73, 1, 7.00, '2025-12-16 14:17:14'),
(1463, 40004481, 1000000433, 73, 1, 8.00, '2025-12-16 14:17:14'),
(1464, 40004481, 1000000434, 73, 1, 9.00, '2025-12-16 14:17:14'),
(1465, 40004481, 1000000435, 73, 1, 6.00, '2025-12-16 11:55:35'),
(1466, 40004481, 1000000436, 73, 1, 8.00, '2025-12-16 14:17:14'),
(1467, 40004481, 1000000437, 73, 1, 5.00, '2025-12-16 14:17:14'),
(1468, 40004481, 1000000438, 73, 1, 3.00, '2025-12-16 14:17:14'),
(1469, 40004481, 1000000439, 73, 1, 2.00, '2025-12-16 14:17:14'),
(1470, 40004481, 1000000440, 73, 1, 3.00, '2025-12-16 14:17:14'),
(1471, 40004481, 1000000441, 73, 1, 8.00, '2025-12-16 14:17:14'),
(1472, 40004481, 1000000442, 73, 1, 5.00, '2025-12-16 14:17:14'),
(1473, 40004481, 1000000443, 73, 1, 3.00, '2025-12-16 14:17:14'),
(1474, 40004481, 1000000444, 73, 1, 2.00, '2025-12-16 14:17:14'),
(1475, 40004481, 1000000445, 73, 1, 3.00, '2025-12-16 14:17:14'),
(1476, 40004481, 1000000446, 73, 1, 8.00, '2025-12-16 14:17:14'),
(1477, 40004481, 1000000447, 73, 1, 5.00, '2025-12-16 14:17:14'),
(1478, 40004481, 1000000448, 73, 1, 6.00, '2025-12-16 14:17:14'),
(1479, 40004481, 1000000449, 73, 1, 7.00, '2025-12-16 14:17:14'),
(1480, 40004481, 1000000450, 73, 1, 8.00, '2025-12-16 14:17:14'),
(1481, 40004481, 1000000451, 73, 1, 9.00, '2025-12-16 11:55:35'),
(1482, 40004481, 1000000452, 73, 1, 6.00, '2025-12-16 14:17:14'),
(1483, 40004481, 1000000453, 73, 1, 6.00, '2025-12-16 14:17:14'),
(1484, 40004481, 1000000454, 73, 1, 7.00, '2025-12-16 11:55:35'),
(1485, 40004481, 1000000455, 73, 1, 8.00, '2025-12-16 14:17:14'),
(1486, 40004481, 1000000456, 73, 1, 9.00, '2025-12-16 14:17:14'),
(1487, 40004481, 1000000457, 73, 1, 8.00, '2025-12-16 14:17:14'),
(1488, 40004481, 1000000458, 73, 1, 5.00, '2025-12-16 14:17:14'),
(1489, 40004481, 1000000459, 73, 1, 3.00, '2025-12-16 11:55:35'),
(1490, 40004481, 1000000460, 73, 1, 2.00, '2025-12-16 14:17:14'),
(1491, 40004481, 1000000461, 73, 1, 3.00, '2025-12-16 14:17:14'),
(1492, 40004481, 1000000462, 73, 1, NULL, '2025-12-16 14:17:14'),
(1557, 40004481, 1000000431, 73, 2, 5.00, '2025-12-16 16:26:01'),
(1558, 40004481, 1000000432, 73, 2, 3.00, '2025-12-16 16:26:01'),
(1559, 40004481, 1000000433, 73, 2, 2.00, '2025-12-16 16:26:01'),
(1560, 40004481, 1000000434, 73, 2, 3.00, '2025-12-16 16:26:01'),
(1561, 40004481, 1000000435, 73, 2, 8.00, '2025-12-16 16:26:01'),
(1562, 40004481, 1000000436, 73, 2, 5.00, '2025-12-16 16:26:01'),
(1563, 40004481, 1000000437, 73, 2, 6.00, '2025-12-16 16:26:01'),
(1564, 40004481, 1000000438, 73, 2, 7.00, '2025-12-16 16:26:01'),
(1565, 40004481, 1000000439, 73, 2, 8.00, '2025-12-16 16:26:01'),
(1566, 40004481, 1000000440, 73, 2, 5.00, '2025-12-16 16:26:01'),
(1567, 40004481, 1000000441, 73, 2, 6.00, '2025-12-16 16:26:01'),
(1568, 40004481, 1000000442, 73, 2, 7.00, '2025-12-16 16:26:01'),
(1569, 40004481, 1000000443, 73, 2, 8.00, '2025-12-16 16:26:01'),
(1570, 40004481, 1000000444, 73, 2, 9.00, '2025-12-16 16:26:01'),
(1571, 40004481, 1000000445, 73, 2, 6.00, '2025-12-16 16:26:01'),
(1572, 40004481, 1000000446, 73, 2, 6.00, '2025-12-16 16:26:01'),
(1573, 40004481, 1000000447, 73, 2, 7.00, '2025-12-16 16:26:01'),
(1574, 40004481, 1000000448, 73, 2, 8.00, '2025-12-16 16:26:01'),
(1575, 40004481, 1000000449, 73, 2, 9.00, '2025-12-16 16:26:01'),
(1576, 40004481, 1000000450, 73, 2, 8.00, '2025-12-16 16:26:01'),
(1577, 40004481, 1000000451, 73, 2, 5.00, '2025-12-16 16:26:01'),
(1578, 40004481, 1000000452, 73, 2, 3.00, '2025-12-16 16:26:01'),
(1579, 40004481, 1000000453, 73, 2, 8.00, '2025-12-16 16:26:01'),
(1580, 40004481, 1000000454, 73, 2, 5.00, '2025-12-16 16:26:01'),
(1581, 40004481, 1000000455, 73, 2, 3.00, '2025-12-16 16:26:01'),
(1582, 40004481, 1000000456, 73, 2, 2.00, '2025-12-16 16:26:01'),
(1583, 40004481, 1000000457, 73, 2, 3.00, '2025-12-16 16:26:01'),
(1584, 40004481, 1000000458, 73, 2, 8.00, '2025-12-16 16:26:01'),
(1585, 40004481, 1000000459, 73, 2, 7.00, '2025-12-16 16:26:01'),
(1586, 40004481, 1000000460, 73, 2, 6.00, '2025-12-16 16:26:01'),
(1587, 40004481, 1000000461, 73, 2, 7.00, '2025-12-16 16:26:01'),
(1588, 40004481, 1000000462, 73, 2, 8.00, '2025-12-16 16:26:01'),
(1589, 40004481, 1000000424, 78, 1, 7.00, '2025-12-17 13:23:26'),
(1590, 40004481, 1000000425, 78, 1, 9.00, '2025-12-17 13:23:26'),
(1591, 40004481, 1000000426, 78, 1, 5.00, '2025-12-17 13:23:26'),
(1592, 40004481, 1000000427, 78, 1, 6.00, '2025-12-17 13:23:26'),
(1593, 40004481, 1000000428, 78, 1, 7.00, '2025-12-17 13:23:26'),
(1594, 40004481, 1000000429, 78, 1, 8.00, '2025-12-17 13:23:26'),
(1595, 40004481, 1000000430, 78, 1, 7.00, '2025-12-17 13:23:26'),
(1596, 40004481, 1000000424, 78, 2, 6.00, '2025-12-17 13:23:26'),
(1597, 40004481, 1000000425, 78, 2, 5.00, '2025-12-17 13:23:26'),
(1598, 40004481, 1000000426, 78, 2, 9.00, '2025-12-17 13:23:26'),
(1599, 40004481, 1000000427, 78, 2, 9.00, '2025-12-17 13:23:26'),
(1600, 40004481, 1000000428, 78, 2, 9.00, '2025-12-17 13:23:26'),
(1601, 40004481, 1000000429, 78, 2, 9.00, '2025-12-17 13:23:26'),
(1602, 40004481, 1000000430, 78, 2, 9.00, '2025-12-17 13:23:26'),
(1603, 40004481, 1000000424, 79, 1, 8.00, '2025-12-17 13:23:26'),
(1604, 40004481, 1000000425, 79, 1, 9.00, '2025-12-17 13:23:26'),
(1605, 40004481, 1000000426, 79, 1, 7.00, '2025-12-17 13:23:26'),
(1606, 40004481, 1000000427, 79, 1, 6.00, '2025-12-17 13:23:26'),
(1607, 40004481, 1000000428, 79, 1, 8.00, '2025-12-17 13:23:26'),
(1608, 40004481, 1000000429, 79, 1, 9.00, '2025-12-17 13:23:26'),
(1609, 40004481, 1000000430, 79, 1, 4.00, '2025-12-17 13:23:26'),
(1610, 40004481, 1000000424, 80, 1, 23.00, '2025-12-17 13:23:26'),
(1611, 40004481, 1000000425, 80, 1, 5.00, '2025-12-17 13:23:26'),
(1612, 40004481, 1000000426, 80, 1, 3.00, '2025-12-17 13:23:26'),
(1613, 40004481, 1000000427, 80, 1, 3.00, '2025-12-17 13:23:26'),
(1614, 40004481, 1000000428, 80, 1, 9.00, '2025-12-17 13:23:26'),
(1615, 40004481, 1000000429, 80, 1, 9.00, '2025-12-17 13:23:26'),
(1616, 40004481, 1000000430, 80, 1, 22.00, '2025-12-17 13:23:26');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `token` varchar(128) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `token`, `expires_at`, `created_at`) VALUES
(1, 2147483647, 'f6a06897111b2a953cf5c38e9d5332bc5c35ef5869b84651831b156cbd52c3c0', '2025-11-11 16:55:08', '2025-11-11 20:25:08'),
(2, 2147483647, '4939782cf8db0b0fd28dc60d963df54dd6192c1591713f4bb993ba6732f1737c', '2025-11-11 16:57:03', '2025-11-11 20:27:03'),
(3, 2147483647, 'be8d9faf441415f960a7fa41a028355e3426d16fdecc0c473babdc516315e390', '2025-11-11 17:27:48', '2025-11-11 20:57:48'),
(4, 2147483647, 'b555989a9d2fe657d44230c26e1cc636fb2c03261c38fed0ae8b839b749daecd', '2025-11-11 17:27:53', '2025-11-11 20:57:53'),
(5, 2147483647, 'c8b6f7d2a4e9d32faf459dda426ed9b139fb5950eaecbdbee157214caf3e22d4', '2025-11-11 17:46:40', '2025-11-11 21:16:40'),
(6, 2147483647, 'd5359510c5b5630552396ed8dde2f901d9ba5ef7b600a0d575c45010b435bec4', '2025-11-11 18:08:37', '2025-11-11 21:38:37');

-- --------------------------------------------------------

--
-- Table structure for table `schools`
--

CREATE TABLE `schools` (
  `id` int(11) NOT NULL,
  `school_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schools`
--

INSERT INTO `schools` (`id`, `school_name`, `created_at`) VALUES
(1, 'STME', '2025-10-29 17:29:55'),
(2, 'SOL', '2025-10-29 17:29:55'),
(3, 'SPTM', '2025-10-29 17:29:55'),
(4, 'SBM', '2025-10-29 17:29:55'),
(5, 'SOC', '2025-10-29 17:29:55');

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `section_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`id`, `class_id`, `section_name`) VALUES
(8, 22, 'B'),
(9, 23, 'A'),
(10, 23, 'B'),
(11, 24, 'C'),
(12, 24, 'D'),
(13, 22, 'A'),
(14, 28, 'A'),
(15, 28, 'B'),
(16, 30, 'C'),
(17, 30, 'D'),
(20, 31, 'A'),
(21, 31, 'B');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `user_id`, `setting_key`, `setting_value`) VALUES
(1, 40004483, 'syllabus_threshold', '80'),
(2, 40004483, 'performance_threshold', '50'),
(3, 40004483, 'email_notifications', '1');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `roll_number` varchar(10) NOT NULL,
  `sap_id` bigint(20) DEFAULT NULL,
  `class_id` int(11) NOT NULL,
  `section_id` int(11) DEFAULT NULL,
  `college_email` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `name`, `roll_number`, `sap_id`, `class_id`, `section_id`, `college_email`) VALUES
(1000000147, 'KANCHARLA RATNA CHAND', 'D004', 70572300004, 21, NULL, NULL),
(1000000148, 'MAKKENA LAHARI', 'D006', 70572300006, 21, NULL, 'makkena.lahari06@nmims.in'),
(1000000149, 'ANOUSHKA SARKAR', 'D010', 70572300010, 21, NULL, NULL),
(1000000150, 'ANGSHUMAN CHAKRAVERTTY', 'D013', 70572300013, 21, NULL, NULL),
(1000000151, 'K.SAI RISHITHA', 'D015', 70572300015, 21, NULL, NULL),
(1000000152, 'S. SAIKARTHIK REDDY', 'D018', 70572300018, 21, NULL, NULL),
(1000000153, 'SIRIPURAM VAISHNAVIGOUD', 'D020', 70572300020, 21, NULL, NULL),
(1000000154, 'BRUNGI SHIVA GANESH', 'D021', 70572300021, 21, NULL, NULL),
(1000000155, 'GUMUDAVELLI VIKRAM', 'D022', 70572300022, 21, NULL, NULL),
(1000000156, 'JAHNAVY LAM', 'D023', 70572300023, 21, NULL, NULL),
(1000000157, 'SAHIL SHAIK', 'D024', 70572300024, 21, NULL, NULL),
(1000000158, 'ANOUSHKA AVUTI', 'D030', 70572300030, 21, NULL, NULL),
(1000000159, 'BADAM BALEESHWAR', 'D031', 70572300031, 21, NULL, NULL),
(1000000160, 'SHERY MOUNIKA REDDY', 'D032', 70572300032, 21, NULL, NULL),
(1000000161, 'MD RAYYAN', 'D033', 70572300033, 21, NULL, 'md.rayyan33@nmims.in'),
(1000000162, 'SAI KRISHNA REDDY KUCHURU', 'D034', 70572300034, 21, NULL, 'kuchurusai.krishna34@nmims.in'),
(1000000163, 'AASHRITHA REDDY', 'D035', 70572300035, 21, NULL, NULL),
(1000000164, 'RAMYA KUSUNURILAKSHMI', 'D036', 70572300036, 21, NULL, NULL),
(1000000165, 'PASUPULA SAI TEJA', 'D037', 70572300037, 21, NULL, NULL),
(1000000166, 'G HARSHITH RAJ', 'D039', 70572300039, 21, NULL, NULL),
(1000000167, 'CHINTHAKUNTA HARINI', 'D040', 70572300040, 21, NULL, NULL),
(1000000168, 'T. RISHIKESH', 'D041', 70572300041, 21, NULL, NULL),
(1000000169, 'V SRESHTA REDDY', 'D042', 70572300042, 21, NULL, NULL),
(1000000170, 'P. TARUN KUMAR', 'D043', 70572300043, 21, NULL, NULL),
(1000000171, 'JACOB ALEX', 'D053', 70572300053, 21, NULL, NULL),
(1000000172, 'SV SATHWIKA', 'D054', 70572300054, 21, NULL, NULL),
(1000000173, 'DIVYA NAGUBOYINA', 'D059', 70572300059, 21, NULL, NULL),
(1000000174, 'VEDANTH RAJ', 'D063', 70572300063, 21, NULL, NULL),
(1000000175, 'KOTTAKAPU JANSHI', 'D064', 70572300064, 21, NULL, NULL),
(1000000176, 'C ANIL KUMAR', 'D065', 70572300065, 21, NULL, NULL),
(1000000177, 'MEEDINTI SUNNY VIRAJ', 'D069', 70572300069, 21, NULL, NULL),
(1000000178, 'KOLLURI PRAVALIKA', 'D070', 70572300070, 21, NULL, NULL),
(1000000179, 'ASHI SHARMA', 'L085', 70572400085, 21, NULL, NULL),
(1000000180, 'SHASHANK GOUD P', 'L050', 70572200048, 21, NULL, NULL),
(1000000181, 'Priyanshi Panja', 'A008', 70572400021, 22, 8, NULL),
(1000000182, 'Middle Indu', 'B001', 70572400126, 22, 8, NULL),
(1000000183, 'Guggilam Yuthika', 'B002', 70572400127, 22, 8, NULL),
(1000000184, 'K Hansika', 'B003', 70572400128, 22, 8, NULL),
(1000000185, 'Bangaru Sai Prakash Sagar', 'B004', 70572400129, 22, 8, NULL),
(1000000186, 'Mohammed Omer Farooq', 'B005', 70572400130, 22, 8, NULL),
(1000000187, 'Dasarpalli Srinidhi', 'B007', 70572400132, 22, 8, NULL),
(1000000188, 'Kanduri Mounesh', 'B009', 70572400134, 22, 8, NULL),
(1000000189, 'Charlakola Sai Kumar', 'B010', 70572400135, 22, 8, NULL),
(1000000190, 'Jaami Haider', 'B012', 70572400137, 22, 8, NULL),
(1000000191, 'Mahith Patel', 'B014', 70572400139, 22, 8, NULL),
(1000000192, 'Bannur Prajwala Reddy', 'B022', 70572400147, 22, 8, NULL),
(1000000193, 'Aarush Chaudhary', 'B023', 70572400148, 22, 8, NULL),
(1000000194, 'Sanjana Salunke', 'B024', 70572400150, 22, 8, NULL),
(1000000195, 'Paridhi Talreja', 'B025', 70572400152, 22, 8, NULL),
(1000000196, 'Krishna Patil', 'B026', 70572400153, 22, 8, NULL),
(1000000197, 'Anmol Jana', 'B027', 70572400154, 22, 8, NULL),
(1000000198, 'Hasya Surabhi', 'B028', 70572400155, 22, 8, NULL),
(1000000199, 'Anu Prithi Sambi', 'B029', 70572400156, 22, 8, NULL),
(1000000200, 'Ishanth Bhyramoni', 'B033', 70572400161, 22, 8, NULL),
(1000000201, 'Sambu Mahitha', 'B034', 70572400162, 22, 8, NULL),
(1000000202, 'Basuthkar Koushik', 'B035', 70572400163, 22, 8, NULL),
(1000000203, 'Kevindeep Singh Pannu', 'B038', 70572400166, 22, 8, NULL),
(1000000204, 'Vaishnavi Beiju', 'B041', 70572400175, 22, 8, NULL),
(1000000205, 'Syed Noheluddin Zaid', 'B042', 70572400176, 22, 8, NULL),
(1000000206, 'L Mamatha', 'B043', 70572400177, 22, 8, NULL),
(1000000207, 'Jampula Mayank', 'B045', 70572400179, 22, 8, NULL),
(1000000208, 'Gouni Abhinav', 'B047', 70572400181, 22, 8, NULL),
(1000000209, 'D. Ganesh Yadav', 'B048', 70572400182, 22, 8, NULL),
(1000000210, 'Chirumani Sai Kiran Reddy', 'B049', 70572400183, 22, 8, NULL),
(1000000211, 'Ananya Kolluru', 'B050', 70572400184, 22, 8, NULL),
(1000000212, 'Kommula Akshitha Sagar', 'B051', 70572400185, 22, 8, NULL),
(1000000213, 'Abhishek Sunil Mohale', 'B053', 70572400187, 22, 8, NULL),
(1000000214, 'Anaaya Akhlaque', 'B055', 70572400189, 22, 8, NULL),
(1000000215, 'Narahari Swathi Bhavani', 'B056', 70572400190, 22, 8, NULL),
(1000000216, 'K Hanvitha Sree', 'B058', 70572400203, 22, 8, NULL),
(1000000217, 'ARMAN KHAN', 'A001', 70572500001, 23, 9, NULL),
(1000000218, 'SPURTHIKA K REDDY', 'A054', 70572500054, 23, 9, NULL),
(1000000219, 'CHIRAG SEERVI', 'A055', 70572500055, 23, 9, NULL),
(1000000220, 'NEELI SAI NEHA', 'A056', 70572500056, 23, 9, NULL),
(1000000221, 'SUHANI SRIVASTAVA', 'A057', 70572500057, 23, 9, NULL),
(1000000222, 'ADIRA AKHIRANANDI', 'A058', 70572500058, 23, 9, NULL),
(1000000223, 'RISHIT SRIVASTAVA', 'A059', 70572500059, 23, 9, NULL),
(1000000224, 'ANSHUMAN SINGH', 'A121', 70572500121, 23, 9, NULL),
(1000000225, 'SHASHWAT KALA', 'A132', 70572500132, 23, 9, NULL),
(1000000226, 'ANUMEET PRAKASH', 'A133', 70572500133, 23, 9, NULL),
(1000000227, 'ATMANAND KARIBASWESHWAR PATIL', 'A134', 70572500134, 23, 9, NULL),
(1000000228, 'SOHAM PAWAR', 'A136', 70572500136, 23, 9, NULL),
(1000000229, 'AADI SRIVASTAVA', 'A137', 70572500137, 23, 9, NULL),
(1000000230, 'ARYAN SINGH', 'A138', 70572500138, 23, 9, NULL),
(1000000231, 'ADITYA GUPTA', 'A139', 70572500139, 23, 9, NULL),
(1000000232, 'AYUSH KUMAR DUBEY', 'A140', 70572500140, 23, 9, NULL),
(1000000233, 'PRATIK DEVENDRA JAGTAP', 'A141', 70572500141, 23, 9, NULL),
(1000000234, 'SYED MOHAMMAD TANZEEL JAMALI', 'A142', 70572500142, 23, 9, NULL),
(1000000235, 'RISHIKA DHAKATE', 'A143', 70572500143, 23, 9, NULL),
(1000000236, 'ANUBRAT DAS', 'A144', 70572500144, 23, 9, NULL),
(1000000237, 'OBULA NISHITHA REDDY', 'A145', 70572500145, 23, 9, NULL),
(1000000238, 'SHALINI RUPESH SINGARE', 'A146', 70572500146, 23, 9, NULL),
(1000000239, 'UJJWAL SINGH', 'A147', 70572500147, 23, 9, NULL),
(1000000240, 'PINNAMARAJU KAUSHIK VARMA', 'A148', 70572500148, 23, 9, NULL),
(1000000241, 'VRADDHI SABNANI', 'A160', 70572500160, 23, 9, NULL),
(1000000242, 'SRI SATHWIK G', 'A161', 70572500161, 23, 9, NULL),
(1000000243, 'GONELA VIGNESH', 'A163', 70572500163, 23, 9, NULL),
(1000000244, 'SRESHTA BAYANNA', 'A164', 70572500164, 23, 9, NULL),
(1000000245, 'ARYAN KUMAR', 'A166', 70572500166, 23, 9, NULL),
(1000000246, 'AYUSHMAN SINGH', 'A167', 70572500167, 23, 9, NULL),
(1000000247, 'VANS SEEKHA OJHA', 'A168', 70572500168, 23, 9, NULL),
(1000000248, 'APPANA SYAMALA', 'A169', 70572500169, 23, 9, NULL),
(1000000249, 'SRI CHARITHA SEVAKULA', 'A174', 70572500174, 23, 9, NULL),
(1000000250, 'SATVIKA GUNDA', 'A175', 70572500175, 23, 9, NULL),
(1000000251, 'MD YASEEN', 'A176', 70572500176, 23, 9, NULL),
(1000000252, 'VARUN BADAM', 'A177', 70572500177, 23, 9, NULL),
(1000000253, 'SUNKARI NIKHIL', 'A178', 70572500178, 23, 9, NULL),
(1000000254, 'SRI ABHIRAM MUMMINA', 'A179', 70572500179, 23, 9, NULL),
(1000000255, 'G RISHI RAJ', 'A180', 70572500180, 23, 9, NULL),
(1000000256, 'KARNE KOTTA MOUNIKA', 'A181', 70572500181, 23, 9, NULL),
(1000000257, 'RAJ KUMAR PERUMALLA', 'A182', 70572500182, 23, 9, NULL),
(1000000258, 'ANANTHA PRANATHI', 'A183', 70572500183, 23, 9, NULL),
(1000000259, 'NITHIN CHANDRA PEDDI', 'A184', 70572500184, 23, 9, NULL),
(1000000260, 'ASRITHA SAMA', 'A186', 70572500186, 23, 9, NULL),
(1000000261, 'MOUNIKA G', 'A187', 70572500187, 23, 9, NULL),
(1000000262, 'PRAMODA CHOPP', 'A188', 70572500188, 23, 9, NULL),
(1000000263, 'SHASHI VARDHAN', 'A189', 70572500189, 23, 9, NULL),
(1000000264, 'MOHAMMED AYAN', 'A190', 70572500190, 23, 9, NULL),
(1000000265, 'SHERI RASAGNA REDDY', 'A192', 70572500192, 23, 9, NULL),
(1000000266, 'CHETHAN REDDY VENDRA CHANDRA', 'A193', 70572500193, 23, 9, NULL),
(1000000267, 'SAI ADITYA PEDDINTY', 'A194', 70572500194, 23, 9, NULL),
(1000000268, 'BHAVYA SHARMA', 'A195', 70572500195, 23, 9, NULL),
(1000000269, 'RIDDHIMA GARG', 'A196', 70572500196, 23, 9, NULL),
(1000000270, 'ANJU JASLIN', 'A197', 70572500197, 23, 9, NULL),
(1000000271, 'TEJESH MANDUVADA', 'A198', 70572500198, 23, 9, NULL),
(1000000272, 'DODLA NAGARANI', 'A199', 70572500199, 23, 9, NULL),
(1000000273, 'ARNAV SINGH', 'A200', 70572500200, 23, 9, NULL),
(1000000274, 'PRITISH KUMAR PUSTI', 'A201', 70572500201, 23, 9, NULL),
(1000000275, 'VAIDEHI KHUSHAL PADOLE', 'A202', 70572500202, 23, 9, NULL),
(1000000276, 'ADVAITH CHENNAMGARI', 'A203', 70572500203, 23, 9, NULL),
(1000000277, 'THANVI DESHMUKH', 'A205', 70572500205, 23, 9, NULL),
(1000000278, 'Samya Nasir', 'B206', 70572500206, 23, 10, NULL),
(1000000279, 'Saarthak Divyansh', 'B207', 70572500207, 23, 10, NULL),
(1000000280, 'Hanudeep Reddy', 'B216', 70572500216, 23, 10, NULL),
(1000000281, 'Gantenapalli Karthik Suhaas', 'B217', 70572500217, 23, 10, NULL),
(1000000282, 'Aditya Raj', 'B218', 70572500218, 23, 10, NULL),
(1000000283, 'Sama Surya Teja', 'B219', 70572500219, 23, 10, NULL),
(1000000284, 'G Srilekha', 'B220', 70572500220, 23, 10, NULL),
(1000000285, 'Aroob Ramsha', 'B221', 70572500221, 23, 10, NULL),
(1000000286, 'Ganthala Panchakshar', 'B222', 70572500222, 23, 10, NULL),
(1000000287, 'Parth Kachhara', 'B224', 70572500224, 23, 10, NULL),
(1000000288, 'Aarav Shah', 'B225', 70572500225, 23, 10, NULL),
(1000000289, 'Harsh Parekh', 'B240', 70572500240, 23, 10, NULL),
(1000000290, 'Yogita Manik Chandra', 'B244', 70572500244, 23, 10, NULL),
(1000000291, 'Syed Daniyal', 'B248', 70572500248, 23, 10, NULL),
(1000000292, 'M Shresta Reddy', 'B249', 70572500249, 23, 10, NULL),
(1000000293, 'Hima Varshith Reddy Ankur', 'B251', 70572500251, 23, 10, NULL),
(1000000294, 'Sri Satyam Kumar', 'B254', 70572500254, 23, 10, NULL),
(1000000295, 'Karthikeya Avasarala', 'B255', 70572500255, 23, 10, NULL),
(1000000296, 'S Srivatsava Raghavendra', 'B256', 70572500256, 23, 10, NULL),
(1000000297, 'Pabbathi Vaishnavi', 'B258', 70572500258, 23, 10, NULL),
(1000000298, 'Kesi Reddy Siri Chandana', 'B259', 70572500259, 23, 10, NULL),
(1000000299, 'Divyanshi Vidhani', 'B261', 70572500261, 23, 10, NULL),
(1000000300, 'Palakonda Moksha', 'B263', 70572500263, 23, 10, NULL),
(1000000301, 'Ampolu Utsav Baradwaj', 'B265', 70572500265, 23, 10, NULL),
(1000000302, 'Allam Deekshitha', 'B266', 70572500266, 23, 10, NULL),
(1000000303, 'A Saiteja Goud', 'B268', 70572500268, 23, 10, NULL),
(1000000304, 'Manduvada Vignesh', 'B269', 70572500269, 23, 10, NULL),
(1000000305, 'Rishitha Reddy Raikanti', 'B270', 70572500270, 23, 10, NULL),
(1000000306, 'Agganooru Hyndavi', 'B271', 70572500271, 23, 10, NULL),
(1000000307, 'Vivek Kumar', 'B272', 70572500272, 23, 10, NULL),
(1000000308, 'Pranshu Sharma', 'B282', 70572500282, 23, 10, NULL),
(1000000309, 'Ved Shrivastava', 'B295', 70572500295, 23, 10, NULL),
(1000000310, 'Drishti Shankar', 'B301', 70572500301, 23, 10, NULL),
(1000000311, 'Manya Bhupesh Singh', 'B324', 70572500324, 23, 10, NULL),
(1000000312, 'Aditya Sinha', 'B325', 70572500325, 23, 10, NULL),
(1000000313, 'Darsh Jinna', 'B326', 70572500326, 23, 10, NULL),
(1000000314, 'Rounak Raj', 'B328', 70572500328, 23, 10, NULL),
(1000000315, 'Mohammed Areeb Ali Shivji', 'B329', 70572500329, 23, 10, NULL),
(1000000316, 'Tanmay Anand', 'B330', 70572500330, 23, 10, NULL),
(1000000317, 'Parwez Musharraf', 'B332', 70572500332, 23, 10, NULL),
(1000000318, 'Ayushman Datta', 'B333', 70572500333, 23, 10, NULL),
(1000000319, 'Rajveer Chowdhury', 'B334', 70572500334, 23, 10, NULL),
(1000000320, 'Tirth Sagwaria', 'B336', 70572500336, 23, 10, NULL),
(1000000321, 'Vishist Agrahari', 'B337', 70572500337, 23, 10, NULL),
(1000000322, 'Kousalya Narayana Gari', 'B338', 70572500338, 23, 10, NULL),
(1000000323, 'Anand Sharma', 'B339', 70572500339, 23, 10, NULL),
(1000000324, 'Punugu Harshith Reddy', 'B340', 70572500340, 23, 10, NULL),
(1000000325, 'Dachani Yashwanth Reddy', 'B342', 70572500342, 23, 10, NULL),
(1000000326, 'Karrevaru Gnaneshwar Goud', 'B343', 70572500343, 23, 10, NULL),
(1000000327, 'Nitin Kumar Gupta', 'B344', 70572500344, 23, 10, NULL),
(1000000328, 'Gangolla Srinidhi Reddy', 'B345', 70572500345, 23, 10, NULL),
(1000000329, 'Geetika Agrawal', 'B380', 70572500380, 23, 10, NULL),
(1000000330, 'V. Sanjay Sai', 'B390', 70572500390, 23, 10, NULL),
(1000000331, 'LAUKIK DHURI', 'A027', 70572400027, 23, 10, NULL),
(1000000332, 'Abhishek Rajput', 'B016', 70572400141, 23, 10, NULL),
(1000000333, 'Krysha Mehta', 'A048', 705724000000, 23, 10, NULL),
(1000000334, 'Sreekar', 'B011', 70572400136, 23, 10, NULL),
(1000000335, 'Dilip', 'A040', 70572400107, 23, 10, NULL),
(1000000336, 'harsith', 'B019', 70572400144, 23, 10, NULL),
(1000000337, 'Indukuri Sashank Varma', 'C126', 70022500126, 24, 11, NULL),
(1000000338, 'Pranjal Om Prakash Pathak', 'C1267', 70022500267, 24, 11, NULL),
(1000000339, 'Mahammed Almas Mandlik', 'C268', 70022500268, 24, 11, NULL),
(1000000340, 'Quadri Md Suleman Ali', 'C269', 70022500269, 24, 11, NULL),
(1000000341, 'Sahiti Domudala', 'C271', 70022500271, 24, 11, NULL),
(1000000342, 'Sukanya Mulik', 'C283', 70022500283, 24, 11, NULL),
(1000000343, 'Ekansh bansal', 'C311', 70022500311, 24, 11, NULL),
(1000000344, 'Alugubelly Sahasra', 'C314', 70022500314, 24, 11, NULL),
(1000000345, 'Govinda Karthikeya Neelam', 'C321', 70022500321, 24, 11, NULL),
(1000000346, 'Arnav Kalra', 'C385', 70022500385, 24, 11, NULL),
(1000000347, 'Mohammed Ismail Sohaib', 'C386', 70022500386, 24, 11, NULL),
(1000000348, 'Beere Dheeraj', 'C388', 70022500388, 24, 11, NULL),
(1000000349, 'Rishendra Vignesh Goud', 'C390', 70022500390, 24, 11, NULL),
(1000000350, 'Shruti Priya', 'C395', 70022500395, 24, 11, NULL),
(1000000351, 'Samridh Shrivastava', 'C397', 70022500397, 24, 11, NULL),
(1000000352, 'Eklavya Mankar', 'C399', 70022500399, 24, 11, NULL),
(1000000353, 'Boyini Siddhartha Hanuma', 'C400', 70022500400, 24, 11, NULL),
(1000000354, 'Rishav Kumar', 'C419', 70022500419, 24, 11, NULL),
(1000000355, 'Nishad Utpal Dave', 'C477', 70022500477, 24, 11, NULL),
(1000000356, 'Sanidhya Pandey', 'C565', 70022500565, 24, 11, NULL),
(1000000357, 'Marri Asrith Reddy', 'C566', 70022500566, 24, 11, NULL),
(1000000358, 'Deepika Pasula', 'C568', 70022500568, 24, 11, NULL),
(1000000359, 'Aelleni Shivanjali', 'C569', 70022500569, 24, 11, NULL),
(1000000360, 'Kavya Gunrule', 'C570', 70022500570, 24, 11, NULL),
(1000000361, 'Purushottam Jha', 'C584', 70022500584, 24, 11, NULL),
(1000000362, 'Ayush Mishra', 'C593', 70022500593, 24, 11, NULL),
(1000000363, 'Ayush Yadav', 'C597', 70022500597, 24, 11, NULL),
(1000000364, 'Tanish Tiwari', 'C608', 70022500608, 24, 11, NULL),
(1000000365, 'Mahima Choudhari', 'C623', 70022500623, 24, 11, NULL),
(1000000366, 'Ashwin Shukla', 'C638', 70022500638, 24, 11, NULL),
(1000000367, 'Shivam Gupta', 'C644', 70022500644, 24, 11, NULL),
(1000000368, 'Shanmukhi Balakuntla', 'C645', 70022500645, 24, 11, NULL),
(1000000369, 'Krishna Deshmukh', 'C652', 70022500652, 24, 11, NULL),
(1000000370, 'Tejas Srivastava', 'C656', 70022500656, 24, 11, NULL),
(1000000371, 'Sonakshi Salvi', 'C665', 70022500665, 24, 11, NULL),
(1000000372, 'Rohan Sreejith', 'C668', 70022500668, 24, 11, NULL),
(1000000373, 'Keerthana Cheenavaram', 'D704', 70022500704, 24, 12, NULL),
(1000000374, 'Mohammad Sufiyan', 'D705', 70022500725, 24, 12, NULL),
(1000000375, 'Kaathe Jeevan Kumar', 'D706', 70022500728, 24, 12, NULL),
(1000000376, 'Bala Rajesh Kumar', 'D707', 70022500730, 24, 12, NULL),
(1000000377, 'Kondoju Sainithi', 'D708', 70022500748, 24, 12, NULL),
(1000000378, 'Akshya Priya J', 'D709', 70022500749, 24, 12, NULL),
(1000000379, 'Bejju Revanth', 'D710', 70022500750, 24, 12, NULL),
(1000000380, 'Bejju Sri Ashwitha', 'D711', 70022500751, 24, 12, NULL),
(1000000381, 'Swapnil Patil', 'D712', 70022500752, 24, 12, NULL),
(1000000382, 'Vishwajeet Bedse', 'D713', 70022500753, 24, 12, NULL),
(1000000383, 'Kriday Mishra', 'D714', 70022500754, 24, 12, NULL),
(1000000384, 'Raj Taparia', 'D715', 70022500756, 24, 12, NULL),
(1000000385, 'G Srinithya Reddy', 'D716', 70022500781, 24, 12, NULL),
(1000000386, 'Narayandas Vishwagnya', 'D717', 70022500782, 24, 12, NULL),
(1000000387, 'Kamble Akshata Mahendra', 'D718', 70022500787, 24, 12, NULL),
(1000000388, 'Ayaan Patel', 'D719', 70022500789, 24, 12, NULL),
(1000000389, 'V Kranthi Sri', 'D720', 70022500790, 24, 12, NULL),
(1000000390, 'Noule Harsha Sai', 'D721', 70022500791, 24, 12, NULL),
(1000000391, 'B Hansika Bujagouni Hansika', 'D722', 70022500792, 24, 12, NULL),
(1000000392, 'Golla Harsha Vardhan', 'D723', 70022500793, 24, 12, NULL),
(1000000393, 'Chaitnya Josyula', 'D724', 70022500794, 24, 12, NULL),
(1000000394, 'Sri Maha Lakshmi Thulasi', 'D725', 70022500795, 24, 12, NULL),
(1000000395, 'Manya Puranik', 'D726', 70022500796, 24, 12, NULL),
(1000000396, 'Harpreet Kaur Malhotra', 'D727', 70022500797, 24, 12, NULL),
(1000000397, 'Akkala Harshitha', 'D728', 70022500799, 24, 12, NULL),
(1000000398, 'Huriya Maheen Khan', 'D729', 70022500802, 24, 12, NULL),
(1000000399, 'Boya Vinutna', 'D730', 70022500803, 24, 12, NULL),
(1000000400, 'Sri Mani Rao', 'D731', 70022500804, 24, 12, NULL),
(1000000401, 'Hema Teja T', 'D732', 70022500805, 24, 12, NULL),
(1000000402, 'Bayani Shivani', 'D733', 70022500806, 24, 12, NULL),
(1000000403, 'T Manideep Goud', 'D734', 70022500808, 24, 12, NULL),
(1000000404, 'Sreeharsha Sreeram Shetty', 'D735', 70022500809, 24, 12, NULL),
(1000000405, 'M Arjun', 'D736', 70022500823, 24, 12, NULL),
(1000000406, 'Hritik Reddy', 'D737', 70022500824, 24, 12, NULL),
(1000000407, 'A Somya Sree', 'D738', 70022500825, 24, 12, NULL),
(1000000408, 'Tanisha Vijay Dembla', 'D739', 70022500826, 24, 12, NULL),
(1000000409, 'P Gurudatta', 'D740', 70022500927, 24, 12, NULL),
(1000000410, 'Chinmayee Bakshi', 'D741', 70022500828, 24, 12, NULL),
(1000000411, 'G Saicharangoud', 'D742', 70022500829, 24, 12, NULL),
(1000000412, 'Sreeja K', 'D743', 70022500840, 24, 12, NULL),
(1000000413, 'Yogith Reddy', 'D744', 70022500841, 24, 12, NULL),
(1000000414, 'Sachin Kumar', 'D745', 70022500842, 24, 12, NULL),
(1000000415, 'Ravi Teja', 'D746', 70022500843, 24, 12, NULL),
(1000000416, 'R.Ashritha', 'D747', 70022500844, 24, 12, NULL),
(1000000417, 'Shreyas Modgil', 'D748', 70022500845, 24, 12, NULL),
(1000000418, 'Kshitij yadav', 'D749', 70022500847, 24, 12, NULL),
(1000000419, 'Manda Amruta', 'D750', 70022500849, 24, 12, NULL),
(1000000420, 'Meet Jain', 'D751', 70022500696, 24, 12, NULL),
(1000000421, 'Azfar Sidduqui', 'D752', 70022400640, 24, 12, NULL),
(1000000422, 'Abhi Gupta', 'D753', 70022400703, 24, 12, NULL),
(1000000423, 'Ksai Teja Reddy', 'D754', 70022400698, 24, 12, NULL),
(1000000424, 'ANSHUMAN CHAKRAVORTY', 'C001', 70022300271, 26, NULL, NULL),
(1000000425, 'ZEESHAN ALI', 'C007', 70022300461, 26, NULL, 'zeeshan@nmims.in'),
(1000000426, 'BOYINI DHANUSH', 'C010', 70022300509, 26, NULL, NULL),
(1000000427, 'MOHD OMER FARAZ', 'C011', 70022300511, 26, NULL, NULL),
(1000000428, 'K SAI CHARAN', 'C012', 70022300533, 26, NULL, NULL),
(1000000429, 'MEHUL NARAYANOLLA', 'C014', 70022300535, 26, NULL, NULL),
(1000000430, 'M RAVI KUMAR', 'C016', 70022300556, 26, NULL, NULL),
(1000000431, 'Kapperi Divya Sri', 'L021', 70572200010, 25, NULL, NULL),
(1000000432, 'Pavithra Sevakula', 'L022', 70572200011, 25, NULL, NULL),
(1000000433, 'M.Vaishnavi', 'L023', 70572200012, 25, NULL, NULL),
(1000000434, 'Akula Srinithya', 'L024', 70572200013, 25, NULL, NULL),
(1000000435, 'K.Manishankar Goud', 'L025', 70572200014, 25, NULL, NULL),
(1000000436, 'Jahnavi Maddi', 'L026', 70572200015, 25, NULL, NULL),
(1000000437, 'M Bharghav Kumar', 'L027', 70572200016, 25, NULL, NULL),
(1000000438, 'Charala Pujitha', 'L028', 70572200017, 25, NULL, NULL),
(1000000439, 'J. Thrisha Reddy', 'L030', 70572200026, 25, NULL, NULL),
(1000000440, 'Harsh Bang', 'L031', 70572200027, 25, NULL, NULL),
(1000000441, 'Ruthvik Akula', 'L032', 70572200028, 25, NULL, NULL),
(1000000442, 'D.Lokeshwar Goud', 'L033', 70572200029, 25, NULL, NULL),
(1000000443, 'Anmagandla Snehil', 'L034', 70572200030, 25, NULL, NULL),
(1000000444, 'Narahari Abhinav', 'L035', 70572200031, 25, NULL, NULL),
(1000000445, 'Md Sohail', 'L036', 70572200032, 25, NULL, NULL),
(1000000446, 'Malde Saicharan', 'L037', 70572200033, 25, NULL, NULL),
(1000000447, 'Prasad Sham Kannawar', 'L038', 70572200034, 25, NULL, NULL),
(1000000448, 'Venkatesh M', 'L039', 70572200035, 25, NULL, NULL),
(1000000449, 'Rachit Jain', 'L040', 70572200036, 25, NULL, NULL),
(1000000450, 'Khushal Baldava', 'L042', 70572200038, 25, NULL, NULL),
(1000000451, 'Sidra Fatima', 'L044', 70572200042, 25, NULL, NULL),
(1000000452, 'Sai Vijaya Laxmi', 'L046', 70572200044, 25, NULL, NULL),
(1000000453, 'Vadla Vaishnavi', 'L047', 70572200045, 25, NULL, NULL),
(1000000454, 'B Vaishnavi', 'L048', 70572200046, 25, NULL, NULL),
(1000000455, 'G. Sainath Goud', 'L049', 70572200047, 25, NULL, NULL),
(1000000456, 'Kurumidde John Austin', 'L051', 70572200049, 25, NULL, NULL),
(1000000457, 'Chetan H', 'L052', 70572200050, 25, NULL, NULL),
(1000000458, 'Ananya P', 'L053', 70572200052, 25, NULL, NULL),
(1000000459, 'M Sowmya', 'L054', 70572200053, 25, NULL, NULL),
(1000000460, 'G Pragnya Reddy', 'L055', 70572200054, 25, NULL, NULL),
(1000000461, 'V Abhiram Reddy', 'L056', 70572200055, 25, NULL, NULL),
(1000000462, 'R Ananth Yadav', 'L057', 70572200056, 25, NULL, NULL),
(1000000463, 'RITAM MAHAKUR', 'C001', 70022400269, 29, NULL, NULL),
(1000000464, 'TANISHI ANIL SHUKLA', 'C002', 70022400270, 29, NULL, NULL),
(1000000465, 'SULAKSHANA SONAVANE', 'C004', 70022400299, 29, NULL, NULL),
(1000000466, 'NANDINI DEVNANI', 'C005', 70022400304, 29, NULL, NULL),
(1000000467, 'ANJALI KHANDPAL', 'C006', 70022400307, 29, NULL, NULL),
(1000000468, 'AKANKSHA PATIL', 'C007', 70022400342, 29, NULL, NULL),
(1000000469, 'ASHWINI RAMAN PANDEY.', 'C008', 70022400480, 29, NULL, NULL),
(1000000470, 'SWEETY JAIN', 'C009', 70022400612, 29, NULL, NULL),
(1000000471, 'HANSINI VADDEMPUDI', 'C010', 70022400613, 29, NULL, NULL),
(1000000472, 'N.SANTHOSH NAGELI', 'C014', 70022400618, 29, NULL, NULL),
(1000000473, 'SAINATH GOUD', 'C018', 70022300564, 29, NULL, NULL),
(1000000474, 'NUNNA RISHIT', 'C018', 70022400624, 29, NULL, NULL),
(1000000475, 'SAINATH REDDY', 'C019', 70022300565, 29, NULL, NULL),
(1000000476, 'PENDELA SRI DAKSHAYANI', 'C019', 70022400625, 29, NULL, NULL),
(1000000477, 'CHITTINENI YOSHITHA SREE', 'C020', 70022400628, 29, NULL, NULL),
(1000000478, 'KARNUKA GUPTA', 'C021', 70022400631, 29, NULL, NULL),
(1000000479, 'BANURI SAIRAM', 'C023', 70022400681, 29, NULL, NULL),
(1000000480, 'MOHAMMED ZOHAIBUDDIN.', 'C024', 70022400682, 29, NULL, NULL),
(1000000481, 'PASULA DEEPTHI REDDY', 'C027', 70022400685, 29, NULL, NULL),
(1000000482, 'CIDDOTAM TARUN', 'C028', 70022400686, 29, NULL, NULL),
(1000000483, 'JALAJAM SAI NEHA', 'C029', 70022400687, 29, NULL, NULL),
(1000000484, 'ABHIRAM GOUD', 'C030', 70022400688, 29, NULL, NULL),
(1000000485, 'S.RAVI SAGAR', 'C031', 70022400689, 29, NULL, NULL),
(1000000486, 'MALIPEDDI HARSHITHA', 'C033', 70022400691, 29, NULL, NULL),
(1000000487, 'G.CHANDRIKA', 'C034', 70022400692, 29, NULL, NULL),
(1000000488, 'RAMESH REDDY', 'C035', 70022400693, 29, NULL, NULL),
(1000000489, 'SREEJA REDDY', 'C036', 70022400694, 29, NULL, NULL),
(1000000490, 'HARSHITHA HASINI THATIKONDA', 'C037', 70022400695, 29, NULL, NULL),
(1000000491, 'K.KAVYA', 'C038', 70022400696, 29, NULL, NULL),
(1000000492, 'SAHASRA KALUVA', 'C046', 70022400705, 29, NULL, NULL),
(1000000493, 'SIDDHARTH REDDY SIDA REDDY', 'C048', 70022400707, 29, NULL, NULL),
(1000000494, 'TUSHAR MAWALE', 'C053', 70022400713, 29, NULL, NULL),
(1000000495, 'KURUVA AKHILA', 'C055', 70022400715, 29, NULL, NULL),
(1000000496, 'OJAS BHAYAL', 'C057', 70022400726, 29, NULL, NULL),
(1000000497, 'SARTHAK LANDE', 'C059', 70022400728, 29, NULL, NULL),
(1000000498, 'EPURI AKSHAYA', 'C060', 70022400730, 29, NULL, NULL),
(1000000499, 'AMAN SINHA', 'C063', 70022400793, 29, NULL, NULL),
(1000000500, 'YASH KAVAR', 'C066', 70022400796, 29, NULL, NULL),
(1000000501, 'B SAI SRUTHI PATRO', 'C067', 70022400800, 29, NULL, NULL),
(1000000502, 'KONDUR UJWALA', 'C068', 70022400801, 29, NULL, NULL),
(1000000503, 'MADAN GOUD', 'C070', 70022400803, 29, NULL, NULL),
(1000000504, 'HIMESH CHANDRA', 'C071', 70022400804, 29, NULL, NULL),
(1000000505, 'VIJAY SARATH REDDY', 'C072', 70022400805, 29, NULL, NULL),
(1000000506, 'NEERAJ KUMAR', 'C073', 70022400806, 29, NULL, NULL),
(1000000507, 'DATTA DASU MANOJ', 'D019', 70572300019, 22, 13, NULL),
(1000000508, 'RACHURI SAHASRA', 'A002', 70572400008, 22, 13, NULL),
(1000000509, 'DHRUV SONAR', 'A003', 70572400009, 22, 13, NULL),
(1000000510, 'AYUSHMAN PADHY', 'A005', 70572400015, 22, 13, NULL),
(1000000511, 'RISHAB SARDA', 'A006', 70572400018, 22, 13, NULL),
(1000000512, 'GEEREDDY SRICHARAN REDDY', 'A007', 70572400020, 22, 13, NULL),
(1000000513, 'ZAID AHMAD', 'A058', 70572400022, 22, 13, NULL),
(1000000514, 'RYAN MANVAR', 'A010', 70572400023, 22, 13, NULL),
(1000000515, 'HEMANG MENARIA', 'A011', 70572400026, 22, 13, NULL),
(1000000516, 'ANIRUDH POODHATTHU', 'A013', 70572400054, 22, 13, NULL),
(1000000517, 'VATTIPALLY BHANUPRAVEEN REDDY', 'A014', 70572400057, 22, 13, NULL),
(1000000518, 'DACHANI JASHWANTH VIKRAM', 'A016', 70572400059, 22, 13, NULL),
(1000000519, 'ZAMEER KHAN', 'A017', 70572400060, 22, 13, NULL),
(1000000520, 'VIVEK CHAITANYA SAMBU', 'A019', 70572400063, 22, 13, NULL),
(1000000521, 'BANDARU SAKETH ABHINANDAN', 'A020', 70572400064, 22, 13, NULL),
(1000000522, 'CHEDAM HARIN', 'A021', 70572400065, 22, 13, NULL),
(1000000523, 'AVUTI SUPREETHI', 'A022', 70572400066, 22, 13, NULL),
(1000000524, 'AKSHAYA GOVINDU', 'A023', 70572400068, 22, 13, NULL),
(1000000525, 'SPANDANA KARNEKOTA', 'A024', 70572400069, 22, 13, NULL),
(1000000526, 'OMKAR KHANDEPARKAR', 'A025', 70572400070, 22, 13, NULL),
(1000000527, 'MAREPALLY VARSHINI CHAVANA', 'A026', 70572400071, 22, 13, NULL),
(1000000528, 'G. BHAVYA SREE', 'A027', 70572400072, 22, 13, NULL),
(1000000529, 'TUMUKUNTA SRIVALLI', 'A028', 70572400073, 22, 13, NULL),
(1000000530, 'TANISHQ KUMAR PRAJAPATI', 'A029', 70572400074, 22, 13, NULL),
(1000000531, 'G.AKHIL GOUD', 'A030', 70572400077, 22, 13, NULL),
(1000000532, 'YASH SOMWANSHI', 'A032', 70572400080, 22, 13, NULL),
(1000000533, 'KUCHUR RISHIKANTHREDDY', 'A033', 70572400099, 22, 13, NULL),
(1000000534, 'GIRAMONI NIHARIKA', 'A034', 70572400101, 22, 13, NULL),
(1000000535, 'MANIKONDA VINUTHNA', 'A036', 70572400103, 22, 13, NULL),
(1000000536, 'S SRITHAN GOUD', 'A037', 70572400104, 22, 13, NULL),
(1000000537, 'P BHAVANI NAGA', 'A038', 70572400105, 22, 13, NULL),
(1000000538, 'K.VAMSHI KRISHNA.', 'A039', 70572400106, 22, 13, NULL),
(1000000539, 'DEV CHALANA', 'A041', 70572400109, 22, 13, NULL),
(1000000540, 'GOTUR VARSHITH', 'A044', 70572400112, 22, 13, NULL),
(1000000541, 'MANKALA SUDHEEKSHA', 'A045', 70572400113, 22, 13, NULL),
(1000000542, 'SAHITHI ALAMPALLY', 'A046', 70572400114, 22, 13, NULL),
(1000000543, 'AKSHAYA REDDY VUNDHYALA', 'A047', 70572400115, 22, 13, NULL),
(1000000544, 'AVINASH UBA', 'A049', 70572400117, 22, 13, NULL),
(1000000545, 'SHAIK MOHD FAIZ SAYEED', 'A050', 70572400118, 22, 13, NULL),
(1000000546, 'GUDIPALLY MADHUMITHA', 'A051', 70572400119, 22, 13, NULL),
(1000000547, 'RAMIREDDY BHANU TEJA REDDY', 'A052', 70572400120, 22, 13, NULL),
(1000000548, 'BODA SRIVANI', 'A053', 70572400121, 22, 13, NULL),
(1000000549, 'SATHVIKA VADUGULA', 'A054', 70572400122, 22, 13, NULL),
(1000000550, 'SANDADI RAHUL REDDY', 'A055', 70572400123, 22, 13, NULL),
(1000000551, 'P. CHARAN', 'A056', 70572400124, 22, 13, NULL),
(1000000552, 'MUSANI VIVEKANANDA', 'A057', 70572400125, 22, 13, NULL),
(1000000553, 'ARMAN KHAN', 'A001', 70572500001, 28, 14, NULL),
(1000000554, 'SPURTHIKA K REDDY', 'A054', 70572500054, 28, 14, NULL),
(1000000555, 'CHIRAG SEERVI', 'A055', 70572500055, 28, 14, NULL),
(1000000556, 'NEELI SAI NEHA', 'A056', 70572500056, 28, 14, NULL),
(1000000557, 'SUHANI SRIVASTAVA', 'A057', 70572500057, 28, 14, NULL),
(1000000558, 'ADIRA AKHIRANANDI', 'A058', 70572500058, 28, 14, NULL),
(1000000559, 'RISHIT SRIVASTAVA', 'A059', 70572500059, 28, 14, NULL),
(1000000560, 'ANSHUMAN SINGH', 'A121', 70572500121, 28, 14, NULL),
(1000000561, 'SHASHWAT KALA', 'A132', 70572500132, 28, 14, NULL),
(1000000562, 'ANUMEET PRAKASH', 'A133', 70572500133, 28, 14, NULL),
(1000000563, 'ATMANAND KARIBASWESHWAR PATIL', 'A134', 70572500134, 28, 14, NULL),
(1000000564, 'SOHAM PAWAR', 'A136', 70572500136, 28, 14, NULL),
(1000000565, 'AADI SRIVASTAVA', 'A137', 70572500137, 28, 14, NULL),
(1000000566, 'ARYAN SINGH', 'A138', 70572500138, 28, 14, NULL),
(1000000567, 'ADITYA GUPTA', 'A139', 70572500139, 28, 14, NULL),
(1000000568, 'AYUSH KUMAR DUBEY', 'A140', 70572500140, 28, 14, NULL),
(1000000569, 'PRATIK DEVENDRA JAGTAP', 'A141', 70572500141, 28, 14, NULL),
(1000000570, 'SYED MOHAMMAD TANZEEL JAMALI', 'A142', 70572500142, 28, 14, NULL),
(1000000571, 'RISHIKA DHAKATE', 'A143', 70572500143, 28, 14, NULL),
(1000000572, 'ANUBRAT DAS', 'A144', 70572500144, 28, 14, NULL),
(1000000573, 'OBULA NISHITHA REDDY', 'A145', 70572500145, 28, 14, NULL),
(1000000574, 'SHALINI RUPESH SINGARE', 'A146', 70572500146, 28, 14, NULL),
(1000000575, 'UJJWAL SINGH', 'A147', 70572500147, 28, 14, NULL),
(1000000576, 'PINNAMARAJU KAUSHIK VARMA', 'A148', 70572500148, 28, 14, NULL),
(1000000577, 'VRADDHI SABNANI', 'A160', 70572500160, 28, 14, NULL),
(1000000578, 'SRI SATHWIK G', 'A161', 70572500161, 28, 14, NULL),
(1000000579, 'GONELA VIGNESH', 'A163', 70572500163, 28, 14, NULL),
(1000000580, 'SRESHTA BAYANNA', 'A164', 70572500164, 28, 14, NULL),
(1000000581, 'ARYAN KUMAR', 'A166', 70572500166, 28, 14, NULL),
(1000000582, 'AYUSHMAN SINGH', 'A167', 70572500167, 28, 14, NULL),
(1000000583, 'VANS SEEKHA OJHA', 'A168', 70572500168, 28, 14, NULL),
(1000000584, 'APPANA SYAMALA', 'A169', 70572500169, 28, 14, NULL),
(1000000585, 'SRI CHARITHA SEVAKULA', 'A174', 70572500174, 28, 14, NULL),
(1000000586, 'SATVIKA GUNDA', 'A175', 70572500175, 28, 14, NULL),
(1000000587, 'MD YASEEN', 'A176', 70572500176, 28, 14, NULL),
(1000000588, 'VARUN BADAM', 'A177', 70572500177, 28, 14, NULL),
(1000000589, 'SUNKARI NIKHIL', 'A178', 70572500178, 28, 14, NULL),
(1000000590, 'SRI ABHIRAM MUMMINA', 'A179', 70572500179, 28, 14, NULL),
(1000000591, 'G RISHI RAJ', 'A180', 70572500180, 28, 14, NULL),
(1000000592, 'KARNE KOTTA MOUNIKA', 'A181', 70572500181, 28, 14, NULL),
(1000000593, 'RAJ KUMAR PERUMALLA', 'A182', 70572500182, 28, 14, NULL),
(1000000594, 'ANANTHA PRANATHI', 'A183', 70572500183, 28, 14, NULL),
(1000000595, 'NITHIN CHANDRA PEDDI', 'A184', 70572500184, 28, 14, NULL),
(1000000596, 'ASRITHA SAMA', 'A186', 70572500186, 28, 14, NULL),
(1000000597, 'MOUNIKA G', 'A187', 70572500187, 28, 14, NULL),
(1000000598, 'PRAMODA CHOPP', 'A188', 70572500188, 28, 14, NULL),
(1000000599, 'SHASHI VARDHAN', 'A189', 70572500189, 28, 14, NULL),
(1000000600, 'MOHAMMED AYAN', 'A190', 70572500190, 28, 14, NULL),
(1000000601, 'SHERI RASAGNA REDDY', 'A192', 70572500192, 28, 14, NULL),
(1000000602, 'CHETHAN REDDY VENDRA CHANDRA', 'A193', 70572500193, 28, 14, NULL),
(1000000603, 'SAI ADITYA PEDDINTY', 'A194', 70572500194, 28, 14, NULL),
(1000000604, 'BHAVYA SHARMA', 'A195', 70572500195, 28, 14, NULL),
(1000000605, 'RIDDHIMA GARG', 'A196', 70572500196, 28, 14, NULL),
(1000000606, 'ANJU JASLIN', 'A197', 70572500197, 28, 14, NULL),
(1000000607, 'TEJESH MANDUVADA', 'A198', 70572500198, 28, 14, NULL),
(1000000608, 'DODLA NAGARANI', 'A199', 70572500199, 28, 14, NULL),
(1000000609, 'ARNAV SINGH', 'A200', 70572500200, 28, 14, NULL),
(1000000610, 'PRITISH KUMAR PUSTI', 'A201', 70572500201, 28, 14, NULL),
(1000000611, 'VAIDEHI KHUSHAL PADOLE', 'A202', 70572500202, 28, 14, NULL),
(1000000612, 'ADVAITH CHENNAMGARI', 'A203', 70572500203, 28, 14, NULL),
(1000000613, 'THANVI DESHMUKH', 'A205', 70572500205, 28, 14, NULL),
(1000000614, 'Samya Nasir', 'B206', 70572500206, 28, 15, NULL),
(1000000615, 'Saarthak Divyansh', 'B207', 70572500207, 28, 15, NULL),
(1000000616, 'Hanudeep Reddy', 'B216', 70572500216, 28, 15, NULL),
(1000000617, 'Gantenapalli Karthik Suhaas', 'B217', 70572500217, 28, 15, NULL),
(1000000618, 'Aditya Raj', 'B218', 70572500218, 28, 15, NULL),
(1000000619, 'Sama Surya Teja', 'B219', 70572500219, 28, 15, NULL),
(1000000620, 'G Srilekha', 'B220', 70572500220, 28, 15, NULL),
(1000000621, 'Aroob Ramsha', 'B221', 70572500221, 28, 15, NULL),
(1000000622, 'Ganthala Panchakshar', 'B222', 70572500222, 28, 15, NULL),
(1000000623, 'Parth Kachhara', 'B224', 70572500224, 28, 15, NULL),
(1000000624, 'Aarav Shah', 'B225', 70572500225, 28, 15, NULL),
(1000000625, 'Harsh Parekh', 'B240', 70572500240, 28, 15, NULL),
(1000000626, 'Yogita Manik Chandra', 'B244', 70572500244, 28, 15, NULL),
(1000000627, 'Syed Daniyal', 'B248', 70572500248, 28, 15, NULL),
(1000000628, 'M Shresta Reddy', 'B249', 70572500249, 28, 15, NULL),
(1000000629, 'Hima Varshith Reddy Ankur', 'B251', 70572500251, 28, 15, NULL),
(1000000630, 'Sri Satyam Kumar', 'B254', 70572500254, 28, 15, NULL),
(1000000631, 'Karthikeya Avasarala', 'B255', 70572500255, 28, 15, NULL),
(1000000632, 'S Srivatsava Raghavendra', 'B256', 70572500256, 28, 15, NULL),
(1000000633, 'Pabbathi Vaishnavi', 'B258', 70572500258, 28, 15, NULL),
(1000000634, 'Kesi Reddy Siri Chandana', 'B259', 70572500259, 28, 15, NULL),
(1000000635, 'Divyanshi Vidhani', 'B261', 70572500261, 28, 15, NULL),
(1000000636, 'Palakonda Moksha', 'B263', 70572500263, 28, 15, NULL),
(1000000637, 'Ampolu Utsav Baradwaj', 'B265', 70572500265, 28, 15, NULL),
(1000000638, 'Allam Deekshitha', 'B266', 70572500266, 28, 15, NULL),
(1000000639, 'A Saiteja Goud', 'B268', 70572500268, 28, 15, NULL),
(1000000640, 'Manduvada Vignesh', 'B269', 70572500269, 28, 15, NULL),
(1000000641, 'Rishitha Reddy Raikanti', 'B270', 70572500270, 28, 15, NULL),
(1000000642, 'Agganooru Hyndavi', 'B271', 70572500271, 28, 15, NULL),
(1000000643, 'Vivek Kumar', 'B272', 70572500272, 28, 15, NULL),
(1000000644, 'Pranshu Sharma', 'B282', 70572500282, 28, 15, NULL),
(1000000645, 'Ved Shrivastava', 'B295', 70572500295, 28, 15, NULL),
(1000000646, 'Drishti Shankar', 'B301', 70572500301, 28, 15, NULL),
(1000000647, 'Manya Bhupesh Singh', 'B324', 70572500324, 28, 15, NULL),
(1000000648, 'Aditya Sinha', 'B325', 70572500325, 28, 15, NULL),
(1000000649, 'Darsh Jinna', 'B326', 70572500326, 28, 15, NULL),
(1000000650, 'Rounak Raj', 'B328', 70572500328, 28, 15, NULL),
(1000000651, 'Mohammed Areeb Ali Shivji', 'B329', 70572500329, 28, 15, NULL),
(1000000652, 'Tanmay Anand', 'B330', 70572500330, 28, 15, NULL),
(1000000653, 'Parwez Musharraf', 'B332', 70572500332, 28, 15, NULL),
(1000000654, 'Ayushman Datta', 'B333', 70572500333, 28, 15, NULL),
(1000000655, 'Rajveer Chowdhury', 'B334', 70572500334, 28, 15, NULL),
(1000000656, 'Tirth Sagwaria', 'B336', 70572500336, 28, 15, NULL),
(1000000657, 'Vishist Agrahari', 'B337', 70572500337, 28, 15, NULL),
(1000000658, 'Kousalya Narayana Gari', 'B338', 70572500338, 28, 15, NULL),
(1000000659, 'Anand Sharma', 'B339', 70572500339, 28, 15, NULL),
(1000000660, 'Punugu Harshith Reddy', 'B340', 70572500340, 28, 15, NULL),
(1000000661, 'Dachani Yashwanth Reddy', 'B342', 70572500342, 28, 15, NULL),
(1000000662, 'Karrevaru Gnaneshwar Goud', 'B343', 70572500343, 28, 15, NULL),
(1000000663, 'Nitin Kumar Gupta', 'B344', 70572500344, 28, 15, NULL),
(1000000664, 'Gangolla Srinidhi Reddy', 'B345', 70572500345, 28, 15, NULL),
(1000000665, 'Geetika Agrawal', 'B380', 70572500380, 28, 15, NULL),
(1000000666, 'V. Sanjay Sai', 'B390', 70572500390, 28, 15, NULL),
(1000000667, 'LAUKIK DHURI', 'A027', 70572400027, 28, 15, NULL),
(1000000668, 'Abhishek Rajput', 'B016', 70572400141, 28, 15, NULL),
(1000000669, 'Krysha Mehta', 'A048', 705724000000, 28, 15, NULL),
(1000000670, 'Sreekar', 'B011', 70572400136, 28, 15, NULL),
(1000000671, 'Dilip', 'A040', 70572400107, 28, 15, NULL),
(1000000672, 'harsith', 'B019', 70572400144, 28, 15, NULL),
(1000000673, 'Indukuri Sashank Varma', 'C126', 70022500126, 30, 16, NULL),
(1000000674, 'Pranjal Om Prakash Pathak', 'C1267', 70022500267, 30, 16, NULL),
(1000000675, 'Mahammed Almas Mandlik', 'C268', 70022500268, 30, 16, NULL),
(1000000676, 'Quadri Md Suleman Ali', 'C269', 70022500269, 30, 16, NULL),
(1000000677, 'Sahiti Domudala', 'C271', 70022500271, 30, 16, NULL),
(1000000678, 'Sukanya Mulik', 'C283', 70022500283, 30, 16, NULL),
(1000000679, 'Ekansh bansal', 'C311', 70022500311, 30, 16, NULL),
(1000000680, 'Alugubelly Sahasra', 'C314', 70022500314, 30, 16, NULL),
(1000000681, 'Govinda Karthikeya Neelam', 'C321', 70022500321, 30, 16, NULL),
(1000000682, 'Arnav Kalra', 'C385', 70022500385, 30, 16, NULL),
(1000000683, 'Mohammed Ismail Sohaib', 'C386', 70022500386, 30, 16, NULL),
(1000000684, 'Beere Dheeraj', 'C388', 70022500388, 30, 16, NULL),
(1000000685, 'Rishendra Vignesh Goud', 'C390', 70022500390, 30, 16, NULL),
(1000000686, 'Shruti Priya', 'C395', 70022500395, 30, 16, NULL),
(1000000687, 'Samridh Shrivastava', 'C397', 70022500397, 30, 16, NULL),
(1000000688, 'Eklavya Mankar', 'C399', 70022500399, 30, 16, NULL),
(1000000689, 'Boyini Siddhartha Hanuma', 'C400', 70022500400, 30, 16, NULL),
(1000000690, 'Rishav Kumar', 'C419', 70022500419, 30, 16, NULL),
(1000000691, 'Nishad Utpal Dave', 'C477', 70022500477, 30, 16, NULL),
(1000000692, 'Sanidhya Pandey', 'C565', 70022500565, 30, 16, NULL),
(1000000693, 'Marri Asrith Reddy', 'C566', 70022500566, 30, 16, NULL),
(1000000694, 'Deepika Pasula', 'C568', 70022500568, 30, 16, NULL),
(1000000695, 'Aelleni Shivanjali', 'C569', 70022500569, 30, 16, NULL),
(1000000696, 'Kavya Gunrule', 'C570', 70022500570, 30, 16, NULL),
(1000000697, 'Purushottam Jha', 'C584', 70022500584, 30, 16, NULL),
(1000000698, 'Ayush Mishra', 'C593', 70022500593, 30, 16, NULL),
(1000000699, 'Ayush Yadav', 'C597', 70022500597, 30, 16, NULL),
(1000000700, 'Tanish Tiwari', 'C608', 70022500608, 30, 16, NULL),
(1000000701, 'Mahima Choudhari', 'C623', 70022500623, 30, 16, NULL),
(1000000702, 'Ashwin Shukla', 'C638', 70022500638, 30, 16, NULL),
(1000000703, 'Shivam Gupta', 'C644', 70022500644, 30, 16, NULL),
(1000000704, 'Shanmukhi Balakuntla', 'C645', 70022500645, 30, 16, NULL),
(1000000705, 'Krishna Deshmukh', 'C652', 70022500652, 30, 16, NULL),
(1000000706, 'Tejas Srivastava', 'C656', 70022500656, 30, 16, NULL),
(1000000707, 'Sonakshi Salvi', 'C665', 70022500665, 30, 16, NULL),
(1000000708, 'Rohan Sreejith', 'C668', 70022500668, 30, 16, NULL),
(1000000709, 'Keerthana Cheenavaram', 'D704', 70022500704, 30, 17, NULL),
(1000000710, 'Mohammad Sufiyan', 'D705', 70022500725, 30, 17, NULL),
(1000000711, 'Kaathe Jeevan Kumar', 'D706', 70022500728, 30, 17, NULL),
(1000000712, 'Bala Rajesh Kumar', 'D707', 70022500730, 30, 17, NULL),
(1000000713, 'Kondoju Sainithi', 'D708', 70022500748, 30, 17, NULL),
(1000000714, 'Akshya Priya J', 'D709', 70022500749, 30, 17, NULL),
(1000000715, 'Bejju Revanth', 'D710', 70022500750, 30, 17, NULL),
(1000000716, 'Bejju Sri Ashwitha', 'D711', 70022500751, 30, 17, NULL),
(1000000717, 'Swapnil Patil', 'D712', 70022500752, 30, 17, NULL),
(1000000718, 'Vishwajeet Bedse', 'D713', 70022500753, 30, 17, NULL),
(1000000719, 'Kriday Mishra', 'D714', 70022500754, 30, 17, NULL),
(1000000720, 'Raj Taparia', 'D715', 70022500756, 30, 17, NULL),
(1000000721, 'G Srinithya Reddy', 'D716', 70022500781, 30, 17, NULL),
(1000000722, 'Narayandas Vishwagnya', 'D717', 70022500782, 30, 17, NULL),
(1000000723, 'Kamble Akshata Mahendra', 'D718', 70022500787, 30, 17, NULL),
(1000000724, 'Ayaan Patel', 'D719', 70022500789, 30, 17, NULL),
(1000000725, 'V Kranthi Sri', 'D720', 70022500790, 30, 17, NULL),
(1000000726, 'Noule Harsha Sai', 'D721', 70022500791, 30, 17, NULL),
(1000000727, 'B Hansika Bujagouni Hansika', 'D722', 70022500792, 30, 17, NULL),
(1000000728, 'Golla Harsha Vardhan', 'D723', 70022500793, 30, 17, NULL),
(1000000729, 'Chaitnya Josyula', 'D724', 70022500794, 30, 17, NULL),
(1000000730, 'Sri Maha Lakshmi Thulasi', 'D725', 70022500795, 30, 17, NULL),
(1000000731, 'Manya Puranik', 'D726', 70022500796, 30, 17, NULL),
(1000000732, 'Harpreet Kaur Malhotra', 'D727', 70022500797, 30, 17, NULL),
(1000000733, 'Akkala Harshitha', 'D728', 70022500799, 30, 17, NULL),
(1000000734, 'Huriya Maheen Khan', 'D729', 70022500802, 30, 17, NULL),
(1000000735, 'Boya Vinutna', 'D730', 70022500803, 30, 17, NULL),
(1000000736, 'Sri Mani Rao', 'D731', 70022500804, 30, 17, NULL),
(1000000737, 'Hema Teja T', 'D732', 70022500805, 30, 17, NULL),
(1000000738, 'Bayani Shivani', 'D733', 70022500806, 30, 17, NULL),
(1000000739, 'T Manideep Goud', 'D734', 70022500808, 30, 17, NULL),
(1000000740, 'Sreeharsha Sreeram Shetty', 'D735', 70022500809, 30, 17, NULL),
(1000000741, 'M Arjun', 'D736', 70022500823, 30, 17, NULL),
(1000000742, 'Hritik Reddy', 'D737', 70022500824, 30, 17, NULL),
(1000000743, 'A Somya Sree', 'D738', 70022500825, 30, 17, NULL),
(1000000744, 'Tanisha Vijay Dembla', 'D739', 70022500826, 30, 17, NULL),
(1000000745, 'P Gurudatta', 'D740', 70022500927, 30, 17, NULL),
(1000000746, 'Chinmayee Bakshi', 'D741', 70022500828, 30, 17, NULL),
(1000000747, 'G Saicharangoud', 'D742', 70022500829, 30, 17, NULL),
(1000000748, 'Sreeja K', 'D743', 70022500840, 30, 17, NULL),
(1000000749, 'Yogith Reddy', 'D744', 70022500841, 30, 17, NULL),
(1000000750, 'Sachin Kumar', 'D745', 70022500842, 30, 17, NULL),
(1000000751, 'Ravi Teja', 'D746', 70022500843, 30, 17, NULL),
(1000000752, 'R.Ashritha', 'D747', 70022500844, 30, 17, NULL),
(1000000753, 'Shreyas Modgil', 'D748', 70022500845, 30, 17, NULL),
(1000000754, 'Kshitij yadav', 'D749', 70022500847, 30, 17, NULL),
(1000000755, 'Manda Amruta', 'D750', 70022500849, 30, 17, NULL),
(1000000756, 'Meet Jain', 'D751', 70022500696, 30, 17, NULL),
(1000000757, 'Azfar Sidduqui', 'D752', 70022400640, 30, 17, NULL),
(1000000758, 'Abhi Gupta', 'D753', 70022400703, 30, 17, NULL),
(1000000759, 'Ksai Teja Reddy', 'D754', 70022400698, 30, 17, NULL),
(1000000760, 'KANCHARLA RATNA CHAND', 'D004', 70572300004, 27, NULL, NULL),
(1000000761, 'MAKKENA LAHARI', 'D006', 70572300006, 27, NULL, NULL),
(1000000762, 'ANOUSHKA SARKAR', 'D010', 70572300010, 27, NULL, NULL),
(1000000763, 'ANGSHUMAN CHAKRAVERTTY', 'D013', 70572300013, 27, NULL, NULL),
(1000000764, 'K.SAI RISHITHA', 'D015', 70572300015, 27, NULL, NULL),
(1000000765, 'S. SAIKARTHIK REDDY', 'D018', 70572300018, 27, NULL, NULL),
(1000000766, 'SIRIPURAM VAISHNAVIGOUD', 'D020', 70572300020, 27, NULL, NULL),
(1000000767, 'BRUNGI SHIVA GANESH', 'D021', 70572300021, 27, NULL, NULL),
(1000000768, 'GUMUDAVELLI VIKRAM', 'D022', 70572300022, 27, NULL, NULL),
(1000000769, 'JAHNAVY LAM', 'D023', 70572300023, 27, NULL, NULL),
(1000000770, 'SAHIL SHAIK', 'D024', 70572300024, 27, NULL, NULL),
(1000000771, 'ANOUSHKA AVUTI', 'D030', 70572300030, 27, NULL, NULL),
(1000000772, 'BADAM BALEESHWAR', 'D031', 70572300031, 27, NULL, NULL),
(1000000773, 'SHERY MOUNIKA REDDY', 'D032', 70572300032, 27, NULL, NULL),
(1000000774, 'MD RAYYAN', 'D033', 70572300033, 27, NULL, 'md.rayyan33@nmims.in'),
(1000000775, 'SAI KRISHNA REDDY KUCHURU', 'D034', 70572300034, 27, NULL, 'kuchurusai.krishna34@nmims.in'),
(1000000776, 'AASHRITHA REDDY', 'D035', 70572300035, 27, NULL, NULL),
(1000000777, 'RAMYA KUSUNURILAKSHMI', 'D036', 70572300036, 27, NULL, NULL),
(1000000778, 'PASUPULA SAI TEJA', 'D037', 70572300037, 27, NULL, NULL),
(1000000779, 'G HARSHITH RAJ', 'D039', 70572300039, 27, NULL, NULL),
(1000000780, 'CHINTHAKUNTA HARINI', 'D040', 70572300040, 27, NULL, NULL),
(1000000781, 'T. RISHIKESH', 'D041', 70572300041, 27, NULL, NULL),
(1000000782, 'V SRESHTA REDDY', 'D042', 70572300042, 27, NULL, NULL),
(1000000783, 'P. TARUN KUMAR', 'D043', 70572300043, 27, NULL, NULL),
(1000000784, 'JACOB ALEX', 'D053', 70572300053, 27, NULL, NULL),
(1000000785, 'SV SATHWIKA', 'D054', 70572300054, 27, NULL, NULL),
(1000000786, 'DIVYA NAGUBOYINA', 'D059', 70572300059, 27, NULL, NULL),
(1000000787, 'VEDANTH RAJ', 'D063', 70572300063, 27, NULL, NULL),
(1000000788, 'KOTTAKAPU JANSHI', 'D064', 70572300064, 27, NULL, NULL),
(1000000789, 'C ANIL KUMAR', 'D065', 70572300065, 27, NULL, NULL),
(1000000790, 'MEEDINTI SUNNY VIRAJ', 'D069', 70572300069, 27, NULL, NULL),
(1000000791, 'KOLLURI PRAVALIKA', 'D070', 70572300070, 27, NULL, NULL),
(1000000792, 'ASHI SHARMA', 'L085', 70572400085, 27, NULL, NULL),
(1000000793, 'SHASHANK GOUD P', 'L050', 70572200048, 27, NULL, NULL),
(1000000794, 'RITAM MAHAKUR', 'C001', 70022400269, 32, NULL, NULL),
(1000000795, 'TANISHI ANIL SHUKLA', 'C002', 70022400270, 32, NULL, NULL),
(1000000796, 'SULAKSHANA SONAVANE', 'C004', 70022400299, 32, NULL, NULL),
(1000000797, 'NANDINI DEVNANI', 'C005', 70022400304, 32, NULL, NULL),
(1000000798, 'ANJALI KHANDPAL', 'C006', 70022400307, 32, NULL, NULL),
(1000000799, 'AKANKSHA PATIL', 'C007', 70022400342, 32, NULL, NULL),
(1000000800, 'ASHWINI RAMAN PANDEY.', 'C008', 70022400480, 32, NULL, NULL),
(1000000801, 'SWEETY JAIN', 'C009', 70022400612, 32, NULL, NULL),
(1000000802, 'HANSINI VADDEMPUDI', 'C010', 70022400613, 32, NULL, NULL),
(1000000803, 'N.SANTHOSH NAGELI', 'C014', 70022400618, 32, NULL, NULL),
(1000000804, 'SAINATH GOUD', 'C018', 70022300564, 32, NULL, NULL),
(1000000805, 'NUNNA RISHIT', 'C018', 70022400624, 32, NULL, NULL),
(1000000806, 'SAINATH REDDY', 'C019', 70022300565, 32, NULL, NULL),
(1000000807, 'PENDELA SRI DAKSHAYANI', 'C019', 70022400625, 32, NULL, NULL),
(1000000808, 'CHITTINENI YOSHITHA SREE', 'C020', 70022400628, 32, NULL, NULL),
(1000000809, 'KARNUKA GUPTA', 'C021', 70022400631, 32, NULL, NULL),
(1000000810, 'BANURI SAIRAM', 'C023', 70022400681, 32, NULL, NULL),
(1000000811, 'MOHAMMED ZOHAIBUDDIN.', 'C024', 70022400682, 32, NULL, NULL),
(1000000812, 'PASULA DEEPTHI REDDY', 'C027', 70022400685, 32, NULL, NULL),
(1000000813, 'CIDDOTAM TARUN', 'C028', 70022400686, 32, NULL, NULL),
(1000000814, 'JALAJAM SAI NEHA', 'C029', 70022400687, 32, NULL, NULL),
(1000000815, 'ABHIRAM GOUD', 'C030', 70022400688, 32, NULL, NULL),
(1000000816, 'S.RAVI SAGAR', 'C031', 70022400689, 32, NULL, NULL),
(1000000817, 'MALIPEDDI HARSHITHA', 'C033', 70022400691, 32, NULL, NULL),
(1000000818, 'G.CHANDRIKA', 'C034', 70022400692, 32, NULL, NULL),
(1000000819, 'RAMESH REDDY', 'C035', 70022400693, 32, NULL, NULL),
(1000000820, 'SREEJA REDDY', 'C036', 70022400694, 32, NULL, NULL),
(1000000821, 'HARSHITHA HASINI THATIKONDA', 'C037', 70022400695, 32, NULL, NULL),
(1000000822, 'K.KAVYA', 'C038', 70022400696, 32, NULL, NULL),
(1000000823, 'SAHASRA KALUVA', 'C046', 70022400705, 32, NULL, NULL),
(1000000824, 'SIDDHARTH REDDY SIDA REDDY', 'C048', 70022400707, 32, NULL, NULL),
(1000000825, 'TUSHAR MAWALE', 'C053', 70022400713, 32, NULL, NULL),
(1000000826, 'KURUVA AKHILA', 'C055', 70022400715, 32, NULL, NULL),
(1000000827, 'OJAS BHAYAL', 'C057', 70022400726, 32, NULL, NULL),
(1000000828, 'SARTHAK LANDE', 'C059', 70022400728, 32, NULL, NULL),
(1000000829, 'EPURI AKSHAYA', 'C060', 70022400730, 32, NULL, NULL),
(1000000830, 'AMAN SINHA', 'C063', 70022400793, 32, NULL, NULL),
(1000000831, 'YASH KAVAR', 'C066', 70022400796, 32, NULL, NULL),
(1000000832, 'B SAI SRUTHI PATRO', 'C067', 70022400800, 32, NULL, NULL),
(1000000833, 'KONDUR UJWALA', 'C068', 70022400801, 32, NULL, NULL),
(1000000834, 'MADAN GOUD', 'C070', 70022400803, 32, NULL, NULL),
(1000000835, 'HIMESH CHANDRA', 'C071', 70022400804, 32, NULL, NULL),
(1000000836, 'VIJAY SARATH REDDY', 'C072', 70022400805, 32, NULL, NULL),
(1000000837, 'NEERAJ KUMAR', 'C073', 70022400806, 32, NULL, NULL),
(1000000838, 'Priyanshi Panja', 'A008', 70572400021, 31, 21, NULL),
(1000000839, 'Middle Indu', 'B001', 70572400126, 31, 21, NULL),
(1000000840, 'Guggilam Yuthika', 'B002', 70572400127, 31, 21, NULL),
(1000000841, 'K Hansika', 'B003', 70572400128, 31, 21, NULL),
(1000000842, 'Bangaru Sai Prakash Sagar', 'B004', 70572400129, 31, 21, NULL),
(1000000843, 'Mohammed Omer Farooq', 'B005', 70572400130, 31, 21, NULL),
(1000000844, 'Dasarpalli Srinidhi', 'B007', 70572400132, 31, 21, NULL),
(1000000845, 'Kanduri Mounesh', 'B009', 70572400134, 31, 21, NULL),
(1000000846, 'Charlakola Sai Kumar', 'B010', 70572400135, 31, 21, NULL),
(1000000847, 'Jaami Haider', 'B012', 70572400137, 31, 21, NULL),
(1000000848, 'Mahith Patel', 'B014', 70572400139, 31, 21, NULL),
(1000000849, 'Bannur Prajwala Reddy', 'B022', 70572400147, 31, 21, NULL),
(1000000850, 'Aarush Chaudhary', 'B023', 70572400148, 31, 21, NULL),
(1000000851, 'Sanjana Salunke', 'B024', 70572400150, 31, 21, NULL),
(1000000852, 'Paridhi Talreja', 'B025', 70572400152, 31, 21, NULL),
(1000000853, 'Krishna Patil', 'B026', 70572400153, 31, 21, NULL),
(1000000854, 'Anmol Jana', 'B027', 70572400154, 31, 21, NULL),
(1000000855, 'Hasya Surabhi', 'B028', 70572400155, 31, 21, NULL),
(1000000856, 'Anu Prithi Sambi', 'B029', 70572400156, 31, 21, NULL),
(1000000857, 'Ishanth Bhyramoni', 'B033', 70572400161, 31, 21, NULL),
(1000000858, 'Sambu Mahitha', 'B034', 70572400162, 31, 21, NULL),
(1000000859, 'Basuthkar Koushik', 'B035', 70572400163, 31, 21, NULL),
(1000000860, 'Kevindeep Singh Pannu', 'B038', 70572400166, 31, 21, NULL),
(1000000861, 'Vaishnavi Beiju', 'B041', 70572400175, 31, 21, NULL),
(1000000862, 'Syed Noheluddin Zaid', 'B042', 70572400176, 31, 21, NULL),
(1000000863, 'L Mamatha', 'B043', 70572400177, 31, 21, NULL),
(1000000864, 'Jampula Mayank', 'B045', 70572400179, 31, 21, NULL),
(1000000865, 'Gouni Abhinav', 'B047', 70572400181, 31, 21, NULL),
(1000000866, 'D. Ganesh Yadav', 'B048', 70572400182, 31, 21, NULL),
(1000000867, 'Chirumani Sai Kiran Reddy', 'B049', 70572400183, 31, 21, NULL),
(1000000868, 'Ananya Kolluru', 'B050', 70572400184, 31, 21, NULL),
(1000000869, 'Kommula Akshitha Sagar', 'B051', 70572400185, 31, 21, NULL),
(1000000870, 'Abhishek Sunil Mohale', 'B053', 70572400187, 31, 21, NULL),
(1000000871, 'Anaaya Akhlaque', 'B055', 70572400189, 31, 21, NULL),
(1000000872, 'Narahari Swathi Bhavani', 'B056', 70572400190, 31, 21, NULL),
(1000000873, 'K Hanvitha Sree', 'B058', 70572400203, 31, 21, NULL),
(1000000874, 'DATTA DASU MANOJ', 'D019', 70572300019, 31, 20, NULL),
(1000000875, 'RACHURI SAHASRA', 'A002', 70572400008, 31, 20, NULL),
(1000000876, 'DHRUV SONAR', 'A003', 70572400009, 31, 20, NULL),
(1000000877, 'AYUSHMAN PADHY', 'A005', 70572400015, 31, 20, NULL),
(1000000878, 'RISHAB SARDA', 'A006', 70572400018, 31, 20, NULL),
(1000000879, 'GEEREDDY SRICHARAN REDDY', 'A007', 70572400020, 31, 20, NULL),
(1000000880, 'ZAID AHMAD', 'A058', 70572400022, 31, 20, NULL),
(1000000881, 'RYAN MANVAR', 'A010', 70572400023, 31, 20, NULL),
(1000000882, 'HEMANG MENARIA', 'A011', 70572400026, 31, 20, NULL),
(1000000883, 'ANIRUDH POODHATTHU', 'A013', 70572400054, 31, 20, NULL),
(1000000884, 'VATTIPALLY BHANUPRAVEEN REDDY', 'A014', 70572400057, 31, 20, NULL),
(1000000885, 'DACHANI JASHWANTH VIKRAM', 'A016', 70572400059, 31, 20, NULL),
(1000000886, 'ZAMEER KHAN', 'A017', 70572400060, 31, 20, NULL),
(1000000887, 'VIVEK CHAITANYA SAMBU', 'A019', 70572400063, 31, 20, NULL);
INSERT INTO `students` (`id`, `name`, `roll_number`, `sap_id`, `class_id`, `section_id`, `college_email`) VALUES
(1000000888, 'BANDARU SAKETH ABHINANDAN', 'A020', 70572400064, 31, 20, NULL),
(1000000889, 'CHEDAM HARIN', 'A021', 70572400065, 31, 20, NULL),
(1000000890, 'AVUTI SUPREETHI', 'A022', 70572400066, 31, 20, NULL),
(1000000891, 'AKSHAYA GOVINDU', 'A023', 70572400068, 31, 20, NULL),
(1000000892, 'SPANDANA KARNEKOTA', 'A024', 70572400069, 31, 20, NULL),
(1000000893, 'OMKAR KHANDEPARKAR', 'A025', 70572400070, 31, 20, NULL),
(1000000894, 'MAREPALLY VARSHINI CHAVANA', 'A026', 70572400071, 31, 20, NULL),
(1000000895, 'G. BHAVYA SREE', 'A027', 70572400072, 31, 20, NULL),
(1000000896, 'TUMUKUNTA SRIVALLI', 'A028', 70572400073, 31, 20, NULL),
(1000000897, 'TANISHQ KUMAR PRAJAPATI', 'A029', 70572400074, 31, 20, NULL),
(1000000898, 'G.AKHIL GOUD', 'A030', 70572400077, 31, 20, NULL),
(1000000899, 'YASH SOMWANSHI', 'A032', 70572400080, 31, 20, NULL),
(1000000900, 'KUCHUR RISHIKANTHREDDY', 'A033', 70572400099, 31, 20, NULL),
(1000000901, 'GIRAMONI NIHARIKA', 'A034', 70572400101, 31, 20, NULL),
(1000000902, 'MANIKONDA VINUTHNA', 'A036', 70572400103, 31, 20, NULL),
(1000000903, 'S SRITHAN GOUD', 'A037', 70572400104, 31, 20, NULL),
(1000000904, 'P BHAVANI NAGA', 'A038', 70572400105, 31, 20, NULL),
(1000000905, 'K.VAMSHI KRISHNA.', 'A039', 70572400106, 31, 20, NULL),
(1000000906, 'DEV CHALANA', 'A041', 70572400109, 31, 20, NULL),
(1000000907, 'GOTUR VARSHITH', 'A044', 70572400112, 31, 20, NULL),
(1000000908, 'MANKALA SUDHEEKSHA', 'A045', 70572400113, 31, 20, NULL),
(1000000909, 'SAHITHI ALAMPALLY', 'A046', 70572400114, 31, 20, NULL),
(1000000910, 'AKSHAYA REDDY VUNDHYALA', 'A047', 70572400115, 31, 20, NULL),
(1000000911, 'AVINASH UBA', 'A049', 70572400117, 31, 20, NULL),
(1000000912, 'SHAIK MOHD FAIZ SAYEED', 'A050', 70572400118, 31, 20, NULL),
(1000000913, 'GUDIPALLY MADHUMITHA', 'A051', 70572400119, 31, 20, NULL),
(1000000914, 'RAMIREDDY BHANU TEJA REDDY', 'A052', 70572400120, 31, 20, NULL),
(1000000915, 'BODA SRIVANI', 'A053', 70572400121, 31, 20, NULL),
(1000000916, 'SATHVIKA VADUGULA', 'A054', 70572400122, 31, 20, NULL),
(1000000917, 'SANDADI RAHUL REDDY', 'A055', 70572400123, 31, 20, NULL),
(1000000918, 'P. CHARAN', 'A056', 70572400124, 31, 20, NULL),
(1000000919, 'MUSANI VIVEKANANDA', 'A057', 70572400125, 31, 20, NULL),
(1000000920, 'Kapperi Divya Sri', 'L021', 70572200010, 33, NULL, NULL),
(1000000921, 'Pavithra Sevakula', 'L022', 70572200011, 33, NULL, NULL),
(1000000922, 'M.Vaishnavi', 'L023', 70572200012, 33, NULL, NULL),
(1000000923, 'Akula Srinithya', 'L024', 70572200013, 33, NULL, NULL),
(1000000924, 'K.Manishankar Goud', 'L025', 70572200014, 33, NULL, NULL),
(1000000925, 'Jahnavi Maddi', 'L026', 70572200015, 33, NULL, NULL),
(1000000926, 'M Bharghav Kumar', 'L027', 70572200016, 33, NULL, NULL),
(1000000927, 'Charala Pujitha', 'L028', 70572200017, 33, NULL, NULL),
(1000000928, 'J. Thrisha Reddy', 'L030', 70572200026, 33, NULL, NULL),
(1000000929, 'Harsh Bang', 'L031', 70572200027, 33, NULL, NULL),
(1000000930, 'Ruthvik Akula', 'L032', 70572200028, 33, NULL, NULL),
(1000000931, 'D.Lokeshwar Goud', 'L033', 70572200029, 33, NULL, NULL),
(1000000932, 'Anmagandla Snehil', 'L034', 70572200030, 33, NULL, NULL),
(1000000933, 'Narahari Abhinav', 'L035', 70572200031, 33, NULL, NULL),
(1000000934, 'Md Sohail', 'L036', 70572200032, 33, NULL, NULL),
(1000000935, 'Malde Saicharan', 'L037', 70572200033, 33, NULL, NULL),
(1000000936, 'Prasad Sham Kannawar', 'L038', 70572200034, 33, NULL, NULL),
(1000000937, 'Venkatesh M', 'L039', 70572200035, 33, NULL, NULL),
(1000000938, 'Rachit Jain', 'L040', 70572200036, 33, NULL, NULL),
(1000000939, 'Khushal Baldava', 'L042', 70572200038, 33, NULL, NULL),
(1000000940, 'Sidra Fatima', 'L044', 70572200042, 33, NULL, NULL),
(1000000941, 'Sai Vijaya Laxmi', 'L046', 70572200044, 33, NULL, NULL),
(1000000942, 'Vadla Vaishnavi', 'L047', 70572200045, 33, NULL, NULL),
(1000000943, 'B Vaishnavi', 'L048', 70572200046, 33, NULL, NULL),
(1000000944, 'G. Sainath Goud', 'L049', 70572200047, 33, NULL, NULL),
(1000000945, 'Kurumidde John Austin', 'L051', 70572200049, 33, NULL, NULL),
(1000000946, 'Chetan H', 'L052', 70572200050, 33, NULL, NULL),
(1000000947, 'Ananya P', 'L053', 70572200052, 33, NULL, NULL),
(1000000948, 'M Sowmya', 'L054', 70572200053, 33, NULL, NULL),
(1000000949, 'G Pragnya Reddy', 'L055', 70572200054, 33, NULL, NULL),
(1000000950, 'V Abhiram Reddy', 'L056', 70572200055, 33, NULL, NULL),
(1000000951, 'R Ananth Yadav', 'L057', 70572200056, 33, NULL, NULL),
(1000000952, 'ANSHUMAN CHAKRAVORTY', 'C001', 70022300271, 34, NULL, NULL),
(1000000953, 'ZEESHAN ALI', 'C007', 70022300461, 34, NULL, 'zeeshan@nmims.in'),
(1000000954, 'BOYINI DHANUSH', 'C010', 70022300509, 34, NULL, NULL),
(1000000955, 'MOHD OMER FARAZ', 'C011', 70022300511, 34, NULL, NULL),
(1000000956, 'K SAI CHARAN', 'C012', 70022300533, 34, NULL, NULL),
(1000000957, 'MEHUL NARAYANOLLA', 'C014', 70022300535, 34, NULL, NULL),
(1000000958, 'M RAVI KUMAR', 'C016', 70022300556, 34, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student_assignments`
--

CREATE TABLE `student_assignments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `submission_status` enum('pending','submitted','graded') DEFAULT 'pending',
  `assignment_status` varchar(30) DEFAULT 'pending',
  `submission_state` varchar(30) DEFAULT 'pending',
  `marks_obtained` decimal(5,2) DEFAULT NULL,
  `graded_marks` decimal(6,2) DEFAULT NULL,
  `graded_at` datetime DEFAULT NULL,
  `teacher_feedback` text DEFAULT NULL,
  `feedback_file_path` varchar(255) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `submission_date` timestamp NULL DEFAULT NULL,
  `submitted_file_path` varchar(255) DEFAULT NULL,
  `last_submission_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_assignments`
--

INSERT INTO `student_assignments` (`id`, `student_id`, `assignment_id`, `submission_status`, `assignment_status`, `submission_state`, `marks_obtained`, `graded_marks`, `graded_at`, `teacher_feedback`, `feedback_file_path`, `reviewed_at`, `reviewed_by`, `submission_date`, `submitted_file_path`, `last_submission_at`, `created_at`) VALUES
(20, 1000000147, 17, 'pending', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-21 20:09:14'),
(21, 1000000148, 17, 'pending', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-21 20:09:14'),
(22, 1000000149, 17, 'pending', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-21 20:09:14'),
(23, 1000000150, 17, 'pending', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-21 20:09:14'),
(24, 1000000151, 17, 'pending', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-21 20:09:14'),
(25, 1000000152, 17, 'pending', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-21 20:09:14'),
(26, 1000000153, 17, 'pending', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-21 20:09:14'),
(27, 1000000154, 17, 'pending', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-21 20:09:14'),
(28, 1000000155, 17, 'pending', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-21 20:09:14'),
(29, 1000000156, 17, 'pending', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-21 20:09:14'),
(30, 1000000157, 17, 'pending', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-21 20:09:14'),
(31, 1000000158, 17, 'pending', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-21 20:09:14'),
(32, 1000000159, 17, 'pending', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-21 20:09:14'),
(33, 1000000160, 17, 'pending', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-21 20:09:14'),
(34, 1000000161, 17, 'pending', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-21 20:09:14'),
(35, 1000000162, 17, 'pending', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-21 20:09:14'),
(36, 1000000163, 17, 'pending', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-21 20:09:14'),
(37, 1000000164, 17, 'pending', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-21 20:09:14'),
(38, 1000000165, 17, 'pending', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-21 20:09:14'),
(39, 1000000166, 17, 'pending', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-21 20:09:14'),
(40, 1000000167, 17, 'pending', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-21 20:09:14'),
(41, 1000000168, 17, 'pending', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-21 20:09:14'),
(42, 1000000169, 17, 'pending', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-21 20:09:14'),
(43, 1000000170, 17, 'pending', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-21 20:09:14'),
(44, 1000000171, 17, 'pending', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-21 20:09:14'),
(45, 1000000172, 17, 'pending', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-21 20:09:14'),
(46, 1000000173, 17, 'pending', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-21 20:09:14'),
(47, 1000000174, 17, 'pending', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-21 20:09:14'),
(48, 1000000175, 17, 'pending', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-21 20:09:14'),
(49, 1000000176, 17, 'pending', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-21 20:09:14'),
(50, 1000000177, 17, 'pending', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-21 20:09:14'),
(51, 1000000178, 17, 'pending', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-21 20:09:14'),
(52, 1000000179, 17, 'pending', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-21 20:09:14'),
(53, 1000000180, 17, 'pending', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-21 20:09:14');

-- --------------------------------------------------------

--
-- Table structure for table `student_elective_choices`
--

CREATE TABLE `student_elective_choices` (
  `id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_elective_choices`
--

INSERT INTO `student_elective_choices` (`id`, `subject_id`, `student_id`, `class_id`, `created_at`, `updated_at`) VALUES
(1, 25, 1000000147, 21, '2025-11-14 14:07:05', '2025-11-15 11:19:34'),
(3, 25, 1000000148, 21, '2025-11-15 11:19:34', '2025-11-15 11:19:34'),
(4, 25, 1000000149, 21, '2025-11-15 11:19:34', '2025-11-15 11:19:34'),
(5, 25, 1000000150, 21, '2025-11-15 11:19:34', '2025-11-15 11:19:34'),
(6, 50, 1000000432, 25, '2025-12-30 10:44:45', '2025-12-30 10:44:45'),
(7, 50, 1000000433, 25, '2025-12-30 10:44:45', '2025-12-30 10:44:45'),
(8, 50, 1000000436, 25, '2025-12-30 10:44:45', '2025-12-30 10:44:45'),
(9, 50, 1000000437, 25, '2025-12-30 10:44:45', '2025-12-30 10:44:45'),
(10, 50, 1000000439, 25, '2025-12-30 10:44:45', '2025-12-30 10:44:45'),
(11, 50, 1000000440, 25, '2025-12-30 10:44:45', '2025-12-30 10:44:45'),
(12, 50, 1000000443, 25, '2025-12-30 10:44:45', '2025-12-30 10:44:45'),
(13, 50, 1000000445, 25, '2025-12-30 10:44:45', '2025-12-30 10:44:45'),
(14, 50, 1000000447, 25, '2025-12-30 10:44:45', '2025-12-30 10:44:45'),
(15, 50, 1000000448, 25, '2025-12-30 10:44:45', '2025-12-30 10:44:45'),
(16, 50, 1000000452, 25, '2025-12-30 10:44:45', '2025-12-30 10:44:45'),
(17, 50, 1000000453, 25, '2025-12-30 10:44:45', '2025-12-30 10:44:45'),
(18, 50, 1000000454, 25, '2025-12-30 10:44:45', '2025-12-30 10:44:45'),
(19, 50, 1000000456, 25, '2025-12-30 10:44:45', '2025-12-30 10:44:45'),
(20, 50, 1000000459, 25, '2025-12-30 10:44:45', '2025-12-30 10:44:45'),
(21, 50, 1000000462, 25, '2025-12-30 10:44:45', '2025-12-30 10:44:45'),
(22, 83, 1000000760, 27, '2026-01-02 08:13:00', '2026-01-02 08:16:06'),
(23, 83, 1000000761, 27, '2026-01-02 08:13:00', '2026-01-02 08:16:06'),
(24, 83, 1000000762, 27, '2026-01-02 08:13:00', '2026-01-02 08:16:06'),
(25, 83, 1000000763, 27, '2026-01-02 08:13:00', '2026-01-02 08:16:06'),
(26, 83, 1000000764, 27, '2026-01-02 08:13:00', '2026-01-02 08:16:06'),
(27, 83, 1000000765, 27, '2026-01-02 08:13:00', '2026-01-02 08:16:06'),
(28, 83, 1000000766, 27, '2026-01-02 08:13:00', '2026-01-02 08:16:06'),
(29, 83, 1000000767, 27, '2026-01-02 08:13:00', '2026-01-02 08:16:06'),
(30, 83, 1000000768, 27, '2026-01-02 08:13:00', '2026-01-02 08:16:06'),
(31, 83, 1000000769, 27, '2026-01-02 08:13:00', '2026-01-02 08:16:06'),
(32, 83, 1000000770, 27, '2026-01-02 08:13:00', '2026-01-02 08:16:06'),
(33, 83, 1000000771, 27, '2026-01-02 08:13:00', '2026-01-02 08:16:06'),
(34, 83, 1000000772, 27, '2026-01-02 08:13:00', '2026-01-02 08:16:06'),
(35, 83, 1000000773, 27, '2026-01-02 08:13:00', '2026-01-02 08:16:06'),
(36, 83, 1000000774, 27, '2026-01-02 08:13:00', '2026-01-02 08:16:06'),
(37, 83, 1000000775, 27, '2026-01-02 08:13:00', '2026-01-02 08:16:06'),
(38, 83, 1000000776, 27, '2026-01-02 08:13:00', '2026-01-02 08:16:06'),
(39, 83, 1000000777, 27, '2026-01-02 08:13:00', '2026-01-02 08:16:06'),
(40, 83, 1000000778, 27, '2026-01-02 08:13:00', '2026-01-02 08:16:06'),
(41, 83, 1000000779, 27, '2026-01-02 08:13:00', '2026-01-02 08:16:06'),
(42, 83, 1000000780, 27, '2026-01-02 08:13:00', '2026-01-02 08:16:06'),
(43, 83, 1000000781, 27, '2026-01-02 08:13:00', '2026-01-02 08:16:06'),
(44, 83, 1000000782, 27, '2026-01-02 08:13:00', '2026-01-02 08:16:06'),
(45, 83, 1000000783, 27, '2026-01-02 08:13:00', '2026-01-02 08:16:06'),
(46, 83, 1000000784, 27, '2026-01-02 08:13:00', '2026-01-02 08:16:06'),
(47, 83, 1000000785, 27, '2026-01-02 08:13:00', '2026-01-02 08:16:06'),
(48, 83, 1000000786, 27, '2026-01-02 08:13:00', '2026-01-02 08:16:06'),
(49, 83, 1000000787, 27, '2026-01-02 08:13:00', '2026-01-02 08:16:06'),
(50, 83, 1000000788, 27, '2026-01-02 08:13:00', '2026-01-02 08:16:06'),
(51, 83, 1000000789, 27, '2026-01-02 08:13:00', '2026-01-02 08:16:06'),
(52, 83, 1000000790, 27, '2026-01-02 08:13:00', '2026-01-02 08:16:06'),
(53, 83, 1000000791, 27, '2026-01-02 08:13:00', '2026-01-02 08:16:06'),
(54, 83, 1000000793, 27, '2026-01-02 08:13:00', '2026-01-02 08:16:06'),
(55, 83, 1000000792, 27, '2026-01-02 08:13:00', '2026-01-02 08:16:06'),
(124, 82, 1000000760, 27, '2026-01-02 08:23:04', '2026-01-02 08:23:04'),
(125, 82, 1000000761, 27, '2026-01-02 08:23:04', '2026-01-02 08:23:04'),
(126, 82, 1000000762, 27, '2026-01-02 08:23:04', '2026-01-02 08:23:04'),
(127, 82, 1000000763, 27, '2026-01-02 08:23:04', '2026-01-02 08:23:04'),
(128, 82, 1000000764, 27, '2026-01-02 08:23:04', '2026-01-02 08:23:04'),
(129, 82, 1000000765, 27, '2026-01-02 08:23:04', '2026-01-02 08:23:04'),
(130, 82, 1000000766, 27, '2026-01-02 08:23:04', '2026-01-02 08:23:04'),
(131, 82, 1000000767, 27, '2026-01-02 08:23:04', '2026-01-02 08:23:04'),
(132, 82, 1000000768, 27, '2026-01-02 08:23:04', '2026-01-02 08:23:04'),
(133, 82, 1000000769, 27, '2026-01-02 08:23:04', '2026-01-02 08:23:04'),
(134, 82, 1000000770, 27, '2026-01-02 08:23:04', '2026-01-02 08:23:04'),
(135, 82, 1000000771, 27, '2026-01-02 08:23:04', '2026-01-02 08:23:04'),
(136, 82, 1000000772, 27, '2026-01-02 08:23:04', '2026-01-02 08:23:04'),
(137, 82, 1000000773, 27, '2026-01-02 08:23:04', '2026-01-02 08:23:04'),
(138, 82, 1000000774, 27, '2026-01-02 08:23:04', '2026-01-02 08:23:04'),
(139, 82, 1000000775, 27, '2026-01-02 08:23:04', '2026-01-02 08:23:04'),
(140, 82, 1000000776, 27, '2026-01-02 08:23:04', '2026-01-02 08:23:04'),
(141, 82, 1000000777, 27, '2026-01-02 08:23:04', '2026-01-02 08:23:04'),
(142, 82, 1000000778, 27, '2026-01-02 08:23:04', '2026-01-02 08:23:04'),
(143, 82, 1000000779, 27, '2026-01-02 08:23:04', '2026-01-02 08:23:04'),
(144, 82, 1000000780, 27, '2026-01-02 08:23:04', '2026-01-02 08:23:04'),
(145, 82, 1000000781, 27, '2026-01-02 08:23:04', '2026-01-02 08:23:04'),
(146, 82, 1000000782, 27, '2026-01-02 08:23:04', '2026-01-02 08:23:04'),
(147, 82, 1000000783, 27, '2026-01-02 08:23:04', '2026-01-02 08:23:04'),
(148, 82, 1000000784, 27, '2026-01-02 08:23:04', '2026-01-02 08:23:04'),
(149, 82, 1000000785, 27, '2026-01-02 08:23:04', '2026-01-02 08:23:04'),
(150, 82, 1000000786, 27, '2026-01-02 08:23:04', '2026-01-02 08:23:04'),
(151, 82, 1000000787, 27, '2026-01-02 08:23:04', '2026-01-02 08:23:04'),
(152, 82, 1000000788, 27, '2026-01-02 08:23:04', '2026-01-02 08:23:04'),
(153, 82, 1000000789, 27, '2026-01-02 08:23:04', '2026-01-02 08:23:04'),
(154, 82, 1000000790, 27, '2026-01-02 08:23:04', '2026-01-02 08:23:04'),
(155, 82, 1000000791, 27, '2026-01-02 08:23:04', '2026-01-02 08:23:04'),
(156, 82, 1000000793, 27, '2026-01-02 08:23:04', '2026-01-02 08:23:04'),
(157, 82, 1000000792, 27, '2026-01-02 08:23:04', '2026-01-02 08:23:04');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `short_name` varchar(20) DEFAULT NULL,
  `semester` varchar(50) NOT NULL,
  `school` varchar(100) NOT NULL,
  `total_planned_hours` int(11) NOT NULL DEFAULT 0,
  `department` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `subject_name`, `short_name`, `semester`, `school`, `total_planned_hours`, `department`) VALUES
(13, 'SE-Software Engineering', 'SE', '5', 'STME', 60, NULL),
(14, 'MAD-Mobile Application Development', 'MAD', '5', 'STME', 60, NULL),
(15, 'CN-Computer Networks', 'CN', '5', 'STME', 60, NULL),
(16, 'OS-Operating Systems', 'OS', '5', 'STME', 60, NULL),
(17, 'IVAR-Introduction to Virtual And Augmented Reality', 'IVAR', '5', 'STME', 60, NULL),
(18, 'DC-Distributed Computing', 'DC', '5', 'STME', 60, NULL),
(19, 'EPM-Elememts of Project Management', 'EPM', '5', 'STME', 45, NULL),
(20, 'CTPS-Computational Thinking For Problem Solving', 'CTPS', '1', 'STME', 75, NULL),
(21, 'CAL-Calculus', 'CAL', '1', 'STME', 60, NULL),
(22, 'PHY-Physics', 'PHY', '1', 'STME', 75, NULL),
(23, 'Capstone Project', 'CP', '7', 'STME', 60, NULL),
(26, 'AI-Artificial Intelligence', 'AI', '5', 'STME', 60, NULL),
(27, 'IVP-Image and Video Processing', 'IVP', '5', 'STME', 60, NULL),
(28, 'CC-Cloud Computing', 'CC', '7', 'STME', 60, NULL),
(29, 'BDA-Big Data Analytics', 'BDA', '7', 'STME', 60, NULL),
(31, 'BEEE-Basic Electrical and Electronics Engineering', 'BEEE', '1', 'STME', 60, NULL),
(32, 'EGD-Engineering Graphics and Design', 'EGD', '1', 'STME', 45, NULL),
(33, 'EE-Engineering Ethics', 'EE', '1', 'STME', 15, NULL),
(34, 'EEP-Essential Electronics Practices', 'EEP', '1', 'STME', 30, NULL),
(35, 'COI-Constitution of India', 'COI', '1', 'STME', 15, NULL),
(36, 'IKS-Indian Knowledge System', 'IKS', '1', 'STME', 15, NULL),
(37, 'EOB-Elements of Biology', 'EOB', '1', 'STME', 45, NULL),
(38, 'ES-Environmental Studies', 'ES', '1', 'STME', 15, NULL),
(39, 'PS-Probability and Statistics', 'PS', '3', 'STME', 60, NULL),
(40, 'DM-Discrete Mathematics', 'DM', '3', 'STME', 45, NULL),
(41, 'PEM-Principles of Economics and Management', 'PEM', '3', 'STME', 45, NULL),
(42, 'DLD-Digital Logic Design', 'DLD', '3', 'STME', 60, NULL),
(43, 'DSA-Data Structures and Algorithms', 'DSA', '3', 'STME', 75, NULL),
(44, 'CN-Computer Networks', 'CN', '3', 'STME', 60, NULL),
(45, 'TC-Technical Communication', 'TC', '3', 'STME', 15, NULL),
(46, 'DEP-Data Extraction and Processing', 'DEP', '3', 'STME', 45, NULL),
(47, 'DW-Data Wrangling', 'DW', '3', 'STME', 75, NULL),
(48, 'OM-Optimization Methods', 'OM', '3', 'STME', 75, NULL),
(49, 'MAE-Management Accounting for Engineers', 'MAE', '3', 'STME', 30, NULL),
(50, 'CNS-CRYPTOGRAPHY AND NETWORK SECURITY', 'CNS', '7', 'STME', 60, NULL),
(51, 'LADE-Linear Algebra and Differential Equations', 'LADE', '2', 'STME', 75, NULL),
(52, 'QP-Quantum Physics', 'QP', '2', 'STME', 60, NULL),
(53, 'OOP-Object Oriented Programming', 'OOP', '2', 'STME', 30, NULL),
(54, 'OOPL-Object Oriented Programming Lab', 'OOPL', '2', 'STME', 30, NULL),
(55, 'BEEE-Basic Electrical and Electronics Engineering', 'BEEE', '2', 'STME', 60, NULL),
(56, 'WD-Web Development', 'WD', '2', 'STME', 60, NULL),
(57, 'PR-PRODUCT REALIZATION', 'PR', '2', 'STME', 45, NULL),
(58, 'COI-Constitution of India', 'COI', '2', 'STME', 15, NULL),
(59, 'EC-English Communication', 'EC', '2', 'STME', 30, NULL),
(60, 'EOB-Elements of Biology', 'EOB', '2', 'STME', 30, NULL),
(61, 'EOB-Elements of Biology(T)', 'EOB', '2', 'STME', 15, NULL),
(62, 'MAE-Management Accounting for Engineers', 'MAE', '2', 'STME', 30, NULL),
(63, 'TII-Transforming Ideas to Innovation', 'TII', '2', 'STME', 30, NULL),
(64, 'ES-Environmental Science', 'ES', '2', 'STME', 15, NULL),
(65, 'CVT-Complex Variables and Transforms', 'CVT', '4', 'STME', 60, NULL),
(66, 'COA-Computer Organization and Architecture', 'COA', '4', 'STME', 45, NULL),
(67, 'DAA-Design and Analysis of Algorithms', 'DAA', '4', 'STME', 60, NULL),
(68, 'DBMS-Database Management Systems', 'DBMS', '4', 'STME', 60, NULL),
(69, 'MM-Microprocessor and Microcontroller', 'MM', '4', 'STME', 75, NULL),
(70, 'TCS-Theoretical Computer Science', 'TCS', '4', 'STME', 45, NULL),
(71, 'WP-Web Programming', 'WP', '4', 'STME', 60, NULL),
(72, 'OOPJ-Object Oriented Programming through JAVA', 'OOPJ', '4', 'STME', 30, NULL),
(73, 'DE1-Design Experience I', 'DE1', '4', 'STME', 30, NULL),
(74, 'SM-Statistical Methods', 'SM', '4', 'STME', 60, NULL),
(75, 'ML-Machine Learning', 'ML', '4', 'STME', 45, NULL),
(76, 'IDS&IA-Introduction to Data, Signal, and Image Analysis', 'IDSIA', '4', 'STME', 75, NULL),
(77, 'DHV-Data Handling and Visualization', 'DHV', '4', 'STME', 45, NULL),
(78, 'AAI-Applied Artificial Intelligence', 'AAI', '6', 'STME', 60, NULL),
(79, 'NNDL-Neural Networks and Deep Learning', 'NNDL', '6', 'STME', 60, NULL),
(80, 'ADSA-Advance Data Structure for Analytics', 'ADSA', '6', 'STME', 60, NULL),
(82, 'DE3-VCC- Virtualization and Cloud Computing', 'DE3', '6', 'STME', 60, NULL),
(83, 'DE2-PA- Predictive Analysis', 'DE2', '6', 'STME', 60, NULL),
(84, 'OE3-RM- Research Methodology', 'OE3', '6', 'STME', 45, NULL),
(85, 'OE3-DT-DRONE TECHNOLOGY', 'OE3', '6', 'STME', 60, NULL),
(86, 'OE4-FIMIS - Financial Institutions, Markets, Instruments and Services', 'OE4', '6', 'STME', 45, NULL),
(87, 'OE4-CEM-CREATIVITY AND ETHICS IN MARKETING', 'OE4', '6', 'STME', 45, NULL),
(88, 'IS-Interpersonal Skills', 'IS', '6', 'STME', 30, NULL),
(89, 'CS-CYBER SECURITY', 'CS', '6', 'STME', 60, NULL),
(90, 'ML-MACHINE LEARNING', 'ML', '6', 'STME', 60, NULL),
(91, 'DC-Distributed Computing', 'DC', '6', 'STME', 60, NULL),
(92, 'DE2-CV-COMPUTER VISION', 'DE2', '6', 'STME', 60, NULL),
(93, 'DE3-SQA-Software Quality Assurance', 'DE3', '6', 'STME', 60, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `subject_class_map`
--

CREATE TABLE `subject_class_map` (
  `id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `section_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subject_class_map`
--

INSERT INTO `subject_class_map` (`id`, `subject_id`, `class_id`, `section_id`, `created_at`) VALUES
(1, 21, 23, 9, '2025-11-13 08:51:00'),
(2, 23, 25, NULL, '2025-11-13 16:56:13'),
(3, 15, 21, NULL, '2025-11-13 16:56:30'),
(4, 20, 23, 9, '2025-11-13 16:58:37'),
(5, 18, 21, NULL, '2025-11-13 16:58:52'),
(6, 19, 21, NULL, '2025-11-13 16:59:07'),
(7, 17, 21, NULL, '2025-11-13 16:59:19'),
(8, 14, 21, NULL, '2025-11-13 16:59:48'),
(9, 16, 21, NULL, '2025-11-13 17:00:00'),
(10, 22, 23, 9, '2025-11-13 17:00:19'),
(11, 13, 21, NULL, '2025-11-13 17:00:31'),
(12, 24, 27, NULL, '2025-11-13 17:14:01'),
(13, 25, 21, NULL, '2025-11-14 14:06:45'),
(14, 26, 26, NULL, '2025-12-22 02:02:18'),
(15, 27, 26, NULL, '2025-12-22 02:03:26'),
(16, 28, 25, NULL, '2025-12-22 02:08:29'),
(17, 29, 25, NULL, '2025-12-22 02:17:49'),
(18, 30, 21, NULL, '2025-12-22 02:28:19'),
(19, 31, 23, 9, '2025-12-22 02:32:57'),
(20, 32, 23, 9, '2025-12-22 02:33:55'),
(21, 33, 23, 9, '2025-12-22 02:34:43'),
(22, 34, 23, 9, '2025-12-22 02:38:07'),
(23, 35, 23, 9, '2025-12-22 02:39:37'),
(24, 36, 23, 9, '2025-12-22 02:40:17'),
(25, 37, 24, 11, '2025-12-28 16:40:43'),
(26, 38, 24, 11, '2025-12-28 16:42:29'),
(27, 39, 29, NULL, '2025-12-30 08:18:46'),
(28, 40, 29, NULL, '2025-12-30 09:36:13'),
(29, 41, 29, NULL, '2025-12-30 09:37:20'),
(30, 42, 29, NULL, '2025-12-30 09:37:49'),
(31, 43, 29, NULL, '2025-12-30 09:40:48'),
(32, 44, 29, NULL, '2025-12-30 09:41:09'),
(33, 45, 29, NULL, '2025-12-30 09:41:39'),
(34, 46, 29, NULL, '2025-12-30 09:42:11'),
(35, 47, 22, 13, '2025-12-30 10:14:24'),
(36, 48, 22, 13, '2025-12-30 10:14:50'),
(37, 49, 22, 13, '2025-12-30 10:15:23'),
(38, 50, 25, NULL, '2025-12-30 10:42:51'),
(39, 51, 30, 16, '2026-01-01 17:36:02'),
(40, 52, 30, 16, '2026-01-01 17:36:43'),
(41, 53, 30, 16, '2026-01-01 17:37:10'),
(42, 54, 30, 16, '2026-01-01 17:37:35'),
(43, 55, 30, 16, '2026-01-01 17:40:19'),
(44, 56, 30, 16, '2026-01-01 18:02:16'),
(45, 57, 30, 16, '2026-01-01 18:02:50'),
(46, 58, 30, 16, '2026-01-01 18:03:14'),
(47, 59, 30, 16, '2026-01-01 18:08:52'),
(48, 60, 28, 14, '2026-01-01 18:29:58'),
(49, 61, 28, 14, '2026-01-01 18:31:02'),
(50, 62, 28, 14, '2026-01-01 18:31:43'),
(51, 63, 28, 14, '2026-01-01 18:32:18'),
(52, 64, 28, 14, '2026-01-01 18:32:42'),
(53, 65, 32, NULL, '2026-01-02 03:57:56'),
(54, 66, 32, NULL, '2026-01-02 03:58:29'),
(55, 67, 32, NULL, '2026-01-02 03:58:57'),
(56, 68, 32, NULL, '2026-01-02 03:59:26'),
(57, 69, 32, NULL, '2026-01-02 03:59:52'),
(58, 70, 32, NULL, '2026-01-02 04:00:22'),
(59, 71, 32, NULL, '2026-01-02 04:00:48'),
(60, 72, 32, NULL, '2026-01-02 04:01:21'),
(61, 73, 32, NULL, '2026-01-02 04:01:50'),
(62, 74, 31, 20, '2026-01-02 04:08:30'),
(63, 75, 31, 20, '2026-01-02 04:09:32'),
(64, 76, 31, 20, '2026-01-02 04:10:06'),
(65, 77, 31, 20, '2026-01-02 04:10:39'),
(66, 78, 27, NULL, '2026-01-02 04:24:03'),
(67, 79, 27, NULL, '2026-01-02 04:24:32'),
(68, 80, 27, NULL, '2026-01-02 04:25:07'),
(69, 81, 27, NULL, '2026-01-02 04:25:52'),
(70, 82, 27, NULL, '2026-01-02 04:27:47'),
(71, 83, 27, NULL, '2026-01-02 04:30:18'),
(72, 84, 27, NULL, '2026-01-02 04:30:59'),
(73, 85, 27, NULL, '2026-01-02 04:31:43'),
(74, 86, 27, NULL, '2026-01-02 04:33:40'),
(75, 87, 27, NULL, '2026-01-02 04:35:01'),
(76, 88, 27, NULL, '2026-01-02 04:35:28'),
(77, 89, 34, NULL, '2026-01-02 04:46:24'),
(78, 90, 34, NULL, '2026-01-02 04:46:54'),
(79, 91, 34, NULL, '2026-01-02 04:48:05'),
(80, 92, 34, NULL, '2026-01-02 04:48:40'),
(81, 93, 34, NULL, '2026-01-02 05:13:51');

-- --------------------------------------------------------

--
-- Table structure for table `subject_details`
--

CREATE TABLE `subject_details` (
  `subject_id` int(11) NOT NULL,
  `subject_type` varchar(20) NOT NULL DEFAULT 'regular',
  `elective_category` varchar(20) DEFAULT NULL,
  `elective_number` varchar(50) DEFAULT NULL,
  `theory_hours` int(11) NOT NULL DEFAULT 0,
  `practical_hours` int(11) NOT NULL DEFAULT 0,
  `tutorial_hours` int(11) NOT NULL DEFAULT 0,
  `tutorial_label` varchar(50) NOT NULL DEFAULT 'Practical',
  `practical_label` varchar(50) NOT NULL DEFAULT 'Practical',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subject_details`
--

INSERT INTO `subject_details` (`subject_id`, `subject_type`, `elective_category`, `elective_number`, `theory_hours`, `practical_hours`, `tutorial_hours`, `tutorial_label`, `practical_label`, `created_at`, `updated_at`) VALUES
(13, 'regular', NULL, NULL, 30, 30, 0, 'Practical', 'Practical', '2025-12-30 09:53:25', '2025-12-30 09:53:25'),
(14, 'regular', NULL, NULL, 30, 30, 0, 'Practical', 'Practical', '2025-12-30 09:52:40', '2025-12-30 09:52:40'),
(15, 'regular', NULL, NULL, 30, 30, 0, 'Practical', 'Practical', '2025-12-30 09:44:56', '2025-12-30 09:44:56'),
(16, 'regular', NULL, NULL, 30, 30, 0, 'Practical', 'Practical', '2025-12-30 09:52:50', '2025-12-30 09:52:50'),
(17, 'regular', NULL, NULL, 30, 30, 0, 'Practical', 'Practical', '2025-12-30 09:52:29', '2025-12-30 09:52:29'),
(18, 'regular', NULL, NULL, 30, 30, 0, 'Practical', 'Practical', '2025-12-30 09:48:09', '2025-12-30 09:48:09'),
(19, 'regular', NULL, NULL, 45, 0, 0, 'Practical', 'Practical', '2025-12-30 09:51:10', '2025-12-30 09:51:10'),
(20, 'regular', NULL, NULL, 45, 30, 0, 'Practical', 'Practical', '2025-12-30 09:44:48', '2025-12-30 09:44:48'),
(21, 'regular', NULL, NULL, 45, 15, 0, 'Tutorial', 'Practical', '2025-12-30 09:45:49', '2025-12-30 09:45:49'),
(22, 'regular', NULL, NULL, 45, 30, 0, 'Practical', 'Practical', '2025-11-13 17:00:19', '2025-11-13 17:00:19'),
(23, 'regular', NULL, NULL, 30, 30, 0, 'Practical', 'Practical', '2025-11-13 16:56:13', '2025-11-13 16:56:13'),
(24, 'elective', NULL, NULL, 30, 30, 0, 'Practical', 'Practical', '2025-12-30 09:53:18', '2025-12-30 09:53:18'),
(25, 'elective', NULL, NULL, 60, 0, 0, 'Practical', 'Practical', '2025-11-14 14:06:45', '2025-11-14 14:06:45'),
(26, 'regular', NULL, NULL, 30, 30, 0, 'Practical', 'Practical', '2025-12-30 09:42:32', '2025-12-30 09:42:32'),
(27, 'regular', NULL, NULL, 30, 30, 0, 'Practical', 'Practical', '2025-12-30 09:52:10', '2025-12-30 09:52:10'),
(28, 'regular', NULL, NULL, 30, 30, 0, 'Practical', 'Practical', '2025-12-30 09:44:05', '2025-12-30 09:44:05'),
(29, 'regular', NULL, NULL, 30, 30, 0, 'Practical', 'Practical', '2025-12-30 09:43:47', '2025-12-30 09:43:47'),
(30, 'elective', 'open', 'New', 30, 30, 0, 'Practical', 'Practical', '2025-12-22 02:28:19', '2025-12-22 02:28:19'),
(31, 'regular', NULL, NULL, 30, 30, 0, 'Practical', 'Practical', '2025-12-30 09:42:47', '2025-12-30 09:42:47'),
(32, 'regular', NULL, NULL, 15, 30, 0, 'Practical', 'Practical', '2025-12-30 09:51:36', '2025-12-30 09:51:36'),
(33, 'regular', NULL, NULL, 15, 0, 0, 'Practical', 'Practical', '2025-12-30 09:51:26', '2025-12-30 09:51:26'),
(34, 'regular', NULL, NULL, 0, 30, 0, 'Practical', 'Practical', '2025-12-30 09:52:00', '2025-12-30 09:52:00'),
(35, 'regular', NULL, NULL, 15, 0, 0, 'Practical', 'Practical', '2025-12-30 09:45:17', '2025-12-30 09:45:17'),
(36, 'regular', NULL, NULL, 15, 0, 0, 'Practical', 'Practical', '2025-12-30 09:52:19', '2025-12-30 09:52:19'),
(37, 'regular', NULL, NULL, 45, 0, 0, 'Practical', 'Practical', '2025-12-30 09:51:18', '2025-12-30 09:51:18'),
(38, 'regular', NULL, NULL, 15, 0, 0, 'Practical', 'Practical', '2025-12-30 09:51:51', '2025-12-30 09:51:51'),
(39, 'regular', NULL, NULL, 30, 30, 0, 'Practical', 'Practical', '2025-12-30 09:53:10', '2025-12-30 09:53:10'),
(40, 'regular', NULL, NULL, 30, 15, 0, 'Tutorial', 'Practical', '2025-12-30 09:46:22', '2025-12-30 09:46:22'),
(41, 'regular', NULL, NULL, 45, 0, 0, 'Practical', 'Practical', '2025-12-30 09:52:58', '2025-12-30 09:52:58'),
(42, 'regular', NULL, NULL, 30, 30, 0, 'Practical', 'Practical', '2025-12-30 09:46:12', '2025-12-30 09:46:12'),
(43, 'regular', NULL, NULL, 45, 30, 0, 'Practical', 'Practical', '2025-12-30 09:40:48', '2025-12-30 09:40:48'),
(44, 'regular', NULL, NULL, 30, 30, 0, 'Practical', 'Practical', '2025-12-30 09:41:09', '2025-12-30 09:41:09'),
(45, 'regular', NULL, NULL, 0, 15, 0, 'Tutorial', 'Practical', '2025-12-30 09:41:39', '2025-12-30 09:41:39'),
(46, 'regular', NULL, NULL, 15, 30, 0, 'Practical', 'Practical', '2025-12-30 09:42:11', '2025-12-30 09:42:11'),
(47, 'regular', NULL, NULL, 15, 60, 0, 'Practical', 'Practical', '2025-12-30 10:14:24', '2025-12-30 10:14:24'),
(48, 'regular', NULL, NULL, 45, 30, 0, 'Practical', 'Practical', '2025-12-30 10:14:50', '2025-12-30 10:14:50'),
(49, 'regular', NULL, NULL, 30, 0, 0, 'Practical', 'Practical', '2025-12-30 10:15:23', '2025-12-30 10:15:23'),
(50, 'elective', 'departmental', '4', 30, 30, 0, 'Practical', 'Practical', '2025-12-30 10:42:51', '2025-12-30 10:42:51'),
(51, 'regular', NULL, NULL, 45, 30, 0, 'Practical', 'Practical', '2026-01-01 17:36:02', '2026-01-01 17:36:02'),
(52, 'regular', NULL, NULL, 30, 30, 0, 'Practical', 'Practical', '2026-01-01 17:36:43', '2026-01-01 17:36:43'),
(53, 'regular', NULL, NULL, 30, 0, 0, 'Practical', 'Practical', '2026-01-01 17:37:10', '2026-01-01 17:37:10'),
(54, 'regular', NULL, NULL, 0, 30, 0, 'Practical', 'Practical', '2026-01-01 17:37:35', '2026-01-01 17:37:35'),
(55, 'regular', NULL, NULL, 30, 30, 0, 'Practical', 'Practical', '2026-01-01 17:40:19', '2026-01-01 17:40:19'),
(56, 'regular', NULL, NULL, 0, 30, 30, 'Practical', 'Practical', '2026-01-01 18:02:16', '2026-01-01 18:02:16'),
(57, 'regular', NULL, NULL, 15, 30, 0, 'Practical', 'Practical', '2026-01-01 18:06:37', '2026-01-01 18:06:37'),
(58, 'regular', NULL, NULL, 15, 0, 0, 'Practical', 'Practical', '2026-01-01 18:03:14', '2026-01-01 18:03:14'),
(59, 'regular', NULL, NULL, 0, 30, 0, 'Practical', 'Practical', '2026-01-01 18:08:52', '2026-01-01 18:08:52'),
(60, 'regular', NULL, NULL, 30, 0, 0, 'Practical', 'Practical', '2026-01-01 18:29:58', '2026-01-01 18:29:58'),
(61, 'regular', NULL, NULL, 0, 0, 15, 'Practical', 'Practical', '2026-01-01 18:31:02', '2026-01-01 18:31:02'),
(62, 'regular', NULL, NULL, 30, 0, 0, 'Practical', 'Practical', '2026-01-01 18:31:43', '2026-01-01 18:31:43'),
(63, 'regular', NULL, NULL, 0, 30, 0, 'Practical', 'Practical', '2026-01-01 18:32:18', '2026-01-01 18:32:18'),
(64, 'regular', NULL, NULL, 15, 0, 0, 'Practical', 'Practical', '2026-01-01 18:32:42', '2026-01-01 18:32:42'),
(65, 'regular', NULL, NULL, 45, 0, 15, 'Practical', 'Practical', '2026-01-02 03:57:56', '2026-01-02 03:57:56'),
(66, 'regular', NULL, NULL, 45, 0, 0, 'Practical', 'Practical', '2026-01-02 03:58:29', '2026-01-02 03:58:29'),
(67, 'regular', NULL, NULL, 30, 30, 0, 'Practical', 'Practical', '2026-01-02 03:58:57', '2026-01-02 03:58:57'),
(68, 'regular', NULL, NULL, 30, 30, 0, 'Practical', 'Practical', '2026-01-02 03:59:26', '2026-01-02 03:59:26'),
(69, 'regular', NULL, NULL, 45, 30, 0, 'Practical', 'Practical', '2026-01-02 03:59:52', '2026-01-02 03:59:52'),
(70, 'regular', NULL, NULL, 30, 0, 15, 'Practical', 'Practical', '2026-01-02 04:00:22', '2026-01-02 04:00:22'),
(71, 'regular', NULL, NULL, 30, 30, 0, 'Practical', 'Practical', '2026-01-02 04:00:48', '2026-01-02 04:00:48'),
(72, 'regular', NULL, NULL, 0, 30, 0, 'Practical', 'Practical', '2026-01-02 04:01:21', '2026-01-02 04:01:21'),
(73, 'regular', NULL, NULL, 0, 30, 0, 'Practical', 'Practical', '2026-01-02 04:01:50', '2026-01-02 04:01:50'),
(74, 'regular', NULL, NULL, 30, 30, 0, 'Practical', 'Practical', '2026-01-02 04:08:30', '2026-01-02 04:08:30'),
(75, 'regular', NULL, NULL, 15, 30, 0, 'Practical', 'Practical', '2026-01-02 04:09:32', '2026-01-02 04:09:32'),
(76, 'regular', NULL, NULL, 45, 30, 0, 'Practical', 'Practical', '2026-01-02 04:10:06', '2026-01-02 04:10:06'),
(77, 'regular', NULL, NULL, 15, 30, 0, 'Practical', 'Practical', '2026-01-02 04:10:39', '2026-01-02 04:10:39'),
(78, 'regular', NULL, NULL, 30, 30, 0, 'Practical', 'Practical', '2026-01-02 04:24:03', '2026-01-02 04:24:03'),
(79, 'regular', NULL, NULL, 30, 30, 0, 'Practical', 'Practical', '2026-01-02 04:24:32', '2026-01-02 04:24:32'),
(80, 'regular', NULL, NULL, 30, 30, 0, 'Practical', 'Practical', '2026-01-02 04:25:07', '2026-01-02 04:25:07'),
(81, 'regular', NULL, NULL, 30, 30, 0, 'Practical', 'Practical', '2026-01-02 04:25:52', '2026-01-02 04:25:52'),
(82, 'elective', 'departmental', '3', 30, 30, 0, 'Practical', 'Practical', '2026-01-02 04:27:47', '2026-01-02 04:27:47'),
(83, 'elective', 'departmental', '2', 30, 30, 0, 'Practical', 'Practical', '2026-01-02 04:30:18', '2026-01-02 04:30:18'),
(84, 'elective', 'open', '3', 45, 0, 0, 'Practical', 'Practical', '2026-01-02 04:30:59', '2026-01-02 04:30:59'),
(85, 'elective', 'open', '3', 30, 30, 0, 'Practical', 'Practical', '2026-01-02 04:31:43', '2026-01-02 04:31:43'),
(86, 'elective', 'open', '4', 45, 0, 0, 'Practical', 'Practical', '2026-01-02 04:33:40', '2026-01-02 04:33:40'),
(87, 'elective', 'open', '4', 45, 0, 0, 'Practical', 'Practical', '2026-01-02 04:35:01', '2026-01-02 04:35:01'),
(88, 'regular', NULL, NULL, 0, 30, 0, 'Practical', 'Practical', '2026-01-02 04:35:28', '2026-01-02 04:35:28'),
(89, 'regular', NULL, NULL, 30, 30, 0, 'Practical', 'Practical', '2026-01-02 04:47:39', '2026-01-02 04:47:39'),
(90, 'regular', NULL, NULL, 30, 30, 0, 'Practical', 'Practical', '2026-01-02 04:47:52', '2026-01-02 04:47:52'),
(91, 'regular', NULL, NULL, 30, 30, 0, 'Practical', 'Practical', '2026-01-02 04:48:05', '2026-01-02 04:48:05'),
(92, 'elective', 'departmental', '2', 30, 30, 0, 'Practical', 'Practical', '2026-01-02 05:12:42', '2026-01-02 05:12:42'),
(93, 'elective', 'departmental', '3', 30, 30, 0, 'Practical', 'Practical', '2026-01-02 05:13:51', '2026-01-02 05:13:51');

-- --------------------------------------------------------

--
-- Table structure for table `syllabus_progress`
--

CREATE TABLE `syllabus_progress` (
  `id` int(11) NOT NULL,
  `teacher_id` bigint(20) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `topic` varchar(255) NOT NULL,
  `planned_hours` int(11) NOT NULL,
  `actual_hours` int(11) DEFAULT 0,
  `completion_percentage` int(11) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `timeline` varchar(50) NOT NULL,
  `modules_completed` int(11) NOT NULL DEFAULT 0,
  `extra_classes` decimal(6,2) NOT NULL DEFAULT 0.00,
  `actual_theory_hours` decimal(6,2) NOT NULL DEFAULT 0.00,
  `actual_practical_hours` decimal(6,2) NOT NULL DEFAULT 0.00,
  `extra_theory_hours` decimal(6,2) NOT NULL DEFAULT 0.00,
  `extra_practical_hours` decimal(6,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teacher_classes`
--

CREATE TABLE `teacher_classes` (
  `teacher_id` bigint(20) NOT NULL,
  `class_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_classes`
--

INSERT INTO `teacher_classes` (`teacher_id`, `class_id`) VALUES
(40004481, 21),
(40004481, 24),
(40004481, 25),
(40004481, 26),
(40004481, 28),
(40004481, 30),
(40004481, 32),
(40004483, 21),
(40004483, 23),
(40004483, 24),
(40004483, 27),
(40004483, 28),
(40004483, 30),
(40004483, 34),
(40004494, 21),
(40004494, 25),
(40004494, 26),
(40004494, 27),
(40004494, 29),
(40004494, 31),
(40004495, 23),
(40004495, 24),
(40004495, 28),
(40004495, 30),
(40004495, 31),
(2147483664, 22),
(2147483664, 27),
(2147483664, 29),
(2147483665, 21),
(2147483665, 22),
(2147483665, 26),
(2147483665, 31),
(2147483665, 32),
(2147483665, 34),
(2147483666, 21),
(2147483666, 23),
(2147483666, 26),
(2147483666, 27),
(2147483666, 31),
(2147483666, 32),
(2147483667, 21),
(2147483667, 29),
(2147483667, 30),
(2147483667, 32),
(2147483667, 34),
(2147483668, 21),
(2147483668, 22),
(2147483668, 23),
(2147483668, 26),
(2147483670, 23),
(2147483670, 24),
(2147483670, 28),
(2147483670, 30),
(2147483671, 23),
(2147483671, 24),
(2147483671, 27),
(2147483671, 30),
(2147483671, 32),
(2147483671, 34),
(2147483672, 23),
(2147483673, 23),
(2147483673, 29),
(2147483673, 31),
(2147483673, 32),
(2147483674, 23),
(2147483674, 24),
(2147483674, 27),
(2147483674, 28),
(2147483674, 30),
(2147483674, 34),
(2147483675, 23),
(2147483675, 24),
(2147483675, 27),
(2147483675, 28),
(2147483675, 34),
(2147483676, 22),
(2147483676, 24),
(2147483676, 28),
(2147483676, 30),
(2147483676, 32),
(2147483677, 24),
(2147483678, 24),
(2147483678, 28),
(2147483679, 22),
(2147483679, 25),
(2147483679, 26),
(2147483679, 31),
(2147483679, 34),
(2147483680, 22),
(2147483681, 22),
(2147483681, 27),
(2147483681, 28),
(2147483681, 29),
(2147483681, 30),
(2147483681, 34),
(2147483682, 29),
(2147483683, 29),
(2147483684, 22),
(2147483684, 26),
(2147483684, 27),
(2147483684, 29),
(2147483684, 32),
(2147483684, 34),
(2147483699, 22),
(2147483699, 28),
(2147483699, 30),
(2147483699, 31),
(2147483704, 28),
(2147483705, 30);

-- --------------------------------------------------------

--
-- Table structure for table `teacher_subjects`
--

CREATE TABLE `teacher_subjects` (
  `id` int(11) NOT NULL,
  `teacher_id` bigint(20) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `total_planned_hours` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_subjects`
--

INSERT INTO `teacher_subjects` (`id`, `teacher_id`, `subject_name`, `total_planned_hours`) VALUES
(13, 40004481, 'SE-Software Engineering', 60),
(14, 2147483668, 'EPM-Elememts of Project Management', 45),
(15, 2147483667, 'DC-Distributed Computing', 60),
(16, 40004494, 'CN-Computer Networks', 60),
(17, 40004494, 'MAD-Mobile Application Development', 60),
(18, 2147483665, 'Introduction to Virtual And Augmented Reality ', 60),
(19, 2147483666, 'OS-Operating Systems', 60),
(20, 2147483666, 'CTPS-Computational Thinking For Problem Solving', 75),
(21, 40004495, 'CAL-Calculus', 60),
(22, 2147483670, 'PHY-Physics', 75),
(23, 40004481, 'Capstone Project', 60),
(25, 40004483, 'Elective try', 60),
(26, 2147483679, 'AI-Artificial Intelligence', 60),
(27, 2147483684, 'IVP-Image and Video Processing', 60),
(28, 40004494, 'CC-Cloud Computing', 60),
(29, 2147483679, 'BDA-Big Data Analytics', 60),
(30, 2147483671, 'BEEE-Basic Electrical and Electronics Engineering', 60),
(31, 40004483, 'EGD-Engineering Graphics and Design', 45),
(32, 2147483672, 'EE-Engineering Ethics', 15),
(33, 2147483673, 'EEP-Essential Electronics Practices', 30),
(34, 2147483674, 'COI-Constitution of India', 15),
(35, 2147483674, 'IKS-Indian Knowledge System', 15),
(36, 2147483668, 'EGD-Engineering Graphics and Design', 45),
(37, 2147483675, 'EE-Engineering Ethics', 15),
(38, 40004495, 'CTPS-Computational Thinking For Problem Solving', 75),
(39, 2147483676, 'CAL-Calculus', 60),
(40, 2147483678, 'EOB-Elements of Biology', 45),
(41, 2147483671, 'EEP-Essential Electronics Practices', 30),
(42, 2147483675, 'ES-Environmental Studies', 15),
(43, 40004481, 'CTPS-Computational Thinking For Problem Solving', 75),
(44, 2147483677, 'PHY-Physics', 75),
(45, 2147483664, 'PS-Probability and Statistics', 60),
(46, 2147483682, 'DM-Discrete Mathematics', 45),
(47, 2147483683, 'PEM-Principles of Economics and Management', 45),
(48, 2147483673, 'DLD-Digital Logic Design', 60),
(49, 2147483684, 'DSA-Data Structures and Algorithms', 75),
(50, 2147483681, 'TC-Technical Communication', 15),
(51, 2147483667, 'DEP-Data Extraction and Processing', 45),
(52, 2147483679, 'DW-Data Wrangling', 75),
(53, 2147483668, 'OM-Optimization Methods', 75),
(54, 2147483699, 'DM-Discrete Mathematics', 45),
(55, 2147483665, 'DSA-Data Structures and Algorithms', 75),
(56, 2147483680, 'MAE-Management Accounting for Engineers', 30),
(57, 2147483676, 'DM-Discrete Mathematics', 45),
(58, 40004483, 'PR-PRODUCT REALIZATION', 45),
(59, 2147483676, 'LADE-Linear Algebra and Differential Equations', 75),
(60, 2147483670, 'QP-Quantum Physics', 60),
(61, 40004495, 'OOP-Object Oriented Programming', 30),
(62, 40004495, 'OOPL-Object Oriented Programming Lab', 30),
(63, 2147483667, 'WD-Web Development', 60),
(64, 2147483681, 'EC-English Communication', 30),
(65, 2147483699, 'LADE-Linear Algebra and Differential Equations', 75),
(66, 40004481, 'OOP-Object Oriented Programming', 30),
(67, 40004481, 'OOPL-Object Oriented Programming Lab', 30),
(68, 2147483705, 'PR-PRODUCT REALIZATION', 45),
(69, 2147483704, 'EOB-Elements of Biology(T)', 15),
(70, 2147483674, 'MAE-Management Accounting for Engineers', 30),
(71, 2147483674, 'TII-Transforming Ideas to Innovation', 30),
(72, 2147483675, 'ES-Environmental Science', 15),
(73, 2147483676, 'CVT-Complex Variables and Transforms', 60),
(74, 2147483671, 'COA-Computer Organization and Architecture', 45),
(75, 40004481, 'DAA-Design and Analysis of Algorithms', 60),
(76, 2147483666, 'DBMS-Database Management Systems', 60),
(77, 2147483673, 'MM-Microprocessor and Microcontroller', 75),
(78, 2147483667, 'TCS-Theoretical Computer Science', 45),
(79, 2147483684, 'WP-Web Programming', 60),
(80, 2147483665, 'OOPJ-Object Oriented Programming through JAVA', 30),
(81, 40004495, 'SM-Statistical Methods', 60),
(82, 40004494, 'ML-Machine Learning', 45),
(83, 2147483673, 'IDS&IA-Introduction to Data, Signal, and Image Analysis', 75),
(84, 2147483665, 'WP-Web Programming', 60),
(85, 2147483679, 'DHV-Data Handling and Visualization', 45),
(86, 2147483699, 'SM-Statistical Methods', 60),
(87, 2147483679, 'DBMS-Database Management Systems', 60),
(88, 2147483664, 'AAI-Applied Artificial Intelligence', 60),
(89, 2147483684, 'NNDL-Neural Networks and Deep Learning', 60),
(90, 2147483666, 'ADSA-Advance Data Structure for Analytics', 60),
(91, 2147483664, 'DE2-PA- Predictive Analysis', 60),
(92, 40004494, 'DE3-VCC- Virtualization and Cloud Computing', 60),
(93, 40004483, 'OE3-RM- Research Methodology', 45),
(94, 2147483671, 'OE3-DT-DRONE TECHNOLOGY', 60),
(95, 2147483674, 'OE4-FIMIS - Financial Institutions, Markets, Instruments and Services', 45),
(96, 2147483675, 'OE4-CEM-CREATIVITY AND ETHICS IN MARKETING', 45),
(97, 2147483681, 'IS-Interpersonal Skills', 30),
(98, 2147483665, 'CS-CYBER SECURITY', 60),
(99, 2147483684, 'ML-MACHINE LEARNING', 60),
(100, 2147483684, 'DE2-CV-COMPUTER VISION', 60),
(101, 2147483679, 'DE3-SQA-Software Quality Assurance', 60);

-- --------------------------------------------------------

--
-- Table structure for table `teacher_subject_assignments`
--

CREATE TABLE `teacher_subject_assignments` (
  `id` int(11) NOT NULL,
  `teacher_id` bigint(20) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `section_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_subject_assignments`
--

INSERT INTO `teacher_subject_assignments` (`id`, `teacher_id`, `subject_id`, `class_id`, `section_id`) VALUES
(8, 2147483666, 20, 23, 9),
(9, 40004495, 21, 23, 9),
(10, 2147483670, 22, 23, 9),
(11, 40004481, 23, 25, NULL),
(12, 40004481, 13, 21, NULL),
(13, 40004481, 13, 26, NULL),
(14, 40004494, 15, 21, NULL),
(15, 2147483665, 17, 21, NULL),
(16, 2147483665, 17, 26, NULL),
(17, 2147483666, 16, 26, NULL),
(18, 40004494, 14, 26, NULL),
(19, 2147483668, 19, 21, NULL),
(20, 2147483668, 19, 26, NULL),
(21, 2147483666, 16, 21, NULL),
(22, 40004494, 14, 21, NULL),
(25, 2147483667, 18, 21, NULL),
(26, 2147483679, 26, 26, NULL),
(27, 2147483684, 27, 26, NULL),
(28, 40004494, 28, 25, NULL),
(29, 2147483679, 29, 25, NULL),
(30, 2147483671, 31, 23, 9),
(31, 40004483, 32, 23, 9),
(32, 2147483672, 33, 23, 9),
(33, 2147483673, 34, 23, 9),
(34, 2147483674, 35, 23, 9),
(35, 2147483674, 36, 23, 9),
(36, 2147483666, 20, 23, 10),
(37, 40004495, 21, 23, 10),
(38, 2147483670, 22, 23, 10),
(39, 2147483671, 31, 23, 10),
(40, 2147483668, 32, 23, 10),
(41, 2147483675, 33, 23, 10),
(42, 2147483673, 34, 23, 10),
(43, 2147483674, 35, 23, 10),
(44, 2147483674, 36, 23, 10),
(45, 40004495, 20, 24, 11),
(46, 2147483676, 21, 24, 11),
(47, 2147483670, 22, 24, 11),
(48, 2147483678, 37, 24, 11),
(49, 40004483, 32, 24, 11),
(50, 2147483675, 33, 24, 11),
(51, 2147483671, 34, 24, 11),
(52, 2147483675, 38, 24, 11),
(53, 2147483674, 36, 24, 11),
(54, 40004481, 20, 24, 12),
(55, 2147483676, 21, 24, 12),
(56, 2147483677, 22, 24, 12),
(57, 2147483678, 37, 24, 12),
(58, 40004483, 32, 24, 12),
(59, 2147483675, 33, 24, 12),
(60, 2147483671, 34, 24, 12),
(61, 2147483675, 38, 24, 12),
(62, 2147483674, 36, 24, 12),
(63, 2147483664, 39, 29, NULL),
(64, 2147483682, 40, 29, NULL),
(65, 2147483683, 41, 29, NULL),
(66, 2147483673, 42, 29, NULL),
(67, 2147483684, 43, 29, NULL),
(68, 2147483681, 45, 29, NULL),
(69, 40004494, 44, 29, NULL),
(70, 2147483667, 46, 29, NULL),
(71, 2147483664, 39, 22, 13),
(72, 2147483679, 47, 22, 13),
(73, 2147483668, 48, 22, 13),
(74, 2147483699, 40, 22, 13),
(75, 2147483665, 43, 22, 13),
(76, 2147483680, 49, 22, 13),
(77, 2147483681, 45, 22, 13),
(78, 2147483664, 39, 22, 8),
(79, 2147483679, 47, 22, 8),
(80, 2147483668, 48, 22, 8),
(81, 2147483676, 40, 22, 8),
(82, 2147483684, 43, 22, 8),
(83, 2147483680, 49, 22, 8),
(84, 2147483681, 45, 22, 8),
(85, 40004483, 57, 30, 16),
(86, 2147483676, 51, 30, 16),
(87, 2147483670, 52, 30, 16),
(88, 40004495, 53, 30, 16),
(89, 40004495, 54, 30, 16),
(90, 2147483671, 55, 30, 16),
(91, 2147483667, 56, 30, 16),
(92, 2147483674, 58, 30, 16),
(93, 2147483681, 59, 30, 16),
(94, 2147483699, 51, 30, 17),
(95, 2147483670, 52, 30, 17),
(96, 40004481, 53, 30, 17),
(97, 40004481, 54, 30, 17),
(98, 2147483671, 55, 30, 17),
(99, 2147483667, 56, 30, 17),
(100, 2147483674, 58, 30, 17),
(101, 2147483681, 59, 30, 17),
(102, 2147483705, 57, 30, 17),
(103, 2147483676, 51, 28, 14),
(104, 2147483670, 52, 28, 14),
(105, 2147483678, 60, 28, 14),
(106, 2147483704, 61, 28, 14),
(107, 2147483674, 62, 28, 14),
(108, 40004481, 53, 28, 14),
(109, 40004481, 54, 28, 14),
(110, 40004483, 57, 28, 14),
(111, 2147483674, 63, 28, 14),
(112, 2147483681, 59, 28, 14),
(113, 2147483675, 64, 28, 14),
(114, 2147483699, 51, 28, 15),
(115, 2147483670, 52, 28, 15),
(116, 2147483678, 60, 28, 15),
(117, 2147483704, 61, 28, 15),
(118, 2147483674, 62, 28, 15),
(119, 40004495, 53, 28, 15),
(120, 40004495, 54, 28, 15),
(121, 40004483, 57, 28, 15),
(122, 2147483674, 63, 28, 15),
(123, 2147483681, 59, 28, 15),
(124, 2147483675, 64, 28, 15),
(125, 2147483676, 65, 32, NULL),
(126, 2147483671, 66, 32, NULL),
(127, 40004481, 67, 32, NULL),
(128, 2147483666, 68, 32, NULL),
(129, 2147483673, 69, 32, NULL),
(130, 2147483667, 70, 32, NULL),
(131, 2147483684, 71, 32, NULL),
(132, 2147483665, 72, 32, NULL),
(133, 40004495, 74, 31, 20),
(134, 40004494, 75, 31, 20),
(135, 2147483673, 76, 31, 20),
(136, 2147483666, 68, 31, 20),
(137, 2147483665, 71, 31, 20),
(138, 2147483679, 77, 31, 20),
(139, 2147483699, 74, 31, 21),
(140, 40004494, 75, 31, 21),
(141, 2147483673, 76, 31, 21),
(142, 2147483679, 68, 31, 21),
(143, 2147483665, 71, 31, 21),
(144, 2147483679, 77, 31, 21),
(145, 2147483664, 78, 27, NULL),
(146, 2147483684, 79, 27, NULL),
(147, 2147483666, 80, 27, NULL),
(148, 2147483664, 83, 27, NULL),
(149, 40004494, 82, 27, NULL),
(150, 40004483, 84, 27, NULL),
(151, 2147483671, 85, 27, NULL),
(152, 2147483674, 86, 27, NULL),
(153, 2147483675, 87, 27, NULL),
(154, 2147483681, 88, 27, NULL),
(155, 2147483665, 89, 34, NULL),
(156, 2147483684, 90, 34, NULL),
(157, 2147483667, 91, 34, NULL),
(158, 2147483684, 92, 34, NULL),
(159, 2147483679, 93, 34, NULL),
(160, 40004483, 84, 34, NULL),
(161, 2147483671, 85, 34, NULL),
(162, 2147483674, 86, 34, NULL),
(163, 2147483675, 87, 34, NULL),
(164, 2147483681, 88, 34, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `timetables`
--

CREATE TABLE `timetables` (
  `id` int(11) NOT NULL,
  `teacher_id` bigint(20) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timetables`
--

INSERT INTO `timetables` (`id`, `teacher_id`, `file_name`, `file_path`, `uploaded_at`) VALUES
(1, 40004495, 'x.jpg', 'uploads/40004495_1754812418.jpg', '2025-08-10 07:53:38');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('teacher','program_chair','admin','student') NOT NULL,
  `email` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `teacher_unique_id` varchar(64) DEFAULT NULL,
  `school` varchar(100) DEFAULT NULL,
  `status` varchar(10) NOT NULL DEFAULT 'active',
  `department` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `email`, `name`, `teacher_unique_id`, `school`, `status`, `department`) VALUES
(40004481, 'Bhanu_sree', '$2y$10$iSxgmV1GeE/GiKymIpBJYO1zpVND.mOp.Soj16esYhTZqwylNYR0m', 'teacher', 'bhanu.sree@nmims.edu', 'Dr.Bhanu Sree', '40004803', 'STME', 'active', NULL),
(40004482, 'Annaji', 'e10adc3949ba59abbe56e057f20f883e', 'admin', '', 'Annaji Rao', '40004482', 'STME', 'active', NULL),
(40004483, 'Chandrakant_Wani', '$2y$10$iuPBMP.5qVVz0aGa53ijteas9FfRe1g1KC3Y8gV7X6jvA/iN4ehyO', 'program_chair', 'Chandrakant.Wani@nmims.edu', 'Prof.Chandrakant Wani', '32100443', 'STME', 'active', NULL),
(40004485, 'Raja_Govind', '$2y$10$MBRC1rtU2RaVlyI0ZZdG5eXP.mcsvj4RyxaPCi0/JhOzOLScMvr2S', 'admin', '', 'Raja Govind', '40004485', 'STME', 'active', NULL),
(40004494, 'Naresh_Vurukonda', 'e10adc3949ba59abbe56e057f20f883e', 'teacher', 'naresh.vurukonda@nmims.edu', 'Dr.Naresh Vurukonda', '40004479', 'STME', 'active', NULL),
(40004495, 'Vidyasagar', 'e10adc3949ba59abbe56e057f20f883e', 'teacher', 'vidya.sagar@nmims.edu', 'Dr.Vidyasagar', '40002404', 'STME', 'active', NULL),
(2147483664, 'Rajesh_Prabhakar', 'e10adc3949ba59abbe56e057f20f883e', 'teacher', 'rajesh.prabhakar@gmail.com', 'Prof. Rajesh Prabhakar', '51434700', 'STME', 'active', NULL),
(2147483665, 'Vinayak_Mukkawar', 'e10adc3949ba59abbe56e057f20f883e', 'teacher', 'vinayak.mukkawar@nmims.edu', 'Prof. Vinayak Mukkawar', '40002405', 'STME', 'active', NULL),
(2147483666, 'Wasiha_Tasneem', 'e10adc3949ba59abbe56e057f20f883e', 'teacher', 'tasneem.wasiha@nmims.edu', 'Prof. Wasiha Tasneem', '40002868', 'STME', 'active', NULL),
(2147483667, 'Ramesh_Munipala', 'e10adc3949ba59abbe56e057f20f883e', 'teacher', 'ramesh.munipala@nmims.edu', 'Dr. Ramesh Munipala', '40005786', 'STME', 'active', NULL),
(2147483668, 'Pradeep_M', 'e10adc3949ba59abbe56e057f20f883e', 'teacher', 'pradeepjnkr@gmail.com', 'Dr. Pradeep Kumar', '40001878', 'STME', 'active', NULL),
(2147483670, 'Soumyajith_Seth', 'e10adc3949ba59abbe56e057f20f883e', 'teacher', 'soumyajit.seth@nmims.edu', 'Dr. Soumyajith Seth	', '40003711', 'STME', 'active', NULL),
(2147483671, 'Rahul_Koshti', 'e10adc3949ba59abbe56e057f20f883e', 'teacher', 'Rahul.Koshti@nmims.edu', 'Dr. Rahul Koshti	', '32100424', 'STME', 'active', NULL),
(2147483672, 'Surendar_Gade', 'e10adc3949ba59abbe56e057f20f883e', 'teacher', 'surendar.gade@nmims.edu', 'Dr. Surendar Gade', '40001903', 'SOC', 'active', NULL),
(2147483673, 'Uday_Panwar', 'e10adc3949ba59abbe56e057f20f883e', 'teacher', 'uday.panwar@nmims.edu', 'Dr. Uday Panwar	', '40005572', 'STME', 'active', NULL),
(2147483674, 'Mubashir_Hassan', 'e10adc3949ba59abbe56e057f20f883e', 'teacher', 'Mubashir.Hassan@nmims.edu', 'Dr. Mubashir Hassan	', '40004014', 'SOC', 'active', NULL),
(2147483675, 'Akshita_Dwivedi', 'e10adc3949ba59abbe56e057f20f883e', 'teacher', 'akshita.dwivedi@nmims.edu', 'Dr. Akshita Dwivedi	', '40005562', 'SOC', 'active', NULL),
(2147483676, 'Dibakar_Dey', 'e10adc3949ba59abbe56e057f20f883e', 'teacher', 'dibakar.dey@nmims.edu', 'Dr. Dibakar Dey	', '40005592', 'STME', 'active', NULL),
(2147483677, 'Sumalatha', 'e10adc3949ba59abbe56e057f20f883e', 'teacher', 'sumalathab82@gmail.com', 'Dr. Sumalatha	', '40001908', 'STME', 'active', NULL),
(2147483678, 'Sharon Blessy', 'e10adc3949ba59abbe56e057f20f883e', 'teacher', 'sharon.blessy@nmims.edu', 'Prof. Sharon Blessy	', '40002434', 'SPTM', 'active', NULL),
(2147483679, 'Anshi_Bajaj', 'e10adc3949ba59abbe56e057f20f883e', 'teacher', 'anshi.bajaj@nmims.edu', 'Prof. Anshi Bajaj	', '40005825', 'STME', 'active', NULL),
(2147483680, 'Praveen_Kumar_Gandra', 'e10adc3949ba59abbe56e057f20f883e', 'teacher', 'praveenkumar.gandra@nmims.edu', 'Dr. Praveen Kumar Gandra	', '40001911', 'SOC', 'active', NULL),
(2147483681, 'Rohith', 'e10adc3949ba59abbe56e057f20f883e', 'teacher', 'rohith.rocharla@nmims.edu', 'Dr. Rocharla Rohith	', '40005593', 'STME', 'active', NULL),
(2147483682, 'Upendar_M', 'e10adc3949ba59abbe56e057f20f883e', 'teacher', 'upendar@gmail.com', 'Dr. Upendar M	', '40001913', 'STME', 'active', NULL),
(2147483683, 'Shadman_Zafar', 'e10adc3949ba59abbe56e057f20f883e', 'teacher', 'Shadman.Zafar@nmims.edu', 'Dr. Shadman Zafar	', '40004497', 'SBM', 'active', NULL),
(2147483684, 'Nikita_Pande', 'e10adc3949ba59abbe56e057f20f883e', 'teacher', 'nikita.pande@nmims.edu', 'Prof. Nikita Pande	', '40005591', 'STME', 'active', NULL),
(2147483685, 'Manabhanjan_Sahu', 'e10adc3949ba59abbe56e057f20f883e', 'teacher', 'Manabhanjan.Sahu@nmims.edu', 'Dr. Manabhanjan Sahu	', '40001916', 'SPTM', 'active', NULL),
(2147483686, 'NMS_Desai', 'e10adc3949ba59abbe56e057f20f883e', 'teacher', 'desai@gmail.com', 'NMS Desai', '40001918', 'STME', 'active', NULL),
(2147483696, '70572300033', '$2y$10$jwUUpbn788dFvZ/b2.ykQ.M.1Jdv1vNuJ3mlwLAaEanu5bWc89Teu', 'student', 'md.rayyan33@nmims.in', 'MD RAYYAN', NULL, NULL, 'active', NULL),
(2147483697, '70022300461', 'e10adc3949ba59abbe56e057f20f883e', 'student', 'zeeshan@nmims.in', 'ZEESHAN ALI', NULL, NULL, 'active', NULL),
(2147483698, '70572300021', 'e10adc3949ba59abbe56e057f20f883e', 'student', '', 'BRUNGI SHIVA GANESH', NULL, NULL, 'active', NULL),
(2147483699, 'Amit_Saini ', 'e10adc3949ba59abbe56e057f20f883e', 'teacher', 'amit.saini@nmims.edu', 'Dr. Amit Kumar Saini ', '40005641', 'STME', 'active', NULL),
(2147483702, '70572300034', 'e10adc3949ba59abbe56e057f20f883e', 'student', 'kuchurusai.krishna34@nmims.in', 'SAI KRISHNA REDDY KUCHURU', NULL, NULL, 'active', NULL),
(2147483704, 'Neha_Maheshwari', 'e10adc3949ba59abbe56e057f20f883e', 'teacher', 'neha.maheshwari@nmims.edu', 'Prof. Neha Maheshwari', '40001778', 'SPTM', 'active', NULL),
(2147483705, 'Ravi_H', 'e10adc3949ba59abbe56e057f20f883e', 'teacher', 'ravihanmanthu@gmail.com', 'Dr. Ravi H', '53337868', 'STME', 'active', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_calendar`
--
ALTER TABLE `academic_calendar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_calendar_entry` (`school_name`,`semester_term`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_actor_id` (`actor_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_target_user_id` (`target_user_id`),
  ADD KEY `idx_object` (`object_type`,`object_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `alerts`
--
ALTER TABLE `alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `class_timetables`
--
ALTER TABLE `class_timetables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_class_id` (`class_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ica_components`
--
ALTER TABLE `ica_components`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `idx_class_id` (`class_id`);

--
-- Indexes for table `ica_marks`
--
ALTER TABLE `ica_marks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_teacher_timeline` (`teacher_id`,`timeline`,`student_name`,`component_type`);

--
-- Indexes for table `ica_student_marks`
--
ALTER TABLE `ica_student_marks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_mark` (`teacher_id`,`student_id`,`component_id`,`instance_number`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `component_id` (`component_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `token` (`token`(64));

--
-- Indexes for table `schools`
--
ALTER TABLE `schools`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `school_name` (`school_name`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_setting_unique` (`user_id`,`setting_key`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_students_sap_class` (`sap_id`,`class_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `section_id` (`section_id`);

--
-- Indexes for table `student_assignments`
--
ALTER TABLE `student_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assignment_id` (`assignment_id`),
  ADD KEY `student_assignments_ibfk_1` (`student_id`);

--
-- Indexes for table `student_elective_choices`
--
ALTER TABLE `student_elective_choices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_student_class_subject` (`student_id`,`class_id`,`subject_id`),
  ADD KEY `idx_subject` (`subject_id`),
  ADD KEY `idx_class` (`class_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subject_class_map`
--
ALTER TABLE `subject_class_map`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_subject` (`subject_id`),
  ADD KEY `idx_class` (`class_id`);

--
-- Indexes for table `subject_details`
--
ALTER TABLE `subject_details`
  ADD PRIMARY KEY (`subject_id`);

--
-- Indexes for table `syllabus_progress`
--
ALTER TABLE `syllabus_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_progress` (`teacher_id`,`subject`,`timeline`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `teacher_classes`
--
ALTER TABLE `teacher_classes`
  ADD PRIMARY KEY (`teacher_id`,`class_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `teacher_subject_assignments`
--
ALTER TABLE `teacher_subject_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `section_id` (`section_id`);

--
-- Indexes for table `timetables`
--
ALTER TABLE `timetables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `ux_users_teacher_unique_id` (`teacher_unique_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_calendar`
--
ALTER TABLE `academic_calendar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT for table `alerts`
--
ALTER TABLE `alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `class_timetables`
--
ALTER TABLE `class_timetables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `ica_components`
--
ALTER TABLE `ica_components`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `ica_marks`
--
ALTER TABLE `ica_marks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=802;

--
-- AUTO_INCREMENT for table `ica_student_marks`
--
ALTER TABLE `ica_student_marks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1617;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `schools`
--
ALTER TABLE `schools`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1000000959;

--
-- AUTO_INCREMENT for table `student_assignments`
--
ALTER TABLE `student_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `student_elective_choices`
--
ALTER TABLE `student_elective_choices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=158;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `subject_class_map`
--
ALTER TABLE `subject_class_map`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `syllabus_progress`
--
ALTER TABLE `syllabus_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102;

--
-- AUTO_INCREMENT for table `teacher_subject_assignments`
--
ALTER TABLE `teacher_subject_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=165;

--
-- AUTO_INCREMENT for table `timetables`
--
ALTER TABLE `timetables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2147483708;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `alerts`
--
ALTER TABLE `alerts`
  ADD CONSTRAINT `alerts_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `class_timetables`
--
ALTER TABLE `class_timetables`
  ADD CONSTRAINT `fk_class_timetables_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ica_components`
--
ALTER TABLE `ica_components`
  ADD CONSTRAINT `ica_components_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ica_components_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ica_student_marks`
--
ALTER TABLE `ica_student_marks`
  ADD CONSTRAINT `ica_student_marks_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ica_student_marks_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ica_student_marks_ibfk_3` FOREIGN KEY (`component_id`) REFERENCES `ica_components` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sections`
--
ALTER TABLE `sections`
  ADD CONSTRAINT `fk_class_id` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sections_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `settings`
--
ALTER TABLE `settings`
  ADD CONSTRAINT `settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_student_section` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`);

--
-- Constraints for table `student_assignments`
--
ALTER TABLE `student_assignments`
  ADD CONSTRAINT `student_assignments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_assignments_ibfk_2` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `syllabus_progress`
--
ALTER TABLE `syllabus_progress`
  ADD CONSTRAINT `syllabus_progress_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_classes`
--
ALTER TABLE `teacher_classes`
  ADD CONSTRAINT `teacher_classes_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_classes_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD CONSTRAINT `teacher_subjects_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_subject_assignments`
--
ALTER TABLE `teacher_subject_assignments`
  ADD CONSTRAINT `teacher_subject_assignments_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_subject_assignments_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_subject_assignments_ibfk_3` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_subject_assignments_ibfk_4` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `timetables`
--
ALTER TABLE `timetables`
  ADD CONSTRAINT `timetables_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
