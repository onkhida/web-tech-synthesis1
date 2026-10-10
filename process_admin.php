<?php
// AFA Match Center - Backend Admin Action Controller
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin.php?error=" . urlencode("Invalid request method."));
    exit;
}

$action = isset($_POST['action']) ? trim($_POST['action']) : '';

try {
    switch ($action) {
        case 'add_team':
            $name       = isset($_POST['name']) ? trim($_POST['name']) : '';
            $short_code = isset($_POST['short_code']) ? strtoupper(trim($_POST['short_code'])) : '';
            $hex_color  = isset($_POST['hex_color']) ? trim($_POST['hex_color']) : '#333333';
            
            if (empty($name) || empty($short_code)) {
                throw new Exception("Team Name and Short Code are required.");
            }

            $stmt = $conn->prepare("INSERT INTO teams (name, short_code, hex_color) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $short_code, $hex_color);
            if (!$stmt->execute()) {
                throw new Exception("Error inserting team: " . $stmt->error);
            }
            $stmt->close();

            header("Location: admin.php?success=" . urlencode("Team '$name' created successfully!"));
            exit;

        case 'add_player':
            $team_id       = isset($_POST['team_id']) ? intval($_POST['team_id']) : 0;
            $name          = isset($_POST['name']) ? trim($_POST['name']) : '';
            $jersey_number = isset($_POST['jersey_number']) ? intval($_POST['jersey_number']) : 0;
            $position      = isset($_POST['position']) ? trim($_POST['position']) : 'MID';

            $valid_positions = ['GK', 'DEF', 'MID', 'FWD'];

            if ($team_id <= 0 || empty($name) || $jersey_number <= 0 || !in_array($position, $valid_positions, true)) {
                throw new Exception("Invalid player details provided.");
            }

            $stmt = $conn->prepare("INSERT INTO players (team_id, name, jersey_number, position) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isis", $team_id, $name, $jersey_number, $position);
            if (!$stmt->execute()) {
                throw new Exception("Error inserting player: " . $stmt->error);
            }
            $stmt->close();

            header("Location: admin.php?success=" . urlencode("Player '$name' added to roster successfully!"));
            exit;

        case 'create_match':
            $home_team_id = isset($_POST['home_team_id']) ? intval($_POST['home_team_id']) : 0;
            $away_team_id = isset($_POST['away_team_id']) ? intval($_POST['away_team_id']) : 0;
            $status       = isset($_POST['status']) ? trim($_POST['status']) : 'live';

            if ($home_team_id <= 0 || $away_team_id <= 0) {
                throw new Exception("Please select valid Home and Away teams.");
            }

            if ($home_team_id === $away_team_id) {
                throw new Exception("Home Team and Away Team cannot be the same.");
            }

            $conn->begin_transaction();

            // Insert match
            $match_stmt = $conn->prepare("INSERT INTO matches (home_team_id, away_team_id, status) VALUES (?, ?, ?)");
            $match_stmt->bind_param("iis", $home_team_id, $away_team_id, $status);
            if (!$match_stmt->execute()) {
                throw new Exception("Error creating match: " . $match_stmt->error);
            }
            $new_match_id = $conn->insert_id;
            $match_stmt->close();

            // Populate match_rosters from Home Team players
            $home_players = $conn->query("SELECT id, position FROM players WHERE team_id = $home_team_id");
            if ($home_players) {
                $roster_stmt = $conn->prepare("INSERT INTO match_rosters (match_id, team_id, player_id, is_starter, position) VALUES (?, ?, ?, 1, ?)");
                while ($hp = $home_players->fetch_assoc()) {
                    $pid = (int)$hp['id'];
                    $pos = $hp['position'];
                    $roster_stmt->bind_param("iiis", $new_match_id, $home_team_id, $pid, $pos);
                    $roster_stmt->execute();
                }
                $roster_stmt->close();
            }

            // Populate match_rosters from Away Team players
            $away_players = $conn->query("SELECT id, position FROM players WHERE team_id = $away_team_id");
            if ($away_players) {
                $roster_stmt = $conn->prepare("INSERT INTO match_rosters (match_id, team_id, player_id, is_starter, position) VALUES (?, ?, ?, 1, ?)");
                while ($ap = $away_players->fetch_assoc()) {
                    $pid = (int)$ap['id'];
                    $pos = $ap['position'];
                    $roster_stmt->bind_param("iiis", $new_match_id, $away_team_id, $pid, $pos);
                    $roster_stmt->execute();
                }
                $roster_stmt->close();
            }

            $conn->commit();
            header("Location: admin.php?success=" . urlencode("New match created (Match #$new_match_id) and starting lineups populated!"));
            exit;

        case 'toggle_status':
            $match_id   = isset($_POST['match_id']) ? intval($_POST['match_id']) : 0;
            $new_status = isset($_POST['new_status']) ? trim($_POST['new_status']) : 'live';

            $valid_statuses = ['scheduled', 'live', 'completed'];
            if ($match_id <= 0 || !in_array($new_status, $valid_statuses, true)) {
                throw new Exception("Invalid match or status toggle.");
            }

            $stmt = $conn->prepare("UPDATE matches SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $match_id);
            $stmt->execute();
            $stmt->close();

            header("Location: admin.php?success=" . urlencode("Match #$match_id status updated to '$new_status'."));
            exit;

        default:
            throw new Exception("Unknown admin action.");
    }
} catch (Exception $e) {
    if ($conn->connect_errno === 0) {
        @$conn->rollback();
    }
    header("Location: admin.php?error=" . urlencode($e->getMessage()));
    exit;
}
