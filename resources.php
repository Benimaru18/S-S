<?php
session_start();
require 'dbinfo.php';
require 'check_well_being.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: index.html");
    exit();
}

$conn = connect_db();
$stmt = $conn->prepare("SELECT username, profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$stmt->bind_result($username, $profile_image);
$stmt->fetch();
$stmt->close();

$_SESSION['profile_image'] = $profile_image ?? './images/user-1.png';
$conn->close();

$needMentalSupport = isset($_SESSION['need_mental_support']) && $_SESSION['need_mental_support'];
echo "<script>const needMentalSupport = " . ($needMentalSupport ? 'true' : 'false') . ";</script>";
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resources - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="resource.css">
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
                <a href="well_being_tracker.php" class="nav-link"><ion-icon name="analytics-outline"></ion-icon> Well-being Tracker</a>
                <a href="community.php" class="nav-link"><ion-icon name="people-outline"></ion-icon> Community</a>
                <a href="resources.php" class="nav-link active"><ion-icon name="book-outline"></ion-icon> Resources</a>
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
            <div class="dashboard-header mb-4">
                <h2 class="settings-title">Resources</h2>
                <p class="settings-subtitle">Helpful resources for students and staff</p>
            </div>
            <div class="resources-container">
                <div class="resource-card">
                    <div class="card-body">
                        <h5 class="card-title">For Students</h5>
                        <ul class="resource-links">
                            <li><a href="https://www.goconqr.com/en/examtime/blog/study-tips-and-techniques/" class="resource-link" target="_blank">Study Tips and Techniques</a></li>
                            <li><a href="https://www.prospects.ac.uk/careers-advice" class="resource-link" target="_blank">Career Guidance</a></li>
                            <li><a href="https://www.mentalhealth.gov/get-help/immediate-help" class="resource-link" target="_blank">Mental Health Support</a></li>
                            <li><a href="https://studentaid.gov/understand-aid/types" class="resource-link" target="_blank">Financial Aid Information</a></li>
                            <li><a href="https://www.calendardate.com/academic.htm" class="resource-link" target="_blank">Academic Calendar</a></li>
                        </ul>
                    </div>
                </div>
                <div class="resource-card">
                    <div class="card-body">
                        <h5 class="card-title">For Staff</h5>
                        <ul class="resource-links">
                            <li><a href="https://www.edx.org/learn/professional-development" class="resource-link" target="_blank">Professional Development</a></li>
                            <li><a href="https://www.teacherplanet.com/" class="resource-link" target="_blank">Teaching Resources</a></li>
                            <li><a href="https://www.shrm.org/resourcesandtools/tools-and-samples/policies/pages/default.aspx" class="resource-link" target="_blank">HR Policies and Procedures</a></li>
                            <li><a href="https://www.techrepublic.com/article/it-support/" class="resource-link" target="_blank">IT Support</a></li>
                            <li><a href="https://www.wellsteps.com/blog/2020/01/02/workplace-wellness-programs-for-small-businesses/" class="resource-link" target="_blank">Staff Wellness Programs</a></li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Theme toggle functionality
        const themeToggle = document.getElementById('themeToggle');
        const body = document.body;

        function toggleTheme() {
            body.classList.toggle('light-mode');
            body.classList.toggle('dark-mode');
            const newTheme = body.classList.contains('dark-mode') ? 'dark-mode' : 'light-mode';
            localStorage.setItem('theme', newTheme);
            updateThemeToggleIcon();
        }

        function updateThemeToggleIcon() {
            const iconName = body.classList.contains('dark-mode') ? 'sunny-outline' : 'moon-outline';
            themeToggle.querySelector('ion-icon').setAttribute('name', iconName);
        }

        themeToggle.addEventListener('click', toggleTheme);

        // Apply theme on page load
        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('theme') || 'light-mode';
            body.classList.add(savedTheme);
            updateThemeToggleIcon();
            checkMentalSupportSuggestion();
        });

        // Sidebar toggle functionality
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

        function checkMentalSupportSuggestion() {
            if (typeof needMentalSupport !== 'undefined' && needMentalSupport) {
                showMentalSupportPopup();
            }
        }

        function showMentalSupportPopup() {
            // Mental support popup code (same as in other pages)
        }
    </script>
</body>

</html>