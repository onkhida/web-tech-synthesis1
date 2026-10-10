<?php
// AFA Match Center - Match Official Console (logger.php)
require_once __DIR__ . '/db.php';

// Fetch the active live match or latest match
$match_query = "SELECT m.*, 
                       th.name AS home_name, th.short_code AS home_code, th.hex_color AS home_color,
                       ta.name AS away_name, ta.short_code AS away_code, ta.hex_color AS away_color
                FROM matches m
                JOIN teams th ON m.home_team_id = th.id
                JOIN teams ta ON m.away_team_id = ta.id
                ORDER BY CASE WHEN m.status = 'live' THEN 1 ELSE 2 END, m.id DESC 
                LIMIT 1";

$match_result = $conn->query($match_query);
$match = $match_result ? $match_result->fetch_assoc() : null;

// Fetch recent event logs for this match if it exists
$recent_events = [];
if ($match) {
    $match_id = (int)$match['id'];
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
    <meta name="description" content="Match Official Console for logging live football match events.">
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="admin-theme">
    <!-- Header Navigation -->
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
                <a href="index.php" class="btn btn-secondary" id="nav-public-btn">
                    <span class="btn-icon">📺</span> View Public Scoreboard
                </a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <!-- Toast / Alert Notification Messages -->
        <?php if ($success_msg): ?>
            <div class="alert alert-success" role="alert" id="success-alert">
                <span class="alert-icon">✅</span>
                <span><?php echo $success_msg; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="alert alert-danger" role="alert" id="error-alert">
                <span class="alert-icon">⚠️</span>
                <span>Error: <?php echo $error_msg; ?></span>
            </div>
        <?php endif; ?>

        <!-- Dynamic JS Validation Alert Container -->
        <div class="alert alert-danger" role="alert" id="js-validation-alert" style="display: none;">
            <span class="alert-icon">⛔</span>
            <span id="js-validation-message"></span>
        </div>

        <?php if (!$match): ?>
            <section class="card empty-state">
                <h2>No Active Match Available</h2>
                <p>Please initialize the database with seed data to begin logging match events.</p>
            </section>
        <?php else: ?>
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
                        <span class="form-subtitle">Records are published instantly to live scoreboard</span>
                    </div>

                    <form id="loggerForm" method="POST" action="process_event.php" novalidate>
                        <!-- Hidden Match ID -->
                        <input type="hidden" name="match_id" value="<?php echo (int)$match['id']; ?>">

                        <!-- Team Selection (Radio Options with Visual Chips) -->
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

                        <!-- Match Minute Input -->
                        <div class="form-group">
                            <label for="minute" class="form-label">Match Minute (1 - 120) <span class="required">*</span></label>
                            <div class="input-with-suffix">
                                <input type="number" id="minute" name="minute" min="1" max="120" value="<?php echo max(1, (int)$match['match_minute']); ?>" class="form-control" required placeholder="e.g. 45">
                                <span class="input-suffix">' min</span>
                            </div>
                            <small class="form-hint">Enter minute when the event took place.</small>
                        </div>

                        <!-- Player Name Input -->
                        <div class="form-group">
                            <label for="player_name" class="form-label">Player Name <span class="required">*</span></label>
                            <input type="text" id="player_name" name="player_name" class="form-control" placeholder="e.g. Kofi Mensah" required maxlength="100">
                        </div>

                        <!-- Event Type Selection -->
                        <div class="form-group">
                            <label for="event_type" class="form-label">Event Type <span class="required">*</span></label>
                            <select id="event_type" name="event_type" class="form-control" required>
                                <option value="goal">⚽ Goal (Score updates automatically)</option>
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
            <p>&copy; <?php echo date('Y'); ?> AFA Official Logger Console. Restricted Access.</p>
        </div>
    </footer>

    <script src="app.js"></script>
</body>
</html>
