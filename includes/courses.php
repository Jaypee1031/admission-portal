<?php
// Course management functions

class Courses {
    private $db;

    public function __construct() {
        $this->db = getDB();
        $this->initializeTable();
    }

    // Ensure courses table exists and seed default values on first use
    private function initializeTable() {
        $this->db->exec("CREATE TABLE IF NOT EXISTS courses (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL UNIQUE,
            category VARCHAR(50) DEFAULT NULL,
            is_board TINYINT(1) DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        // Seed defaults only if table is empty
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM courses");
            $count = (int)$stmt->fetchColumn();
            if ($count > 0) {
                return;
            }
        } catch (PDOException $e) {
            return;
        }

        $defaultCourses = [
            // BOARD COURSES
            ['name' => 'Bachelor of Science in Agriculture major in Animal Science, Crop Science (BSA)', 'category' => 'Board', 'is_board' => 1],
            ['name' => 'Bachelor of Science in Agricultural Biosystems Engineering (BSABE)', 'category' => 'Board', 'is_board' => 1],
            ['name' => 'Bachelor of Science in Forestry (BSF)', 'category' => 'Board', 'is_board' => 1],
            ['name' => 'Bachelor of Science in Nutrition and Dietetics (BSND)', 'category' => 'Board', 'is_board' => 1],
            ['name' => 'Bachelor in Elementary Education (BEED)', 'category' => 'Board', 'is_board' => 1],
            ['name' => 'Bachelor of Secondary Education major in Filipino, Science, Math, English (BSED)', 'category' => 'Board', 'is_board' => 1],
            ['name' => 'Bachelor in Technology and Livelihood Education (BTLED)', 'category' => 'Board', 'is_board' => 1],
            ['name' => 'Bachelor of Science in Criminology (BS Crim)', 'category' => 'Board', 'is_board' => 1],

            // NON-BOARD COURSES
            ['name' => 'Bachelor of Science in Information Technology (BSIT)', 'category' => 'Non-board', 'is_board' => 0],
            ['name' => 'Bachelor of Science in Office Administration (BSOA)', 'category' => 'Non-board', 'is_board' => 0],
            ['name' => 'Bachelor of Science in Hospitality Management (BSHM)', 'category' => 'Non-board', 'is_board' => 0],
            ['name' => 'Bachelor of Science in Tourism Management (BSTM)', 'category' => 'Non-board', 'is_board' => 0],
            ['name' => 'Caregiving Course (CGC)', 'category' => 'Non-board', 'is_board' => 0],
        ];

        $insert = $this->db->prepare("INSERT INTO courses (name, category, is_board, sort_order) VALUES (?, ?, ?, ?)");
        $order = 1;
        foreach ($defaultCourses as $course) {
            try {
                $insert->execute([
                    $course['name'],
                    $course['category'],
                    $course['is_board'],
                    $order++,
                ]);
            } catch (PDOException $e) {
                // Ignore duplicates or insert failures when seeding
            }
        }
    }

    // Get list of active course names for dropdowns
    public function getActiveCourses(): array {
        try {
            $stmt = $this->db->prepare("SELECT name FROM courses WHERE is_active = 1 ORDER BY sort_order, name");
            $stmt->execute();
            $courses = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
            return $courses;
        } catch (PDOException $e) {
            return [];
        }
    }

    // Get all courses with metadata for admin management
    public function getAllCourses(): array {
        try {
            $stmt = $this->db->prepare("SELECT * FROM courses ORDER BY sort_order, name");
            $stmt->execute();
            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    // Add a new course
    public function addCourse(string $name, ?string $category = null): array {
        $name = trim($name);
        $category = $category !== null ? trim($category) : null;

        if ($name === '') {
            return ['success' => false, 'message' => 'Course name is required.'];
        }

        try {
            $stmt = $this->db->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM courses");
            $nextOrder = (int)$stmt->fetchColumn();

            $isBoard = 0;
            if ($category !== null && strcasecmp($category, 'Board') === 0) {
                $isBoard = 1;
            }

            $insert = $this->db->prepare("INSERT INTO courses (name, category, is_board, is_active, sort_order) VALUES (?, ?, ?, 1, ?)");
            $insert->execute([$name, $category ?: null, $isBoard, $nextOrder]);

            return ['success' => true, 'message' => 'Course added successfully.'];
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                return ['success' => false, 'message' => 'This course already exists.'];
            }
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Delete a course by ID
    public function deleteCourse(int $id): array {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid course ID.'];
        }

        try {
            $stmt = $this->db->prepare("DELETE FROM courses WHERE id = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'Course not found.'];
            }

            return ['success' => true, 'message' => 'Course removed successfully.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}
