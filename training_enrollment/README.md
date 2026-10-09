# Training Enrollment System

A multi-table web application built with PHP and PDO for the IPT laboratory activity.
A single administrator manages courses and classes, records students, enrolls them,
cancels enrollments, and views reports. Related data stays consistent because
multi-step operations run inside database transactions.

## Features
- Courses: add, edit, delete (with validation and duplicate code check)
- Classes: add classes with schedule, instructor and slots, listed with their course
- Record student: inserts the student, inserts the enrollment and decrements the slot in one transaction
- Enroll existing student: blocks full classes and duplicate active enrollments
- Enrollments list: JOIN of enrollments, students, classes and courses with search, status filter and pagination
- Cancel enrollment: updates the status and restores the slot in one transaction
- Reports: active and cancelled counts and remaining slots per class

## Setup
1. Install XAMPP (Apache, MySQL, PHP 7.4 or newer).
2. Copy this folder to `C:\xampp\htdocs\training_enrollment\`.
3. Start Apache and MySQL in the XAMPP Control Panel.
4. Open phpMyAdmin, go to the Import tab, and import `sql/schema.sql`.
5. Copy `config/db.sample.php` to `config/db.php` and set your database username and password.
6. Open `http://localhost/training_enrollment/` in the browser.

`config/db.php` is in `.gitignore` so the real password is not committed.

## Design patterns used
**Singleton (Database class).** The `Database` class extends PDO and has a private
constructor. `getInstance()` creates the connection once and returns the same object
every time. This avoids opening many connections and gives every page and class one
shared connection to use, which is also needed so a transaction stays on one connection.

**Repository (EnrollmentRepository class).** This class holds all the data access for
enrollments. The pages in `admin/` only handle the form and the display, and they call
methods like `recordStudent()`, `enroll()` and `cancel()`. This keeps SQL out of the pages,
puts the transaction logic in one place, and makes the code easier to read and change.

## Transactions
- `recordStudent()`: begin, check slots (row locked with `FOR UPDATE`), insert student,
  insert enrollment, decrement slots, commit. Any failure triggers `rollBack()`, so no
  partial rows remain.
- `cancel()`: begin, set status to `cancelled`, add the slot back, commit. An enrollment
  that is already cancelled is rejected so the slot cannot be restored twice.

## Security
- All queries use PDO prepared statements.
- All output is escaped with `htmlspecialchars` (the `Esc()` helper).
- Inputs are validated on the server (required fields, lengths, email, phone, integer slots).
- Data-changing actions (delete, cancel) use POST, not links.

## Database
`sql/schema.sql` creates `training_db` with sample data. Class `PHP201-A` has 0 slots on
purpose so the rollback case can be tested. Export your own copy as `training_db.sql`
from phpMyAdmin after testing.
