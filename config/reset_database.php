<?php
require_once 'database.php';

try {
    // Drop existing tables in correct order (due to foreign key constraints)
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    $pdo->exec("DROP TABLE IF EXISTS quiz_results");
    $pdo->exec("DROP TABLE IF EXISTS quiz_questions");
    $pdo->exec("DROP TABLE IF EXISTS user_skills");
    $pdo->exec("DROP TABLE IF EXISTS portfolio_skills");
    $pdo->exec("DROP TABLE IF EXISTS portfolio_items");
    $pdo->exec("DROP TABLE IF EXISTS user_courses");
    $pdo->exec("DROP TABLE IF EXISTS resources");
    $pdo->exec("DROP TABLE IF EXISTS skills");
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "Tables dropped successfully!\n";
    echo "Now run update_database.php to recreate the tables with fresh data.\n";
    
} catch(PDOException $e) {
    echo "Error resetting database: " . $e->getMessage() . "\n";
    echo "SQL State: " . $e->errorInfo[0] . "\n";
    echo "Error Code: " . $e->errorInfo[1] . "\n";
}
?> 