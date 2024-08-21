<?php
session_start();
require 'dbinfo.php';
require 'check_well_being.php';
if (!isset($_SESSION["user_id"])) {
    header("Location: index.html");
    exit();
}

$conn = connect_db();
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_profile'])) {
        $first_name = $_POST['firstName'];
        $last_name = $_POST['lastName'];
        $email = $_POST['email'];

        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
        $stmt->bind_param("sssi", $first_name, $last_name, $email, $_SESSION["user_id"]);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Profile updated successfully!";
        } else {
            $_SESSION['message'] = "Error updating profile: " . $conn->error;
        }
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['currentPassword'];
        $new_password = $_POST['newPassword'];
        $confirm_password = $_POST['confirmPassword'];

        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION["user_id"]);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (password_verify($current_password, $user['password'])) {
            if ($new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed_password, $_SESSION["user_id"]);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Password changed successfully!";
                } else {
                    $_SESSION['message'] = "Error changing password: " . $conn->error;
                }
                $stmt->close();
            } else {
                $_SESSION['message'] = "New passwords do not match.";
            }
        } else {
            $_SESSION['message'] = "Current password is incorrect.";
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } elseif (isset($_POST['update_privacy'])) {
        $email_visibility = $_POST['emailVisibility'];
        $profile_visibility = $_POST['profileVisibility'];
        $two_factor_auth = isset($_POST['twoFactorAuth']) ? 1 : 0;

        $stmt = $conn->prepare("UPDATE users SET email_visibility = ?, profile_visibility = ?, two_factor_auth = ? WHERE id = ?");
        $stmt->bind_param("ssii", $email_visibility, $profile_visibility, $two_factor_auth, $_SESSION["user_id"]);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Privacy settings updated successfully!";
        } else {
            $_SESSION['message'] = "Error updating privacy settings: " . $conn->error;
        }
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

