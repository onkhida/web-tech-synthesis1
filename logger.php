<?php
// AFA Match Center - Match Official Console (logger.php)
require_once __DIR__ . '/db.php';

// Fetch match requested or default to live/latest match
$selected_match_id = isset($_GET['match_id']) ? intval($_GET['match_id']) : 0;

if ($selected_match_id > 0) {
    $match_query = "SELECT m.*, 
                           th.name AS home_name, th.short_code AS home_code, th.hex_color AS home_color,
                           ta.name AS away_name, ta.short_code AS away_code, ta.hex_color AS away_color
                    FROM matches m
                    JOIN teams th ON m.home_team_id = th.id
                    JOIN teams ta ON m.away_team_id = ta.id
                    WHERE m.id = ? LIMIT 1";
    $stmt = $conn->prepare($match_query);
    $stmt->bind_param("i", $selected_match_id);
    $stmt->execute();
    $match = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} else {
    $match_query = "SELECT m.*, 
                           th.name AS home_name, th.short_code AS home_code, th.hex_color AS home_color,
                           ta.name AS away_name, ta.short_code AS away_code, ta.hex_color AS away_color
                    FROM matches m
                    JOIN teams th ON m.home_team_id = th.id
                    JOIN teams ta ON m.away_team_id = ta.id
                    ORDER BY CASE WHEN m.status = 'live' THEN 1 ELSE 2 END, m.id DESC 
                    LIMIT 1";
    $match_res = $conn->query($match_query);
    $match = $match_res ? $match_res->fetch_assoc() : null;
}

