<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'dbinfo.php';
$conn = connect_db();

if (!$conn) {
    die("Database connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $newPassword = $_POST['newPassword'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    if (!$email || !$newPassword || !$confirmPassword) {
        echo 'All fields are required.';
        exit;
    }

    if ($newPassword !== $confirmPassword) {
        echo 'Passwords do not match.';
        exit;
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo 'Email not found.';
        exit;
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    if (!$updateStmt) {
        die("Prepare failed: " . $conn->error);
    }
    $updateStmt->bind_param("ss", $hashedPassword, $email);

    if ($updateStmt->execute()) {
        echo 'Password reset successful. You can now login with your new password.';
    } else {
        echo 'Failed to reset password. Please try again.';
    }

    $updateStmt->close();
    $stmt->close();
    $conn->close();
} else {
    echo 'Invalid request method.';
}
