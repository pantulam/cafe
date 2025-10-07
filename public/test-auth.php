<?php
// public/test-auth.php
$valid_username = 'admin';
$valid_password = 'YourSecurePassword123!';

if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
    header('WWW-Authenticate: Basic realm="Test Authentication"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Authentication required';
    exit;
}

$username = $_SERVER['PHP_AUTH_USER'];
$password = $_SERVER['PHP_AUTH_PW'];

echo "<h1>Authentication Test</h1>";
echo "<p>Username: $username</p>";
echo "<p>Password: " . str_repeat('*', strlen($password)) . "</p>";

if ($username === $valid_username && $password === $valid_password) {
    echo "<p style='color: green;'>✅ Authentication SUCCESSFUL!</p>";
    echo "<p>You can now access the <a href='/'>main application</a>.</p>";
} else {
    echo "<p style='color: red;'>❌ Authentication FAILED!</p>";
    echo "<p>Expected: admin / YourSecurePassword123!</p>";
}
?>
