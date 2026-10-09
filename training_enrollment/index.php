<?php
// index.php
$base = '';
require_once __DIR__ . '/includes/header.php';
?>
<h1>Training Enrollment System</h1>
<p>Welcome, Administrator. Use this system to manage training courses and
class schedules, record students, enroll them into classes, and view reports.</p>
<ul>
    <li><a href="admin/courses.php">Manage Courses</a></li>
    <li><a href="admin/classes.php">Manage Classes</a></li>
    <li><a href="admin/students.php">Record a New Student</a></li>
    <li><a href="admin/enroll.php">Enroll an Existing Student</a></li>
    <li><a href="admin/enrollments.php">View and Cancel Enrollments</a></li>
    <li><a href="admin/reports.php">Reports</a></li>
</ul>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
