<?php
session_start();
require 'dbinfo.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: index.html");
    exit();
}

$conn = connect_db();
$stmt = $conn->prepare("SELECT username, email, profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$stmt->bind_result($username, $email, $profile_image);
$stmt->fetch();
$stmt->close();
$conn->close();

$_SESSION['profile_image'] = $profile_image ?? './images/user-1.png';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mental Support - Contact a Professional</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="mental_support.css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>

<body class="light-mode">
    <div class="dashboard-container">
        <button id="sidebarToggle" class="btn btn-primary d-md-none">
            <ion-icon name="menu-outline"></ion-icon>
        </button>
        <div class="sidebar">
            <div class="profile-section text-center mb-4">
                <img src="<?php echo htmlspecialchars($_SESSION['profile_image']); ?>" alt="Profile" class="profile-image mb-2" id="profileImage">
                <h5><?php echo htmlspecialchars($username); ?></h5>
            </div>
            <nav>
                <a href="dashboard.php" class="nav-link"><ion-icon name="home-outline"></ion-icon> Dashboard</a>
                <a href="daily_quotes.php" class="nav-link"><ion-icon name="chatbubble-ellipses-outline"></ion-icon> Daily Quotes</a>
                <a href="well_being_tracker.php" class="nav-link active"><ion-icon name="analytics-outline"></ion-icon> Well-being Tracker</a>
                <a href="./community.php" class="nav-link"><ion-icon name="people-outline"></ion-icon> Community</a>
                <a href="./resources.php" class="nav-link"><ion-icon name="book-outline"></ion-icon> Resources</a>
                <a href="privacy_settings.php" class="nav-link"><ion-icon name="person-outline"></ion-icon> Profile Settings</a>
            </nav>
            <div class="sidebar-footer">
                <button id="themeToggle" class="btn btn-outline-secondary btn-sm w-100 mb-2">
                    <ion-icon name="moon-outline"></ion-icon> Toggle Dark Mode
                </button>
                <a href="logout.php" class="btn btn-outline-danger btn-sm w-100">
                    <ion-icon name="log-out-outline"></ion-icon> Logout
                </a>
            </div>
        </div>
        <div class="main-content p-4">
            <h1 class="mb-4">Contact a Mental Health Professional</h1>
            <p>We're here to help you connect with a mental health professional. Please fill out the form below, and we'll arrange a consultation for you.</p>
            <form id="mentalSupportForm">
                <div class="mb-3">
                    <label for="name" class="form-label">Your Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($username); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Your Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="preferredDate" class="form-label">Preferred Consultation Date</label>
                    <input type="date" class="form-control" id="preferredDate" name="preferredDate" required>
                </div>
                <div class="mb-3">
                    <label for="message" class="form-label">Additional Information (Optional)</label>
                    <textarea class="form-control" id="message" name="message" rows="4"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Request Consultation</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('theme') || 'light-mode';
            document.body.classList.add(savedTheme);
            updateThemeToggleIcon();

            document.getElementById('themeToggle').addEventListener('click', toggleTheme);

            document.getElementById('mentalSupportForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                fetch('submit_consultation.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Thank you for reaching out. A mental health professional will contact you soon to arrange a consultation.');
                            window.location.href = 'well_being_tracker.php';
                        } else {
                            alert('There was an error submitting your request. Please try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                    });
            });

        });

        function toggleTheme() {
            document.body.classList.toggle('dark-mode');
            updateThemeToggleIcon();
            const newTheme = document.body.classList.contains('dark-mode') ? 'dark-mode' : 'light-mode';
            localStorage.setItem('theme', newTheme);
        }

        function updateThemeToggleIcon() {
            const iconName = document.body.classList.contains('dark-mode') ? 'sunny-outline' : 'moon-outline';
            document.getElementById('themeToggle').querySelector('ion-icon').setAttribute('name', iconName);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            const backdrop = document.createElement('div');
            backdrop.classList.add('sidebar-backdrop');
            document.body.appendChild(backdrop);

            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                mainContent.classList.toggle('sidebar-active');
                backdrop.classList.toggle('active');
            });

            backdrop.addEventListener('click', function() {
                sidebar.classList.remove('active');
                mainContent.classList.remove('sidebar-active');
                backdrop.classList.remove('active');
            });
        });
    </script>
</body>

</html>