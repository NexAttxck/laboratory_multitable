<?php
// admin/courses.php -- Manage courses (add, edit, delete)
$base = '../';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Course.php';

$course = new Course($db);
$msg = '';
$err = '';

// Values shown in the form (empty = add mode, filled = edit mode)
$form = ['course_id' => 0, 'course_code' => '', 'course_name' => '', 'description' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $action = $_POST['action'] ?? '';

    if ($action === 'delete')
    {
        $id = (int) ($_POST['course_id'] ?? 0);
        if ($id > 0 && $course->delete($id))
        {
            $msg = "Course deleted.";
        }
        else
        {
            $err = "Could not delete. The course may still have classes.";
        }
    }
    else
    {
        // Save (add or edit)
        $form['course_id']   = (int) ($_POST['course_id'] ?? 0);
        $form['course_code'] = trim($_POST['course_code'] ?? '');
        $form['course_name'] = trim($_POST['course_name'] ?? '');
        $form['description'] = trim($_POST['description'] ?? '');

        // ---- Validation ----
        $errors = [];
        if ($form['course_code'] === '' || strlen($form['course_code']) > 20)
        {
            $errors[] = "Course code is required (max 20 characters).";
        }
        if ($form['course_name'] === '' || strlen($form['course_name']) > 100)
        {
            $errors[] = "Course name is required (max 100 characters).";
        }
        if (strlen($form['description']) > 1000)
        {
            $errors[] = "Description is too long (max 1000 characters).";
        }

        if (empty($errors))
        {
            try
            {
                if ($form['course_id'] > 0)
                {
                    $course->update($form['course_id'], $form['course_code'],
                        $form['course_name'], $form['description']);
                    $msg = "Course updated.";
                }
                else
                {
                    $course->create($form['course_code'], $form['course_name'],
                        $form['description']);
                    $msg = "Course added.";
                }
                // clear the form after success
                $form = ['course_id' => 0, 'course_code' => '', 'course_name' => '', 'description' => ''];
            }
            catch (PDOException $e)
            {
                // 23000 = duplicate course_code (UNIQUE)
                $err = ($e->getCode() === '23000')
                    ? "That course code already exists."
                    : "Database error. Please try again.";
            }
        }
        else
        {
            $err = implode(' ', $errors);
        }
    }
}
elseif (isset($_GET['edit']))
{
    // Edit mode: load the course into the form
    $found = $course->find((int) $_GET['edit']);
    if ($found)
    {
        $form = $found;
    }
}

$list = $course->all();
require_once __DIR__ . '/../includes/header.php';
?>
<h1>Courses</h1>

<?php if ($msg !== '') { ?><div class="ok" role="status"><?php echo Esc($msg); ?></div><?php } ?>
<?php if ($err !== '') { ?><div class="error" role="alert"><?php echo Esc($err); ?></div><?php } ?>

<form method="post" class="box">
    <h3><?php echo $form['course_id'] > 0 ? 'Edit Course' : 'Add Course'; ?></h3>
    <input type="hidden" name="course_id" value="<?php echo (int) $form['course_id']; ?>">

    <label>Course Code</label>
    <input type="text" name="course_code" maxlength="20" value="<?php echo Esc($form['course_code']); ?>">

    <label>Course Name</label>
    <input type="text" name="course_name" maxlength="100" value="<?php echo Esc($form['course_name']); ?>">

    <label>Description</label>
    <textarea name="description" rows="3"><?php echo Esc($form['description']); ?></textarea>

    <button type="submit" name="action" value="save">Save</button>
    <?php if ($form['course_id'] > 0) { ?><a class="btn" href="courses.php">Cancel</a><?php } ?>
</form>

<table>
    <tr><th>ID</th><th>Code</th><th>Name</th><th>Description</th><th>Actions</th></tr>
    <?php foreach ($list as $c) { ?>
    <tr>
        <td><?php echo (int) $c['course_id']; ?></td>
        <td><?php echo Esc($c['course_code']); ?></td>
        <td><?php echo Esc($c['course_name']); ?></td>
        <td><?php echo Esc($c['description']); ?></td>
        <td>
            <a href="courses.php?edit=<?php echo (int) $c['course_id']; ?>">Edit</a>
            <form method="post" class="inline" data-confirm="Delete this course?" onsubmit="return confirm('Delete this course?');">
                <input type="hidden" name="course_id" value="<?php echo (int) $c['course_id']; ?>">
                <button type="submit" name="action" value="delete" class="danger">Delete</button>
            </form>
        </td>
    </tr>
    <?php } ?>
</table>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
