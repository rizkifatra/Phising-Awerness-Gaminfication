<?php
include_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get the SQL content from the file
    $sqlFile = file_get_contents('../database/create_scores_table.sql');
    
    // Execute the SQL
    $db->exec($sqlFile);
    
    echo "Tables created successfully!";
} catch (PDOException $e) {
    echo "Error creating tables: " . $e->getMessage();
}
?>
