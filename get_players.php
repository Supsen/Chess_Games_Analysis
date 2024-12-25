<?php
// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "chess_db";

// Create database connection
$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all players
$sql = "SELECT PlayerID, Username, Country, ProfileUrl, Elo FROM Players";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $players = [];
    while ($row = $result->fetch_assoc()) {
        $players[] = $row;
    }
    header('Content-Type: application/json');
    echo json_encode($players);
} else {
    echo json_encode([]);
}

$conn->close();
?>