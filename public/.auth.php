<?php
// public/.auth.php - Basic HTTP Authentication
$valid_username = 'admin';
$valid_password = 'Ravi1975!';

if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
    header('WWW-Authenticate: Basic realm="Campus Cafe Admin - Restricted Area"');
    header('HTTP/1.0 401 Unauthorized');
    echo '<html><body>';
    echo '<h1>Authentication Required</h1>';
    echo '<p>You must authenticate to access the Campus Cafe Admin.</p>';
    echo '</body></html>';
    exit;
}

$username = $_SERVER['PHP_AUTH_USER'];
$password = $_SERVER['PHP_AUTH_PW'];

if ($username !== $valid_username || $password !== $valid_password) {
    header('WWW-Authenticate: Basic realm="Campus Cafe Admin - Restricted Area"');
    header('HTTP/1.0 401 Unauthorized');
    echo '<html><body>';
    echo '<h1>Authentication Failed</h1>';
    echo '<p>Invalid username or password.</p>';
    echo '</body></html>';
    exit;
}
?>
