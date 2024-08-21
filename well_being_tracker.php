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
    <title>Well-being Tracker - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="well_being_tracker.css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
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
            <div class="dashboard-header mb-4">
                <h2 class="settings-title">Well-being Tracker</h2>
                <p class="settings-subtitle">Track your daily well-being scores</p>
            </div>
            <section class="well-being-section p-5">
                <div class="container">
                    <div class="well-being-content text-center">
                        <h2 class="section-title mb-4">Submit Your Daily Scores</h2>
                        <form id="scoreForm" class="mb-4">
                            <div class="mb-3">
                                <label for="date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="date" name="date" required pattern="\d{4}-\d{2}-\d{2}">
                            </div>
                            <div class="mb-3">
                                <label for="happiness" class="form-label">Happiness (1-5)</label>
                                <input type="number" class="form-control" id="happiness" name="happiness" min="1" max="10" required>
                            </div>
                            <div class="mb-3">
                                <label for="workload" class="form-label">Workload (1-5)</label>
                                <input type="number" class="form-control" id="workload" name="workload" min="1" max="10" required>
                            </div>
                            <div class="mb-3">
                                <label for="anxiety" class="form-label">Anxiety (1-5)</label>
                                <input type="number" class="form-control" id="anxiety" name="anxiety" min="1" max="10" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </form>
                        <h2 class="section-title mb-4">Your Well-being Over Time</h2>
                        <div class="container-fluid mt-5">
                            <div class="row">
                                <div class="col-12">
                                    <div class="chart-container">
                                        <div id="chart_div" style="width: 100%; height: 400px;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('theme') || 'light-mode';
            document.body.classList.add(savedTheme);
            updateThemeToggleIcon();

            const today = new Date().toISOString().split('T')[0];
            document.getElementById('date').value = today;

            google.charts.load('current', {
                packages: ['corechart', 'line']
            });
            google.charts.setOnLoadCallback(drawChart);

            document.getElementById('scoreForm').addEventListener('submit', function(event) {
                event.preventDefault();
                const formData = new FormData(this);

                fetch('submit_score.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        console.log('Form submission response:', data);
                        if (data === "Success") {
                            drawChart();
                        } else {
                            console.error('Error:', data);
                        }
                    })
                    .catch(error => console.error('Error:', error));
            });

            document.getElementById('themeToggle').addEventListener('click', toggleTheme);

            checkMentalSupportSuggestion();
        });

        function drawChart() {
            const isDarkMode = document.body.classList.contains('dark-mode');
            const textColor = isDarkMode ? '#ffffff' : '#000000';
            const backgroundColor = isDarkMode ? '#333333' : '#ffffff';

            fetch('get_scores.php?_=' + new Date().getTime())
                .then(response => response.json())
                .then(data => {
                    const chartData = new google.visualization.DataTable();
                    chartData.addColumn('date', 'Date');
                    chartData.addColumn('number', 'Happiness');
                    chartData.addColumn('number', 'Workload');
                    chartData.addColumn('number', 'Anxiety');

                    data.sort((a, b) => new Date(a.date) - new Date(b.date));

                    data.forEach(item => {
                        const [year, month, day] = item.date.split('-');
                        chartData.addRow([
                            new Date(parseInt(year), parseInt(month) - 1, parseInt(day)),
                            parseInt(item.happiness),
                            parseInt(item.workload),
                            parseInt(item.anxiety)
                        ]);
                    });

                    const options = {
                        title: 'Well-being Scores Over Time',
                        curveType: 'function',
                        legend: {
                            position: 'bottom',
                            textStyle: {
                                color: textColor,
                                fontSize: 14,
                                bold: true
                            }
                        },
                        hAxis: {
                            title: 'Date',
                            format: 'yyyy-MM-dd',
                            textStyle: {
                                color: textColor,
                                fontSize: 12,
                                bold: true
                            },
                            titleTextStyle: {
                                color: textColor,
                                fontSize: 16,
                                bold: true
                            }
                        },
                        vAxis: {
                            title: 'Score',
                            minValue: 0,
                            maxValue: 5,
                            ticks: [0, 1, 2, 3, 4, 5],
                            textStyle: {
                                color: textColor,
                                fontSize: 12,
                                bold: true
                            },
                            titleTextStyle: {
                                color: textColor,
                                fontSize: 16,
                                bold: true
                            }
                        },
                        series: {
                            0: {
                                color: '#FF6384'
                            },
                            1: {
                                color: '#36A2EB'
                            },
                            2: {
                                color: '#FFCE56'
                            }
                        },
                        backgroundColor: backgroundColor,
                        titleTextStyle: {
                            color: textColor,
                            fontSize: 18,
                            bold: true
                        },
                        chartArea: {
                            width: '80%',
                            height: '70%'
                        },
                        responsive: true
                    };

                    const chart = new google.visualization.LineChart(document.getElementById('chart_div'));
                    chart.draw(chartData, options);

                    window.addEventListener('resize', function() {
                        chart.draw(chartData, options);
                    });
                })
                .catch(error => console.error('Error:', error));
        }


        function toggleTheme() {
            document.body.classList.toggle('dark-mode');
            updateThemeToggleIcon();
            drawChart();
        }

        function updateThemeToggleIcon() {
            const iconName = document.body.classList.contains('dark-mode') ? 'sunny-outline' : 'moon-outline';
            document.getElementById('themeToggle').querySelector('ion-icon').setAttribute('name', iconName);
        }

        function checkMentalSupportSuggestion() {
            if (typeof needMentalSupport !== 'undefined' && needMentalSupport) {
                showMentalSupportPopup();
            }
        }

        function showMentalSupportPopup() {
            const modalHTML = `
    <div class="modal fade theme-modal" id="mentalSupportModal" tabindex="-1" aria-labelledby="mentalSupportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mentalSupportModalLabel">Mental Support Suggestion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    We've noticed your well-being scores have been lower than usual. Would you like to explore some mental support resources?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Not now</button>
                    <button type="button" class="btn btn-primary" onclick="location.href='mental_support.php'">Yes, please</button>
                </div>
            </div>
        </div>
    </div>
    `;

            // Remove any existing modal
            const existingModal = document.getElementById('mentalSupportModal');
            if (existingModal) {
                existingModal.remove();
            }

            // Add the new modal to the DOM
            document.body.insertAdjacentHTML('beforeend', modalHTML);

            // Initialize and show the modal
            const mentalSupportModal = new bootstrap.Modal(document.getElementById('mentalSupportModal'));
            mentalSupportModal.show();
        }



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