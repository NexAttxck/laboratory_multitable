<?php
// admin/students.php -- Student recording form
$base = '../';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/EnrollmentRepository.php';
require_once __DIR__ . '/../classes/ClassSection.php';

$repo = new EnrollmentRepository($db);
$classes = new ClassSection($db);

$msg = '';
$err = '';
$old = ['full_name' => '', 'email' => '', 'phone' => '', 'class_id' => 0];

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $class_id  = (int) ($_POST['class_id'] ?? 0);

    // Keep what the user typed so the form is not wiped on error
    $old = ['full_name' => $full_name, 'email' => $email, 'phone' => $phone, 'class_id' => $class_id];

    // ---- Validation ----
    $errors = [];
    if ($full_name === '' || strlen($full_name) > 100)
    {
        $errors[] = "Full name is required (max 100 characters).";
    }
    if ($email !== '' && (strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)))
    {
        $errors[] = "Email format is not valid.";
    }
    if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,30}$/', $phone))
    {
        $errors[] = "Phone may only contain digits, spaces, + - ( ) (7 to 30 characters).";
    }
    if ($class_id <= 0)
    {
        $errors[] = "Please select a class.";
    }

    if (empty($errors))
    {
        try
        {
            // One call = insert student + insert enrollment + decrement slot
            $enrollment_id = $repo->recordStudent($full_name, $email, $phone, $class_id);
            $msg = "Student recorded and enrolled successfully (Enrollment #" . (int) $enrollment_id . ").";
            $old = ['full_name' => '', 'email' => '', 'phone' => '', 'class_id' => 0];
        }
        catch (EnrollmentException $e)
        {
            // Business rule problem, e.g. "No slots available". Already rolled back.
            $err = $e->getMessage();
        }
        catch (Exception $e)
        {
            $err = "Something went wrong. Nothing was saved. Please try again.";
        }
    }
    else
    {
        $err = implode(' ', $errors);
    }
}

// Load the class list after processing so remaining slots are up to date
$classList = $classes->allWithCourse();
require_once __DIR__ . '/../includes/header.php';
?>
<h1>Record Student</h1>

<?php if ($msg !== '') { ?><div class="ok" role="status"><?php echo Esc($msg); ?></div><?php } ?>
<?php if ($err !== '') { ?><div class="error" role="alert"><?php echo Esc($err); ?></div><?php } ?>

<form method="post" class="box">
    <label>Full Name</label>
    <input type="text" name="full_name" maxlength="100" value="<?php echo Esc($old['full_name']); ?>">

    <label>Email</label>
    <input type="text" name="email" maxlength="100" value="<?php echo Esc($old['email']); ?>">

    <label>Phone</label>
    <input type="text" name="phone" maxlength="30" value="<?php echo Esc($old['phone']); ?>">

    <label>Class</label>
    <select name="class_id">
        <option value="0">-- select class --</option>
        <?php foreach ($classList as $c) { ?>
        <option value="<?php echo (int) $c['class_id']; ?>"
            <?php echo ((int) $old['class_id'] === (int) $c['class_id']) ? 'selected' : ''; ?>>
            <?php
            echo Esc($c['course_name'] . ' | ' . $c['class_code'] . ' | ' . $c['schedule']);
            echo ((int) $c['slots'] > 0) ? ' (' . (int) $c['slots'] . ' slots left)' : ' (FULL)';
            ?>
        </option>
        <?php } ?>
    </select>

    <button type="submit">Record Student</button>
</form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
