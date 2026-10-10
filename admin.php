<?php
// AFA Match Center - Admin Dashboard (admin.php)
require_once __DIR__ . '/db.php';

// Fetch all teams
$teams_res = $conn->query("SELECT * FROM teams ORDER BY name ASC");
$teams = [];
while ($t = $teams_res->fetch_assoc()) {
    $teams[] = $t;
}

// Fetch all players with team details
$players_query = "SELECT p.*, t.name AS team_name, t.short_code AS team_code, t.hex_color AS team_color 
                 FROM players p 
                 JOIN teams t ON p.team_id = t.id 
                 ORDER BY t.name ASC, p.jersey_number ASC";
$players_res = $conn->query($players_query);
$players = [];
while ($p = $players_res->fetch_assoc()) {
    $players[] = $p;
}

// Fetch all matches with team details
$matches_query = "SELECT m.*, 
                         th.name AS home_name, th.short_code AS home_code, th.hex_color AS home_color,
                         ta.name AS away_name, ta.short_code AS away_code, ta.hex_color AS away_color
                  FROM matches m
                  JOIN teams th ON m.home_team_id = th.id
                  JOIN teams ta ON m.away_team_id = ta.id
                  ORDER BY m.id DESC";
$matches_res = $conn->query($matches_query);
$matches = [];
while ($m = $matches_res->fetch_assoc()) {
    $matches[] = $m;
}