// Fetch all matches for the top selector dropdown
$all_matches_res = $conn->query("
    SELECT m.id, m.status, th.short_code AS home_code, ta.short_code AS away_code 
    FROM matches m 
    JOIN teams th ON m.home_team_id = th.id 
    JOIN teams ta ON m.away_team_id = ta.id 
    ORDER BY m.id DESC
");
$all_matches = [];
while ($am = $all_matches_res->fetch_assoc()) {
    $all_matches[] = $am;
}

// Fetch players for the active match from match_rosters or players table
$roster_players = [];
if ($match) {
    $match_id = (int)$match['id'];
    
    // Fetch players registered in match_rosters or team roster
    $roster_sql = "
        SELECT p.id, p.team_id, p.name, p.jersey_number, p.position 
        FROM players p 
        WHERE p.team_id IN (?, ?) 
        ORDER BY p.jersey_number ASC
    ";
    $r_stmt = $conn->prepare($roster_sql);
    $r_stmt->bind_param("ii", $match['home_team_id'], $match['away_team_id']);
    $r_stmt->execute();
    $r_res = $r_stmt->get_result();
    while ($p = $r_res->fetch_assoc()) {
        $roster_players[] = $p;
    }
    $r_stmt->close();

    // Fetch recent event logs
    $recent_events = [];
    $events_stmt = $conn->prepare("
        SELECT e.*, t.name AS team_name, t.short_code AS team_code, t.hex_color AS team_color
        FROM event_logs e
        JOIN teams t ON e.team_id = t.id
        WHERE e.match_id = ?
        ORDER BY e.created_at DESC
        LIMIT 10
    ");
    $events_stmt->bind_param("i", $match_id);
    $events_stmt->execute();
    $events_res = $events_stmt->get_result();
    while ($r = $events_res->fetch_assoc()) {
        $recent_events[] = $r;
    }
    $events_stmt->close();
}

$success_msg = isset($_GET['success']) ? "Event logged successfully!" : null;
$error_msg   = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Match Official Console | AFA Logger</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="admin-theme">
    <header class="site-header admin-header">
        <div class="header-container">
            <div class="brand-identity">
                <div class="brand-logo admin-badge">OFFICIAL</div>
                <div class="brand-text">
                    <h1>AFA Match Official Console</h1>
                    <span class="sub-heading">Live Match Event Logging System</span>
                </div>
            </div>
            <nav class="nav-actions">
                <a href="admin.php" class="btn btn-secondary"><span class="btn-icon">⚙️</span> Admin Portal</a>
                <a href="index.php" class="btn btn-secondary" id="nav-public-btn"><span class="btn-icon">📺</span> View Scoreboard</a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <!-- Toast / Alerts -->
        <?php if ($success_msg): ?>
            <div class="alert alert-success" id="success-alert">
                <span class="alert-icon">✅</span>
                <span><?php echo $success_msg; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="alert alert-danger" id="error-alert">
                <span class="alert-icon">⚠️</span>
                <span>Error: <?php echo $error_msg; ?></span>
            </div>
        <?php endif; ?>

        <div class="alert alert-danger" id="js-validation-alert" style="display: none;">
            <span class="alert-icon">⛔</span>
            <span id="js-validation-message"></span>
        </div>

        <?php if (!$match): ?>
            <section class="card empty-state">
                <h2>No Active Match Available</h2>
                <p>Please create a match in the <a href="admin.php" style="color: #60a5fa;">Admin Portal</a> to begin logging events.</p>
            </section>
        <?php else: ?>
            <!-- Top Match Switcher Bar -->
            <div class="match-switcher-bar">
                <label for="match-select">Active Match Selector: </label>
                <select id="match-select" onchange="location.href='logger.php?match_id=' + this.value;" class="form-control-xs">
                    <?php foreach ($all_matches as $am): ?>
                        <option value="<?php echo (int)$am['id']; ?>" <?php echo $am['id'] == $match['id'] ? 'selected' : ''; ?>>
                            Match #<?php echo (int)$am['id']; ?>: <?php echo htmlspecialchars($am['home_code']); ?> vs <?php echo htmlspecialchars($am['away_code']); ?> (<?php echo strtoupper($am['status']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Current Match Overview Bar -->
            <section class="card match-summary-card">
                <div class="summary-header">
                    <span class="badge badge-status"><?php echo strtoupper(htmlspecialchars($match['status'])); ?> MATCH</span>
                    <span class="match-title">Matchday #<?php echo (int)$match['id']; ?></span>
                </div>
                <div class="summary-scoreboard">
                    <div class="summary-team home">
                        <span class="dot" style="background-color: <?php echo htmlspecialchars($match['home_color']); ?>;"></span>
                        <span class="name"><?php echo htmlspecialchars($match['home_name']); ?></span>
                    </div>
                    <div class="summary-score">
                        <span><?php echo (int)$match['home_score']; ?></span>
                        <span class="sep">-</span>
                        <span><?php echo (int)$match['away_score']; ?></span>
                    </div>
                    <div class="summary-team away">
                        <span class="name"><?php echo htmlspecialchars($match['away_name']); ?></span>
                        <span class="dot" style="background-color: <?php echo htmlspecialchars($match['away_color']); ?>;"></span>
                    </div>
                </div>
            </section>

            <!-- Logger Form & Recent Logs Grid -->
            <div class="logger-grid">
                <!-- Logger Form Card -->
                <section class="card logger-form-card">
                    <div class="card-header">
                        <h2><span class="header-icon">✍️</span> Log Match Event</h2>
                        <span class="form-subtitle">Records publish instantly</span>
                    </div>

                    <form id="loggerForm" method="POST" action="process_event.php" novalidate>
                        <input type="hidden" name="match_id" value="<?php echo (int)$match['id']; ?>">

                        <!-- Team Selection Radio Cards -->
                        <div class="form-group">
                            <label class="form-label">Select Team <span class="required">*</span></label>
                            <div class="team-radio-group">
                                <label class="team-radio-card">
                                    <input type="radio" name="team_id" value="<?php echo (int)$match['home_team_id']; ?>" checked required>
                                    <div class="radio-content" style="--team-accent: <?php echo htmlspecialchars($match['home_color']); ?>;">
                                        <span class="team-chip-color"></span>
                                        <span class="team-chip-name"><?php echo htmlspecialchars($match['home_name']); ?></span>
                                        <span class="team-chip-role">(Home)</span>
                                    </div>
                                </label>

                                <label class="team-radio-card">
                                    <input type="radio" name="team_id" value="<?php echo (int)$match['away_team_id']; ?>" required>
                                    <div class="radio-content" style="--team-accent: <?php echo htmlspecialchars($match['away_color']); ?>;">
                                        <span class="team-chip-color"></span>
                                        <span class="team-chip-name"><?php echo htmlspecialchars($match['away_name']); ?></span>
                                        <span class="team-chip-role">(Away)</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Player Selection Dropdown (Dynamically Filtered via JS & Roster Data) -->
                        <div class="form-group">
                            <label for="player_select" class="form-label">Select Registered Player <span class="required">*</span></label>
                            <select id="player_select" class="form-control">
                                <option value="">-- Select Player from Roster --</option>
                                <?php foreach ($roster_players as $rp): ?>
                                    <option value="<?php echo (int)$rp['id']; ?>" 
                                            data-team-id="<?php echo (int)$rp['team_id']; ?>" 
                                            data-player-name="<?php echo htmlspecialchars($rp['name']); ?>">
                                        #<?php echo (int)$rp['jersey_number']; ?> <?php echo htmlspecialchars($rp['name']); ?> (<?php echo htmlspecialchars($rp['position']); ?>)
                                    </option>
                                <?php endforeach; ?>
                                <option value="custom">+ Type Manual / Unlisted Player Name</option>
                            </select>
                        </div>

                        <!-- Hidden / Manual Player Name Input -->
                        <div class="form-group" id="manual-player-group">
                            <label for="player_name" class="form-label">Player Name <span class="required">*</span></label>
                            <input type="text" id="player_name" name="player_name" class="form-control" placeholder="e.g. Kofi Mensah" required maxlength="100">
                            <input type="hidden" id="player_id" name="player_id" value="">
                        </div>

                        <!-- Match Minute Input -->
                        <div class="form-group">
                            <label for="minute" class="form-label">Match Minute (1 - 120) <span class="required">*</span></label>
                            <div class="input-with-suffix">
                                <input type="number" id="minute" name="minute" min="1" max="120" value="<?php echo max(1, (int)$match['match_minute']); ?>" class="form-control" required placeholder="e.g. 45">
                                <span class="input-suffix">' min</span>
                            </div>
                        </div>

                        <!-- Event Type Selection -->
                        <div class="form-group">
                            <label for="event_type" class="form-label">Event Type <span class="required">*</span></label>
                            <select id="event_type" name="event_type" class="form-control" required>
                                <option value="goal">⚽ Goal (Updates Score Automatically)</option>
                                <option value="yellow_card">🟨 Yellow Card</option>
                                <option value="red_card">🟥 Red Card</option>
                                <option value="sub">🔄 Substitution</option>
                            </select>
                        </div>

                        <!-- Submit Button -->
                        <div class="form-actions">
                            <button type="submit" class="btn btn-submit" id="submit-event-btn">
                                <span class="btn-icon">🚀</span> Log Event Now
                            </button>
                        </div>
                    </form>
                </section>

                <!-- Recent Events Audit Console -->
                <section class="card recent-logs-card">
                    <div class="card-header">
                        <h2><span class="header-icon">📋</span> Recent Session Logs</h2>
                        <span class="badge badge-secondary">Last 10 Events</span>
                    </div>

                    <div class="recent-logs-list">
                        <?php if (empty($recent_events)): ?>
                            <p class="no-logs">No events logged in this session yet.</p>
                        <?php else: ?>
                            <div class="table-wrapper">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>Min</th>
                                            <th>Team</th>
                                            <th>Player</th>
                                            <th>Event</th>
                                            <th>Logged At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_events as $ev): ?>
                                            <tr>
                                                <td><span class="min-pill"><?php echo (int)$ev['minute']; ?>'</span></td>
                                                <td>
                                                    <span class="team-indicator-dot" style="background-color: <?php echo htmlspecialchars($ev['team_color']); ?>;"></span>
                                                    <strong><?php echo htmlspecialchars($ev['team_code']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($ev['player_name']); ?></td>
                                                <td>
                                                    <span class="event-tag tag-<?php echo htmlspecialchars($ev['event_type']); ?>">
                                                        <?php echo strtoupper(str_replace('_', ' ', $ev['event_type'])); ?>
                                                    </span>
                                                </td>
                                                <td class="text-muted"><?php echo date('H:i:s', strtotime($ev['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        <?php endif; ?>
    </main>

    <footer class="site-footer">
        <div class="footer-container">
            <p>&copy; <?php echo date('Y'); ?> AFA Official Logger Console.</p>
        </div>
    </footer>

    <script src="app.js"></script>
</body>
</html>
