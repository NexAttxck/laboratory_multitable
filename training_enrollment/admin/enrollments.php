<?php
// admin/enrollments.php -- List enrollments (JOINs), search, filter, paginate, cancel
$base = '../';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/EnrollmentRepository.php';

$repo = new EnrollmentRepository($db);
$msg = '';
$err = '';

// ---- Cancel (POST, because it changes data) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel')
{
    $enrollment_id = (int) ($_POST['enrollment_id'] ?? 0);
    if ($enrollment_id <= 0)
    {
        $err = "Invalid enrollment.";
    }
    else
    {
        try
        {
            $repo->cancel($enrollment_id);
            $msg = "Enrollment cancelled and the slot was restored.";
        }
        catch (EnrollmentException $e)
        {
            $err = $e->getMessage();
        }
        catch (Exception $e)
        {
            $err = "Something went wrong. Nothing was changed.";
        }
    }
}

// ---- Search, filter, pagination (from the URL) ----
$search = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
if ($status !== 'active' && $status !== 'cancelled')
{
    $status = '';
}
$perPage = 10;
$page = max(1, (int) ($_GET['page'] ?? 1));

$total = $repo->countWithDetails($search, $status);
$pages = max(1, (int) ceil($total / $perPage));
if ($page > $pages)
{
    $page = $pages;
}
$rows = $repo->allWithDetails($search, $status, $perPage, ($page - 1) * $perPage);

require_once __DIR__ . '/../includes/header.php';
?>
<h1>Enrollments</h1>

<?php if ($msg !== '') { ?><div class="ok" role="status"><?php echo Esc($msg); ?></div><?php } ?>
<?php if ($err !== '') { ?><div class="error" role="alert"><?php echo Esc($err); ?></div><?php } ?>

<form method="get">
    <input type="text" name="q" placeholder="Search student, course or class" value="<?php echo Esc($search); ?>" style="width:260px">
    <select name="status" style="width:140px">
        <option value="">All status</option>
        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
        <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
    </select>
    <button type="submit">Filter</button>
</form>

<table>
    <tr>
        <th>ID</th><th>Student</th><th>Email</th><th>Course</th><th>Class</th>
        <th>Schedule</th><th>Date</th><th>Status</th><th>Action</th>
    </tr>
    <?php if (empty($rows)) { ?>
    <tr><td colspan="9">No enrollments found.</td></tr>
    <?php } ?>
    <?php foreach ($rows as $r) { ?>
    <tr>
        <td><?php echo (int) $r['enrollment_id']; ?></td>
        <td><?php echo Esc($r['full_name']); ?></td>
        <td><?php echo Esc($r['email']); ?></td>
        <td><?php echo Esc($r['course_name']); ?></td>
        <td><?php echo Esc($r['class_code']); ?></td>
        <td><?php echo Esc($r['schedule']); ?></td>
        <td><?php echo Esc($r['enrollment_date']); ?></td>
        <td><?php echo Esc($r['status']); ?></td>
        <td>
            <?php if ($r['status'] === 'active') { ?>
            <form method="post" class="inline" data-confirm="Cancel this enrollment?" onsubmit="return confirm('Cancel this enrollment?');">
                <input type="hidden" name="action" value="cancel">
                <input type="hidden" name="enrollment_id" value="<?php echo (int) $r['enrollment_id']; ?>">
                <button type="submit" class="danger">Cancel</button>
            </form>
            <?php } else { echo '-'; } ?>
        </td>
    </tr>
    <?php } ?>
</table>

<p class="pager">
    Page <?php echo $page; ?> of <?php echo $pages; ?> (<?php echo $total; ?> records)
    <?php
    // build links that keep the search and filter values
    for ($i = 1; $i <= $pages; $i++)
    {
        $qs = http_build_query(['q' => $search, 'status' => $status, 'page' => $i]);
        echo $i === $page ? "<strong>[$i]</strong> " : "<a href=\"enrollments.php?$qs\">$i</a> ";
    }
    ?>
</p>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