$success_msg = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : null;
$error_msg   = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AFA Admin Dashboard | Teams, Players & Match Management</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="admin-theme">
    <!-- Master Header -->
    <header class="site-header admin-header">
        <div class="header-container">
            <div class="brand-identity">
                <div class="brand-logo admin-badge">ADMIN</div>
                <div class="brand-text">
                    <h1>AFA Administration Portal</h1>
                    <span class="sub-heading">Teams, Players, Matches & Lineups Control</span>
                </div>
            </div>
            <nav class="nav-actions">
                <a href="index.php" class="btn btn-secondary"><span class="btn-icon">📺</span> Scoreboard</a>
                <a href="logger.php" class="btn btn-primary"><span class="btn-icon">⚡</span> Official Logger</a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <!-- Toast Alerts -->
        <?php if ($success_msg): ?>
            <div class="alert alert-success">
                <span class="alert-icon">✅</span>
                <span><?php echo $success_msg; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="alert alert-danger">
                <span class="alert-icon">⚠️</span>
                <span>Error: <?php echo $error_msg; ?></span>
            </div>
        <?php endif; ?>

        <!-- Grid Layout for Admin Management Panels -->
        <div class="admin-dashboard-grid">
            
            <!-- Panel 1: Team Management -->
            <section class="card admin-card">
                <div class="card-header">
                    <h2><span class="header-icon">🛡️</span> Teams Management</h2>
                    <span class="badge badge-count"><?php echo count($teams); ?> Teams</span>
                </div>

                <!-- Add Team Form -->
                <form action="process_admin.php" method="POST" class="admin-form">
                    <input type="hidden" name="action" value="add_team">
                    
                    <div class="form-group">
                        <label class="form-label">Team Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Accra Lions FC" required>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Short Code *</label>
                            <input type="text" name="short_code" class="form-control" placeholder="e.g. ALFC" maxlength="10" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Color Hex *</label>
                            <input type="color" name="hex_color" class="form-control color-picker" value="#3b82f6" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-full">+ Create Team</button>
                </form>

                <!-- Teams Table -->
                <div class="table-wrapper margin-top">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Team Name</th>
                                <th>Color</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teams as $t): ?>
                                <tr>
                                    <td><span class="short-code-badge" style="background-color: <?php echo htmlspecialchars($t['hex_color']); ?>;"><?php echo htmlspecialchars($t['short_code']); ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($t['name']); ?></strong></td>
                                    <td><code><?php echo htmlspecialchars($t['hex_color']); ?></code></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Panel 2: Player Management -->
            <section class="card admin-card">
                <div class="card-header">
                    <h2><span class="header-icon">🏃</span> Player Roster Management</h2>
                    <span class="badge badge-count"><?php echo count($players); ?> Players</span>
                </div>

                <!-- Add Player Form -->
                <form action="process_admin.php" method="POST" class="admin-form">
                    <input type="hidden" name="action" value="add_player">
                    
                    <div class="form-group">
                        <label class="form-label">Select Team *</label>
                        <select name="team_id" class="form-control" required>
                            <option value="">-- Choose Team --</option>
                            <?php foreach ($teams as $t): ?>
                                <option value="<?php echo (int)$t['id']; ?>"><?php echo htmlspecialchars($t['name']); ?> (<?php echo htmlspecialchars($t['short_code']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Player Full Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Abedi Pele" required>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Jersey Number *</label>
                            <input type="number" name="jersey_number" min="1" max="99" class="form-control" placeholder="10" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Position *</label>
                            <select name="position" class="form-control" required>
                                <option value="GK">GK - Goalkeeper</option>
                                <option value="DEF">DEF - Defender</option>
                                <option value="MID" selected>MID - Midfielder</option>
                                <option value="FWD">FWD - Forward</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-full">+ Add Player to Squad</button>
                </form>

                <!-- Players Table -->
                <div class="table-wrapper margin-top scrollable-table">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Player</th>
                                <th>Pos</th>
                                <th>Team</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($players as $p): ?>
                                <tr>
                                    <td><span class="jersey-pill">#<?php echo (int)$p['jersey_number']; ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
                                    <td><span class="pos-badge pos-<?php echo strtolower($p['position']); ?>"><?php echo htmlspecialchars($p['position']); ?></span></td>
                                    <td>
                                        <span class="team-indicator-dot" style="background-color: <?php echo htmlspecialchars($p['team_color']); ?>;"></span>
                                        <?php echo htmlspecialchars($p['team_code']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        </div>

        <!-- Panel 3: Match & Lineup Schedule Creator -->
        <section class="card admin-card full-width">
            <div class="card-header">
                <h2><span class="header-icon">⚔️</span> Create New Match & Generate Squad Lineups</h2>
                <span class="badge badge-info">Automated Lineup Initialization</span>
            </div>

            <div class="create-match-grid">
                <form action="process_admin.php" method="POST" class="admin-form">
                    <input type="hidden" name="action" value="create_match">

                    <div class="form-grid-3">
                        <div class="form-group">
                            <label class="form-label">Home Team *</label>
                            <select name="home_team_id" class="form-control" required>
                                <option value="">-- Select Home Team --</option>
                                <?php foreach ($teams as $t): ?>
                                    <option value="<?php echo (int)$t['id']; ?>"><?php echo htmlspecialchars($t['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Away Team *</label>
                            <select name="away_team_id" class="form-control" required>
                                <option value="">-- Select Away Team --</option>
                                <?php foreach ($teams as $t): ?>
                                    <option value="<?php echo (int)$t['id']; ?>"><?php echo htmlspecialchars($t['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Initial Match Status *</label>
                            <select name="status" class="form-control" required>
                                <option value="live" selected>LIVE NOW</option>
                                <option value="scheduled">Scheduled</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-submit">+ Schedule Match & Auto-populate Starting Lineups</button>
                </form>
            </div>

            <!-- Existing Matches Table -->
            <div class="table-wrapper margin-top">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Match ID</th>
                            <th>Home Team</th>
                            <th>Score</th>
                            <th>Away Team</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matches as $m): ?>
                            <tr>
                                <td>#<?php echo (int)$m['id']; ?></td>
                                <td>
                                    <span class="team-indicator-dot" style="background-color: <?php echo htmlspecialchars($m['home_color']); ?>;"></span>
                                    <strong><?php echo htmlspecialchars($m['home_name']); ?></strong>
                                </td>
                                <td><span class="score-pill"><?php echo (int)$m['home_score']; ?> - <?php echo (int)$m['away_score']; ?></span></td>
                                <td>
                                    <span class="team-indicator-dot" style="background-color: <?php echo htmlspecialchars($m['away_color']); ?>;"></span>
                                    <strong><?php echo htmlspecialchars($m['away_name']); ?></strong>
                                </td>
                                <td>
                                    <span class="badge status-badge status-<?php echo htmlspecialchars($m['status']); ?>">
                                        <?php echo strtoupper(htmlspecialchars($m['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <form action="process_admin.php" method="POST" style="display:inline-block;">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="match_id" value="<?php echo (int)$m['id']; ?>">
                                        <select name="new_status" onchange="this.form.submit()" class="form-control-xs">
                                            <option value="scheduled" <?php echo $m['status'] === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                            <option value="live" <?php echo $m['status'] === 'live' ? 'selected' : ''; ?>>LIVE</option>
                                            <option value="completed" <?php echo $m['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="footer-container">
            <p>&copy; <?php echo date('Y'); ?> AFA Master Admin Console.</p>
        </div>
    </footer>
</body>
</html>
