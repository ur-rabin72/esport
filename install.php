<?php
$servername = "127.0.0.1";
$username = "root";
$password = "root";
$dbname = "adept_play_db";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully<br>";
} else {
    die("Error creating database: " . $conn->error);
}

$conn->select_db($dbname);

// SQL to create tables
$sql = "
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `wallet_balance` decimal(10,2) NOT NULL DEFAULT 100.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tournaments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `game_name` varchar(100) NOT NULL,
  `entry_fee` decimal(10,2) NOT NULL,
  `prize_pool` decimal(10,2) NOT NULL,
  `match_time` datetime NOT NULL,
  `room_id` varchar(50) DEFAULT NULL,
  `room_password` varchar(50) DEFAULT NULL,
  `status` enum('Upcoming','Live','Completed') NOT NULL DEFAULT 'Upcoming',
  `winner_id` int(11) DEFAULT NULL,
  `commission_percentage` int(3) NOT NULL DEFAULT 20,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `participants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `tournament_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `tournament_id` (`tournament_id`),
  CONSTRAINT `participants_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `participants_ibfk_2` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `type` enum('credit','debit') NOT NULL,
  `description` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->multi_query($sql)) {
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
    echo "Tables created successfully<br>";
} else {
    die("Error creating tables: " . $conn->error);
}

// Insert default admin
$admin_user = 'admin';
$admin_pass = password_hash('admin123', PASSWORD_BCRYPT);
$stmt = $conn->prepare("INSERT INTO admin (username, password) VALUES (?, ?) ON DUPLICATE KEY UPDATE username=username");
$stmt->bind_param("ss", $admin_user, $admin_pass);
if ($stmt->execute()) {
    echo "Admin user created successfully<br>";
} else {
    echo "Error creating admin user: " . $stmt->error . "<br>";
}
$stmt->close();

$conn->close();
echo "Installation complete. You can now login.";
header("Refresh:3; url=login.php");
?>