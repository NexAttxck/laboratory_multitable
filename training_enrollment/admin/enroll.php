<?php
// admin/enroll.php -- Enroll an EXISTING student into a class
$base = '../';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/EnrollmentRepository.php';
require_once __DIR__ . '/../classes/Student.php';
require_once __DIR__ . '/../classes/ClassSection.php';

$repo = new EnrollmentRepository($db);
$students = new Student($db);
$classes = new ClassSection($db);

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $student_id = (int) ($_POST['student_id'] ?? 0);
    $class_id   = (int) ($_POST['class_id'] ?? 0);

    // ---- Validation ----
    if ($student_id <= 0 || $class_id <= 0)
    {
        $err = "Please select both a student and a class.";
    }
    else
    {
        try
        {
            $id = $repo->enroll($student_id, $class_id);
            $msg = "Enrollment successful (Enrollment #" . (int) $id . ").";
        }
        catch (EnrollmentException $e)
        {
            $err = $e->getMessage();
        }
        catch (Exception $e)
        {
            $err = "Something went wrong. Nothing was saved. Please try again.";
        }
    }
}

$studentList = $students->all();
$classList = $classes->allWithCourse();
require_once __DIR__ . '/../includes/header.php';
?>
<h1>Enroll Existing Student</h1>

<?php if ($msg !== '') { ?><div class="ok" role="status"><?php echo Esc($msg); ?></div><?php } ?>
<?php if ($err !== '') { ?><div class="error" role="alert"><?php echo Esc($err); ?></div><?php } ?>

<form method="post" class="box">
    <label>Student</label>
    <select name="student_id">
        <option value="0">-- select student --</option>
        <?php foreach ($studentList as $s) { ?>
        <option value="<?php echo (int) $s['student_id']; ?>"><?php echo Esc($s['full_name']); ?></option>
        <?php } ?>
    </select>

    <label>Class</label>
    <select name="class_id">
        <option value="0">-- select class --</option>
        <?php foreach ($classList as $c) { ?>
        <option value="<?php echo (int) $c['class_id']; ?>">
            <?php
            echo Esc($c['course_name'] . ' | ' . $c['class_code'] . ' | ' . $c['schedule']);
            echo ((int) $c['slots'] > 0) ? ' (' . (int) $c['slots'] . ' slots left)' : ' (FULL)';
            ?>
        </option>
        <?php } ?>
    </select>

    <button type="submit">Enroll</button>
</form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
