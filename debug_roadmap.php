<?php
require_once 'config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

$user_id = $_SESSION['user_id'];

// Debug function
function debug($message, $data = null) {
    echo "<pre>";
    echo "<strong>$message:</strong>\n";
    if ($data !== null) {
        print_r($data);
    }
    echo "</pre>";
}

try {
    // 1. Check if Data Analyst goal exists
    $stmt = $pdo->prepare("SELECT * FROM career_goals WHERE name = 'Data Analyst'");
    $stmt->execute();
    $goal = $stmt->fetch(PDO::FETCH_ASSOC);
    debug("Data Analyst Goal", $goal);

    // 2. Check if user has the goal active
    if ($goal) {
        $stmt = $pdo->prepare("
            SELECT ucg.*, cg.name, cg.required_skills 
            FROM user_career_goals ucg 
            JOIN career_goals cg ON ucg.career_goal_id = cg.id 
            WHERE ucg.user_id = ? AND cg.id = ? AND ucg.status = 'active'
        ");
        $stmt->execute([$user_id, $goal['id']]);
        $user_goal = $stmt->fetch(PDO::FETCH_ASSOC);
        debug("User's Active Data Analyst Goal", $user_goal);
    }

    // 3. Check required skills
    $required_skills = ['Python', 'Data Analysis'];
    foreach ($required_skills as $skill) {
        $stmt = $pdo->prepare("
            SELECT s.*, us.level 
            FROM skills s 
            LEFT JOIN user_skills us ON s.id = us.skill_id AND us.user_id = ?
            WHERE s.skill_name = ?
        ");
        $stmt->execute([$user_id, $skill]);
        $skill_info = $stmt->fetch(PDO::FETCH_ASSOC);
        debug("Skill Info for $skill", $skill_info);
    }

    // 4. Check resources
    $stmt = $pdo->prepare("
        SELECT r.*, s.skill_name 
        FROM resources r 
        JOIN skills s ON r.skill_id = s.id 
        WHERE s.skill_name IN ('Python', 'Data Analysis')
    ");
    $stmt->execute();
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
    debug("Available Resources", $resources);

    // 5. Check existing roadmap
    $stmt = $pdo->prepare("
        SELECT r.*, res.title as resource_title, s.skill_name
        FROM roadmap r
        LEFT JOIN resources res ON r.resource_id = res.id
        LEFT JOIN skills s ON res.skill_id = s.id
        WHERE r.user_id = ?
        ORDER BY r.week, r.id
    ");
    $stmt->execute([$user_id]);
    $roadmap = $stmt->fetchAll(PDO::FETCH_ASSOC);
    debug("Current Roadmap", $roadmap);

} catch (Exception $e) {
    debug("Error", $e->getMessage());
} 