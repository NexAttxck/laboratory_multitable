<?php
// admin/classes.php -- Manage class schedules and slots
$base = '../';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Course.php';
require_once __DIR__ . '/../classes/ClassSection.php';

$courses  = new Course($db);
$sections = new ClassSection($db);
$msg = '';
$err = '';
$form = ['course_id' => 0, 'class_code' => '', 'schedule' => '', 'instructor' => '', 'slots' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $form['course_id']  = (int) ($_POST['course_id'] ?? 0);
    $form['class_code'] = trim($_POST['class_code'] ?? '');
    $form['schedule']   = trim($_POST['schedule'] ?? '');
    $form['instructor'] = trim($_POST['instructor'] ?? '');
    $form['slots']      = trim($_POST['slots'] ?? '');

    // ---- Validation ----
    $errors = [];
    if ($form['course_id'] <= 0 || !$courses->find($form['course_id']))
    {
        $errors[] = "Please choose a valid course.";
    }
    if ($form['class_code'] === '' || strlen($form['class_code']) > 20)
    {
        $errors[] = "Class code is required (max 20 characters).";
    }
    if (strlen($form['schedule']) > 100 || strlen($form['instructor']) > 100)
    {
        $errors[] = "Schedule and instructor must be 100 characters or less.";
    }
    // slots must be a whole number, 0 or more
    $slots = filter_var($form['slots'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    if ($slots === false)
    {
        $errors[] = "Slots must be a whole number (0 or more).";
    }

    if (empty($errors))
    {
        try
        {
            $sections->create($form['course_id'], $form['class_code'],
                $form['schedule'], $form['instructor'], $slots);
            $msg = "Class added.";
            $form = ['course_id' => 0, 'class_code' => '', 'schedule' => '', 'instructor' => '', 'slots' => ''];
        }
        catch (PDOException $e)
        {
            $err = "Database error. Please try again.";
        }
    }
    else
    {
        $err = implode(' ', $errors);
    }
}

$courseList = $courses->all();
$list = $sections->allWithCourse();
require_once __DIR__ . '/../includes/header.php';
?>
<h1>Classes</h1>

<?php if ($msg !== '') { ?><div class="ok" role="status"><?php echo Esc($msg); ?></div><?php } ?>
<?php if ($err !== '') { ?><div class="error" role="alert"><?php echo Esc($err); ?></div><?php } ?>

<form method="post" class="box">
    <h3>Add Class</h3>
    <label>Course</label>
    <select name="course_id">
        <option value="0">-- select course --</option>
        <?php foreach ($courseList as $c) { ?>
        <option value="<?php echo (int) $c['course_id']; ?>"
            <?php echo ((int) $form['course_id'] === (int) $c['course_id']) ? 'selected' : ''; ?>>
            <?php echo Esc($c['course_code'] . ' - ' . $c['course_name']); ?>
        </option>
        <?php } ?>
    </select>

    <label>Class Code</label>
    <input type="text" name="class_code" maxlength="20" value="<?php echo Esc($form['class_code']); ?>">

    <label>Schedule</label>
    <input type="text" name="schedule" maxlength="100" value="<?php echo Esc($form['schedule']); ?>">

    <label>Instructor</label>
    <input type="text" name="instructor" maxlength="100" value="<?php echo Esc($form['instructor']); ?>">

    <label>Available Slots</label>
    <input type="number" name="slots" min="0" value="<?php echo Esc($form['slots']); ?>">

    <button type="submit">Add Class</button>
</form>

<table>
    <tr><th>ID</th><th>Course</th><th>Class Code</th><th>Schedule</th><th>Instructor</th><th>Remaining Slots</th></tr>
    <?php foreach ($list as $c) { ?>
    <tr>
        <td><?php echo (int) $c['class_id']; ?></td>
        <td><?php echo Esc($c['course_name']); ?></td>
        <td><?php echo Esc($c['class_code']); ?></td>
        <td><?php echo Esc($c['schedule']); ?></td>
        <td><?php echo Esc($c['instructor']); ?></td>
        <td><?php echo (int) $c['slots']; ?></td>
    </tr>
    <?php } ?>
</table>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
