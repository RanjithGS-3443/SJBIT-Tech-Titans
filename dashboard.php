<?php
require_once 'config/database.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Debug information
error_log("User ID: " . $user_id);

// Get user statistics
$stmt = $pdo->prepare("SELECT 
    (SELECT COUNT(*) FROM user_skills WHERE user_id = :user_id) as total_skills,
    (SELECT COUNT(*) FROM user_career_goals WHERE user_id = :user_id AND status = 'active') as active_goals,
    (SELECT COUNT(*) FROM roadmap WHERE user_id = :user_id AND status = 'completed') as completed_tasks,
    (SELECT COUNT(*) FROM roadmap WHERE user_id = :user_id AND status = 'pending') as pending_tasks");

try {
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Debug the results
    error_log("Statistics: " . print_r($stats, true));
    
    if ($stats === false) {
        error_log("No statistics found for user ID: " . $user_id);
        $stats = array(
            'total_skills' => 0,
            'active_goals' => 0,
            'completed_tasks' => 0,
            'pending_tasks' => 0
        );
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $stats = array(
        'total_skills' => 0,
        'active_goals' => 0,
        'completed_tasks' => 0,
        'pending_tasks' => 0
    );
}

// Get recent activities
$stmt = $pdo->prepare("
    SELECT 'skill' as type, s.skill_name as name, us.level as detail, NOW() as date
    FROM user_skills us
    JOIN skills s ON us.skill_id = s.id
    WHERE us.user_id = ?
    UNION ALL
    SELECT 'goal' as type, cg.name, ucg.status as detail, NOW() as date
    FROM user_career_goals ucg
    JOIN career_goals cg ON ucg.career_goal_id = cg.id
    WHERE ucg.user_id = ?
    UNION ALL
    SELECT 'task' as type, task_description as name, status as detail, NOW() as date
    FROM roadmap
    WHERE user_id = ?
    ORDER BY date DESC
    LIMIT 5
");
$stmt->execute([$user_id, $user_id, $user_id]);
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's top skills
$stmt = $pdo->prepare("
    SELECT s.skill_name, us.level
    FROM user_skills us
    JOIN skills s ON us.skill_id = s.id
    WHERE us.user_id = ?
    ORDER BY 
        CASE us.level
            WHEN 'advanced' THEN 3
            WHEN 'intermediate' THEN 2
            WHEN 'beginner' THEN 1
        END DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$top_skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's active career goals
$stmt = $pdo->prepare("
    SELECT cg.name, ucg.target_date
    FROM user_career_goals ucg
    JOIN career_goals cg ON ucg.career_goal_id = cg.id
    WHERE ucg.user_id = ? AND ucg.status = 'active'
    ORDER BY ucg.target_date ASC
    LIMIT 3
");
$stmt->execute([$user_id]);
$career_goals = $stmt->fetchAll(PDO::FETCH_ASSOC);

$current_page = 'dashboard';
$page_title = 'Dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Career Roadmap Generator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        /* Disable all animations and transitions */
        * {
            animation: none !important;
            transition: none !important;
            transform: none !important;
        }
        .navbar-collapse {
            transition: none !important;
        }
        .collapse {
            transition: none !important;
        }
        .collapsing {
            transition: none !important;
        }
        
        .dashboard-welcome {
            background: linear-gradient(135deg,rgb(2, 12, 44) 100%);
            color: white;
            border-radius: 0.5rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(14, 205, 7, 0.15);
        }

        .stat-card {
            border-radius: 0.5rem;
            height: 100%;
            display: block !important;
            box-shadow: none;
            transform: none;
        }

        .stat-card:hover {
            box-shadow: none;
            transform: none;
        }

        .stat-icon {
            font-size: 2rem;
            opacity: 0.8;
        }

        .feature-card {
            border-radius: 0.5rem;
            height: 100%;
            border: none;
            display: block !important;
            box-shadow: none;
            transform: none;
        }

        .feature-card:hover {
            box-shadow: none;
            transform: none;
        }

        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .activity-item {
            padding: 0.75rem 0;
            border-bottom: 1px solidrgb(222, 219, 5);
            display: block !important;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin-right: 1rem;
        }

        .skill-badge {
            display: inline-block;
            padding: 0.35rem 0.65rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 0.35rem;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .goal-item {
            padding: 0.75rem 0;
            border-bottom: 1px solid #e3e6f0;
            display: block !important;
        }

        .goal-item:last-child {
            border-bottom: none;
        }

        .progress {
            height: 0.8rem;
            border-radius: 0.35rem;
        }

        .card {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        .row {
            display: flex !important;
            flex-wrap: wrap !important;

        }

        .col, .col-md-4, .col-md-6, .col-lg-4, .col-lg-8, .col-xl-3 {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        .mb-4 {
            margin-bottom: 1.5rem !important;
            background-color:rgb(11, 4, 50);
            padding: 10px;
        }

        .card-body {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            color: black;
        }

        .card-header {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            color: black;
        }

        .profile-dropdown {
            position: relative;
            display: inline-block;
        }

        .profile-dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color:rgb(199, 233, 7);
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1000;
            border-radius: 0.35rem;
        }

        .profile-dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }

        .profile-dropdown-content a:hover {
            background-color: #f1f1f1;
        }

        .show {
            display: block;
        }
        .profile-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
        }
        
        /* Back Button Styling */
        .btn-secondary {
            background-color: #2c3e50;
            border: none;
            padding: 8px 20px;
            font-weight: bold;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .btn-secondary:hover {
            background-color: #34495e;
        }
        
        .btn-secondary i {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid py-4">
        <!-- Back Button -->
        <div class="row mb-3">
            <div class="col-12">
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </div>
        
        <!-- Welcome Section -->
        <div class="dashboard-welcome">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-5 fw-bold mb-3">Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h1>
                    <p class="lead mb-0">Track your progress, set new goals, and continue your journey to success.</p>
                </div>
                <div class="col-md-4 text-center">
                    <i class="fas fa-rocket fa-4x"></i>
                </div>
            </div>
        </div>
        
        <!-- Statistics Section -->
        
        <div class="row mb-4">
            
            <div class="col-xl-3 col-md-6 mb-4">
                <br>
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-uppercase mb-1" style="
            color: #000000; 
            padding: 10px 15px; 
            border-radius: 10px; 
            letter-spacing: 1px;
            font-size: 14px;
            text-align: center;
            background-color: rgb(185, 167, 4);
            font-weight: bold;">ASSESSED SKILLS</div>
                                <div class="h5 mb-0 font-weight-bold" style="font-weight: bold; color: black; font-size: 24px; text-align: center;"><?php echo isset($stats['total_skills']) ? intval($stats['total_skills']) : '0'; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-clipboard-check fa-2x text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <br>
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-uppercase mb-1" style="
            color: #000000; 
            padding: 10px 15px; 
            border-radius: 10px; 
            letter-spacing: 1px;
            font-size: 14px;
            text-align: center;
            background-color: rgb(185, 167, 4);
            font-weight: bold;">ACTIVE GOALS</div>
                                <div class="h5 mb-0 font-weight-bold" style="font-weight: bold; color: black; font-size: 24px; text-align: center;"><?php echo isset($stats['active_goals']) ? intval($stats['active_goals']) : '0'; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-flag fa-2x text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <br>
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-uppercase mb-1" style="
            color: #000000; 
            padding: 10px 15px; 
            border-radius: 10px; 
            letter-spacing: 1px;
            background-color: rgb(185, 167, 4);
            font-size: 14px;
            text-align: center;
            font-weight: bold;">COMPLETED TASKS</div>
                                <div class="h5 mb-0 font-weight-bold" style="font-weight: bold; color: black; font-size: 24px; text-align: center;"><?php echo isset($stats['completed_tasks']) ? intval($stats['completed_tasks']) : '0'; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-check-circle fa-2x text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <br>
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-uppercase mb-1" style="
            color: #000000; 
            padding: 10px 15px; 
            border-radius: 10px; 
            letter-spacing: 1px;
            background-color: rgb(185, 167, 4);
            font-size: 14px;
            text-align: center;
            font-weight: bold;">PENDING TASKS</div>
                                <div class="h5 mb-0 font-weight-bold" style="font-weight: bold; color: black; font-size: 24px; text-align: center;"><?php echo isset($stats['pending_tasks']) ? intval($stats['pending_tasks']) : '0'; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-clock fa-2x text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <br>
        <!-- Main Content Section -->
        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- Quick Access Section -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Quick Access</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-4">
                                <div class="card feature-card bg-primary text-white text-center h-100">
                                    <div class="card-body">
                                        <i class="fas fa-clipboard-check feature-icon"></i>
                                        <h5 class="card-title"><b>Skill Assessment</b></h5>
                                        <p class="card-text">Evaluate your current skills and identify areas for improvement.</p>
                                        <a href="skill_assessment.php" class="btn btn-light mt-2">Start Assessment</a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-4">
                                <div class="card feature-card bg-success text-white text-center h-100">
                                    <div class="card-body">
                                        <i class="fas fa-bullseye feature-icon"></i>
                                        <h5 class="card-title"><b>Career Goals</b></h5>
                                        <p class="card-text">Set and track your career goals to stay focused on your objectives.</p>
                                        <a href="career_goal.php" class="btn btn-light mt-2">Set Goals</a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-4">
                                <div class="card feature-card bg-info text-white text-center h-100">
                                    <div class="card-body">
                                        <i class="fas fa-robot feature-icon"></i>
                                        <h5 class="card-title"><b>Career Advisor</b></h5>
                                        <p class="card-text">Get personalized advice from our AI career advisor.</p>
                                        <button class="btn btn-light mt-2" data-bs-toggle="modal" data-bs-target="#careerAdvisorModal">Chat Now</button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-4">
                                <div class="card feature-card bg-warning text-white text-center h-100">
                                    <div class="card-body">
                                        <i class="fas fa-map feature-icon"></i>
                                        <h5 class="card-title"><b>My Roadmap</b></h5>
                                        <p class="card-text">View your personalized career development roadmap.</p>
                                        <a href="roadmap.php" class="btn btn-light mt-2">View Roadmap</a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-4">
                                <div class="card feature-card bg-danger text-white text-center h-100">
                                    <div class="card-body">
                                        <i class="fas fa-chart-line feature-icon"></i>
                                        <h5 class="card-title"><b>Progress</b></h5>
                                        <p class="card-text">Track your progress and achievements over time.</p>
                                        <a href="progress.php" class="btn btn-light mt-2">View Progress</a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-4">
                                <div class="card feature-card bg-secondary text-white text-center h-100">
                                    <div class="card-body">
                                        <i class="fas fa-book feature-icon"></i>
                                        <h5 class="card-title"><b>Resources</b></h5>
                                        <p class="card-text">Access curated learning materials for your career development.</p>
                                        <a href="resources.php" class="btn btn-light mt-2">Browse Resources</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity Section -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Recent Activity</h6>
                        <a href="progress.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($activities)): ?>
                            <p class="text-center text-muted my-4">No recent activities to display.</p>
                        <?php else: ?>
                            <?php foreach ($activities as $activity): ?>
                                <div class="activity-item d-flex align-items-center">
                                    <div class="activity-icon bg-<?php 
                                        echo $activity['type'] === 'skill' ? 'primary' : 
                                            ($activity['type'] === 'goal' ? 'success' : 'info'); 
                                    ?> text-white">
                                        <i class="fas fa-<?php 
                                            echo $activity['type'] === 'skill' ? 'clipboard-check' : 
                                                ($activity['type'] === 'goal' ? 'flag' : 'check-circle'); 
                                        ?>"></i>
                                    </div>
                                    <div>
                                        <div class="font-weight-bold"><?php echo htmlspecialchars($activity['name']); ?></div>
                                        <div class="small text-muted">
                                            <?php echo ucfirst($activity['type']); ?> - 
                                            <?php echo ucfirst($activity['detail']); ?> - 
                                            <?php echo date('M j, Y', strtotime($activity['date'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Top Skills Section -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Your Top Skills</h6>
                        <a href="skill_assessment.php" class="btn btn-sm btn-primary">Assess Skills</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($top_skills)): ?>
                            <p class="text-center text-muted my-4">No skills assessed yet.</p>
                        <?php else: ?>
                            <?php foreach ($top_skills as $skill): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="font-weight-bold"><?php echo htmlspecialchars($skill['skill_name']); ?></span>
                                        <span class="badge bg-<?php 
                                            echo $skill['level'] === 'advanced' ? 'success' : 
                                                ($skill['level'] === 'intermediate' ? 'info' : 'warning'); 
                                        ?>"><?php echo ucfirst($skill['level']); ?></span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar bg-<?php 
                                            echo $skill['level'] === 'advanced' ? 'success' : 
                                                ($skill['level'] === 'intermediate' ? 'info' : 'warning'); 
                                        ?>" role="progressbar" style="width: <?php 
                                            echo $skill['level'] === 'advanced' ? '100%' : 
                                                ($skill['level'] === 'intermediate' ? '66%' : '33%'); 
                                        ?>"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Career Goals Section -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Your Career Goals</h6>
                        <a href="career_goal.php" class="btn btn-sm btn-primary">Manage Goals</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($career_goals)): ?>
                            <p class="text-center text-muted my-4">No active career goals set.</p>
                        <?php else: ?>
                            <?php foreach ($career_goals as $goal): ?>
                                <div class="goal-item">
                                    <div class="font-weight-bold"><?php echo htmlspecialchars($goal['name']); ?></div>
                                    <div class="small text-muted">
                                        Target Date: <?php echo date('M j, Y', strtotime($goal['target_date'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <!-- Career Advisor Modal -->
    <div class="modal fade" id="careerAdvisorModal" tabindex="-1" aria-labelledby="careerAdvisorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="careerAdvisorModalLabel">Career Advisor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <script async type='module' src='https://interfaces.zapier.com/assets/web-components/zapier-interfaces/zapier-interfaces.esm.js'></script>
                    <zapier-interfaces-chatbot-embed is-popup='false' chatbot-id='cm9co798p005ppxxq11me62yf' height='600px' width='400px'></zapier-interfaces-chatbot-embed>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="js/main.js"></script>
</body>
</html> 