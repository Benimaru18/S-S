<?php
session_start();
require 'dbinfo.php';

if (!isset($_SESSION["user_id"])) {
    echo json_encode([]);
    exit();
}

$user_id = $_SESSION["user_id"];

$conn = connect_db();
$stmt = $conn->prepare("SELECT DATE_FORMAT(date, '%Y-%m-%d') as date, happiness, workload, anxiety FROM well_being_scores WHERE user_id = ? ORDER BY date ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$scores = [];

while ($row = $result->fetch_assoc()) {
    $scores[] = [
        'date' => $row['date'],
        'happiness' => (int)$row['happiness'],
        'workload' => (int)$row['workload'],
        'anxiety' => (int)$row['anxiety']
    ];
}

$stmt->close();
$conn->close();

echo json_encode($scores);
?>
