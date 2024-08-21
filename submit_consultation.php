<?php
session_start();
require 'dbinfo.php';

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit();
}

$user_id = $_SESSION["user_id"];
$name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$preferred_date = filter_input(INPUT_POST, 'preferredDate', FILTER_SANITIZE_STRING);
$message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);

$conn = connect_db();
$stmt = $conn->prepare("INSERT INTO consultation_requests (user_id, name, email, preferred_date, message) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("issss", $user_id, $name, $email, $preferred_date, $message);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Database error"]);
}

$stmt->close();
$conn->close();
