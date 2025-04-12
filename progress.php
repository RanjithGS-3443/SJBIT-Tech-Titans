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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action']) && $_POST['action'] === 'add_progress') {
            $stmt = $pdo->prepare("INSERT INTO progress (user_id, roadmap_id, notes) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $_POST['roadmap_id'], $_POST['notes']]);
            $success_message = "Progress note added successfully!";
        }
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Get user's completed tasks
$stmt = $pdo->prepare("
    SELECT r.*, p.completion_date, p.notes as progress_notes
    FROM roadmap r
    LEFT JOIN progress p ON r.id = p.roadmap_id
    WHERE r.user_id = ? AND r.status = 'completed'
    ORDER BY p.completion_date DESC
");
$stmt->execute([$user_id]);
$completed_tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's active career goals with progress
$stmt = $pdo->prepare("
    SELECT cg.*, ucg.target_date, ucg.status,
           (SELECT COUNT(*) FROM roadmap r WHERE r.user_id = ? AND r.status = 'completed') as completed_tasks,
           (SELECT COUNT(*) FROM roadmap r WHERE r.user_id = ?) as total_tasks
    FROM user_career_goals ucg
    JOIN career_goals cg ON ucg.career_goal_id = cg.id
    WHERE ucg.user_id = ? AND ucg.status = 'active'
");
$stmt->execute([$user_id, $user_id, $user_id]);
$goals_progress = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's skill progress
$stmt = $pdo->prepare("
    SELECT s.*, us.level,
           (SELECT COUNT(*) FROM roadmap r 
            JOIN resources res ON r.resource_id = res.id 
            WHERE r.user_id = ? AND res.skill_id = s.id AND r.status = 'completed') as completed_resources
    FROM skills s
    LEFT JOIN user_skills us ON s.id = us.skill_id AND us.user_id = ?
    ORDER BY s.category, s.skill_name
");
$stmt->execute([$user_id, $user_id]);
$skills_progress = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group skills by category
$skills_by_category = [];
foreach ($skills_progress as $skill) {
    if (!isset($skills_by_category[$skill['category']])) {
        $skills_by_category[$skill['category']] = [];
    }
    $skills_by_category[$skill['category']][] = $skill;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progress - Career Roadmap Generator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
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
        .progress-card {
            transition: all 0.3s ease;
        }
        .progress-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Career Roadmap Generator</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="skill_assessment.php">Skill Assessment</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="career_goal.php">Career Goals</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="roadmap.php">My Roadmap</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="progress.php">Progress</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($user_name); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2>Progress Tracking</h2>
                <p class="lead">Monitor your learning journey and achievements.</p>
            </div>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success mt-3"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger mt-3"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Career Goals Progress</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($goals_progress)): ?>
                            <p class="text-muted">No active career goals found.</p>
                        <?php else: ?>
                            <?php foreach ($goals_progress as $goal): ?>
                                <div class="card progress-card mb-3">
                                    <div class="card-body">
                                        <h6 class="card-title"><?php echo htmlspecialchars($goal['name']); ?></h6>
                                        <p class="card-text"><?php echo htmlspecialchars($goal['description']); ?></p>
                                        <div class="progress mb-2">
                                            <?php 
                                            $progress = $goal['total_tasks'] > 0 
                                                ? ($goal['completed_tasks'] / $goal['total_tasks']) * 100 
                                                : 0;
                                            ?>
                                            <div class="progress-bar" role="progressbar" style="width: <?php echo $progress; ?>%">
                                                <?php echo round($progress); ?>%
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <?php echo $goal['completed_tasks']; ?> of <?php echo $goal['total_tasks']; ?> tasks completed
                                            </small>
                                            <small class="text-muted">
                                                Target: <?php echo date('F j, Y', strtotime($goal['target_date'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Achievements</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($completed_tasks)): ?>
                            <p class="text-muted">No completed tasks yet.</p>
                        <?php else: ?>
                            <?php foreach ($completed_tasks as $task): ?>
                                <div class="card progress-card mb-3">
                                    <div class="card-body">
                                        <h6 class="card-title"><?php echo htmlspecialchars($task['task_description']); ?></h6>
                                        <p class="card-text">
                                            <small class="text-muted">
                                                Completed on: <?php echo date('F j, Y', strtotime($task['completion_date'])); ?>
                                            </small>
                                        </p>
                                        <?php if ($task['progress_notes']): ?>
                                            <p class="card-text"><?php echo htmlspecialchars($task['progress_notes']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Skills Progress</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($skills_by_category as $category => $skills): ?>
                            <h6 class="mt-3"><?php echo htmlspecialchars($category); ?></h6>
                            <?php foreach ($skills as $skill): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span><?php echo htmlspecialchars($skill['skill_name']); ?></span>
                                        <?php if ($skill['level']): ?>
                                            <span class="badge bg-<?php 
                                                echo $skill['level'] === 'advanced' ? 'success' : 
                                                    ($skill['level'] === 'intermediate' ? 'info' : 'secondary'); 
                                            ?>">
                                                <?php echo ucfirst($skill['level']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-dark">Not assessed</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($skill['completed_resources'] > 0): ?>
                                        <div class="progress mt-1" style="height: 5px;">
                                            <div class="progress-bar" role="progressbar" style="width: <?php echo min(($skill['completed_resources'] / 5) * 100, 100); ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?php echo $skill['completed_resources']; ?> resources completed</small>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 