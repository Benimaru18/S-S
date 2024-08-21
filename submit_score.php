<?php
session_start();
require 'dbinfo.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = connect_db();
    $user_id = $_SESSION["user_id"];
    $date = $_POST["date"];
    $happiness = $_POST["happiness"];
    $workload = $_POST["workload"];
    $anxiety = $_POST["anxiety"];

    $stmt = $conn->prepare("INSERT INTO well_being_scores (user_id, date, happiness, workload, anxiety) 
                            VALUES (?, ?, ?, ?, ?) 
                            ON DUPLICATE KEY UPDATE 
                            happiness = VALUES(happiness), 
                            workload = VALUES(workload), 
                            anxiety = VALUES(anxiety)");
    $stmt->bind_param("isiii", $user_id, $date, $happiness, $workload, $anxiety);

    if ($stmt->execute()) {
        echo "Success";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
