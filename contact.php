<?php

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

require_once 'dbinfo.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);

    if (!$name || !$email || !$message) {
        echo json_encode(["success" => false, "message" => "Invalid input data"]);
        exit;
    }

    $conn = connect_db();
    $sql = "INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        error_log("Error preparing statement: " . $conn->error);
        echo json_encode(["success" => false, "message" => "Error preparing statement"]);
        exit;
    }

    $stmt->bind_param("sss", $name, $email, $message);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Message sent successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error sending message"]);
    }
    

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["success" => true, "message" => "Message sent successfully"]);
}

exit;
