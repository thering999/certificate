<?php
require_once 'db.php';

// Check if certificate_designs table exists
$result = $conn->query("SHOW TABLES LIKE 'certificate_designs'");
if ($result && $result->num_rows > 0) {
    echo "✓ Table certificate_designs exists\n";
    $desc = $conn->query("DESC certificate_designs");
    echo "Current schema:\n";
    while ($row = $desc->fetch_assoc()) {
        echo "  - {$row['Field']} ({$row['Type']}) {$row['Extra']}\n";
    }
} else {
    echo "✗ Table certificate_designs does NOT exist - will create it\n";
    
    // Create the table
    $sql = "CREATE TABLE certificate_designs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        design_name VARCHAR(255) NOT NULL,
        template_id VARCHAR(50),
        bg_image VARCHAR(255),
        text_elements JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        KEY idx_user (user_id),
        KEY idx_created (created_at)
    )";
    
    if ($conn->query($sql)) {
        echo "✓ Table certificate_designs created successfully\n";
    } else {
        echo "✗ Error creating table: " . $conn->error . "\n";
    }
}
?>
