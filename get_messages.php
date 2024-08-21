<?php
require 'dbinfo.php';

$conn = connect_db();
$stmt = $conn->prepare("SELECT chat_messages.*, users.username FROM chat_messages JOIN users ON chat_messages.user_id = users.id ORDER BY chat_messages.timestamp DESC LIMIT 50");
$stmt->execute();
$result = $stmt->get_result();
$messages = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

echo json_encode(array_reverse($messages));
