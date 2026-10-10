<?php
// AFA Match Center - Database Connection (Vanilla PHP + mysqli)

$host     = "localhost";
$username = "root";
$password = "";
$dbname   = "afa_match_center";

// Initialize mysqli connection
$conn = @new mysqli($host, $username, $password, $dbname);

// Check for connection errors
if ($conn->connect_error) {
    // Return friendly error message with styled fallback container
    http_response_code(500);
    die("
    <div style='font-family: system-ui, -apple-system, sans-serif; background: #0f172a; color: #f8fafc; padding: 2rem; max-width: 600px; margin: 4rem auto; border-radius: 12px; border: 1px solid #334155; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5);'>
        <h2 style='color: #ef4444; margin-top: 0;'>⚠️ Database Connection Failed</h2>
        <p style='color: #94a3b8; line-height: 1.6;'>Could not connect to MySQL database server standard host <code>{$host}</code> for database <code>{$dbname}</code>.</p>
        <div style='background: #1e293b; padding: 1rem; border-radius: 8px; font-family: monospace; font-size: 0.9rem; color: #f87171;'>
            Error: " . htmlspecialchars($conn->connect_error) . "
        </div>
        <p style='margin-bottom: 0; color: #64748b; font-size: 0.85rem; margin-top: 1.5rem;'>Please verify MySQL server is active and <code>schema.sql</code> has been imported.</p>
    </div>
    ");
}

// Set charset to utf8mb4 for proper string rendering
$conn->set_charset("utf8mb4");
