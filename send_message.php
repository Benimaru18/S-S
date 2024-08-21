<?php
session_start();
require 'dbinfo.php';

if (!isset($_SESSION["user_id"]) || !isset($_POST['message'])) {
    echo json_encode(['success' => false]);
    exit();
}

$conn = connect_db();
$stmt = $conn->prepare("INSERT INTO chat_messages (user_id, message) VALUES (?, ?)");
$stmt->bind_param("is", $_SESSION["user_id"], $_POST['message']);
$success = $stmt->execute();
$stmt->close();
$conn->close();

echo json_encode(['success' => $success]);
