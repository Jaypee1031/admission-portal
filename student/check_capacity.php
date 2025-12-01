<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/test_permit.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Check if user is logged in as student
if (!isStudent()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get POST data
$examDate = sanitizeInput($_POST['exam_date'] ?? '');
$examTime = sanitizeInput($_POST['exam_time'] ?? '');
$examRoom = sanitizeInput($_POST['exam_room'] ?? '');

// Validate input
if (empty($examDate) || empty($examTime) || empty($examRoom)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

try {
    // Initialize TestPermit class
    $testPermit = new TestPermit();
    
    // Check capacity
    $capacityInfo = $testPermit->getSlotCapacityInfo($examDate, $examTime, $examRoom);
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($capacityInfo);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'available' => false,
        'message' => 'Unable to check slot availability. Please try again.',
        'current_bookings' => 0,
        'max_capacity' => 50,
        'remaining_slots' => 0
    ]);
}
?>
