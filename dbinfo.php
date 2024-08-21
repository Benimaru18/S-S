<?php

if (!defined('DB_SERVER')) define('DB_SERVER', '127.0.0.1:3307');
if (!defined('DB_USERNAME')) define('DB_USERNAME', 'myadmin');
if (!defined('DB_PASSWORD')) define('DB_PASSWORD', 'Wmk18<3su11!');
if (!defined('DB_NAME')) define('DB_NAME', 'user_management');

if (!function_exists('connect_db')) {
    function connect_db() {
        $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        return $conn;
    }
}
