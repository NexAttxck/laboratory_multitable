<?php
// classes/ClassSection.php
// Data access for the classes table.
class ClassSection
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // Every class together with its course name (INNER JOIN)
    public function allWithCourse()
    {
        $sql = "SELECT c.*, co.course_code, co.course_name
                FROM classes c
                INNER JOIN courses co ON c.course_id = co.course_id
                ORDER BY co.course_name, c.class_code";
        return $this->db->query($sql)->fetchAll();
    }

    // Insert a class and return the new id
    public function create($course_id, $code, $schedule, $instructor, $slots)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO classes (course_id, class_code, schedule, instructor, slots)
             VALUES (:cid, :code, :sched, :inst, :slots)");
        $stmt->execute([
            ':cid'   => $course_id,
            ':code'  => $code,
            ':sched' => $schedule,
            ':inst'  => $instructor,
            ':slots' => $slots
        ]);
        return $this->db->lastInsertId();
    }

    // Remaining slots of a class, or null if the class does not exist
    public function getSlots($class_id)
    {
        $stmt = $this->db->prepare(
            "SELECT slots FROM classes WHERE class_id = :id");
        $stmt->execute([':id' => $class_id]);
        $row = $stmt->fetch();
        return $row ? (int) $row['slots'] : null;
    }
}
