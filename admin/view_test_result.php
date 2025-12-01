<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/test_results.php';

// Redirect if not logged in as admin
if (!isAdmin()) {
    http_response_code(403);
    echo '<div class="alert alert-danger">Access denied.</div>';
    exit;
}

$resultId = (int)($_GET['id'] ?? 0);

if (!$resultId) {
    echo '<div class="alert alert-danger">Invalid result ID.</div>';
    exit;
}

$testResults = new TestResults();
$db = getDB();

// Get result details
$stmt = $db->prepare("
    SELECT tr.*, s.first_name, s.last_name, s.middle_name, s.email, s.type,
           tp.exam_date as permit_exam_date, tp.exam_time, tp.exam_room,
           a.full_name as processed_by_name
    FROM test_results tr
    JOIN students s ON tr.student_id = s.id
    LEFT JOIN test_permits tp ON tr.permit_number = tp.permit_number
    LEFT JOIN admins a ON tr.processed_by = a.id
    WHERE tr.id = ?
");
$stmt->execute([$resultId]);
$result = $stmt->fetch();

if (!$result) {
    echo '<div class="alert alert-warning">Test result not found.</div>';
    exit;
}
?>
<div class="test-result-view">
    <div class="row">
        <!-- Student Information -->
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-user me-2"></i>Student Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <strong>Name:</strong> <?php echo htmlspecialchars(($result['first_name'] ?? '') . ' ' . ($result['last_name'] ?? '')); ?>
                            <?php if ($result['middle_name']): ?>
                                <?php echo htmlspecialchars(' ' . $result['middle_name']); ?>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Email:</strong> <?php echo htmlspecialchars($result['email']); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Student Type:</strong> 
                            <span class="badge bg-<?php echo $result['type'] === 'Freshman' ? 'info' : 'warning'; ?>">
                                <?php echo $result['type']; ?>
                            </span>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Permit Number:</strong> 
                            <span class="badge bg-info"><?php echo htmlspecialchars($result['permit_number']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Exam Information -->
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-calendar me-2"></i>Exam Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <strong>Exam Date:</strong> <?php echo date('M d, Y', strtotime($result['exam_date'])); ?>
                        </div>
                        <div class="col-md-4 mb-2">
                            <strong>Exam Time:</strong> <?php echo date('g:i A', strtotime($result['exam_time'])); ?>
                        </div>
                        <div class="col-md-4 mb-2">
                            <strong>Exam Room:</strong> <?php echo htmlspecialchars($result['exam_room']); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Test Results -->
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>Test Results
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <div class="text-center">
                                <h4 class="text-primary"><?php echo $result['raw_score'] ?? 'N/A'; ?></h4>
                                <p class="text-muted mb-0">Raw Score</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="text-center">
                                <h4 class="text-success"><?php echo number_format($result['percentage_score'] ?? 0, 1); ?>%</h4>
                                <p class="text-muted mb-0">Percentage Score</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="text-center">
                                <h4 class="text-info"><?php echo number_format($result['percentile_rank'] ?? 0, 1); ?></h4>
                                <p class="text-muted mb-0">Percentile Rank</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="text-center">
                                <h4>
                                    <span class="badge bg-<?php 
                                        echo $result['overall_rating'] === 'Passed' ? 'success' : 
                                            ($result['overall_rating'] === 'Failed' ? 'danger' : 'warning'); 
                                    ?> fs-6">
                                        <?php echo htmlspecialchars($result['overall_rating'] ?? 'N/A'); ?>
                                    </span>
                                </h4>
                                <p class="text-muted mb-0">Overall Rating</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recommendation -->
        <?php if ($result['recommendation']): ?>
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0">
                        <i class="fas fa-comment me-2"></i>Recommendation
                    </h6>
                </div>
                <div class="card-body">
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($result['recommendation'])); ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Processing Information -->
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Processing Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <strong>Processed By:</strong> <?php echo htmlspecialchars($result['processed_by_name'] ?? 'System'); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Processed Date:</strong> <?php echo date('M d, Y H:i', strtotime($result['processed_at'])); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Created:</strong> <?php echo date('M d, Y H:i', strtotime($result['created_at'])); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Last Updated:</strong> <?php echo date('M d, Y H:i', strtotime($result['updated_at'])); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Analysis -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>Performance Analysis
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="text-center">
                                <div class="progress mb-2" style="height: 20px;">
                                    <div class="progress-bar bg-<?php echo ($result['percentage_score'] ?? 0) >= 75 ? 'success' : 'danger'; ?>" 
                                         style="width: <?php echo min(100, $result['percentage_score'] ?? 0); ?>%">
                                        <?php echo number_format($result['percentage_score'] ?? 0, 1); ?>%
                                    </div>
                                </div>
                                <small class="text-muted">Percentage Score</small>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="text-center">
                                <div class="progress mb-2" style="height: 20px;">
                                    <div class="progress-bar bg-info" 
                                         style="width: <?php echo min(100, $result['percentile_rank'] ?? 0); ?>%">
                                        <?php echo number_format($result['percentile_rank'] ?? 0, 1); ?>
                                    </div>
                                </div>
                                <small class="text-muted">Percentile Rank</small>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="text-center">
                                <h5 class="text-<?php 
                                    echo $result['overall_rating'] === 'Passed' ? 'success' : 
                                        ($result['overall_rating'] === 'Failed' ? 'danger' : 'warning'); 
                                ?>">
                                    <?php 
                                    if ($result['overall_rating'] === 'Passed') {
                                        echo '<i class="fas fa-check-circle"></i> Qualified';
                                    } elseif ($result['overall_rating'] === 'Failed') {
                                        echo '<i class="fas fa-times-circle"></i> Not Qualified';
                                    } else {
                                        echo '<i class="fas fa-question-circle"></i> ' . htmlspecialchars($result['overall_rating']);
                                    }
                                    ?>
                                </h5>
                                <small class="text-muted">Final Status</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
