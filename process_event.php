<?php
// AFA Match Center - Backend POST Event Processor
require_once __DIR__ . '/db.php';

// Ensure request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: logger.php?error=" . urlencode("Invalid request method. Only POST allowed."));
    exit;
}

// Extract and sanitize input parameters
$match_id    = isset($_POST['match_id']) ? intval($_POST['match_id']) : 0;
$team_id     = isset($_POST['team_id']) ? intval($_POST['team_id']) : 0;
$minute      = isset($_POST['minute']) ? intval($_POST['minute']) : 0;
$player_name = isset($_POST['player_name']) ? trim($_POST['player_name']) : '';
$event_type  = isset($_POST['event_type']) ? trim($_POST['event_type']) : '';

// Validation rules
$allowed_events = ['goal', 'yellow_card', 'red_card', 'sub'];

if ($match_id <= 0) {
    header("Location: logger.php?error=" . urlencode("Invalid match selected."));
    exit;
}

if ($team_id <= 0) {
    header("Location: logger.php?error=" . urlencode("Please select a valid team."));
    exit;
}

if ($minute < 1 || $minute > 120) {
    header("Location: logger.php?error=" . urlencode("Match minute must be between 1 and 120."));
    exit;
}

if (empty($player_name)) {
    header("Location: logger.php?error=" . urlencode("Player name cannot be empty."));
    exit;
}

if (!in_array($event_type, $allowed_events, true)) {
    header("Location: logger.php?error=" . urlencode("Invalid event type specified."));
    exit;
}

// Verify match exists and retrieve home/away team IDs
$match_stmt = $conn->prepare("SELECT id, home_team_id, away_team_id FROM matches WHERE id = ?");
$match_stmt->bind_param("i", $match_id);
$match_stmt->execute();
$match_res = $match_stmt->get_result();

if ($match_res->num_rows === 0) {
    $match_stmt->close();
    header("Location: logger.php?error=" . urlencode("Specified match does not exist in system."));
    exit;
}

$match_data = $match_res->fetch_assoc();
$match_stmt->close();

// Verify team is actually playing in this match
if ($team_id != $match_data['home_team_id'] && $team_id != $match_data['away_team_id']) {
    header("Location: logger.php?error=" . urlencode("Selected team is not a participant in this match."));
    exit;
}

// Begin transaction for data consistency
$conn->begin_transaction();

try {
    // 1. Insert Event Log into event_logs
    $insert_stmt = $conn->prepare("
        INSERT INTO event_logs (match_id, team_id, minute, player_name, event_type)
        VALUES (?, ?, ?, ?, ?)
    ");
    $insert_stmt->bind_param("iiiss", $match_id, $team_id, $minute, $player_name, $event_type);
    
    if (!$insert_stmt->execute()) {
        throw new Exception("Failed to log event: " . $insert_stmt->error);
    }
    $insert_stmt->close();

    // 2. If event_type is 'goal', dynamically update the match score
    if ($event_type === 'goal') {
        if ($team_id == $match_data['home_team_id']) {
            $update_stmt = $conn->prepare("
                UPDATE matches 
                SET home_score = home_score + 1, 
                    match_minute = GREATEST(match_minute, ?) 
                WHERE id = ?
            ");
        } else {
            $update_stmt = $conn->prepare("
                UPDATE matches 
                SET away_score = away_score + 1, 
                    match_minute = GREATEST(match_minute, ?) 
                WHERE id = ?
            ");
        }
        $update_stmt->bind_param("ii", $minute, $match_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception("Failed to update score: " . $update_stmt->error);
        }
        $update_stmt->close();
    } else {
        // Update current match minute if event minute is higher
        $minute_stmt = $conn->prepare("
            UPDATE matches 
            SET match_minute = GREATEST(match_minute, ?) 
            WHERE id = ?
        ");
        $minute_stmt->bind_param("ii", $minute, $match_id);
        $minute_stmt->execute();
        $minute_stmt->close();
    }

    // Commit transaction
    $conn->commit();

    // Redirect to logger with success status
    header("Location: logger.php?success=1");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    header("Location: logger.php?error=" . urlencode($e->getMessage()));
    exit;
}
