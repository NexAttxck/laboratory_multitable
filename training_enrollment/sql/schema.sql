CREATE DATABASE IF NOT EXISTS training_db;
USE training_db;

CREATE TABLE students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(30),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) NOT NULL UNIQUE,
    course_name VARCHAR(100) NOT NULL,
    description TEXT
);

CREATE TABLE classes (
    class_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    class_code VARCHAR(20) NOT NULL,
    schedule VARCHAR(100),
    instructor VARCHAR(100),
    slots INT NOT NULL DEFAULT 0,
    FOREIGN KEY (course_id) REFERENCES courses(course_id)
);

CREATE TABLE enrollments (
    enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'active',
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    FOREIGN KEY (class_id) REFERENCES classes(class_id)
);

-- ---------- Sample data ----------
INSERT INTO courses (course_code, course_name, description) VALUES
('WEB101', 'Web Development Fundamentals', 'HTML, CSS and basic PHP.'),
('DB110',  'Database Essentials',          'Relational design and SQL.'),
('PHP201', 'PHP and PDO',                  'Database programming with PDO.');

-- slots = REMAINING slots. PHP201-A is full on purpose (rollback demo).
INSERT INTO classes (course_id, class_code, schedule, instructor, slots) VALUES
(1, 'WEB101-A', 'Mon/Wed 9:00-11:00',  'Ms. Reyes',  4),
(2, 'DB110-A',  'Tue/Thu 13:00-15:00', 'Mr. Santos', 10),
(3, 'PHP201-A', 'Sat 8:00-12:00',      'Ms. Cruz',   0),
(3, 'PHP201-B', 'Sat 13:00-17:00',     'Ms. Cruz',   2);

INSERT INTO students (full_name, email, phone) VALUES
('Maria Dela Cruz', 'maria@example.com', '09171234567'),
('Juan Ramos',      'juan@example.com',  '09181234567');

-- Matches the slots above (1 active enrollment in WEB101-A, 1 in PHP201-A)
INSERT INTO enrollments (student_id, class_id) VALUES (1, 1), (2, 3);
