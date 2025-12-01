<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/grading_config.php';

class TestResults {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Upload and process test results from Excel file (TEST-RESULT.xlsx format)
     */
    public function uploadTestResults($excelData, $processedBy) {
        try {
            $this->db->beginTransaction();
            
            $processedCount = 0;
            $errors = [];
            
            // Process each student's results
            foreach ($excelData as $row) {
                // Validate required fields
                if (empty($row['permit_number']) || empty($row['exam_date'])) {
                    $errors[] = "Row missing permit number or exam date";
                    continue;
                }
                
                // Find student by permit number or create if manual entry
                $student = $this->getStudentByPermitNumber($row['permit_number']);
                if (!$student) {
                    // For manual entry, create a temporary student record
                    if (isset($row['student_name']) && !empty($row['student_name'])) {
                        $student = $this->createTemporaryStudent($row);
                    } else {
                        $errors[] = "Student not found for permit number: " . $row['permit_number'];
                        continue;
                    }
                }
                
                // Extract subject scores
                $subjectScores = $this->extractSubjectScores($row);
                
                // Calculate transmuted scores
                $transmutedScores = $this->calculateTransmutedScores($subjectScores);
                
                // Calculate weighted scores for each subject
                $weightedScores = $this->calculateWeightedScores($transmutedScores);
                
                // Calculate weighted exam rating
                $examRating = $this->calculateExamRating($transmutedScores);
                
                // Calculate total raw score
                $totalRawScore = array_sum($subjectScores);
                
                // Calculate percentage score
                $percentageScore = ($totalRawScore / 250) * 100; // Total possible: 30+50+60+60+50 = 250
                
                // Get student's GWA (use provided GWA for manual entry)
                $gwaScore = isset($row['gwa']) ? $row['gwa'] : $this->getStudentGWA($student['id']);
                
                // Calculate total rating (Exam 50% + Interview 10% only, GWA stored separately)
                $interviewScore = isset($row['interview_score']) ? $row['interview_score'] : 0;
                $totalRating = ($examRating * 0.50) + ($interviewScore * 0.10); // Only exam + interview
                
                // Determine overall rating based on exam rating using configuration
                $overallRating = getOverallRating($examRating);
                
                // Generate recommendation
                $recommendation = $this->generateRecommendation($overallRating, $percentageScore);
                
                // Check if result already exists
                $existingResult = $this->getTestResultByPermitNumber($row['permit_number']);
                if ($existingResult) {
                    // Update existing result
                    $stmt = $this->db->prepare("
                        UPDATE test_results 
                        SET raw_score = ?, gen_info_raw = ?, gen_info_transmuted = ?, gen_info_weighted = ?,
                            filipino_raw = ?, filipino_transmuted = ?, filipino_weighted = ?,
                            english_raw = ?, english_transmuted = ?, english_weighted = ?,
                            science_raw = ?, science_transmuted = ?, science_weighted = ?,
                            math_raw = ?, math_transmuted = ?, math_weighted = ?,
                            exam_rating = ?, exam_percentage = ?, gwa_score = ?, total_rating = ?,
                            percentage_score = ?, overall_rating = ?, recommendation = ?, 
                            processed_by = ?, processed_at = NOW(), updated_at = NOW()
                        WHERE permit_number = ?
                    ");
                    $stmt->execute([
                        $totalRawScore,
                        $subjectScores['gen_info'], $transmutedScores['gen_info'], $weightedScores['gen_info'],
                        $subjectScores['filipino'], $transmutedScores['filipino'], $weightedScores['filipino'],
                        $subjectScores['english'], $transmutedScores['english'], $weightedScores['english'],
                        $subjectScores['science'], $transmutedScores['science'], $weightedScores['science'],
                        $subjectScores['math'], $transmutedScores['math'], $weightedScores['math'],
                        $examRating, $percentageScore, $gwaScore, $totalRating,
                        $percentageScore, $overallRating, $recommendation,
                        $processedBy, $row['permit_number']
                    ]);
                } else {
                    // Create new result
                    $stmt = $this->db->prepare("
                        INSERT INTO test_results 
                        (student_id, permit_number, exam_date, raw_score, gen_info_raw, gen_info_transmuted, gen_info_weighted,
                         filipino_raw, filipino_transmuted, filipino_weighted, english_raw, english_transmuted, english_weighted,
                         science_raw, science_transmuted, science_weighted, math_raw, math_transmuted, math_weighted,
                         exam_rating, exam_percentage, gwa_score, total_rating,
                         percentage_score, overall_rating, recommendation, processed_by, processed_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $student['id'], $row['permit_number'], $row['exam_date'],
                        $totalRawScore,
                        $subjectScores['gen_info'], $transmutedScores['gen_info'], $weightedScores['gen_info'],
                        $subjectScores['filipino'], $transmutedScores['filipino'], $weightedScores['filipino'],
                        $subjectScores['english'], $transmutedScores['english'], $weightedScores['english'],
                        $subjectScores['science'], $transmutedScores['science'], $weightedScores['science'],
                        $subjectScores['math'], $transmutedScores['math'], $weightedScores['math'],
                        $examRating, $percentageScore, $gwaScore, $totalRating,
                        $percentageScore, $overallRating, $recommendation, $processedBy
                    ]);
                }
                
                // Mark student as having test result available
                $stmt = $this->db->prepare("UPDATE students SET test_result_available = 1 WHERE id = ?");
                $stmt->execute([$student['id']]);
                
                // Refresh student session if they are currently logged in
                $auth = new Auth();
                $auth->refreshStudentSession($student['id']);
                
                $processedCount++;
            }
            
            // Calculate and update rankings
            $this->updateRankings();
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => "Successfully processed {$processedCount} test results with weighted scoring system",
                'processed_count' => $processedCount,
                'errors' => $errors
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => 'Failed to process test results: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Extract subject scores from input data
     */
    private function extractSubjectScores($row) {
        return [
            'gen_info' => (int)($row['gen_info_raw'] ?? 0),      // 30 pts max
            'filipino' => (int)($row['filipino_raw'] ?? 0),      // 50 pts max
            'english' => (int)($row['english_raw'] ?? 0),        // 60 pts max
            'science' => (int)($row['science_raw'] ?? 0),        // 60 pts max
            'math' => (int)($row['math_raw'] ?? 0)               // 50 pts max
        ];
    }
    
    /**
     * Calculate transmuted scores using standard transmutation table
     */
    private function calculateTransmutedScores($subjectScores) {
        $transmuted = [];
        
        // Transmutation table based on entrance exam standards
        // Maps raw score to transmuted score (0-100 scale)
        $transmutationTables = [
            'gen_info' => [ // 30 items max
                0 => 0, 1 => 25, 2 => 30, 3 => 35, 4 => 40, 5 => 45, 6 => 50, 7 => 55, 8 => 60, 9 => 65,
                10 => 68, 11 => 70, 12 => 72, 13 => 74, 14 => 78.667, 15 => 80, 16 => 82, 17 => 84, 18 => 86, 19 => 88,
                20 => 90, 21 => 91, 22 => 92, 23 => 93, 24 => 94, 25 => 95, 26 => 96, 27 => 97, 28 => 98, 29 => 99, 30 => 100
            ],
            'filipino' => [ // 50 items max
                0 => 0, 1 => 20, 2 => 25, 3 => 30, 4 => 35, 5 => 40, 6 => 45, 7 => 50, 8 => 52, 9 => 54, 10 => 56,
                11 => 58, 12 => 60, 13 => 62, 14 => 64, 15 => 66, 16 => 68, 17 => 70, 18 => 71, 19 => 72, 20 => 73,
                21 => 74, 22 => 75, 23 => 76, 24 => 77, 25 => 78, 26 => 79, 27 => 80, 28 => 82.400, 29 => 84, 30 => 85,
                31 => 86, 32 => 87, 33 => 88, 34 => 89, 35 => 90, 36 => 91, 37 => 92, 38 => 93, 39 => 94, 40 => 95,
                41 => 96, 42 => 96.5, 43 => 97, 44 => 97.5, 45 => 98, 46 => 98.5, 47 => 99, 48 => 99.5, 49 => 99.8, 50 => 100
            ],
            'english' => [ // 60 items max
                0 => 0, 1 => 15, 2 => 20, 3 => 25, 4 => 30, 5 => 35, 6 => 40, 7 => 42, 8 => 44, 9 => 46, 10 => 48,
                11 => 50, 12 => 52, 13 => 54, 14 => 56, 15 => 58, 16 => 60, 17 => 62, 18 => 64, 19 => 66, 20 => 68,
                21 => 70, 22 => 74.667, 23 => 72, 24 => 73, 25 => 74, 26 => 75, 27 => 76, 28 => 77, 29 => 78, 30 => 79,
                31 => 80, 32 => 81, 33 => 82, 34 => 83, 35 => 84, 36 => 85, 37 => 86, 38 => 87, 39 => 88, 40 => 89,
                41 => 90, 42 => 90.5, 43 => 91, 44 => 91.5, 45 => 92, 46 => 92.5, 47 => 93, 48 => 93.5, 49 => 94, 50 => 94.5,
                51 => 95, 52 => 95.5, 53 => 96, 54 => 96.5, 55 => 97, 56 => 97.5, 57 => 98, 58 => 98.5, 59 => 99, 60 => 100
            ],
            'science' => [ // 60 items max - same as English
                0 => 0, 1 => 15, 2 => 20, 3 => 25, 4 => 30, 5 => 35, 6 => 40, 7 => 42, 8 => 44, 9 => 46, 10 => 48,
                11 => 50, 12 => 52, 13 => 54, 14 => 56, 15 => 58, 16 => 60, 17 => 62, 18 => 64, 19 => 66, 20 => 68,
                21 => 70, 22 => 74.667, 23 => 72, 24 => 73, 25 => 74, 26 => 75, 27 => 76, 28 => 77, 29 => 78, 30 => 79,
                31 => 80, 32 => 81, 33 => 82, 34 => 83, 35 => 84, 36 => 85, 37 => 86, 38 => 87, 39 => 88, 40 => 89,
                41 => 90, 42 => 90.5, 43 => 91, 44 => 91.5, 45 => 92, 46 => 92.5, 47 => 93, 48 => 93.5, 49 => 94, 50 => 94.5,
                51 => 95, 52 => 95.5, 53 => 96, 54 => 96.5, 55 => 97, 56 => 97.5, 57 => 98, 58 => 98.5, 59 => 99, 60 => 100
            ],
            'math' => [ // 50 items max
                0 => 0, 1 => 20, 2 => 25, 3 => 30, 4 => 35, 5 => 64.000, 6 => 45, 7 => 50, 8 => 52, 9 => 54, 10 => 56,
                11 => 58, 12 => 60, 13 => 62, 14 => 64, 15 => 66, 16 => 68, 17 => 70, 18 => 71, 19 => 72, 20 => 73,
                21 => 74, 22 => 75, 23 => 76, 24 => 77, 25 => 78, 26 => 79, 27 => 80, 28 => 82, 29 => 84, 30 => 85,
                31 => 86, 32 => 87, 33 => 88, 34 => 89, 35 => 90, 36 => 91, 37 => 92, 38 => 93, 39 => 94, 40 => 95,
                41 => 96, 42 => 96.5, 43 => 97, 44 => 97.5, 45 => 98, 46 => 98.5, 47 => 99, 48 => 99.5, 49 => 99.8, 50 => 100
            ]
        ];
        
        foreach ($subjectScores as $subject => $rawScore) {
            $rawScore = (int)$rawScore;
            $table = $transmutationTables[$subject] ?? [];
            
            // Get transmuted score from table, default to 0 if not found
            $transmuted[$subject] = $table[$rawScore] ?? 0;
        }
        
        return $transmuted;
    }
    
    /**
     * Calculate weighted scores for each subject
     */
    private function calculateWeightedScores($transmutedScores) {
        $weights = [
            'gen_info' => 0.10,  // 10%
            'filipino' => 0.15,  // 15%
            'english' => 0.25,   // 25%
            'science' => 0.25,   // 25%
            'math' => 0.25       // 25%
        ];
        
        $weightedScores = [];
        foreach ($transmutedScores as $subject => $score) {
            $weightedScores[$subject] = round($score * $weights[$subject], 3);
        }
        
        return $weightedScores;
    }
    
    /**
     * Calculate exam rating from weighted scores
     */
    private function calculateExamRating($transmutedScores) {
        $weights = [
            'gen_info' => 0.10,    // 10%
            'filipino' => 0.15,    // 15%
            'english' => 0.25,     // 25%
            'science' => 0.25,     // 25%
            'math' => 0.25         // 25%
        ];
        
        $examRating = 0;
        foreach ($transmutedScores as $subject => $score) {
            $examRating += $score * $weights[$subject];
        }
        
        return round($examRating, 2);
    }
    
    /**
     * Calculate total rating (Exam 50% + Interview 10% + GWA 40%)
     */
    private function calculateTotalRating($examRating, $interviewScore = 0, $gwaScore = 0) {
        $totalRating = ($examRating * 0.50) + ($interviewScore * 0.10) + ($gwaScore * 0.40);
        return round($totalRating, 2);
    }
    
    /**
     * Get student's GWA (General Weighted Average)
     */
    private function getStudentGWA($studentId) {
        try {
            $stmt = $this->db->prepare("SELECT first_sem_gwa, second_sem_gwa, overall_gwa FROM students WHERE id = ?");
            $stmt->execute([$studentId]);
            $student = $stmt->fetch();
            
            if ($student && $student['overall_gwa']) {
                return (float)$student['overall_gwa'];
            } elseif ($student && $student['first_sem_gwa'] && $student['second_sem_gwa']) {
                return (float)(($student['first_sem_gwa'] + $student['second_sem_gwa']) / 2);
            }
            
            return 0; // Default GWA if not available
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    /**
     * Update rankings based on total rating
     */
    private function updateRankings() {
        try {
            // Get all results ordered by total rating (descending)
            $stmt = $this->db->prepare("
                SELECT id, total_rating 
                FROM test_results 
                WHERE total_rating IS NOT NULL 
                ORDER BY total_rating DESC
            ");
            $stmt->execute();
            $results = $stmt->fetchAll();
            
            // Update rankings
            $rank = 1;
            foreach ($results as $result) {
                $stmt = $this->db->prepare("UPDATE test_results SET final_rank = ? WHERE id = ?");
                $stmt->execute([$rank, $result['id']]);
                $rank++;
            }
        } catch (PDOException $e) {
            // Log error but don't fail the entire process
            error_log("Error updating rankings: " . $e->getMessage());
        }
    }
    
    /**
     * Calculate percentile rank for a given score
     */
    private function calculatePercentileRank($score, $allScores) {
        if (empty($allScores)) {
            return 0;
        }
        
        $count = 0;
        foreach ($allScores as $s) {
            if ($s <= $score) {
                $count++;
            }
        }
        
        return ($count / count($allScores)) * 100;
    }
    
    /**
     * Determine overall rating based on percentage score
     */
    private function determineOverallRating($percentageScore, $passingScore) {
        return getOverallRating($percentageScore);
    }
    
    /**
     * Generate recommendation based on overall rating and percentage score
     */
    private function generateRecommendation($overallRating, $percentageScore) {
        switch ($overallRating) {
            case 'Excellent':
                return 'Excellent performance! Qualified for admission with honors consideration.';
            case 'Very Good':
                return 'Very good performance! Qualified for admission.';
            case 'Passed':
                return 'Meets minimum requirements for admission.';
            case 'Conditional':
                return 'Conditional admission. Additional requirements may apply.';
            case 'Failed':
            default:
                if ($percentageScore >= 70) {
                    return 'Close to passing. Consider retaking the exam to improve your chances.';
                } else {
                    return 'Does not meet minimum requirements. Recommend retaking the exam after additional preparation.';
                }
        }
    }
    
    /**
     * Get student by permit number
     */
    private function getStudentByPermitNumber($permitNumber) {
        try {
            $stmt = $this->db->prepare("
                SELECT s.* FROM students s 
                JOIN test_permits tp ON s.id = tp.student_id 
                WHERE tp.permit_number = ?
            ");
            $stmt->execute([$permitNumber]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Create temporary student for manual entry
     */
    private function createTemporaryStudent($row) {
        try {
            // Parse student name
            $nameParts = explode(' ', trim($row['student_name']), 2);
            $firstName = $nameParts[0];
            $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
            
            // Create temporary student record
            $stmt = $this->db->prepare("
                INSERT INTO students (first_name, last_name, email, type, status, created_at) 
                VALUES (?, ?, ?, 'Freshman', 'Active', NOW())
            ");
            $stmt->execute([
                $firstName,
                $lastName,
                'temp_' . $row['permit_number'] . '@temp.com'
            ]);
            
            $studentId = $this->db->lastInsertId();
            
            // Create temporary test permit
            $stmt = $this->db->prepare("
                INSERT INTO test_permits (student_id, permit_number, status, created_at) 
                VALUES (?, ?, 'Approved', NOW())
            ");
            $stmt->execute([$studentId, $row['permit_number']]);
            
            // Update student with GWA if provided
            if (isset($row['gwa'])) {
                $stmt = $this->db->prepare("
                    UPDATE students SET overall_gwa = ? WHERE id = ?
                ");
                $stmt->execute([$row['gwa'], $studentId]);
            }
            
            // Return student data
            $stmt = $this->db->prepare("SELECT * FROM students WHERE id = ?");
            $stmt->execute([$studentId]);
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Get test result by permit number
     */
    public function getTestResultByPermitNumber($permitNumber) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM test_results WHERE permit_number = ?");
            $stmt->execute([$permitNumber]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Get test result for a student
     */
    public function getTestResult($studentId) {
        try {
            $stmt = $this->db->prepare("
                SELECT tr.*, s.first_name, s.last_name, s.middle_name, 
                       tp.exam_date as permit_exam_date, tp.exam_time, tp.exam_room,
                       a.full_name as processed_by_name
                FROM test_results tr
                JOIN students s ON tr.student_id = s.id
                LEFT JOIN test_permits tp ON tr.permit_number = tp.permit_number
                LEFT JOIN admins a ON tr.processed_by = a.id
                WHERE tr.student_id = ?
                ORDER BY tr.created_at DESC
                LIMIT 1
            ");
            $stmt->execute([$studentId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Get all test results with student information
     */
    public function getAllTestResults($filters = []) {
        try {
            $whereClause = "1=1";
            $params = [];
            
            if (!empty($filters['search'])) {
                $whereClause .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR tr.permit_number LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
            }
            
            if (!empty($filters['exam_date'])) {
                $whereClause .= " AND tr.exam_date = ?";
                $params[] = $filters['exam_date'];
            }
            
            if (!empty($filters['overall_rating'])) {
                $whereClause .= " AND tr.overall_rating = ?";
                $params[] = $filters['overall_rating'];
            }
            
            $stmt = $this->db->prepare("
                SELECT tr.*, s.first_name, s.last_name, s.middle_name, s.email,
                       tp.exam_date as permit_exam_date, tp.exam_time, tp.exam_room,
                       a.full_name as processed_by_name
                FROM test_results tr
                JOIN students s ON tr.student_id = s.id
                LEFT JOIN test_permits tp ON tr.permit_number = tp.permit_number
                LEFT JOIN admins a ON tr.processed_by = a.id
                WHERE {$whereClause}
                ORDER BY tr.processed_at DESC
            ");
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Get test result statistics
     */
    public function getTestResultStats() {
        try {
            $stats = [];
            
            // Total test results
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM test_results");
            $stmt->execute();
            $stats['total_results'] = $stmt->fetchColumn();
            
            // Results by overall rating
            $stmt = $this->db->prepare("
                SELECT overall_rating, COUNT(*) as count 
                FROM test_results 
                WHERE overall_rating IS NOT NULL 
                GROUP BY overall_rating
            ");
            $stmt->execute();
            $stats['by_rating'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // Average scores
            $stmt = $this->db->prepare("
                SELECT 
                    AVG(raw_score) as avg_raw_score,
                    AVG(percentage_score) as avg_percentage_score,
                    AVG(percentile_rank) as avg_percentile_rank
                FROM test_results
            ");
            $stmt->execute();
            $stats['averages'] = $stmt->fetch();
            
            // Recent results (last 30 days)
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM test_results 
                WHERE processed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute();
            $stats['recent_results'] = $stmt->fetchColumn();
            
            return $stats;
        } catch (PDOException $e) {
            return [
                'total_results' => 0,
                'by_rating' => [],
                'averages' => [],
                'recent_results' => 0
            ];
        }
    }
    
    /**
     * Delete test result
     */
    public function deleteTestResult($resultId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM test_results WHERE id = ?");
            $stmt->execute([$resultId]);
            
            return ['success' => true, 'message' => 'Test result deleted successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to delete test result'];
        }
    }
    
    /**
     * Update test result
     */
    public function updateTestResult($resultId, $data) {
        try {
            $stmt = $this->db->prepare("
                UPDATE test_results 
                SET raw_score = ?, percentage_score = ?, percentile_rank = ?, 
                    overall_rating = ?, recommendation = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $data['raw_score'] ?? null,
                $data['percentage_score'] ?? null,
                $data['percentile_rank'] ?? null,
                $data['overall_rating'] ?? null,
                $data['recommendation'] ?? null,
                $resultId
            ]);
            
            return ['success' => true, 'message' => 'Test result updated successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to update test result'];
        }
    }
    
    /**
     * Generate TO-PRINT-TEST-RESULT-2026.xlsx format data
     */
    public function generatePrintFormatData($filters = []) {
        try {
            $whereClause = "1=1";
            $params = [];
            
            if (!empty($filters['search'])) {
                $whereClause .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR tr.permit_number LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
            }
            
            if (!empty($filters['exam_date'])) {
                $whereClause .= " AND tr.exam_date = ?";
                $params[] = $filters['exam_date'];
            }
            
            if (!empty($filters['overall_rating'])) {
                $whereClause .= " AND tr.overall_rating = ?";
                $params[] = $filters['overall_rating'];
            }
            
            $stmt = $this->db->prepare("
                SELECT 
                    tr.*,
                    s.first_name,
                    s.middle_name,
                    s.last_name,
                    s.email,
                    af.course_first,
                    af.course_second,
                    af.course_third,
                    af.last_school,
                    f2.gwa_1st_sem,
                    f2.gwa_2nd_sem
                FROM test_results tr
                JOIN students s ON tr.student_id = s.id
                LEFT JOIN admission_forms af ON s.id = af.student_id
                LEFT JOIN f2_personal_data_forms f2 ON s.id = f2.student_id
                WHERE $whereClause
                ORDER BY tr.exam_date DESC, s.last_name ASC, s.first_name ASC
            ");
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Export test results to CSV format (TO-PRINT-TEST-RESULT-2026.xlsx compatible)
     */
    public function exportToCSV($filters = []) {
        $data = $this->generatePrintFormatData($filters);
        
        if (empty($data)) {
            return false;
        }
        
        $filename = 'TEST-RESULT-' . date('Y') . '.csv';
        $filepath = '../generated_pdfs/test_permits/' . $filename;
        
        // Ensure directory exists
        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        $file = fopen($filepath, 'w');
        
        // Write multi-row headers matching the spreadsheet format
        // Row 1: Column identifiers
        fputcsv($file, [
            'No.',
            'Date of Release',
            'Student Name',
            'School Last Attended',
            'Gen Info. (30)',
            'Filipino (50)',
            'English (60)',
            'Science (60)',
            'Math (50)',
            'EXAM RATING',
            '1st SEM',
            '2nd SEM',
            'GWA'
        ]);
        
        // Row 2: Subject weights
        fputcsv($file, [
            '',
            '',
            '',
            '',
            '0.1',
            '0.15',
            '0.25',
            '0.25',
            '0.25',
            '',
            '',
            '',
            ''
        ]);
        
        // Write data rows
        foreach ($data as $row) {
            // Format student name as "Last Name, First Name Middle Name"
            $studentName = '';
            if (!empty($row['last_name']) && !empty($row['first_name'])) {
                $studentName = $row['last_name'] . ', ' . $row['first_name'];
                if (!empty($row['middle_name'])) {
                    $studentName .= ' ' . $row['middle_name'];
                }
            }
            
            // Format scores with proper decimal places (transmuted scores)
            $genInfoTransmuted = isset($row['gen_info_transmuted']) ? number_format($row['gen_info_transmuted'], 2) : '0.00';
            $filipinoTransmuted = isset($row['filipino_transmuted']) ? number_format($row['filipino_transmuted'], 2) : '0.00';
            $englishTransmuted = isset($row['english_transmuted']) ? number_format($row['english_transmuted'], 2) : '0.00';
            $scienceTransmuted = isset($row['science_transmuted']) ? number_format($row['science_transmuted'], 2) : '0.00';
            $mathTransmuted = isset($row['math_transmuted']) ? number_format($row['math_transmuted'], 2) : '0.00';
            $examRating = isset($row['exam_rating']) ? number_format($row['exam_rating'], 2) : '0.00';
            $gwaScore = isset($row['gwa_score']) ? number_format($row['gwa_score'], 2) : '0.00';
            
            // Get GWA values from F2 form
            $gwa1stSem = isset($row['gwa_1st_sem']) ? number_format($row['gwa_1st_sem'], 2) : '0.00';
            $gwa2ndSem = isset($row['gwa_2nd_sem']) ? number_format($row['gwa_2nd_sem'], 2) : '0.00';
            
            fputcsv($file, [
                $row['permit_number'] ?? '',                    // No.
                $row['exam_date'] ?? '',                        // Date of Release
                $studentName,                                   // Student Name
                $row['last_school'] ?? '',                      // School Last Attended
                $genInfoTransmuted,                             // Gen Info. (30)
                $filipinoTransmuted,                            // Filipino (50)
                $englishTransmuted,                             // English (60)
                $scienceTransmuted,                             // Science (60)
                $mathTransmuted,                                // Math (50)
                $examRating,                                    // EXAM RATING
                $gwa1stSem,                                     // 1st SEM
                $gwa2ndSem,                                     // 2nd SEM
                $gwaScore                                       // GWA (overall)
            ]);
        }
        
        fclose($file);
        
        return $filepath;
    }
    
    /**
     * Get status from overall rating
     */
    private function getStatusFromRating($rating) {
        switch ($rating) {
            case 'Excellent':
            case 'Very Good':
            case 'Passed':
                return 'Qualified';
            case 'Conditional':
                return 'Conditional';
            case 'Failed':
                return 'Not Qualified';
            default:
                return 'Pending Review';
        }
    }
}
?>