$stmt = $conn->prepare("SELECT username, email, first_name, last_name, email_visibility, profile_visibility, two_factor_auth FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$stmt->bind_result($username, $email, $first_name, $last_name, $email_visibility, $profile_visibility, $two_factor_auth);
$stmt->fetch();
$stmt->close();

// Calculate average well-being score
$stmt = $conn->prepare("SELECT AVG((happiness + workload + anxiety) / 3) as avg_score FROM well_being_scores WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$result = $stmt->get_result();
$avg_score = $result->fetch_assoc()['avg_score'];
$stmt->close();

// Calculate active days
$stmt = $conn->prepare("SELECT COUNT(DISTINCT date) as active_days FROM well_being_scores WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$result = $stmt->get_result();
$active_days = $result->fetch_assoc()['active_days'];
$stmt->close();

// Now close the connection
$conn->close();

$profile_image = $_SESSION['profile_image'] ?? './images/user-1.png';

$needMentalSupport = isset($_SESSION['need_mental_support']) && $_SESSION['need_mental_support'];
echo "<script>const needMentalSupport = " . ($needMentalSupport ? 'true' : 'false') . ";</script>";
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="profile_settings.css">
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
                <a href="dashboard.php" class="nav-link"><ion-icon name="home-outline"></ion-icon> Dashboard</a>
                <a href="./daily_quotes.php" class="nav-link"><ion-icon name="chatbubble-ellipses-outline"></ion-icon> Daily Quotes</a>
                <a href="./well_being_tracker.php" class="nav-link"><ion-icon name="analytics-outline"></ion-icon> Well-being Tracker</a>
                <a href="./community.php" class="nav-link"><ion-icon name="people-outline"></ion-icon> Community</a>
                <a href="./resources.php" class="nav-link"><ion-icon name="book-outline"></ion-icon> Resources</a>
                <a href="profile_settings.php" class="nav-link active"><ion-icon name="person-outline"></ion-icon> Profile Settings</a>
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
                <h2 class="settings-title">Profile Settings</h2>
                <p class="settings-subtitle">Manage your account information and privacy preferences</p>
            </div>
            <?php if (!empty($message)) : ?>
                <p id='updateMessage' class='alert alert-info'><?php echo $message; ?></p>
            <?php endif; ?>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="dashboard-card profile-card h-100">
                        <div class="card-body text-center">
                            <div class="profile-image-container">
                                <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile" class="profile-image-settings" id="profileImageLarge">
                                <div class="profile-image-overlay">
                                    <label for="profileImageInput" class="btn btn-sm btn-light rounded-circle image-upload-btn">
                                        <ion-icon name="camera-outline"></ion-icon>
                                    </label>
                                </div>
                            </div>
                            <h5 class="profile-name mt-3 mb-2"><?php echo htmlspecialchars($username); ?></h5>
                            <form action="profile_image.php" method="post" enctype="multipart/form-data" id="profileImageForm">
                                <input type="file" name="profileImage" id="profileImageInput" style="display: none;" accept="image/*">
                            </form>
                            <div class="mt-3">
                                <p>Average Well-being Score: <strong><?php echo number_format($avg_score, 2); ?></strong></p>
                                <p>Active Days: <strong><?php echo $active_days; ?></strong></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="dashboard-card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Personal Information</h5>
                            <form id="personalInfoForm" method="post" action="">
                                <div class="mb-3">
                                    <label for="firstName" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="firstName" name="firstName" value="<?php echo htmlspecialchars($first_name); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="lastName" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="lastName" name="lastName" value="<?php echo htmlspecialchars($last_name); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
                                </div>
                                <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-4 mt-4">
                <div class="col-md-6">
                    <div class="dashboard-card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Change Password</h5>
                            <form id="changePasswordForm" method="post" action="">
                                <div class="mb-3">
                                    <label for="currentPassword" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="currentPassword" name="currentPassword" required>
                                </div>
                                <div class="mb-3">
                                    <label for="newPassword" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="newPassword" name="newPassword" required>
                                </div>
                                <div class="mb-3">
                                    <label for="confirmPassword" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                                </div>
                                <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="dashboard-card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Privacy Settings</h5>
                            <form id="privacySettingsForm" method="post" action="">
                                <div class="mb-3">
                                    <label for="emailVisibility" class="form-label">Email Visibility</label>
                                    <select class="form-select" id="emailVisibility" name="emailVisibility">
                                        <option value="public" <?php echo $email_visibility == 'public' ? 'selected' : ''; ?>>Public</option>
                                        <option value="friends" <?php echo $email_visibility == 'friends' ? 'selected' : ''; ?>>Friends Only</option>
                                        <option value="private" <?php echo $email_visibility == 'private' ? 'selected' : ''; ?>>Private</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="profileVisibility" class="form-label">Profile Visibility</label>
                                    <select class="form-select" id="profileVisibility" name="profileVisibility">
                                        <option value="public" <?php echo $profile_visibility == 'public' ? 'selected' : ''; ?>>Public</option>
                                        <option value="friends" <?php echo $profile_visibility == 'friends' ? 'selected' : ''; ?>>Friends Only</option>
                                        <option value="private" <?php echo $profile_visibility == 'private' ? 'selected' : ''; ?>>Private</option>
                                    </select>
                                </div>
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="twoFactorAuth" name="twoFactorAuth" <?php echo $two_factor_auth ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="twoFactorAuth">Enable Two-Factor Authentication</label>
                                </div>
                                <button type="submit" name="update_privacy" class="btn btn-primary">Save Privacy Settings</button>
                            </form>
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

        document.getElementById('profileImageInput').addEventListener('change', function() {
            var form = document.getElementById('profileImageForm');
            var formData = new FormData(form);

            fetch('profile_image.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(result => {
                    if (result === 'success') {
                        var reader = new FileReader();
                        reader.onload = function(e) {
                            document.getElementById('profileImage').src = e.target.result;
                            document.getElementById('profileImageLarge').src = e.target.result;
                            showPopup('Profile picture successfully changed!');
                        }
                        reader.readAsDataURL(this.files[0]);
                    } else {
                        showPopup('Error uploading image: ' + result);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showPopup('An error occurred while changing the profile picture.');
                });
        });

        // Function to hide the message after 3 seconds
        function hideMessage() {
            var message = document.getElementById('updateMessage');
            if (message) {
                setTimeout(function() {
                    message.style.display = 'none';
                }, 3000);
            }
        }

        function showPopup(message) {
            const popup = document.createElement('div');
            popup.className = 'alert alert-success position-fixed top-0 start-50 translate-middle-x mt-3';
            popup.style.zIndex = '1050';
            popup.textContent = message;
            document.body.appendChild(popup);
            setTimeout(() => popup.remove(), 3000);
        }

        // Call the hideMessage function when the page loads
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
            // Mental support popup code
        }

        // Call this function when the page loads
        document.addEventListener('DOMContentLoaded', checkMentalSupportSuggestion);
    </script>
</body>

</html>