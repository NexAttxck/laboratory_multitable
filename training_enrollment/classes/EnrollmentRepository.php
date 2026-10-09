<?php
// classes/EnrollmentRepository.php
// Repository pattern: all enrollment-related data access lives here.
// Multi-step operations run inside ONE database transaction.

// Used for business-rule problems (no slots, duplicate, not found).
// Pages catch this to show a friendly message.
class EnrollmentException extends Exception
{
}

class EnrollmentRepository
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // Record a NEW student AND enroll them into a class.
    // Returns the new enrollment id. Throws EnrollmentException on failure.
    public function recordStudent($full_name, $email, $phone, $class_id)
    {
        try
        {
            // 1. start the transaction
            $this->db->beginTransaction();

            // 2. check available slots. FOR UPDATE locks the row so two
            //    admins cannot take the last slot at the same time.
            $this->checkSlots($class_id);

            // 3. insert the student
            $stmt = $this->db->prepare(
                "INSERT INTO students (full_name, email, phone)
                 VALUES (:name, :email, :phone)");
            $stmt->execute([
                ':name'  => $full_name,
                ':email' => ($email === '' ? null : $email),
                ':phone' => ($phone === '' ? null : $phone)
            ]);

            // 4. get the new student id
            $student_id = $this->db->lastInsertId();

            // 5. insert the enrollment
            $enrollment_id = $this->insertEnrollment($student_id, $class_id);

            // 6. decrement the slots
            $this->changeSlots($class_id, -1);

            // 7. everything worked, save it
            $this->db->commit();
            return $enrollment_id;
        }
        catch (Exception $e)
        {
            // Any failure undoes ALL steps (no partial rows are kept)
            if ($this->db->inTransaction())
            {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    // Enroll an EXISTING student into a class.
    public function enroll($student_id, $class_id)
    {
        try
        {
            $this->db->beginTransaction();

            // the student must exist
            $stmt = $this->db->prepare(
                "SELECT student_id FROM students WHERE student_id = :id");
            $stmt->execute([':id' => $student_id]);
            if (!$stmt->fetch())
            {
                throw new EnrollmentException("Student not found.");
            }

            // no duplicate active enrollment for the same class
            $stmt = $this->db->prepare(
                "SELECT enrollment_id FROM enrollments
                 WHERE student_id = :sid AND class_id = :cid
                   AND status = 'active'");
            $stmt->execute([':sid' => $student_id, ':cid' => $class_id]);
            if ($stmt->fetch())
            {
                throw new EnrollmentException(
                    "This student is already enrolled in that class.");
            }

            $this->checkSlots($class_id);
            $enrollment_id = $this->insertEnrollment($student_id, $class_id);
            $this->changeSlots($class_id, -1);

            $this->db->commit();
            return $enrollment_id;
        }
        catch (Exception $e)
        {
            if ($this->db->inTransaction())
            {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    // Cancel an enrollment and give the slot back.
    public function cancel($enrollment_id)
    {
        try
        {
            $this->db->beginTransaction();

            // lock the enrollment row and read its class and status
            $stmt = $this->db->prepare(
                "SELECT class_id, status FROM enrollments
                 WHERE enrollment_id = :id FOR UPDATE");
            $stmt->execute([':id' => $enrollment_id]);
            $row = $stmt->fetch();

            if (!$row)
            {
                throw new EnrollmentException("Enrollment not found.");
            }
            if ($row['status'] !== 'active')
            {
                // stops the slot from being restored twice
                throw new EnrollmentException(
                    "This enrollment is already cancelled.");
            }

            // 1. mark as cancelled
            $stmt = $this->db->prepare(
                "UPDATE enrollments SET status = 'cancelled'
                 WHERE enrollment_id = :id");
            $stmt->execute([':id' => $enrollment_id]);

            // 2. restore the slot
            $this->changeSlots($row['class_id'], 1);

            $this->db->commit();
            return true;
        }
        catch (Exception $e)
        {
            if ($this->db->inTransaction())
            {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    // Enrollments joined with student, class and course.
    // Optional search, status filter and pagination.
    public function allWithDetails($search = '', $status = '', $limit = 10, $offset = 0)
    {
        list($where, $params) = $this->buildFilter($search, $status);

        // LIMIT/OFFSET are cast to int, so it is safe to put them in the SQL
        $limit  = max(1, (int) $limit);
        $offset = max(0, (int) $offset);

        $sql = "SELECT e.enrollment_id, e.enrollment_date, e.status,
                       s.student_id, s.full_name, s.email,
                       c.class_id, c.class_code, c.schedule, c.instructor,
                       co.course_code, co.course_name
                FROM enrollments e
                INNER JOIN students s  ON e.student_id = s.student_id
                INNER JOIN classes c   ON e.class_id = c.class_id
                INNER JOIN courses co  ON c.course_id = co.course_id
                $where
                ORDER BY e.enrollment_id DESC
                LIMIT $limit OFFSET $offset";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Total rows for the same filter (needed for pagination)
    public function countWithDetails($search = '', $status = '')
    {
        list($where, $params) = $this->buildFilter($search, $status);

        $sql = "SELECT COUNT(*) AS total
                FROM enrollments e
                INNER JOIN students s  ON e.student_id = s.student_id
                INNER JOIN classes c   ON e.class_id = c.class_id
                INNER JOIN courses co  ON c.course_id = co.course_id
                $where";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return (int) $row['total'];
    }

    // Report: per class active/cancelled counts and remaining slots.
    // LEFT JOINs keep courses and classes that have no enrollments yet.
    public function summaryReport()
    {
        $sql = "SELECT co.course_code, co.course_name, c.class_code,
                       c.slots AS remaining,
                       COUNT(CASE WHEN e.status = 'active' THEN 1 END) AS active_count,
                       COUNT(CASE WHEN e.status = 'cancelled' THEN 1 END) AS cancelled_count
                FROM courses co
                LEFT JOIN classes c      ON c.course_id = co.course_id
                LEFT JOIN enrollments e  ON e.class_id = c.class_id
                GROUP BY co.course_id, c.class_id
                ORDER BY co.course_name, c.class_code";
        return $this->db->query($sql)->fetchAll();
    }

    // ---------- private helpers ----------

    // Builds the WHERE part and its parameters for search and status filter
    private function buildFilter($search, $status)
    {
        $parts  = [];
        $params = [];

        if ($search !== '')
        {
            $parts[] = "(s.full_name LIKE :s1 OR co.course_name LIKE :s2
                         OR c.class_code LIKE :s3)";
            $like = '%' . $search . '%';
            $params[':s1'] = $like;
            $params[':s2'] = $like;
            $params[':s3'] = $like;
        }
        if ($status === 'active' || $status === 'cancelled')
        {
            $parts[] = "e.status = :st";
            $params[':st'] = $status;
        }

        $where = $parts ? "WHERE " . implode(" AND ", $parts) : "";
        return [$where, $params];
    }

    // Locks the class row and throws if it is missing or full
    private function checkSlots($class_id)
    {
        $stmt = $this->db->prepare(
            "SELECT slots FROM classes WHERE class_id = :id FOR UPDATE");
        $stmt->execute([':id' => $class_id]);
        $row = $stmt->fetch();

        if (!$row)
        {
            throw new EnrollmentException("Class not found.");
        }
        if ((int) $row['slots'] <= 0)
        {
            throw new EnrollmentException("No slots available for this class.");
        }
    }

    private function insertEnrollment($student_id, $class_id)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO enrollments (student_id, class_id)
             VALUES (:sid, :cid)");
        $stmt->execute([':sid' => $student_id, ':cid' => $class_id]);
        return $this->db->lastInsertId();
    }

    // delta is -1 (take a slot) or +1 (give a slot back)
    private function changeSlots($class_id, $delta)
    {
        $stmt = $this->db->prepare(
            "UPDATE classes SET slots = slots + :d WHERE class_id = :id");
        $stmt->execute([':d' => (int) $delta, ':id' => $class_id]);
    }
}
