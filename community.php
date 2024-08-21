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
    <title>Community Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="communities.css">
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
                <a href="dashboard.php" class="nav-link">
                    <ion-icon name="home-outline"></ion-icon> Dashboard
                </a>
                <a href="./daily_quotes.php" class="nav-link">
                    <ion-icon name="chatbubble-ellipses-outline"></ion-icon> Daily Quotes
                </a>
                <a href="./well_being_tracker.php" class="nav-link">
                    <ion-icon name="analytics-outline"></ion-icon> Well-being Tracker
                </a>
                <a href="./community.php" class="nav-link active">
                    <ion-icon name="people-outline"></ion-icon> Community
                </a>
                <a href="./resources.php" class="nav-link">
                    <ion-icon name="book-outline"></ion-icon> Resources
                </a>
                <a href="privacy_settings.php" class="nav-link">
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
        <div class="main-content p-4">
            <div class="community-section">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="online-users-sidebar mb-4">
                                <h3>Online Users</h3>
                                <ul id="onlineUsersList" class="list-group"></ul>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="community-header mb-4">
                                <h2 class="settings-title">Community Chat</h2>
                                <p class="settings-subtitle">Chat with other users in real-time</p>
                            </div>
                            <div class="chat-container" id="chatContainer"></div>
                            <div id="typingIndicator" class="typing-indicator"></div>
                            <form id="chatForm" class="mt-3">
                                <div class="input-group">
                                    <input type="text" id="messageInput" class="form-control" placeholder="Type your message...">
                                    <button id="emojiButton" class="btn btn-outline-secondary emoji-button" type="button">😊</button>
                                    <input type="file" id="fileInput" style="display: none;">
                                    <button id="fileButton" class="btn btn-outline-secondary file-button" type="button">
                                        <ion-icon name="attach-outline"></ion-icon>
                                    </button>
                                    <button type="submit" class="btn btn-primary">Send</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const themeToggle = document.getElementById('themeToggle');
        const body = document.body;
        const chatContainer = document.getElementById('chatContainer');
        const chatForm = document.getElementById('chatForm');
        const messageInput = document.getElementById('messageInput');
        const onlineUsersList = document.getElementById('onlineUsersList');
        const emojiButton = document.getElementById('emojiButton');
        const fileInput = document.getElementById('fileInput');
        const fileButton = document.getElementById('fileButton');
        const typingIndicator = document.getElementById('typingIndicator');
        const sendButton = document.querySelector('button[type="submit"]');
        const newMessageIndicator = document.createElement('div');
        newMessageIndicator.classList.add('new-message-indicator');
        newMessageIndicator.textContent = 'New messages';
        chatContainer.parentNode.insertBefore(newMessageIndicator, chatContainer.nextSibling);

        let isTyping = false;
        let typingTimeout;

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

        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('theme') || 'light-mode';
            body.classList.add(savedTheme);
            updateThemeToggleIcon();
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
        });

        function addMessage(username, message) {
            const messageElement = document.createElement('div');
            messageElement.classList.add('chat-message');
            messageElement.innerHTML = `<strong>${username}:</strong> ${message}`;
            chatContainer.appendChild(messageElement);
            
            if (chatContainer.scrollTop + chatContainer.clientHeight === chatContainer.scrollHeight) {
                chatContainer.scrollTop = chatContainer.scrollHeight;
            } else {
                newMessageIndicator.style.display = 'block';
            }
        }

        chatForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const message = messageInput.value.trim();
            if (message) {
                fetch('send_message.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `message=${encodeURIComponent(message)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            addMessage('<?php echo $username; ?>', message);
                            messageInput.value = '';
                            sendButton.classList.remove('pulse');
                        }
                    });
            }
        });

        function fetchMessages() {
            fetch('get_messages.php')
                .then(response => response.json())
                .then(data => {
                    chatContainer.innerHTML = '';
                    data.forEach(msg => addMessage(msg.username, msg.message));
                    updateOnlineUsers(data.onlineUsers);
                });
        }

        function updateOnlineUsers(users) {
            onlineUsersList.innerHTML = '';
            users.forEach(user => {
                const li = document.createElement('li');
                li.classList.add('list-group-item');
                li.textContent = user;
                onlineUsersList.appendChild(li);
            });
        }

        messageInput.addEventListener('input', () => {
            if (messageInput.value.trim() !== '') {
                sendButton.classList.add('pulse');
                if (!isTyping) {
                    isTyping = true;
                    // Send typing indicator to server
                }
                clearTimeout(typingTimeout);
                typingTimeout = setTimeout(() => {
                    isTyping = false;
                    // Send stopped typing indicator to server
                }, 3000);
            } else {
                sendButton.classList.remove('pulse');
            }
        });

        function showTypingIndicator(username) {
            typingIndicator.innerHTML = `
                <span></span>
                <span></span>
                <span></span>
                <em>${username} is typing...</em>
            `;
            typingIndicator.style.display = 'block';
        }

        function hideTypingIndicator() {
            typingIndicator.style.display = 'none';
        }

        let lastScrollTop = 0;

        chatContainer.addEventListener('scroll', () => {
            let st = chatContainer.scrollTop;
            if (st < lastScrollTop) {
                // Scrolling up
                newMessageIndicator.style.display = 'block';
            } else {
                // Scrolling down
                newMessageIndicator.style.display = 'none';
            }
            lastScrollTop = st <= 0 ? 0 : st;
        });

        newMessageIndicator.addEventListener('click', () => {
            chatContainer.scrollTop = chatContainer.scrollHeight;
            newMessageIndicator.style.display = 'none';
        });

        onlineUsersList.addEventListener('mouseover', (e) => {
            if (e.target.tagName === 'LI') {
                e.target.style.backgroundColor = '#f0f0f0';
            }
        });
        onlineUsersList.addEventListener('mouseout', (e) => {
            if (e.target.tagName === 'LI') {
                e.target.style.backgroundColor = '';
            }
        });

        emojiButton.addEventListener('click', () => {
            // Implement emoji picker functionality
        });

        fileButton.addEventListener('click', () => {
            fileInput.click();
        });

        fileInput.addEventListener('change', () => {
            // Implement file sharing functionality
        });

        setInterval(fetchMessages, 5000);
        fetchMessages();
    </script>
</body>

</html>
