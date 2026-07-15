<?php
session_start();
require_once 'db_config.php';

// Check admin login
if (!isset($_SESSION['admin_id'])) {
    die("Access denied. Please login as admin.");
}

echo "<h2>Database Diagnostic Tool</h2>";

try {
    // Check if table exists
    $table_check = $pdo->query("SHOW TABLES LIKE 'contact_messages'")->fetch();
    
    if (!$table_check) {
        echo "<p style='color: red;'>❌ Table 'contact_messages' does not exist!</p>";
        exit();
    }
    
    echo "<p style='color: green;'>✅ Table 'contact_messages' exists</p>";
    
    // Check table structure
    echo "<h3>Table Structure:</h3>";
    $structure = $pdo->query("DESCRIBE contact_messages")->fetchAll();
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($structure as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Count records
    $count = $pdo->query("SELECT COUNT(*) as total FROM contact_messages")->fetch();
    echo "<h3>Total Records: {$count['total']}</h3>";
    
    // Show all records
    if ($count['total'] > 0) {
        echo "<h3>All Complaints:</h3>";
        $complaints = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC")->fetchAll();
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Full Name</th><th>Email</th><th>Message</th><th>Status</th><th>Created At</th></tr>";
        foreach ($complaints as $complaint) {
            echo "<tr>";
            echo "<td>{$complaint['id']}</td>";
            echo "<td>{$complaint['full_name']}</td>";
            echo "<td>{$complaint['email']}</td>";
            echo "<td>" . substr($complaint['notes'], 0, 50) . "...</td>";
            echo "<td>{$complaint['status']}</td>";
            echo "<td>{$complaint['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ No complaints found in the database</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}
?>

<br><br>
<a href="admin_complaints.php">← Back to Complaints Page</a>