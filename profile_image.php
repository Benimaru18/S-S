<?php
include 'dbinfo.php'; // Include the file where connect_db() is defined

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] === UPLOAD_ERR_OK) {
        // File upload path
        $uploadDir = 'uploads/';
        $fileName = basename($_FILES['profileImage']['name']);
        $uploadFilePath = $uploadDir . $fileName;

        // Move the uploaded file to the server
        if (move_uploaded_file($_FILES['profileImage']['tmp_name'], $uploadFilePath)) {
            // File uploaded successfully
            session_start();
            $user_id = $_SESSION['user_id'];
            $profileImage = $uploadFilePath;

            // Update the profile image in the database
            $conn = connect_db();
            $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
            $stmt->bind_param("si", $profileImage, $user_id);

            if ($stmt->execute()) {
                echo 'success';
            } else {
                echo 'Error updating profile image in the database';
            }

            $stmt->close();
            $conn->close();
        } else {
            echo 'Error uploading image';
        }
    } else {
        echo 'Error: ' . $_FILES['profileImage']['error'];
    }
} else {
    echo 'Invalid request';
}
?>
