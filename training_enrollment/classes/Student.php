<?php
// classes/Student.php
// Data access for the students table.
class Student
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // Return one student by id, or false if not found
    public function find($id)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM students WHERE student_id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    // Return all students ordered by full_name
    public function all()
    {
        return $this->db->query(
            "SELECT * FROM students ORDER BY full_name")->fetchAll();
    }
}
