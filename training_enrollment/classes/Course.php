<?php
// classes/Course.php
// Data access for the courses table.
class Course
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // Return all courses ordered by course_name
    public function all()
    {
        return $this->db->query(
            "SELECT * FROM courses ORDER BY course_name")->fetchAll();
    }

    // Return one course by id, or false if not found
    public function find($id)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM courses WHERE course_id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    // Insert a course and return the new id
    public function create($code, $name, $description)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO courses (course_code, course_name, description)
             VALUES (:code, :name, :descr)");
        $stmt->execute([
            ':code'  => $code,
            ':name'  => $name,
            ':descr' => $description
        ]);
        return $this->db->lastInsertId();
    }

    // Update a course, returns true on success
    public function update($id, $code, $name, $description)
    {
        $stmt = $this->db->prepare(
            "UPDATE courses
             SET course_code = :code, course_name = :name, description = :descr
             WHERE course_id = :id");
        return $stmt->execute([
            ':code'  => $code,
            ':name'  => $name,
            ':descr' => $description,
            ':id'    => $id
        ]);
    }

    // Delete a course. Returns false if it still has classes (foreign key)
    public function delete($id)
    {
        try
        {
            $stmt = $this->db->prepare(
                "DELETE FROM courses WHERE course_id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->rowCount() > 0;
        }
        catch (PDOException $e)
        {
            // 23000 = integrity constraint violation (course has classes)
            if ($e->getCode() === '23000')
            {
                return false;
            }
            throw $e;
        }
    }
}
