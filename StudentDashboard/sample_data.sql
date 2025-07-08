-- Sample data for testing the student management system

-- Insert sample schools
INSERT INTO schools (name, code, description) VALUES
('School of Engineering', 'ENG', 'Engineering and Technology'),
('School of Science', 'SCI', 'Natural and Applied Sciences'),
('School of Business', 'BUS', 'Business and Management');

-- Insert sample levels
INSERT INTO levels (school_id, name, description) VALUES
(1, 'Undergraduate', 'Bachelor degree programs'),
(2, 'Undergraduate', 'Bachelor degree programs'),
(3, 'Undergraduate', 'Bachelor degree programs');

-- Insert sample departments
INSERT INTO departments (level_id, name, code, description) VALUES
(1, 'Computer Science', 'CS', 'Computer Science and Software Engineering'),
(1, 'Electrical Engineering', 'EE', 'Electrical and Electronics Engineering'),
(2, 'Mathematics', 'MATH', 'Pure and Applied Mathematics'),
(3, 'Business Administration', 'BA', 'Business Administration and Management');

-- Insert sample courses
INSERT INTO courses (department_id, name, code, semester, credit_hours) VALUES
-- Computer Science courses
(1, 'Introduction to Programming', 'CS101', 'First Semester', 3),
(1, 'Data Structures', 'CS102', 'First Semester', 4),
(1, 'Database Systems', 'CS201', 'Second Semester', 3),
(1, 'Web Development', 'CS202', 'Second Semester', 3),
(1, 'Software Engineering', 'CS301', 'First Semester', 4),
(1, 'Computer Networks', 'CS302', 'Second Semester', 3),

-- Electrical Engineering courses
(2, 'Circuit Analysis', 'EE101', 'First Semester', 4),
(2, 'Digital Logic', 'EE102', 'First Semester', 3),
(2, 'Electronics', 'EE201', 'Second Semester', 4),
(2, 'Signal Processing', 'EE202', 'Second Semester', 3),

-- Mathematics courses
(3, 'Calculus I', 'MATH101', 'First Semester', 4),
(3, 'Linear Algebra', 'MATH102', 'First Semester', 3),
(3, 'Calculus II', 'MATH201', 'Second Semester', 4),
(3, 'Statistics', 'MATH202', 'Second Semester', 3),

-- Business Administration courses
(4, 'Principles of Management', 'BA101', 'First Semester', 3),
(4, 'Financial Accounting', 'BA102', 'First Semester', 3),
(4, 'Marketing Management', 'BA201', 'Second Semester', 3),
(4, 'Human Resource Management', 'BA202', 'Second Semester', 3);

-- Insert sample students
INSERT INTO students (matricule, full_name, email, phone_number, password, school_id, department_name) VALUES
('CS2024001', 'John Doe', 'john.doe@student.edu', 1234567890, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1', 1),
('EE2024002', 'Jane Smith', 'jane.smith@student.edu', 1234567891, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1', 2),
('MATH2024003', 'Mike Johnson', 'mike.johnson@student.edu', 1234567892, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2', 3),
('BA2024004', 'Sarah Wilson', 'sarah.wilson@student.edu', 1234567893, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '3', 4);

-- Insert sample enrollments (John Doe - Computer Science)
INSERT INTO enrollments (student_id, course_id) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6);

-- Insert sample enrollments (Jane Smith - Electrical Engineering)
INSERT INTO enrollments (student_id, course_id) VALUES
(2, 7), (2, 8), (2, 9), (2, 10);

-- Insert sample exam results for John Doe
INSERT INTO exam_results (enrollment_id, exam_type, ca_mark, exam_mark, total_score) VALUES
(1, 'Final', 25, 65, 90),
(2, 'Final', 22, 58, 80),
(3, 'Final', 28, 67, 95),
(4, 'Final', 20, 55, 75),
(5, 'Final', 26, 62, 88),
(6, 'Final', 24, 61, 85);

-- Insert sample exam results for Jane Smith
INSERT INTO exam_results (enrollment_id, exam_type, ca_mark, exam_mark, total_score) VALUES
(7, 'Final', 27, 68, 95),
(8, 'Final', 23, 57, 80),
(9, 'Final', 29, 71, 100),
(10, 'Final', 21, 54, 75);

-- Create notifications table
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','success','danger') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample notifications
INSERT INTO notifications (student_id, title, message, type, is_read) VALUES
(1, 'Welcome to SMA', 'Welcome to the Student Management System! Please complete your profile.', 'info', 0),
(1, 'Assignment Due', 'Your CS101 assignment is due tomorrow. Please submit before the deadline.', 'warning', 0),
(1, 'Exam Results Available', 'Your exam results for CS102 are now available. Check your results page.', 'success', 1),
(1, 'Fee Payment Reminder', 'Your semester fee payment is due in 3 days. Please make payment to avoid late fees.', 'danger', 0),
(1, 'New Course Available', 'A new elective course "Mobile App Development" is now available for registration.', 'info', 0),
(2, 'Welcome to SMA', 'Welcome to the Student Management System! Please complete your profile.', 'info', 0),
(2, 'Lab Session Scheduled', 'Your EE101 lab session is scheduled for tomorrow at 2:00 PM.', 'info', 1),
(2, 'Grade Updated', 'Your grade for Digital Logic has been updated. Check your results.', 'success', 0);

