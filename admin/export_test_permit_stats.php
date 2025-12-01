<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/test_permit.php';
require_once '../includes/test_results.php';

// Redirect if not logged in as admin
if (!isAdmin()) {
    redirect('../index.php');
}

$testPermit = new TestPermit();
$testResults = new TestResults();
$db = getDB();

// Overall statistics
$stats = $testPermit->getTestPermitStats();

// Monthly test permit statistics (last 12 months)
$monthlyStats = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN status = 'Approved' THEN 1 END) as approved,
            COUNT(CASE WHEN status = 'Pending' THEN 1 END) as pending,
            COUNT(CASE WHEN status = 'Rejected' THEN 1 END) as rejected
        FROM test_permits 
        WHERE DATE_FORMAT(issued_at, '%Y-%m') = ?
    ");
    $stmt->execute([$month]);
    $monthlyStats[$month] = $stmt->fetch();
}

// Course preference statistics (top 10)
$courseStats = $db->prepare("
    SELECT 
        af.course_first,
        COUNT(*) as count
    FROM test_permits tp
    JOIN students s ON tp.student_id = s.id
    JOIN admission_forms af ON s.id = af.student_id
    GROUP BY af.course_first
    ORDER BY count DESC
    LIMIT 10
");
$courseStats->execute();
$coursePreferences = $courseStats->fetchAll();

// Test results statistics
$testResultsStats = $testResults->getTestResultStats();

// Exam performance statistics
$examPerformanceStmt = $db->prepare("
    SELECT 
        COUNT(*) as total_exams,
        COUNT(CASE WHEN exam_rating >= 75 THEN 1 END) as passed,
        COUNT(CASE WHEN exam_rating < 75 THEN 1 END) as failed,
        AVG(exam_rating) as average_rating,
        MAX(exam_rating) as highest_rating,
        MIN(exam_rating) as lowest_rating
    FROM test_results 
    WHERE exam_rating IS NOT NULL
");
$examPerformanceStmt->execute();
$examPerformance = $examPerformanceStmt->fetch();

// Subject performance statistics
$subjectPerformanceStmt = $db->prepare("
    SELECT 
        AVG(gen_info_transmuted) as avg_gen_info,
        AVG(filipino_transmuted) as avg_filipino,
        AVG(english_transmuted) as avg_english,
        AVG(science_transmuted) as avg_science,
        AVG(math_transmuted) as avg_math
    FROM test_results 
    WHERE gen_info_transmuted IS NOT NULL
");
$subjectPerformanceStmt->execute();
$subjectPerformance = $subjectPerformanceStmt->fetch();

// Monthly test results statistics (last 12 months)
$monthlyTestResults = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN exam_rating >= 75 THEN 1 END) as passed,
            COUNT(CASE WHEN exam_rating < 75 THEN 1 END) as failed,
            AVG(exam_rating) as avg_rating
        FROM test_results 
        WHERE DATE_FORMAT(processed_at, '%Y-%m') = ?
    ");
    $stmt->execute([$month]);
    $monthlyTestResults[$month] = $stmt->fetch();
}

// Prepare CSV output (Excel compatible)
$filename = 'Admission_Analytics_' . date('Y-m-d_H-i-s') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$fh = fopen('php://output', 'w');

// UTF-8 BOM so Excel shows characters correctly
fwrite($fh, "\xEF\xBB\xBF");

// Helper to write a blank line
$blank = function() use ($fh) {
    fputcsv($fh, ['']);
};

// Section 1: Overall statistics
fputcsv($fh, ['Admission Analytics Dashboard']);
$blank();
fputcsv($fh, ['Overall Test Permit Statistics']);
fputcsv($fh, ['Metric', 'Value']);
fputcsv($fh, ['Total Test Permits', (int)($stats['total_permits'] ?? 0)]);
fputcsv($fh, ['Upcoming Exams', (int)($stats['upcoming_exams'] ?? 0)]);
fputcsv($fh, ['Completed Exams', (int)($stats['past_exams'] ?? 0)]);

// If additional counts are available
if (isset($stats['approved_permits']) || isset($stats['pending_permits']) || isset($stats['rejected_permits'])) {
    fputcsv($fh, ['Approved Permits', (int)($stats['approved_permits'] ?? 0)]);
    fputcsv($fh, ['Pending Permits', (int)($stats['pending_permits'] ?? 0)]);
    fputcsv($fh, ['Rejected Permits', (int)($stats['rejected_permits'] ?? 0)]);
}

$totalPermits = max((int)($stats['total_permits'] ?? 0), 1);
$activeRate = ($stats['upcoming_exams'] ?? 0) / $totalPermits * 100;
fputcsv($fh, ['Active Rate (%)', round($activeRate, 1)]);

$blank();

// Section 2: Monthly Test Permit Statistics
fputcsv($fh, ['Monthly Test Permit Statistics (Last 12 Months)']);
fputcsv($fh, ['Month', 'Total', 'Approved', 'Pending', 'Rejected']);
foreach ($monthlyStats as $month => $row) {
    $label = date('M Y', strtotime($month . '-01'));
    fputcsv($fh, [
        $label,
        (int)($row['total'] ?? 0),
        (int)($row['approved'] ?? 0),
        (int)($row['pending'] ?? 0),
        (int)($row['rejected'] ?? 0),
    ]);
}

$blank();

// Section 3: Exam Performance Summary
fputcsv($fh, ['Exam Performance Summary']);
fputcsv($fh, ['Metric', 'Value']);
$totalExams = (int)($examPerformance['total_exams'] ?? 0);
$passed = (int)($examPerformance['passed'] ?? 0);
$failed = (int)($examPerformance['failed'] ?? 0);
$avgRating = (float)($examPerformance['average_rating'] ?? 0);
$highest = (float)($examPerformance['highest_rating'] ?? 0);
$lowest = (float)($examPerformance['lowest_rating'] ?? 0);
$passRate = $totalExams > 0 ? $passed / $totalExams * 100 : 0;

fputcsv($fh, ['Total Exams', $totalExams]);
fputcsv($fh, ['Students Passed (>= 75)', $passed]);
fputcsv($fh, ['Students Failed (< 75)', $failed]);
fputcsv($fh, ['Average Rating (%)', number_format($avgRating, 1)]);
fputcsv($fh, ['Highest Rating (%)', number_format($highest, 1)]);
fputcsv($fh, ['Lowest Rating (%)', number_format($lowest, 1)]);
fputcsv($fh, ['Pass Rate (%)', number_format($passRate, 1)]);

$blank();

// Section 4: Subject Performance Breakdown
fputcsv($fh, ['Subject Performance Breakdown (Average Transmuted Scores)']);
fputcsv($fh, ['Subject', 'Average Score']);
fputcsv($fh, ['General Info', number_format((float)($subjectPerformance['avg_gen_info'] ?? 0), 1)]);
fputcsv($fh, ['Filipino', number_format((float)($subjectPerformance['avg_filipino'] ?? 0), 1)]);
fputcsv($fh, ['English', number_format((float)($subjectPerformance['avg_english'] ?? 0), 1)]);
fputcsv($fh, ['Science', number_format((float)($subjectPerformance['avg_science'] ?? 0), 1)]);
fputcsv($fh, ['Mathematics', number_format((float)($subjectPerformance['avg_math'] ?? 0), 1)]);

$blank();

// Section 5: Monthly Test Result Statistics
fputcsv($fh, ['Monthly Test Result Statistics (Last 12 Months)']);
fputcsv($fh, ['Month', 'Total Exams', 'Passed', 'Failed', 'Average Rating (%)']);
foreach ($monthlyTestResults as $month => $row) {
    $label = date('M Y', strtotime($month . '-01'));
    fputcsv($fh, [
        $label,
        (int)($row['total'] ?? 0),
        (int)($row['passed'] ?? 0),
        (int)($row['failed'] ?? 0),
        number_format((float)($row['avg_rating'] ?? 0), 1),
    ]);
}

$blank();

// Section 6: Popular Course Choices (Top 10)
$totalCourseCount = array_sum(array_column($coursePreferences, 'count')) ?: 1;
fputcsv($fh, ['Popular Course Choices (Top 10)']);
fputcsv($fh, ['Rank', 'Course', 'Applications', 'Share (%)']);
$rank = 0;
foreach ($coursePreferences as $course) {
    $rank++;
    $count = (int)($course['count'] ?? 0);
    $percentage = $count / $totalCourseCount * 100;
    fputcsv($fh, [
        $rank,
        $course['course_first'] ?? 'Unknown Course',
        $count,
        number_format($percentage, 1),
    ]);
}

fclose($fh);
exit;
