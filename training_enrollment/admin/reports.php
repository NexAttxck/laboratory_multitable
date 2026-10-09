<?php
// admin/reports.php -- Summary report (LEFT JOIN + GROUP BY)
$base = '../';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/EnrollmentRepository.php';

$repo = new EnrollmentRepository($db);
$rows = $repo->summaryReport();

require_once __DIR__ . '/../includes/header.php';
?>
<h1>Reports</h1>
<p>Active and cancelled enrollments per class, with remaining slots.</p>
<table>
    <tr><th>Course</th><th>Class</th><th>Active</th><th>Cancelled</th><th>Remaining Slots</th></tr>
    <?php foreach ($rows as $r) { ?>
    <tr>
        <td><?php echo Esc($r['course_code'] . ' - ' . $r['course_name']); ?></td>
        <td><?php echo $r['class_code'] === null ? '(no class yet)' : Esc($r['class_code']); ?></td>
        <td><?php echo (int) $r['active_count']; ?></td>
        <td><?php echo (int) $r['cancelled_count']; ?></td>
        <td><?php echo $r['remaining'] === null ? '-' : (int) $r['remaining']; ?></td>
    </tr>
    <?php } ?>
</table>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
