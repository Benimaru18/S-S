<?php
require_once 'dbinfo.php';

function checkWellBeing($userId) {
    $conn = connect_db();
    $stmt = $conn->prepare("SELECT happiness, workload, anxiety FROM well_being_scores WHERE user_id = ? ORDER BY date DESC LIMIT 3");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $scores = [
        'happiness' => [],
        'workload' => [],
        'anxiety' => []
    ];

    while ($row = $result->fetch_assoc()) {
        $scores['happiness'][] = $row['happiness'];
        $scores['workload'][] = $row['workload'];
        $scores['anxiety'][] = $row['anxiety'];
    }

    $needSupport = false;
    $lowCategories = [];

    foreach ($scores as $category => $values) {
        if (count($values) == 3) {
            $average = array_sum($values) / 3;
            if ($average < 1.5) {
                $needSupport = true;
                $lowCategories[] = $category;
            }
        }
    }

    if ($needSupport) {
        $_SESSION['need_mental_support'] = true;
        $_SESSION['low_categories'] = $lowCategories;
    } else {
        $_SESSION['need_mental_support'] = false;
        unset($_SESSION['low_categories']);
    }

    $stmt->close();
    $conn->close();
}

// Call this function at the beginning of each page load
checkWellBeing($_SESSION['user_id']);
