<?php
/**
 * Grading Configuration
 * Contains all grading-related settings for the admission system
 */

// Passing threshold for exam rating (not percentage)
define('PASSING_THRESHOLD', 75);

// Overall rating thresholds based on exam rating
define('RATING_EXCELLENT_MIN', 90);
define('RATING_VERY_GOOD_MIN', 85);
define('RATING_PASSED_MIN', 75);
define('RATING_CONDITIONAL_MIN', 70);

// Subject maximum scores
define('MAX_GEN_INFO_SCORE', 30);
define('MAX_FILIPINO_SCORE', 50);
define('MAX_ENGLISH_SCORE', 60);
define('MAX_SCIENCE_SCORE', 60);
define('MAX_MATH_SCORE', 50);

// Weight distribution
define('EXAM_WEIGHT', 0.50);
define('INTERVIEW_WEIGHT', 0.10);
define('GWA_WEIGHT', 0.40);

// Helper function to get overall rating based on exam rating (not percentage)
function getOverallRating($examRating) {
    if ($examRating >= RATING_EXCELLENT_MIN) {
        return 'Excellent';
    } elseif ($examRating >= RATING_VERY_GOOD_MIN) {
        return 'Very Good';
    } elseif ($examRating >= PASSING_THRESHOLD) {
        return 'Passed';
    } elseif ($examRating >= RATING_CONDITIONAL_MIN) {
        return 'Conditional';
    } else {
        return 'Failed';
    }
}

// Helper function to check if student passed based on exam rating
function hasPassed($examRating) {
    return $examRating >= PASSING_THRESHOLD;
}
?>
