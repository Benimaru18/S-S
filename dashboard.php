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
$conn->close();

$_SESSION['profile_image'] = $profile_image ?? './images/user-1.png';
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="sidebar.css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>

<body>
    <div class="dashboard-container">
        <button id="sidebarToggle" class="btn btn-primary d-md-none">
            <ion-icon name="menu-outline"></ion-icon>
        </button>
        <div class="sidebar">
            <div class="profile-section text-center mb-4">
                <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile" class="profile-image mb-2" id="profileImage">
                <h5><?php echo htmlspecialchars($username); ?></h5>
            </div>
            <nav>
                <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <ion-icon name="home-outline"></ion-icon> Dashboard
                </a>
                <a href="./daily_quotes.php" class="nav-link">
                    <ion-icon name="chatbubble-ellipses-outline"></ion-icon> Daily Quotes
                </a>
                <a href="./well_being_tracker.php" class="nav-link">
                    <ion-icon name="analytics-outline"></ion-icon> Well-being Tracker
                </a>
                <a href="./community.php" class="nav-link">
                    <ion-icon name="people-outline"></ion-icon> Community
                </a>
                <a href="./resources.php" class="nav-link">
                    <ion-icon name="book-outline"></ion-icon> Resources
                </a>
                <a href="privacy_settings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'privacy_settings.php' ? 'active' : ''; ?>">
                    <ion-icon name="person-outline"></ion-icon> Profile Settings
                </a>
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

        <div class="main-content">
            <div class="dashboard-header">
                <h2>Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
                <p class="lead">Your mental wellness journey at a glance</p>
            </div>
            <div class="row mt-4">
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="dashboard-card">
                        <div class="card-body">
                            <div class="card-icon">
                                <ion-icon name="chatbubble-ellipses-outline"></ion-icon>
                            </div>
                            <h5 class="card-title">Daily Quotes</h5>
                            <p class="card-text">Start your day with inspiration. Your personalized quote awaits you.</p>
                            <a href="./daily_quotes.php" class="btn btn-primary">View Today's Quote</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="dashboard-card">
                        <div class="card-body">
                            <div class="card-icon">
                                <ion-icon name="analytics-outline"></ion-icon>
                            </div>
                            <h5 class="card-title">Well-being Tracker</h5>
                            <p class="card-text">Monitor your mental health progress. Check your latest well-being score.</p>
                            <a href="./well_being_tracker.php" class="btn btn-primary">Track Well-being</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="dashboard-card">
                        <div class="card-body">
                            <div class="card-icon">
                                <ion-icon name="people-outline"></ion-icon>
                            </div>
                            <h5 class="card-title">Community</h5>
                            <p class="card-text">Connect with others on similar journeys. Share experiences and find support.</p>
                            <a href="./community.php" class="btn btn-primary">Join Discussions</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="dashboard-card">
                        <div class="card-body">
                            <div class="card-icon">
                                <ion-icon name="book-outline"></ion-icon>
                            </div>
                            <h5 class="card-title">Resources</h5>
                            <p class="card-text">Access a wealth of mental health resources and educational materials.</p>
                            <a href="./resources.php" class="btn btn-primary">Explore Library</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="dashboard-card">
                        <div class="card-body">
                            <div class="card-icon">
                                <ion-icon name="shield-checkmark-outline"></ion-icon>
                            </div>
                            <h5 class="card-title">Privacy Settings</h5>
                            <p class="card-text">Manage your account privacy and security preferences here.</p>
                            <a href="./privacy_settings.php" class="btn btn-primary">Update Settings</a>
                        </div>
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

        themeToggle.addEventListener('click', (e) => {
            e.preventDefault();
            toggleTheme();
        });

        // Apply theme on page load
        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('theme') || 'light-mode';
            body.classList.add(savedTheme);
            updateThemeToggleIcon();
        });
        document.addEventListener('DOMContentLoaded', () => {
            const currentTheme = localStorage.getItem('theme') || 'light-mode';
            document.body.classList.add(currentTheme);
            updateThemeToggleIcon();
        });

        function updateThemeToggleIcon() {
            const themeToggle = document.getElementById('theme-toggle');
            if (themeToggle) {
                const iconName = document.body.classList.contains('dark-mode') ? 'sunny-outline' : 'moon-outline';
                themeToggle.querySelector('ion-icon').setAttribute('name', iconName);
            }
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