<?php
// AFA Match Center - Public Live Match Scoreboard & Center
require_once __DIR__ . '/db.php';

// Fetch active live match or latest match
$match_query = "SELECT m.*, 
                       th.name AS home_name, th.short_code AS home_code, th.hex_color AS home_color, th.logo_url AS home_logo,
                       ta.name AS away_name, ta.short_code AS away_code, ta.hex_color AS away_color, ta.logo_url AS away_logo
                FROM matches m
                JOIN teams th ON m.home_team_id = th.id
                JOIN teams ta ON m.away_team_id = ta.id
                ORDER BY CASE WHEN m.status = 'live' THEN 1 ELSE 2 END, m.id DESC 
                LIMIT 1";

$match_result = $conn->query($match_query);
$match = $match_result ? $match_result->fetch_assoc() : null;

$event_logs = [];
if ($match) {
    $match_id = (int)$match['id'];
    $events_stmt = $conn->prepare("
        SELECT e.*, t.name AS team_name, t.short_code AS team_code, t.hex_color AS team_color
        FROM event_logs e
        JOIN teams t ON e.team_id = t.id
        WHERE e.match_id = ?
        ORDER BY e.minute ASC, e.created_at ASC
    ");
    $events_stmt->bind_param("i", $match_id);
    $events_stmt->execute();
    $events_result = $events_stmt->get_result();
    while ($row = $events_result->fetch_assoc()) {
        $event_logs[] = $row;
    }
    $events_stmt->close();
}

// Function to get display label & icon for events
function getEventBadge($type) {
    switch ($type) {
        case 'goal':
            return ['icon' => '⚽', 'label' => 'GOAL', 'class' => 'badge-goal'];
        case 'yellow_card':
            return ['icon' => '🟨', 'label' => 'Yellow Card', 'class' => 'badge-yellow'];
        case 'red_card':
            return ['icon' => '🟥', 'label' => 'Red Card', 'class' => 'badge-red'];
        case 'sub':
            return ['icon' => '🔄', 'label' => 'Substitution', 'class' => 'badge-sub'];
        default:
            return ['icon' => '📌', 'label' => ucfirst($type), 'class' => 'badge-default'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AFA Match Center | Live Football Scoreboard</title>
    <meta name="description" content="Ashesi Football Association Live Match Logger & Real-time Scoreboard Dashboard.">
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Top Site Navigation Header -->
    <header class="site-header">
        <div class="header-container">
            <div class="brand-identity">
                <div class="brand-logo">AFA</div>
                <div class="brand-text">
                    <h1>Ashesi Football Association</h1>
                    <span class="sub-heading">Official Live Match Center</span>
                </div>
            </div>
            <nav class="nav-actions">
                <a href="logger.php" class="btn btn-primary" id="nav-logger-btn">
                    <span class="btn-icon">⚡</span> Go to Official Logger
                </a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <?php if (!$match): ?>
            <section class="card empty-state">
                <h2>No Active Matches Found</h2>
                <p>There are no matches scheduled or live right now. Please import <code>schema.sql</code> or create a new match in the database.</p>
            </section>
        <?php else: ?>
            <!-- Scoreboard Card Header -->
            <section class="scoreboard-card" id="scoreboard-header">
                <div class="status-ribbon">
                    <span class="status-indicator live-pulse"></span>
                    <span class="status-text"><?php echo strtoupper(htmlspecialchars($match['status'])); ?></span>
                    <span class="match-league">AFA Premier Cup • Matchday #<?php echo (int)$match['id']; ?></span>
                </div>

                <div class="scoreboard-body">
                    <!-- Home Team -->
                    <div class="team-block home-team">
                        <div class="team-badge" style="--team-color: <?php echo htmlspecialchars($match['home_color']); ?>;">
                            <span class="color-stripe"></span>
                            <span class="short-code"><?php echo htmlspecialchars($match['home_code']); ?></span>
                        </div>
                        <h2 class="team-name"><?php echo htmlspecialchars($match['home_name']); ?></h2>
                        <span class="team-role">Home</span>
                    </div>

                    <!-- Score display & Clock Ticker -->
                    <div class="score-display">
                        <div class="scores">
                            <span class="score-number" id="home-score-display"><?php echo (int)$match['home_score']; ?></span>
                            <span class="score-divider">:</span>
                            <span class="score-number" id="away-score-display"><?php echo (int)$match['away_score']; ?></span>
                        </div>
                        <div class="live-clock" id="live-clock-container" data-initial-minute="<?php echo (int)$match['match_minute']; ?>" data-status="<?php echo htmlspecialchars($match['status']); ?>">
                            <span class="clock-icon">⏱️</span>
                            <span id="match-minute-display"><?php echo (int)$match['match_minute']; ?></span><span class="clock-unit">'</span>
                            <span class="clock-label">LIVE TICKER</span>
                        </div>
                    </div>

                    <!-- Away Team -->
                    <div class="team-block away-team">
                        <div class="team-badge" style="--team-color: <?php echo htmlspecialchars($match['away_color']); ?>;">
                            <span class="color-stripe"></span>
                            <span class="short-code"><?php echo htmlspecialchars($match['away_code']); ?></span>
                        </div>
                        <h2 class="team-name"><?php echo htmlspecialchars($match['away_name']); ?></h2>
                        <span class="team-role">Away</span>
                    </div>
                </div>
            </section>

            <!-- Grid Layout for SVG Pitch Graphic & Timeline Feed -->
            <div class="dashboard-grid">
                <!-- SVG Pitch Graphic Container -->
                <section class="card pitch-section">
                    <div class="card-header">
                        <h2><span class="header-icon">🏟️</span> Live Tactical Pitch Overview</h2>
                        <span class="badge badge-info">Interactive View</span>
                    </div>
                    <div class="pitch-container">
                        <svg class="pitch-svg" viewBox="0 0 800 500" preserveAspectRatio="xMidYMid meet" xmlns="http://www.w3.org/2000/svg">
                            <!-- Pitch Turf Background with Stripes -->
                            <defs>
                                <pattern id="grass-stripes" width="100" height="500" patternUnits="userSpaceOnUse">
                                    <rect x="0" y="0" width="50" height="500" fill="#15803d" />
                                    <rect x="50" y="0" width="50" height="500" fill="#166534" />
                                </pattern>
                                <radialGradient id="pitch-glow" cx="50%" cy="50%" r="60%">
                                    <stop offset="0%" stop-color="#22c55e" stop-opacity="0.25"/>
                                    <stop offset="100%" stop-color="#052e16" stop-opacity="0.8"/>
                                </radialGradient>
                            </defs>
                            
                            <!-- Base Turf -->
                            <rect width="800" height="500" fill="url(#grass-stripes)" rx="16" />
                            <rect width="800" height="500" fill="url(#pitch-glow)" rx="16" />

                            <!-- Boundary Line -->
                            <rect x="30" y="30" width="740" height="440" fill="none" stroke="#ffffff" stroke-width="3" stroke-opacity="0.85" rx="4" />

                            <!-- Halfway Line -->
                            <line x1="400" y1="30" x2="400" y2="470" stroke="#ffffff" stroke-width="3" stroke-opacity="0.85" />

                            <!-- Center Circle -->
                            <circle cx="400" cy="250" r="70" fill="none" stroke="#ffffff" stroke-width="3" stroke-opacity="0.85" />
                            <circle cx="400" cy="250" r="4" fill="#ffffff" />

                            <!-- Left Penalty Area (Home) -->
                            <rect x="30" y="115" width="125" height="270" fill="none" stroke="#ffffff" stroke-width="3" stroke-opacity="0.85" />
                            <rect x="30" y="185" width="45" height="130" fill="none" stroke="#ffffff" stroke-width="3" stroke-opacity="0.85" />
                            <circle cx="120" cy="250" r="3" fill="#ffffff" />
                            <path d="M 155 195 A 70 70 0 0 1 155 305" fill="none" stroke="#ffffff" stroke-width="3" stroke-opacity="0.85" />

                            <!-- Right Penalty Area (Away) -->
                            <rect x="645" y="115" width="125" height="270" fill="none" stroke="#ffffff" stroke-width="3" stroke-opacity="0.85" />
                            <rect x="725" y="185" width="45" height="130" fill="none" stroke="#ffffff" stroke-width="3" stroke-opacity="0.85" />
                            <circle cx="680" cy="250" r="3" fill="#ffffff" />
                            <path d="M 645 195 A 70 70 0 0 0 645 305" fill="none" stroke="#ffffff" stroke-width="3" stroke-opacity="0.85" />

                            <!-- Corner Arcs -->
                            <path d="M 30 50 A 20 20 0 0 0 50 30" fill="none" stroke="#ffffff" stroke-width="3" stroke-opacity="0.85" />
                            <path d="M 30 450 A 20 20 0 0 1 50 470" fill="none" stroke="#ffffff" stroke-width="3" stroke-opacity="0.85" />
                            <path d="M 770 50 A 20 20 0 0 1 750 30" fill="none" stroke="#ffffff" stroke-width="3" stroke-opacity="0.85" />
                            <path d="M 770 450 A 20 20 0 0 0 750 470" fill="none" stroke="#ffffff" stroke-width="3" stroke-opacity="0.85" />

                            <!-- Team Color Banners on Pitch -->
                            <g transform="translate(50, 45)">
                                <rect x="0" y="0" width="140" height="28" rx="6" fill="<?php echo htmlspecialchars($match['home_color']); ?>" fill-opacity="0.9" stroke="#ffffff" stroke-width="1.5"/>
                                <text x="70" y="19" fill="#ffffff" font-family="'Outfit', sans-serif" font-weight="700" font-size="13" text-anchor="middle">
                                    <?php echo htmlspecialchars($match['home_code']); ?> (HOME)
                                </text>
                            </g>

                            <g transform="translate(610, 45)">
                                <rect x="0" y="0" width="140" height="28" rx="6" fill="<?php echo htmlspecialchars($match['away_color']); ?>" fill-opacity="0.9" stroke="#ffffff" stroke-width="1.5"/>
                                <text x="70" y="19" fill="#ffffff" font-family="'Outfit', sans-serif" font-weight="700" font-size="13" text-anchor="middle">
                                    <?php echo htmlspecialchars($match['away_code']); ?> (AWAY)
                                </text>
                            </g>

                            <!-- Center Ball Graphic -->
                            <circle cx="400" cy="250" r="14" fill="#ffffff" stroke="#0f172a" stroke-width="2"/>
                            <polygon points="400,240 408,246 405,255 395,255 392,246" fill="#0f172a" />
                        </svg>
                    </div>
                </section>

                <!-- Chronological Match Event Timeline Feed -->
                <section class="card timeline-section">
                    <div class="card-header">
                        <h2><span class="header-icon">📜</span> Live Timeline Feed</h2>
                        <span class="badge badge-count"><?php echo count($event_logs); ?> Events Logged</span>
                    </div>

                    <div class="timeline-container">
                        <?php if (empty($event_logs)): ?>
                            <div class="timeline-empty">
                                <span class="empty-icon">⚽</span>
                                <p>No match events logged yet. Match officials can log goals, cards, and subs in real time!</p>
                            </div>
                        <?php else: ?>
                            <ol class="timeline-feed">
                                <?php foreach ($event_logs as $event): 
                                    $badge = getEventBadge($event['event_type']);
                                    $is_home = ($event['team_id'] == $match['home_team_id']);
                                ?>
                                    <li class="timeline-item <?php echo $is_home ? 'home-event' : 'away-event'; ?>">
                                        <div class="timeline-minute-badge">
                                            <span class="min-val"><?php echo (int)$event['minute']; ?>'</span>
                                        </div>
                                        
                                        <article class="timeline-card">
                                            <div class="timeline-card-header">
                                                <span class="event-type-badge <?php echo $badge['class']; ?>">
                                                    <span class="event-icon"><?php echo $badge['icon']; ?></span>
                                                    <span class="event-text"><?php echo $badge['label']; ?></span>
                                                </span>
                                                <span class="event-team" style="color: <?php echo htmlspecialchars($event['team_color']); ?>;">
                                                    <span class="team-dot" style="background-color: <?php echo htmlspecialchars($event['team_color']); ?>;"></span>
                                                    <?php echo htmlspecialchars($event['team_name']); ?>
                                                </span>
                                            </div>
                                            <div class="timeline-card-body">
                                                <h3 class="player-name"><?php echo htmlspecialchars($event['player_name']); ?></h3>
                                                <time class="event-time" datetime="<?php echo htmlspecialchars($event['created_at']); ?>">
                                                    Logged at <?php echo date('H:i', strtotime($event['created_at'])); ?>
                                                </time>
                                            </div>
                                        </article>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        <?php endif; ?>
    </main>

    <footer class="site-footer">
        <div class="footer-container">
            <p>&copy; <?php echo date('Y'); ?> Ashesi Football Association (AFA). All Rights Reserved.</p>
            <p class="tech-stack">Built with Vanilla PHP, MySQLi, HTML5, CSS3 Grid/Flexbox & JavaScript.</p>
        </div>
    </footer>

    <script src="app.js"></script>
</body>
</html>
