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

function getDailyQuote($offset = 0)
{
    $quotesData = json_decode(file_get_contents('quotes.json'), true);
    if (!$quotesData || !isset($quotesData['quotes']) || empty($quotesData['quotes'])) {
        return null;
    }

    $quotes = $quotesData['quotes'];
    $dayOfYear = date('z') + $offset;
    $sizeOfList = count($quotes);
    $quoteIndex = ($dayOfYear + $sizeOfList) % $sizeOfList;

    $quote = $quotes[$quoteIndex];

    if (!isset($quote['quote'])) {
        $quote['quote'] = 'No quote available.';
    }
    if (!isset($quote['author'])) {
        $quote['author'] = 'Unknown';
    }
    if (!isset($quote['date'])) {
        $quote['date'] = date('Y-m-d', strtotime("+$offset days"));
    }

    return $quote;
}

if (isset($_GET['offset'])) {
    $offset = intval($_GET['offset']);
    $dailyQuote = getDailyQuote($offset);
    echo json_encode($dailyQuote);
    exit();
}

function getPreviousQuotes($userId)
{
    $conn = connect_db();
    $stmt = $conn->prepare("SELECT join_date FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $joinDate = new DateTime($user['join_date']);
    $currentDate = new DateTime();

    $quotesData = json_decode(file_get_contents('quotes.json'), true);
    if (!$quotesData || !isset($quotesData['quotes']) || empty($quotesData['quotes'])) {
        return [];
    }

    $quotes = $quotesData['quotes'];
    $previousQuotes = [];

    $interval = $joinDate->diff($currentDate);
    $daysSinceJoin = $interval->days;

    for ($i = 1; $i <= $daysSinceJoin; $i++) {
        $date = clone $currentDate;
        $date->modify("-$i days");
        $dayOfYear = $date->format('z');
        $sizeOfList = count($quotes);
        $index = ($dayOfYear + $sizeOfList) % $sizeOfList;
        $quote = $quotes[$index];
        $quote['date'] = $date->format('Y-m-d');
        $previousQuotes[] = $quote;
    }

    return $previousQuotes;
}

$dailyQuote = getDailyQuote();
$previousQuotes = getPreviousQuotes($_SESSION["user_id"]);

$conn->close();
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Inspirational Quotes - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="quotes.css">
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
                <a href="daily_quotes.php" class="nav-link active"><ion-icon name="chatbubble-ellipses-outline"></ion-icon> Daily Quotes</a>
                <a href="./well_being_tracker.php" class="nav-link"><ion-icon name="analytics-outline"></ion-icon> Well-being Tracker</a>
                <a href="./resources.php" class="nav-link"><ion-icon name="people-outline"></ion-icon> Community</a>
                <a href="./community.php" class="nav-link"><ion-icon name="book-outline"></ion-icon> Resources</a>
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
        <div class="main-content">
            <div class="search-container mb-4">
                <form class="d-flex position-relative" id="quoteSearchForm">
                    <input class="form-control search-input" type="search" placeholder="Search quotes or authors" aria-label="Search" id="quoteSearchInput">
                    <ion-icon name="search-outline" class="search-icon"></ion-icon>
                </form>
                <div id="searchResultsDropdown" class="search-results-dropdown">
                    <div id="searchResults"></div>
                </div>
            </div>
            <section class="quotes-section p-5">
                <div class="container">
                    <div class="quote-content text-center">
                        <h2 class="section-title mb-4">Your Daily Inspiration</h2>
                        <div id="quoteContainer" class="quote-card">
                            <blockquote>
                                <p>quote text goes here</p>
                                <p>author name goes here</p>
                            </blockquote>
                            <div class="quote-nav-buttons mt-4">
                                <button id="prevQuoteBtn" class="btn btn-outline-secondary">Previous</button>
                                <button id="nextQuoteBtn" class="btn btn-outline-secondary">Next</button>
                            </div>
                        </div>
                        <div id="returnToDailyQuoteBtn" class="btn btn-primary mt-3" style="display: none;">Return to Daily Quote</div>
                    </div>
                </div>
            </section>
            <section class="previous-quotes-section mt-5">
                <div class="container">
                    <h3 class="section-title mb-4">Previous Daily Quotes</h3>
                    <div class="search-container mb-4">
                        <form class="d-flex position-relative" id="previousQuotesSearchForm">
                            <input class="form-control search-input" type="search" placeholder="Search previous quotes, authors, or date (YYYY-MM-DD)" aria-label="Search previous quotes" id="previousQuotesSearchInput">
                            <ion-icon name="search-outline" class="search-icon"></ion-icon>
                        </form>
                    </div>
                    <div id="previousQuotesContainer" class="row">
                        <!-- Previous quotes will be dynamically inserted here -->
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('quoteSearchInput');
            const searchResultsDropdown = document.getElementById('searchResultsDropdown');
            const quoteContainer = document.getElementById('quoteContainer');
            const previousQuotesContainer = document.getElementById('previousQuotesContainer');
            const returnToDailyQuoteBtn = document.getElementById('returnToDailyQuoteBtn');

            let currentOffset = 0;

            searchInput.addEventListener('input', function() {
                if (this.value.length >= 1) {
                    performSearch(this.value);
                } else {
                    searchResultsDropdown.style.display = 'none';
                }
            });

            function performSearch(searchTerm) {
                fetch('quotes.json')
                    .then(response => response.json())
                    .then(data => {
                        const filteredQuotes = data.quotes.filter(quote => {
                            const lowerSearchTerm = searchTerm.toLowerCase();
                            return quote.quote.toLowerCase().includes(lowerSearchTerm) ||
                                quote.author.toLowerCase().includes(lowerSearchTerm);
                        });
                        displaySearchResults(filteredQuotes);
                    });
            }

            function displaySearchResults(quotes) {
                const searchResults = document.getElementById('searchResults');
                searchResults.innerHTML = '';
                if (quotes.length > 0) {
                    quotes.forEach(quote => {
                        const quoteElement = document.createElement('div');
                        quoteElement.classList.add('search-result-item');
                        quoteElement.innerHTML = `
                    <p class="quote-text">${quote.quote}</p>
                    <p class="quote-author">- ${quote.author}</p>
                `;
                        quoteElement.addEventListener('click', () => {
                            replaceQuote(quote);
                        });
                        searchResults.appendChild(quoteElement);
                    });
                    searchResultsDropdown.style.display = 'block';
                } else {
                    searchResults.innerHTML = '<p>No results found</p>';
                    searchResultsDropdown.style.display = 'block';
                }
            }

            function replaceQuote(quote) {
                const quoteText = quoteContainer.querySelector('blockquote p:first-child');
                const authorText = quoteContainer.querySelector('blockquote p:last-child');

                quoteText.textContent = quote.quote;
                authorText.textContent = quote.author;

                searchResultsDropdown.style.display = 'none';
                searchInput.value = '';
                returnToDailyQuoteBtn.style.display = 'block';
            }

            returnToDailyQuoteBtn.addEventListener('click', function() {
                fetchAndDisplayQuote(currentOffset);
                this.style.display = 'none';
            });

            document.addEventListener('click', function(event) {
                if (!searchResultsDropdown.contains(event.target) && event.target !== searchInput) {
                    searchResultsDropdown.style.display = 'none';
                }
            });

            const prevQuoteBtn = document.getElementById('prevQuoteBtn');
            const nextQuoteBtn = document.getElementById('nextQuoteBtn');

            function getPreviousQuote() {
                currentOffset--;
                fetchAndDisplayQuote(currentOffset);
            }

            function getNextQuote() {
                currentOffset++;
                fetchAndDisplayQuote(currentOffset);
            }

            function fetchAndDisplayQuote(offset) {
                fetch(`daily_quotes.php?offset=${offset}`)
                    .then(response => response.json())
                    .then(data => {
                        const quoteText = quoteContainer.querySelector('blockquote p:first-child');
                        const authorText = quoteContainer.querySelector('blockquote p:last-child');

                        quoteText.textContent = data.quote;
                        authorText.textContent = data.author;
                        returnToDailyQuoteBtn.style.display = 'none';
                    })
                    .catch(error => {
                        console.error('Error fetching quote:', error);
                    });
            }

            prevQuoteBtn.addEventListener('click', getPreviousQuote);
            nextQuoteBtn.addEventListener('click', getNextQuote);

            function displayPreviousQuotes(quotes) {
                previousQuotesContainer.innerHTML = '';
                quotes.forEach(quote => {
                    const quoteElement = document.createElement('div');
                    quoteElement.classList.add('col-md-6', 'mb-4', 'quote-item');
                    quoteElement.innerHTML = `
                <div class="quote-card p-3">
                    <blockquote>
                        <p>${quote.quote}</p>
                        <p>${quote.author}</p>
                    </blockquote>
                    <div class="quote-date">${quote.date}</div>
                </div>
            `;
                    previousQuotesContainer.appendChild(quoteElement);
                });
            }

            const previousQuotesSearchInput = document.getElementById('previousQuotesSearchInput');

            previousQuotesSearchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const quoteItems = previousQuotesContainer.querySelectorAll('.quote-item');
                quoteItems.forEach(item => {
                    const quoteText = item.querySelector('blockquote p:first-child').textContent.toLowerCase();
                    const quoteAuthor = item.querySelector('blockquote p:last-child').textContent.toLowerCase();
                    const quoteDate = item.querySelector('.quote-date').textContent.toLowerCase();
                    if (quoteText.includes(searchTerm) || quoteAuthor.includes(searchTerm) || quoteDate.includes(searchTerm)) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });

            // Initialize with the current day's quote and previous quotes
            fetchAndDisplayQuote(currentOffset);
            displayPreviousQuotes(<?php echo json_encode($previousQuotes); ?>);
        });

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
            const savedTheme = localStorage.getItem('theme') || 'light-mode';
            body.classList.add(savedTheme);
            updateThemeToggleIcon();
        });
    </script>
</body>

</html>