<?php
// Test permit management functions

class TestPermit {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    // Generate unique permit number
    private function generatePermitNumber() {
        $prefix = 'TP';
        $year = date('Y');
        $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return $prefix . $year . $random;
    }
    
    // Save test permit data
    public function saveTestPermit($studentId, $permitData) {
        try {
            // Check capacity limits before saving
            $capacityCheck = $this->checkCapacityLimit($permitData['exam_date'], $permitData['exam_time'], $permitData['exam_room']);
            if (!$capacityCheck['available']) {
                return ['success' => false, 'message' => $capacityCheck['message']];
            }
            
            // Check if permit already exists
            $stmt = $this->db->prepare("SELECT id FROM test_permits WHERE student_id = ?");
            $stmt->execute([$studentId]);
            
            if ($stmt->rowCount() > 0) {
                // Update existing permit
                $stmt = $this->db->prepare("
                    UPDATE test_permits SET
                        exam_date = ?, exam_time = ?, exam_room = ?, remarks = ?, status = 'Pending'
                    WHERE student_id = ?
                ");
                
                $stmt->execute([
                    $permitData['exam_date'],
                    $permitData['exam_time'],
                    $permitData['exam_room'],
                    $permitData['remarks'],
                    $studentId
                ]);
                
                $permitId = $this->db->prepare("SELECT id FROM test_permits WHERE student_id = ?")->execute([$studentId]) ? 
                           $this->db->prepare("SELECT id FROM test_permits WHERE student_id = ?")->fetchColumn() : null;
            } else {
                // Insert new permit
                $permitNumber = $this->generatePermitNumber();
                
                $stmt = $this->db->prepare("
                    INSERT INTO test_permits (student_id, permit_number, exam_date, exam_time, exam_room, remarks, status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'Pending')
                ");
                
                $stmt->execute([
                    $studentId,
                    $permitNumber,
                    $permitData['exam_date'],
                    $permitData['exam_time'],
                    $permitData['exam_room'],
                    $permitData['remarks']
                ]);
                
                $permitId = $this->db->lastInsertId();
            }
            
            return ['success' => true, 'message' => 'Test permit saved successfully', 'permit_id' => $permitId];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Get test permit data
    public function getTestPermit($studentId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM test_permits WHERE student_id = ?");
            $stmt->execute([$studentId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }
    
    // Check if test permit exists
    public function hasTestPermit($studentId) {
        try {
            $stmt = $this->db->prepare("SELECT id FROM test_permits WHERE student_id = ?");
            $stmt->execute([$studentId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Get available exam dates (weekdays only with 2-day intervals)
    public function getAvailableExamDates() {
        $dates = [];
        $startDate = date('Y-m-d', strtotime('+3 days')); // Start from 3 days from today (skip 2 days)
        $currentDate = strtotime($startDate);
        
        // Generate dates for the next 30 days with 2-day intervals, weekdays only
        $dateCount = 0;
        while ($dateCount < 15) { // Limit to 15 available dates
            $date = date('Y-m-d', $currentDate);
            $dayOfWeek = date('N', $currentDate); // 1 = Monday, 7 = Sunday
            
            // Only include weekdays (Monday to Friday)
            if ($dayOfWeek <= 5) {
                $dates[] = $date;
                $dateCount++;
                
                // Skip 2 days after each weekday selection
                $currentDate = strtotime('+3 days', $currentDate);
            } else {
                // If it's weekend, move to next weekday
                $currentDate = strtotime('+1 day', $currentDate);
            }
        }
        
        return $dates;
    }
    
    // Get available exam times
    public function getAvailableExamTimes() {
        return [
            '08:30' => '8:30 AM - 11:00 AM',
            '13:00' => '1:00 PM - 3:30 PM'
        ];
    }
    
    // Get available exam rooms
    public function getAvailableExamRooms() {
        return [
            'qsu_student_center' => 'QSU Student Center - Testing room'
        ];
    }
    
    
    // Get test permit statistics
    public function getTestPermitStats() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_permits,
                    COUNT(CASE WHEN exam_date >= CURDATE() THEN 1 END) as upcoming_exams,
                    COUNT(CASE WHEN exam_date < CURDATE() THEN 1 END) as past_exams
                FROM test_permits
            ");
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            return ['total_permits' => 0, 'upcoming_exams' => 0, 'past_exams' => 0];
        }
    }
    
    // Get upcoming exams
    public function getUpcomingExams($limit = 10) {
        try {
            $stmt = $this->db->prepare("
                SELECT tp.*, s.name, s.email, s.type
                FROM test_permits tp
                JOIN students s ON tp.student_id = s.id
                WHERE tp.exam_date >= CURDATE()
                ORDER BY tp.exam_date, tp.exam_time
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // Check exam room availability
    public function checkRoomAvailability($examDate, $examTime, $examRoom, $excludePermitId = null) {
        try {
            $sql = "SELECT COUNT(*) FROM test_permits WHERE exam_date = ? AND exam_time = ? AND exam_room = ?";
            $params = [$examDate, $examTime, $examRoom];
            
            if ($excludePermitId) {
                $sql .= " AND id != ?";
                $params[] = $excludePermitId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchColumn() == 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Get admin settings
    private function getAdminSettings() {
        try {
            $stmt = $this->db->prepare("SELECT setting_key, setting_value FROM test_permit_settings");
            $stmt->execute();
            $settings = [];
            while ($row = $stmt->fetch()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            
            // Default values if no settings found
            $defaults = [
                'max_permits_per_day' => 50,
                'exam_duration' => 150,
                'morning_start_time' => '08:30',
                'morning_end_time' => '11:00',
                'afternoon_start_time' => '13:00',
                'afternoon_end_time' => '15:30'
            ];
            
            return array_merge($defaults, $settings);
        } catch (PDOException $e) {
            // Return defaults if table doesn't exist
            return [
                'max_permits_per_day' => 50,
                'exam_duration' => 150,
                'morning_start_time' => '08:30',
                'morning_end_time' => '11:00',
                'afternoon_start_time' => '13:00',
                'afternoon_end_time' => '15:30'
            ];
        }
    }
    
    // Check capacity limit for a specific slot
    public function checkCapacityLimit($examDate, $examTime, $examRoom) {
        try {
            $settings = $this->getAdminSettings();
            $maxCapacity = (int)$settings['max_permits_per_day'];
            
            // Count current bookings for this specific slot
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM test_permits 
                WHERE exam_date = ? AND exam_time = ? AND exam_room = ? 
                AND status IN ('Pending', 'Approved')
            ");
            $stmt->execute([$examDate, $examTime, $examRoom]);
            $currentBookings = $stmt->fetchColumn();
            
            $remainingSlots = $maxCapacity - $currentBookings;
            
            if ($currentBookings >= $maxCapacity) {
                return [
                    'available' => false,
                    'message' => "This exam slot is full. Maximum capacity ($maxCapacity students) has been reached. Please choose another date or time.",
                    'current_bookings' => $currentBookings,
                    'max_capacity' => $maxCapacity,
                    'remaining_slots' => 0
                ];
            }
            
            return [
                'available' => true,
                'message' => "Slot available. $remainingSlots spots remaining.",
                'current_bookings' => $currentBookings,
                'max_capacity' => $maxCapacity,
                'remaining_slots' => $remainingSlots
            ];
            
        } catch (PDOException $e) {
            return [
                'available' => false,
                'message' => 'Unable to check slot availability. Please try again.',
                'current_bookings' => 0,
                'max_capacity' => 50,
                'remaining_slots' => 0
            ];
        }
    }
    
    // Get capacity information for display
    public function getSlotCapacityInfo($examDate, $examTime, $examRoom) {
        return $this->checkCapacityLimit($examDate, $examTime, $examRoom);
    }
}

// Initialize TestPermit class
$testPermit = new TestPermit();
?>
