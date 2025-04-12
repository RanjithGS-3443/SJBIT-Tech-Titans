<?php
require_once 'config/database.php';
session_start();

// Helper function to get skill level class
function getSkillLevelClass($level) {
    if ($level >= 3) {
        return 'advanced';
    } else if ($level >= 2) {
        return 'intermediate';
    } else {
        return 'beginner';
    }
}

// Helper function to get status color
function getStatusColor($status) {
    switch ($status) {
        case 'completed':
            return 'success';
        case 'in_progress':
            return 'primary';
        case 'pending':
            return 'warning';
        default:
            return 'secondary';
    }
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit;
    }
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Handle AJAX form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Set JSON header first
    header('Content-Type: application/json');
    
    // Disable error output for AJAX requests
    error_reporting(0);
    
    try {
        if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
            // Validate inputs
            if (!isset($_POST['task_id']) || !isset($_POST['status'])) {
                throw new Exception('Missing required parameters');
            }

            $valid_statuses = ['pending', 'in_progress', 'completed'];
            if (!in_array($_POST['status'], $valid_statuses)) {
                throw new Exception('Invalid status value');
            }

            // Start transaction
            $pdo->beginTransaction();

            // Update task status
                $stmt = $pdo->prepare("UPDATE roadmap SET status = ? WHERE id = ? AND user_id = ?");
            $success = $stmt->execute([$_POST['status'], $_POST['task_id'], $user_id]);

            if (!$success) {
                throw new Exception('Failed to update task status');
            }

            // Get task details including associated skill
            $stmt = $pdo->prepare("
                SELECT r.*, s.id as skill_id, s.skill_name
                FROM roadmap r
                LEFT JOIN resources res ON r.resource_id = res.id
                LEFT JOIN skills s ON res.skill_id = s.id
                WHERE r.id = ? AND r.user_id = ?
            ");
            $stmt->execute([$_POST['task_id'], $user_id]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$task) {
                throw new Exception('Task not found');
            }

            // Update skill level if task is completed
            if ($_POST['status'] === 'completed' && $task['skill_id']) {
                // Calculate new skill level based on completed tasks
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as total_tasks,
                           SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks
                    FROM roadmap r
                    LEFT JOIN resources res ON r.resource_id = res.id
                    WHERE r.user_id = ? AND res.skill_id = ?
                ");
                $stmt->execute([$user_id, $task['skill_id']]);
                $progress = $stmt->fetch(PDO::FETCH_ASSOC);

                // Calculate new level (0 to 3)
                $completion_percentage = ($progress['total_tasks'] > 0) 
                    ? ($progress['completed_tasks'] / $progress['total_tasks']) 
                    : 0;
                $new_level = min(3, ceil($completion_percentage * 3));

                // Update user_skills table
                $stmt = $pdo->prepare("
                    INSERT INTO user_skills (user_id, skill_id, level) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE level = ?
                ");
                $stmt->execute([$user_id, $task['skill_id'], $new_level, $new_level]);
            }

            // Get updated skill levels
            $stmt = $pdo->prepare("
                SELECT s.skill_name, us.level
                FROM skills s
                LEFT JOIN user_skills us ON s.id = us.skill_id AND us.user_id = ?
                WHERE s.id IN (
                    SELECT DISTINCT res.skill_id 
                    FROM roadmap r
                    LEFT JOIN resources res ON r.resource_id = res.id
                    WHERE r.user_id = ?
                )
            ");
            $stmt->execute([$user_id, $user_id]);
            $updatedSkills = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Task status updated successfully!',
                'task' => $task,
                'skills' => $updatedSkills
            ]);
            exit;
        } else {
            throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

// Get user's active career goals
$stmt = $pdo->prepare("
    SELECT cg.* 
    FROM user_career_goals ucg 
    JOIN career_goals cg ON ucg.career_goal_id = cg.id 
    WHERE ucg.user_id = ? AND ucg.status = 'active'
");
$stmt->execute([$user_id]);
$active_goals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's roadmap tasks
$stmt = $pdo->prepare("
    SELECT r.*, res.title as resource_title, res.url as resource_url, res.type as resource_type,
           COALESCE(r.status, 'pending') as status
    FROM roadmap r
    LEFT JOIN resources res ON r.resource_id = res.id
    WHERE r.user_id = ?
    ORDER BY r.week, r.id
");
$stmt->execute([$user_id]);
$roadmap_tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group tasks by week
$tasks_by_week = [];
foreach ($roadmap_tasks as $task) {
    $week = $task['week'];
    if (!isset($tasks_by_week[$week])) {
        $tasks_by_week[$week] = [];
    }
    $tasks_by_week[$week][] = [
        'id' => $task['id'],
        'description' => $task['task_description'],
        'status' => $task['status'],
        'resource_title' => $task['resource_title'],
        'resource_url' => $task['resource_url'],
        'resource_type' => $task['resource_type']
    ];
}

// Get user's skills
$stmt = $pdo->prepare("
    SELECT s.*, us.level
    FROM skills s
    LEFT JOIN user_skills us ON s.id = us.skill_id AND us.user_id = ?
    ORDER BY s.category, s.skill_name
");
$stmt->execute([$user_id]);
$user_skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

class RoadmapGenerator {
    private $pdo;
    private $user_id;
    
    public function __construct($pdo, $user_id) {
        $this->pdo = $pdo;
        $this->user_id = $user_id;
    }
    
    // Get user's current skills and levels
    private function getUserSkills() {
        $stmt = $this->pdo->prepare("
            SELECT s.skill_name, s.category, us.level 
            FROM user_skills us 
            JOIN skills s ON us.skill_id = s.id 
            WHERE us.user_id = ?
        ");
        $stmt->execute([$this->user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get user's career goals
    private function getUserGoals() {
        $stmt = $this->pdo->prepare("
            SELECT cg.* 
            FROM user_career_goals ucg 
            JOIN career_goals cg ON ucg.career_goal_id = cg.id 
            WHERE ucg.user_id = ? AND ucg.status = 'active'
        ");
        $stmt->execute([$this->user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Calculate skill gap for each goal
    private function calculateSkillGap($userSkills, $goalSkills) {
        $gap = [];
        $userSkillLevels = [];
        
        // Convert user skills to associative array
        foreach ($userSkills as $skill) {
            $userSkillLevels[$skill['skill_name']] = $this->convertLevelToScore($skill['level']);
        }
        
        // Parse required skills from goal
        $requiredSkills = explode(',', $goalSkills);
        foreach ($requiredSkills as $skill) {
            $skill = trim($skill);
            if (!isset($userSkillLevels[$skill])) {
                $gap[$skill] = 3; // Maximum gap if skill doesn't exist
            } else {
                $gap[$skill] = max(0, 3 - $userSkillLevels[$skill]);
            }
        }
        
        return $gap;
    }
    
    // Convert skill level to numeric score
    private function convertLevelToScore($level) {
        switch ($level) {
            case 'beginner': return 1;
            case 'intermediate': return 2;
            case 'advanced': return 3;
            default: return 0;
        }
    }
    
    // Get recommended resources for skills
    private function getRecommendedResources($skills) {
        $resources = [];
        $placeholders = str_repeat('?,', count($skills) - 1) . '?';
        
        $stmt = $this->pdo->prepare("
            SELECT r.*, s.skill_name 
            FROM resources r 
            JOIN skills s ON r.skill_id = s.id 
            WHERE s.skill_name IN ($placeholders)
            ORDER BY r.id DESC
        ");
        $stmt->execute(array_keys($skills));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Generate personalized roadmap
    public function generateRoadmap() {
        $userSkills = $this->getUserSkills();
        $userGoals = $this->getUserGoals();
        $roadmap = [];
        
        foreach ($userGoals as $goal) {
            $skillGap = $this->calculateSkillGap($userSkills, $goal['required_skills']);
            
            // Sort skills by gap size (prioritize larger gaps)
            arsort($skillGap);
            
            // Get recommended resources
            $resources = $this->getRecommendedResources($skillGap);
            
            // Create weekly plan
            $weeklyPlan = $this->createWeeklyPlan($skillGap, $resources, $goal);
            
            $roadmap[] = [
                'goal' => $goal,
                'skill_gap' => $skillGap,
                'weekly_plan' => $weeklyPlan
            ];
        }
        
        return $roadmap;
    }
    
    // Create weekly learning plan
    private function createWeeklyPlan($skillGap, $resources, $goal) {
        $weeks = [];
        $week = 1;
        $resourceIndex = 0;
        
        // Estimate total weeks from goal's estimated_time
        $totalWeeks = $this->estimateTotalWeeks($goal['estimated_time']);
        
        foreach ($skillGap as $skill => $gap) {
            $skillResources = array_filter($resources, function($r) use ($skill) {
                return $r['skill_name'] === $skill;
            });
            
            // Calculate weeks needed for this skill based on gap size
            $weeksForSkill = max(1, ceil(($gap / 3) * ($totalWeeks / count($skillGap))));
            
            for ($i = 0; $i < $weeksForSkill && $week <= $totalWeeks; $i++) {
                $weeklyResources = array_slice($skillResources, $i * 2, 2); // 2 resources per week
                
                $weeks[$week] = [
                    'skill_focus' => $skill,
                    'gap_level' => $gap,
                    'resources' => $weeklyResources,
                    'tasks' => $this->generateTasks($skill, $gap, $week)
                ];
                
                $week++;
            }
        }
        
        return $weeks;
    }
    
    // Estimate total weeks from estimated_time string
    private function estimateTotalWeeks($estimatedTime) {
        // Parse strings like "6-12 months" or "4-8 weeks"
        preg_match('/(\d+)-(\d+)\s+(month|week)s?/', $estimatedTime, $matches);
        if (count($matches) >= 4) {
            $minTime = (int)$matches[1];
            $maxTime = (int)$matches[2];
            $unit = $matches[3];
            
            $avgTime = ($minTime + $maxTime) / 2;
            if ($unit === 'month') {
                return ceil($avgTime * 4); // Convert months to weeks
            }
            return ceil($avgTime);
        }
        return 12; // Default to 12 weeks if parsing fails
    }
    
    // Generate weekly tasks based on skill and gap
    private function generateTasks($skill, $gap, $week) {
        $tasks = [];
        
        // Basic task templates
        $taskTemplates = [
            'Learn fundamentals of %s',
            'Practice %s through exercises',
            'Work on %s projects',
            'Review and reinforce %s concepts',
            'Apply %s in real-world scenarios'
        ];
        
        // Generate 2-3 tasks per week
        $numTasks = rand(2, 3);
        for ($i = 0; $i < $numTasks; $i++) {
            $template = $taskTemplates[array_rand($taskTemplates)];
            $tasks[] = sprintf($template, $skill);
        }
        
        return $tasks;
    }

    public function generateDataAnalystRoadmap() {
        // Define required skills for Data Analyst role
        $requiredSkills = [
            'SQL' => 3,
            'Python' => 3,
            'Data Visualization' => 3,
            'Statistics' => 3,
            'Excel' => 3,
            'Tableau' => 2,
            'Power BI' => 2,
            'Machine Learning' => 2
        ];

        // Get user's current skills
        $userSkills = $this->getUserSkills();
        $skillGaps = [];

        // Calculate skill gaps
        foreach ($requiredSkills as $skillName => $requiredLevel) {
            $currentLevel = 0;
            foreach ($userSkills as $skill) {
                if ($skill['skill_name'] === $skillName) {
                    $currentLevel = $skill['level'];
                    break;
                }
            }
            $skillGaps[$skillName] = $requiredLevel - $currentLevel;
        }

        // Get recommended resources for each skill
        $resources = $this->getRecommendedResources(array_keys($requiredSkills));

        // Generate weekly plan
        $roadmap = [];
        $week = 1;
        $totalWeeks = 12; // 3 months plan

        // Week 1-2: SQL and Excel
        $roadmap[$week] = [
            [
                'description' => 'Learn SQL basics and advanced queries',
                'resource_id' => $this->findResourceId($resources, 'SQL', 'beginner')
            ],
            [
                'description' => 'Master Excel functions and data analysis',
                'resource_id' => $this->findResourceId($resources, 'Excel', 'beginner')
            ]
        ];
        $week++;

        // Week 3-4: Python and Statistics
        $roadmap[$week] = [
            [
                'description' => 'Learn Python for data analysis',
                'resource_id' => $this->findResourceId($resources, 'Python', 'beginner')
            ],
            [
                'description' => 'Study basic statistics concepts',
                'resource_id' => $this->findResourceId($resources, 'Statistics', 'beginner')
            ]
        ];
        $week++;

        // Week 5-6: Data Visualization and Tableau
        $roadmap[$week] = [
            [
                'description' => 'Learn data visualization principles',
                'resource_id' => $this->findResourceId($resources, 'Data Visualization', 'beginner')
            ],
            [
                'description' => 'Get started with Tableau',
                'resource_id' => $this->findResourceId($resources, 'Tableau', 'beginner')
            ]
        ];
        $week++;

        // Week 7-8: Advanced SQL and Power BI
        $roadmap[$week] = [
            [
                'description' => 'Advanced SQL techniques',
                'resource_id' => $this->findResourceId($resources, 'SQL', 'advanced')
            ],
            [
                'description' => 'Learn Power BI basics',
                'resource_id' => $this->findResourceId($resources, 'Power BI', 'beginner')
            ]
        ];
        $week++;

        // Week 9-10: Advanced Python and Machine Learning
        $roadmap[$week] = [
            [
                'description' => 'Advanced Python for data analysis',
                'resource_id' => $this->findResourceId($resources, 'Python', 'advanced')
            ],
            [
                'description' => 'Introduction to Machine Learning',
                'resource_id' => $this->findResourceId($resources, 'Machine Learning', 'beginner')
            ]
        ];
        $week++;

        // Week 11-12: Project Work and Portfolio
        $roadmap[$week] = [
            [
                'description' => 'Complete a data analysis project using SQL and Python',
                'resource_id' => null
            ],
            [
                'description' => 'Create a portfolio with Tableau and Power BI dashboards',
                'resource_id' => null
            ]
        ];

        return $roadmap;
    }

    private function findResourceId($resources, $skillName, $level) {
        foreach ($resources as $resource) {
            if ($resource['skill_name'] === $skillName && $resource['level'] === $level) {
                return $resource['id'];
            }
        }
        return null;
    }
}

// Create roadmap generator instance
$generator = new RoadmapGenerator($pdo, $user_id);

// Check if user has Data Analyst goal
$isDataAnalystGoal = false;
foreach ($active_goals as $goal) {
    if (strtolower($goal['name']) === 'data analyst') {
        $isDataAnalystGoal = true;
        break;
    }
}

// Generate appropriate roadmap
$roadmap = [];
if ($isDataAnalystGoal) {
    $dataAnalystRoadmap = $generator->generateDataAnalystRoadmap();
    
    // Clear existing roadmap
    $stmt = $pdo->prepare("DELETE FROM roadmap WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    // Save the Data Analyst roadmap
    try {
        $pdo->beginTransaction();
        
        foreach ($dataAnalystRoadmap as $week => $tasks) {
            foreach ($tasks as $task) {
                $stmt = $pdo->prepare("
                    INSERT INTO roadmap (user_id, week, task_description, resource_id, status)
                    VALUES (?, ?, ?, ?, 'pending')
                ");
                $stmt->execute([
                    $user_id,
                    $week,
                    $task['description'],
                    $task['resource_id']
                ]);
            }
        }
        
        $pdo->commit();
        
        // Structure the roadmap data for display
        $roadmap = [[
            'goal' => [
                'name' => 'Data Analyst',
                'estimated_time' => '12 weeks',
                'salary_range' => '$60,000 - $100,000'
            ],
            'skill_gap' => [
                'SQL' => 3,
                'Python' => 3,
                'Data Visualization' => 3,
                'Statistics' => 3,
                'Excel' => 3,
                'Tableau' => 2,
                'Power BI' => 2,
                'Machine Learning' => 2
            ],
            'weekly_plan' => []
        ]];

        // Convert the Data Analyst roadmap format to match the expected structure
        foreach ($dataAnalystRoadmap as $week => $tasks) {
            $roadmap[0]['weekly_plan'][$week] = [
                'skill_focus' => 'Data Analysis',
                'gap_level' => 3,
                'resources' => [],
                'tasks' => array_map(function($task) {
                    return $task['description'];
                }, $tasks)
            ];
        }
        
        // Refresh roadmap tasks after saving
        $stmt = $pdo->prepare("
            SELECT r.*, res.title as resource_title, res.url as resource_url, res.type as resource_type,
                   COALESCE(r.status, 'pending') as status
            FROM roadmap r
            LEFT JOIN resources res ON r.resource_id = res.id
            WHERE r.user_id = ?
            ORDER BY r.week, r.id
        ");
        $stmt->execute([$user_id]);
        $roadmap_tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Regroup tasks by week
        $tasks_by_week = [];
        foreach ($roadmap_tasks as $task) {
            $week = $task['week'];
            if (!isset($tasks_by_week[$week])) {
                $tasks_by_week[$week] = [];
            }
            $tasks_by_week[$week][] = [
                'id' => $task['id'],
                'description' => $task['task_description'],
                'status' => $task['status'],
                'resource_title' => $task['resource_title'],
                'resource_url' => $task['resource_url'],
                'resource_type' => $task['resource_type']
            ];
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error saving Data Analyst roadmap: " . $e->getMessage());
    }
} else {
    // Generate regular roadmap for other goals
    $roadmap = $generator->generateRoadmap();
    
    // Save regular roadmap to database
    try {
        $pdo->beginTransaction();
        
        // Clear existing roadmap
        $stmt = $pdo->prepare("DELETE FROM roadmap WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Insert new roadmap and store tasks by week
        $stmt = $pdo->prepare("
            INSERT INTO roadmap (user_id, week, task_description, status) 
            VALUES (?, ?, ?, 'pending')
        ");
        
        $tasks_by_week = [];
        foreach ($roadmap as $goalRoadmap) {
            foreach ($goalRoadmap['weekly_plan'] as $week => $plan) {
                if (!isset($tasks_by_week[$week])) {
                    $tasks_by_week[$week] = [];
                }
                
                foreach ($plan['tasks'] as $task) {
                    $stmt->execute([$user_id, $week, $task]);
                    $taskId = $pdo->lastInsertId();
                    
                    // Store task data for display
                    $tasks_by_week[$week][] = [
                        'id' => $taskId,
                        'description' => $task,
                        'status' => 'pending',
                        'resource_title' => null,
                        'resource_url' => null,
                        'resource_type' => null
                    ];
                }
            }
        }
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error saving roadmap: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Learning Roadmap - Career Roadmap Generator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #2e59d9;
            --accent-color: #36b9cc;
            --success-color: #1cc88a;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --dark-color: #5a5c69;
            --light-color: #f8f9fc;
        }

        body {
            background-color: var(--light-color);
        }

        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .roadmap-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .goal-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .goal-card:hover {
            transform: translateY(-5px);
        }

        .goal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 1.5rem;
            color: white;
            position: relative;
        }

        .goal-header h3 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .skill-section {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .skill-gap-indicator {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin: 8px 0;
            transition: all 0.3s ease;
        }

        .skill-gap-indicator.beginner .skill-gap-fill {
            background: linear-gradient(90deg, var(--warning-color), #ffd43b);
        }

        .skill-gap-indicator.intermediate .skill-gap-fill {
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }

        .skill-gap-indicator.advanced .skill-gap-fill {
            background: linear-gradient(90deg, var(--success-color), #28a745);
        }

        .skill-gap-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.5s ease, background 0.3s ease;
        }

        .skill-level-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            background: var(--light-color);
            color: var(--dark-color);
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .timeline-container {
            position: relative;
            padding: 2rem 0;
        }

        .timeline-line {
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(180deg, var(--primary-color), var(--secondary-color));
            transform: translateX(-50%);
        }

        .week-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            margin-bottom: 2rem;
            position: relative;
            width: calc(50% - 3rem);
            margin-left: auto;
            margin-right: auto;
        }

        .week-card::before {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            background: var(--primary-color);
            border-radius: 50%;
            top: 50%;
            transform: translateY(-50%);
        }

        .week-card:nth-child(odd) {
            margin-right: 50%;
        }

        .week-card:nth-child(odd)::before {
            right: -40px;
        }

        .week-card:nth-child(even) {
            margin-left: 50%;
        }

        .week-card:nth-child(even)::before {
            left: -40px;
        }

        .week-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 20px;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .task-list {
            margin-top: 1rem;
        }

        .task-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: var(--light-color);
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: transform 0.2s ease;
        }

        .task-item:hover {
            transform: translateX(5px);
            background: linear-gradient(90deg, var(--light-color), white);
        }

        .task-icon {
            width: 32px;
            height: 32px;
            min-width: 32px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-right: 1rem;
        }

        .task-content {
            flex: 1;
        }

        .task-actions {
            display: flex;
            gap: 0.5rem;
        }

        .resource-section {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }

        .resource-card {
            background: var(--light-color);
            border-radius: 8px;
            padding: 1rem;
            height: 100%;
            transition: transform 0.2s ease;
        }

        .resource-card:hover {
            transform: translateY(-3px);
        }

        .btn-resource {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .btn-resource:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            color: white;
        }

        .progress-indicator {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .progress-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--light-color);
            border: 2px solid var(--primary-color);
            margin-right: 0.5rem;
        }

        .progress-dot.active {
            background: var(--primary-color);
        }

        .progress-line {
            flex: 1;
            height: 2px;
            background: var(--light-color);
            position: relative;
        }

        .progress-line::after {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            background: var(--primary-color);
            width: var(--progress);
        }

        @media (max-width: 768px) {
            .week-card {
                width: 100%;
                margin-left: 0 !important;
                margin-right: 0 !important;
            }

            .week-card::before {
                display: none;
            }

            .timeline-line {
                left: 20px;
            }
        }

        /* Print Styles */
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background-color: white !important;
            }
            .goal-card {
                break-inside: avoid;
                box-shadow: none !important;
            }
            .dashboard-header {
                background: none !important;
                color: black !important;
                padding: 1rem 0 !important;
                box-shadow: none !important;
            }
            .container-fluid {
                width: 100% !important;
                padding: 0 !important;
            }
            .task-item {
                break-inside: avoid;
            }
            a[href]:after {
                content: none !important;
            }
        }

        .print-btn {
            background-color: #2c3e50;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-right: 10px;
        }

        .print-btn:hover {
            background-color: #34495e;
        }

        .print-btn i {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid py-4">
        <!-- Back and Print Buttons -->
        <div class="row mb-3">
            <div class="col-12">
                <a href="javascript:history.back()" class="btn btn-secondary no-print">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
                <button onclick="window.print()" class="print-btn no-print">
                    <i class="bi bi-printer"></i> Print Roadmap
            </button>
            </div>
        </div>

        <div class="dashboard-header">
            <div class="roadmap-container">
                <h1 class="h3 mb-0"><i class="bi bi-map me-2"></i>Your Learning Roadmap</h1>
                <p class="lead mb-0">Personalized learning path based on your skills and career goals</p>
            </div>
        </div>

        <div class="roadmap-container">
        <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if (empty($active_goals)): ?>
                <div class="alert alert-warning">
                <h4 class="alert-heading">No Active Career Goals</h4>
                <p>You need to set some career goals before you can view your roadmap. 
                   <a href="career_goal.php" class="alert-link">Set your career goals now</a>.</p>
            </div>
        <?php else: ?>
                <?php foreach ($roadmap as $goalRoadmap): ?>
                    <div class="goal-card">
                        <div class="goal-header">
                            <h3><i class="bi bi-trophy me-2"></i><?php echo htmlspecialchars($goalRoadmap['goal']['name']); ?></h3>
                        </div>

                        <div class="skill-section">
                            <div class="row">
                                <div class="col-md-6">
                                    <h4 class="h6 mb-3"><i class="bi bi-stars me-2"></i>Required Skills</h4>
                                    <?php foreach ($goalRoadmap['skill_gap'] as $skill => $gap): 
                                        // Get the actual skill level from user_skills
                                        $currentLevel = 0;
                                        foreach ($user_skills as $userSkill) {
                                            if ($userSkill['skill_name'] === $skill) {
                                                $currentLevel = $userSkill['level'] ?? 0;
                                                break;
                                            }
                                        }
                                    ?>
                                <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="fw-medium"><?php echo htmlspecialchars($skill); ?></span>
                                                <small class="text-muted">Level <?php echo $currentLevel; ?>/3</small>
                                            </div>
                                            <div class="skill-gap-indicator <?php echo getSkillLevelClass($currentLevel); ?>" 
                                                 data-skill="<?php echo htmlspecialchars($skill); ?>" 
                                                 data-current-level="<?php echo $currentLevel; ?>">
                                                <div class="skill-gap-fill" style="width: <?php echo ($currentLevel / 3 * 100); ?>%"></div>
                                            </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                                <div class="col-md-6">
                                    <h4 class="h6 mb-3"><i class="bi bi-calendar-check me-2"></i>Timeline Overview</h4>
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="bi bi-clock text-primary me-2"></i>
                                        <div>
                                            <small class="text-muted d-block">Estimated Completion</small>
                                            <span class="fw-medium"><?php echo htmlspecialchars($goalRoadmap['goal']['estimated_time']); ?></span>
                    </div>
                        </div>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-currency-dollar text-success me-2"></i>
                                        <div>
                                            <small class="text-muted d-block">Target Salary Range</small>
                                            <span class="fw-medium"><?php echo htmlspecialchars($goalRoadmap['goal']['salary_range']); ?></span>
                                        </div>
                                    </div>
                        </div>
                    </div>
                </div>

                        <div class="timeline-container">
                            <div class="timeline-line"></div>
                            <?php foreach ($goalRoadmap['weekly_plan'] as $week => $plan): ?>
                                <div class="week-card">
                                    <div class="week-badge">
                                        <i class="bi bi-calendar-week me-2"></i>Week <?php echo $week; ?>
                        </div>

                                    <h5 class="mb-3">
                                        <i class="bi bi-target me-2"></i>Focus: <?php echo htmlspecialchars($plan['skill_focus']); ?>
                                    </h5>

                                    <div class="task-list">
                                        <?php 
                                        // Find tasks for this week
                                        $weekTasks = isset($tasks_by_week[$week]) ? $tasks_by_week[$week] : [];
                                        $taskIndex = 0;
                                        
                                        foreach ($plan['tasks'] as $taskDescription): 
                                            // Get the corresponding task from tasks_by_week
                                            $taskData = isset($weekTasks[$taskIndex]) ? $weekTasks[$taskIndex] : null;
                                            $taskIndex++;
                                        ?>
                                            <div class="task-item" data-task-id="<?php echo htmlspecialchars($taskData['id']); ?>" data-status="<?php echo htmlspecialchars($taskData['status']); ?>">
                                                <div class="task-icon">
                                                    <?php
                                                    $iconClass = 'bi-clock';
                                                    if ($taskData['status'] === 'completed') {
                                                        $iconClass = 'bi-check2';
                                                    } elseif ($taskData['status'] === 'in_progress') {
                                                        $iconClass = 'bi-arrow-repeat';
                                                    }
                                                    ?>
                                                    <i class="bi <?php echo $iconClass; ?>"></i>
                                </div>
                                                <div class="task-content">
                                                    <?php echo htmlspecialchars($taskData['description']); ?>
                                                    </div>
                                                <div class="task-actions">
                                                    <button class="btn btn-sm <?php echo $taskData['status'] === 'pending' ? 'btn-primary' : 'btn-outline-primary'; ?> task-status-btn" data-status="pending">
                                                        <i class="bi bi-clock"></i>
                                                    </button>
                                                    <button class="btn btn-sm <?php echo $taskData['status'] === 'in_progress' ? 'btn-warning' : 'btn-outline-warning'; ?> task-status-btn" data-status="in_progress">
                                                        <i class="bi bi-arrow-repeat"></i>
                                                    </button>
                                                    <button class="btn btn-sm <?php echo $taskData['status'] === 'completed' ? 'btn-success' : 'btn-outline-success'; ?> task-status-btn" data-status="completed">
                                                        <i class="bi bi-check2"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <?php if (!empty($plan['resources'])): ?>
                                        <div class="resource-section">
                                            <h6 class="mb-3"><i class="bi bi-journal-text me-2"></i>Learning Resources</h6>
                                            <div class="row g-3">
                                                <?php foreach ($plan['resources'] as $resource): ?>
                                                    <div class="col-md-6">
                                                        <div class="resource-card">
                                                            <h6 class="mb-2">
                                                                <i class="bi bi-book me-2"></i>
                                                                <?php echo htmlspecialchars($resource['title']); ?>
                                                            </h6>
                                                            <p class="small text-muted mb-3"><?php echo htmlspecialchars($resource['description']); ?></p>
                                                            <a href="<?php echo htmlspecialchars($resource['url']); ?>" class="btn btn-resource" target="_blank">
                                                                <i class="bi bi-box-arrow-up-right me-1"></i>Access Resource
                                                            </a>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="js/main.js"></script>
    <script src="js/roadmap.js"></script>
</body>
</html> 