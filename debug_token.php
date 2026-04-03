<?php
/**
 * DEBUG: Check Token in Database
 * Run this to see what's actually stored in the database
 *
 * Usage: Add token to URL like: debug_token.php?token=YOUR_TOKEN_HERE
 */

require_once 'config/db.php';

echo "<h2>Password Reset Token Debug</h2>";

$token = $_GET['token'] ?? '';

if (empty($token)) {
    echo "<p style='color:red;'>Please provide a token: debug_token.php?token=YOUR_TOKEN</p>";
    echo "<p>Or enter a token hash to search</p>";
    echo "<form method='get'>";
    echo "<input type='text' name='token' placeholder='Enter token or token_hash' style='width:100%; padding:10px;'>";
    echo "<button type='submit' style='margin-top:10px; padding:10px;'>Check Token</button>";
    echo "</form>";
    exit;
}

echo "<h3>Token Info</h3>";
echo "<pre>";
echo "Input:      " . $token . "\n";
echo "Input Len:  " . strlen($token) . " chars\n";
echo "</pre>";

// Generate hash from input
$tokenHash = hash('sha256', $token);
echo "<pre>";
echo "SHA256 Hash: " . $tokenHash . "\n";
echo "Hash Len:    " . strlen($tokenHash) . " chars\n";
echo "</pre>";

echo "<h3>Checking Database...</h3>";

// Check by token_hash (how we're doing it now)
$sqlByHash = "SELECT id, email, token, token_hash, expires_at, used, created_at,
              TIMESTAMPDIFF(SECOND, created_at, NOW()) as seconds_since_created
              FROM password_resets
              WHERE token_hash = ?";
$recordByHash = fetchOne($sqlByHash, "s", [$tokenHash]);

if ($recordByHash) {
    echo "<p style='color:green;'>✓ Found by token_hash!</p>";
    echo "<pre>";
    print_r($recordByHash);
    echo "</pre>";

    echo "<h4>Expiry Check:</h4>";
    echo "<pre>";
    echo "Current MySQL Time: " . date('Y-m-d H:i:s') . "\n";
    echo "Expires At:         " . $recordByHash['expires_at'] . "\n";
    echo "Is Expired?        " . ($recordByHash['expires_at'] > date('Y-m-d H:i:s') ? 'NO' : 'YES') . "\n";
    echo "Used?              " . ($recordByHash['used'] ? 'YES' : 'NO') . "\n";
    echo "</pre>";
} else {
    echo "<p style='color:red;'>✗ Not found by token_hash</p>";
}

// Check by plain token
$sqlByPlain = "SELECT id, email, token, token_hash, expires_at, used, created_at
               FROM password_resets
               WHERE token = ?";
$recordByPlain = fetchOne($sqlByPlain, "s", [$token]);

if ($recordByPlain) {
    echo "<p style='color:orange;'>⚠ Found by PLAIN token (not hash)!</p>";
    echo "<pre>";
    print_r($recordByPlain);
    echo "</pre>";
    echo "<p><strong>This means the token was stored without hashing!</strong></p>";
}

// Show all recent tokens
echo "<h3>All Recent Tokens (Last 5)</h3>";
$allTokens = fetchAll("SELECT id, email, LEFT(token, 20) as token_preview, LEFT(token_hash, 20) as hash_preview, expires_at, used, created_at
                      FROM password_resets
                      ORDER BY created_at DESC
                      LIMIT 5");

if ($allTokens) {
    echo "<table border='1' cellpadding='10' style='border-collapse:collapse;'>";
    echo "<tr><th>ID</th><th>Email</th><th>Token (preview)</th><th>Hash (preview)</th><th>Expires</th><th>Used</th><th>Created</th></tr>";
    foreach ($allTokens as $t) {
        echo "<tr>";
        echo "<td>" . $t['id'] . "</td>";
        echo "<td>" . $t['email'] . "</td>";
        echo "<td>" . $t['token_preview'] . "...</td>";
        echo "<td>" . $t['hash_preview'] . "...</td>";
        echo "<td>" . $t['expires_at'] . "</td>";
        echo "<td>" . ($t['used'] ? 'YES' : 'NO') . "</td>";
        echo "<td>" . $t['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No tokens found in database.</p>";
}

// Show MySQL time info
echo "<h3>MySQL Time Info</h3>";
$timeInfo = fetchOne("SELECT NOW() as mysql_time, @@time_zone as timezone");
echo "<pre>";
print_r($timeInfo);
echo "</pre>";
