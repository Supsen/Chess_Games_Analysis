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

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Helper function to normalize time values
function extractTime($timeString) {
    if (preg_match('/\b(\d{1,2}:\d{2}:\d{2})\b/', $timeString, $matches)) {
        return $matches[1];
    }
    return null;
}

// Helper function to split the PGN content into individual games
function parseGames($pgnContent) {
    // Split games by [Event
    return preg_split('/\n(?=\[Event)/', trim($pgnContent));
}

// Handle PGN upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pgnContent'])) {
    $pgnContent = $_POST['pgnContent'];

    // Split PGN content into games
    $games = parseGames($pgnContent);
    $uploadedGames = 0;
    $errors = [];

    foreach ($games as $gameContent) {
        // Parse metadata
        preg_match_all('/\[(.*?)\s"(.*?)"\]/', $gameContent, $matches);
        if (empty($matches[1]) || empty($matches[2])) {
            $errors[] = "Metadata parsing failed for a game.";
            continue;
        }

        $metadata = array_combine($matches[1], $matches[2]);

        $result = $metadata['Result'] ?? null;

        // Normalize time values
        $metadata['StartTime'] = extractTime($metadata['StartTime'] ?? '');
        $metadata['EndTime'] = extractTime($metadata['EndTime'] ?? '');

        // Handle missing Country fields
        $metadata['WhiteCountry'] = $metadata['WhiteCountry'] ?? 'Unknown';
        $metadata['BlackCountry'] = $metadata['BlackCountry'] ?? 'Unknown';

        // Insert players
        $stmt = $conn->prepare("INSERT INTO Players (Username, Elo, Title, Country) VALUES (?, ?, ?, ?) 
                                ON DUPLICATE KEY UPDATE PlayerID=LAST_INSERT_ID(PlayerID)");
        if (!$stmt) {
            $errors[] = "Prepare failed (Players): " . $conn->error;
            continue;
        }

        // Insert white player
        $stmt->bind_param("siss", $metadata['White'], $metadata['WhiteElo'], $metadata['WhiteTitle'], $metadata['WhiteCountry']);
        if (!$stmt->execute()) {
            $errors[] = "Execution failed (White Player): " . $stmt->error;
            continue;
        }
        $whitePlayerId = $stmt->insert_id;

        // Insert black player
        $stmt->bind_param("siss", $metadata['Black'], $metadata['BlackElo'], $metadata['BlackTitle'], $metadata['BlackCountry']);
        if (!$stmt->execute()) {
            $errors[] = "Execution failed (Black Player): " . $stmt->error;
            continue;
        }
        $blackPlayerId = $stmt->insert_id;

        // Insert game
        $stmt = $conn->prepare("INSERT INTO Games 
        (`Event`, `Site`, `Date`, `Round`, `WhitePlayerID`, `BlackPlayerID`, `Result`, `CurrentPosition`, `Timezone`, `ECO`,
         `ECOUrl`, `UTCDate`, `UTCTime`, `TimeControl`, `Termination`, `StartTime`, `EndDate`, `EndTime`, `Link`, `WhiteUrl`,
          `WhiteCountry`, `WhiteTitle`, `BlackUrl`, `BlackCountry`, `BlackTitle`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            $errors[] = "Prepare failed (Games): " . $conn->error;
            continue;
        }

        $stmt->bind_param(
            "ssssiisssssssssssssssssss",
            $metadata['Event'], $metadata['Site'], $metadata['Date'], $metadata['Round'],
            $whitePlayerId, $blackPlayerId,
            $result, $metadata['CurrentPosition'], $metadata['Timezone'], $metadata['ECO'], $metadata['ECOUrl'], $metadata['UTCDate'],
            $metadata['UTCTime'], $metadata['TimeControl'], $metadata['Termination'], 
            $metadata['StartTime'], $metadata['EndDate'], $metadata['EndTime'], $metadata['Link'],
            $metadata['WhiteUrl'], $metadata['WhiteCountry'], $metadata['WhiteTitle'], 
            $metadata['BlackUrl'], $metadata['BlackCountry'], $metadata['BlackTitle']
        );

        if (!$stmt->execute()) {
            $errors[] = "Execution failed (Games): " . $stmt->error;
            continue;
        }

        $gameId = $stmt->insert_id; // Unique GameID for this game

        // Extract moves from the game content
        $movesContent = preg_replace('/\[\s*.*?\s*\]/', '', $gameContent); // Remove metadata
        $movesContent = trim(preg_replace('/\s+/', ' ', $movesContent));  // Normalize spaces

        preg_match_all('/(\d+)\.\s([^\s]+)\s?([^\s]*)/', $movesContent, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $moveNumber = (int)$match[1];
            $whiteMove = $match[2];
            $blackMove = !empty($match[3]) ? $match[3] : null;

            $stmt = $conn->prepare("INSERT INTO Moves (GameID, MoveNumber, WhiteMove, BlackMove) VALUES (?, ?, ?, ?)");
            if (!$stmt) {
                $errors[] = "Prepare failed (Moves): " . $conn->error;
                break;
            }

            $stmt->bind_param("iiss", $gameId, $moveNumber, $whiteMove, $blackMove);
            if (!$stmt->execute()) {
                $errors[] = "Execution failed (Moves): " . $stmt->error;
                break;
            }
        }

        $uploadedGames++;
    }

    echo "Uploaded $uploadedGames games successfully.";
    if (!empty($errors)) {
        echo " Errors: " . implode('; ', $errors);
    }
} else {
    echo "No file content received.";
}

$conn->close();
?>