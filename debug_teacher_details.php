<?php
session_id('debug');
session_start();
$_SESSION['user_id'] = 40004483;
$_SESSION['role'] = 'program_chair';
$_GET['action'] = 'get_teacher_details';
$_GET['id'] = 40004481; // Dr.Bhanu Sree
include 'program_reports.php';
