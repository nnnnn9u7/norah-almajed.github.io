<?php
// create_admin.php - Run this file once then delete it
session_start();
require_once 'db_config.php';

// Password you want to use
$password = 'admin123';

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Delete table if exists (optional)
try {
    $pdo->exec("DROP TABLE IF EXISTS admin_users");
} catch (Exception $e) {
    // Ignore error if table doesn't exist
}

// Create the table
$create_table_sql = "
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css'>
    <link rel='stylesheet' href='style.css'>
    <style>
    role ENUM('super_admin', 'admin', 'moderator') DEFAULT 'admin',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$pdo->exec($create_table_sql);

// Add admin user
$stmt = $pdo->prepare("INSERT INTO admin_users (username, password, full_name, email, role) VALUES (?, ?, ?, ?, ?)");
$stmt->execute(['admin', $hashed_password, 'System Administrator', 'admin@igm.com', 'super_admin']);

echo "<h2>Admin user created successfully!</h2>";
echo "<p><strong>Username:</strong> admin</p>";
echo "<p><strong>Password:</strong> admin123</p>";
echo "<p><strong>Hashed Password:</strong> " . $hashed_password . "</p>";
echo "<p><a href='admin_login.php'>Go to Login Page</a></p>";

// Test password verification
if (password_verify('admin123', $hashed_password)) {
    echo "<p style='color: green;'><strong>✓ Password test successful</strong></p>";
} else {
    echo "<p style='color: red;'><strong>✗ Password test failed</strong></p>";
}
?>