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

// Get user's skills
$stmt = $pdo->prepare("
    SELECT s.*, us.level
    FROM skills s
    JOIN user_skills us ON s.id = us.skill_id
    WHERE us.user_id = ?
    ORDER BY s.category, s.skill_name
");
$stmt->execute([$user_id]);
$user_skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group skills by category
$skills_by_category = [];
foreach ($user_skills as $skill) {
    if (!isset($skills_by_category[$skill['category']])) {
        $skills_by_category[$skill['category']] = [];
    }
    $skills_by_category[$skill['category']][] = $skill;
}

// Get all career goals
$stmt = $pdo->query("SELECT * FROM career_goals ORDER BY name");
$career_goals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's current career goals
$stmt = $pdo->prepare("
    SELECT cg.*, ucg.target_date, ucg.status
    FROM career_goals cg
    JOIN user_career_goals ucg ON cg.id = ucg.career_goal_id
    WHERE ucg.user_id = ?
    ORDER BY ucg.created_at DESC
");
$stmt->execute([$user_id]);
$user_career_goals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recommended career goals based on user's skills
$recommended_goals = [];
foreach ($career_goals as $goal) {
    $required_skills = explode(',', $goal['required_skills']);
    $matching_skills = 0;
    $total_required = count($required_skills);
    
    foreach ($user_skills as $skill) {
        if (in_array($skill['skill_name'], $required_skills)) {
            $matching_skills++;
        }
    }
    
    // Calculate match percentage
    $match_percentage = $total_required > 0 ? ($matching_skills / $total_required) * 100 : 0;
    
    // Only recommend if at least 30% match
    if ($match_percentage >= 30) {
        $goal['match_percentage'] = round($match_percentage);
        $recommended_goals[] = $goal;
    }
}

// Sort recommended goals by match percentage (highest first)
usort($recommended_goals, function($a, $b) {
    return $b['match_percentage'] - $a['match_percentage'];
});

// Get industry trends
$stmt = $pdo->query("SELECT * FROM industry_trends ORDER BY growth_rate DESC LIMIT 5");
$industry_trends = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Career Guidance - Career Roadmap Generator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .skill-badge {
            font-size: 0.8rem;
            margin-right: 0.3rem;
            margin-bottom: 0.3rem;
        }
        .match-bar {
            height: 10px;
            border-radius: 5px;
            background-color: #e9ecef;
            margin-bottom: 0.5rem;
        }
        .match-fill {
            height: 100%;
            border-radius: 5px;
            background-color: #28a745;
        }
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
                        <a class="nav-link" href="quiz.php">Skill Quiz</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="portfolio.php">Portfolio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="career_goal.php">Career Goals</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="career_guidance.php">Career Guidance</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="roadmap.php">My Roadmap</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="progress.php">Progress</a>
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
                <h2>Career Guidance</h2>
                <p class="lead">Get personalized career recommendations based on your skills and industry trends.</p>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Your Skills</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($user_skills)): ?>
                            <div class="alert alert-info">
                                <p class="mb-0">You haven't assessed your skills yet. <a href="skill_assessment.php">Take the assessment</a> to get personalized career recommendations.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($skills_by_category as $category => $skills): ?>
                                <h6 class="mt-3"><?php echo htmlspecialchars($category); ?></h6>
                                <div class="mb-3">
                                    <?php foreach ($skills as $skill): ?>
                                        <span class="badge bg-<?php 
                                            echo $skill['level'] === 'beginner' ? 'secondary' : 
                                                ($skill['level'] === 'intermediate' ? 'primary' : 'success'); 
                                        ?> skill-badge">
                                            <?php echo htmlspecialchars($skill['skill_name']); ?> 
                                            (<?php echo ucfirst($skill['level']); ?>)
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Industry Trends</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($industry_trends)): ?>
                            <div class="alert alert-info">
                                <p class="mb-0">No industry trend data available.</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($industry_trends as $trend): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($trend['industry']); ?></h6>
                                            <small class="text-success">+<?php echo $trend['growth_rate']; ?>% growth</small>
                                        </div>
                                        <p class="mb-1 small"><?php echo htmlspecialchars($trend['description']); ?></p>
                                        <small class="text-muted">Projected: <?php echo $trend['projected_year']; ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Recommended Career Goals</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($user_skills)): ?>
                            <div class="alert alert-info">
                                <p class="mb-0">Complete your skill assessment to get personalized career recommendations.</p>
                            </div>
                        <?php elseif (empty($recommended_goals)): ?>
                            <div class="alert alert-info">
                                <p class="mb-0">No career goals match your current skill set. Consider expanding your skills or exploring different career paths.</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($recommended_goals as $goal): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($goal['name']); ?></h5>
                                            <span class="badge bg-success"><?php echo $goal['match_percentage']; ?>% Match</span>
                                        </div>
                                        <div class="match-bar">
                                            <div class="match-fill" style="width: <?php echo $goal['match_percentage']; ?>%"></div>
                                        </div>
                                        <p class="mb-1"><?php echo htmlspecialchars($goal['description']); ?></p>
                                        <div class="mb-2">
                                            <strong>Required Skills:</strong>
                                            <?php 
                                                $required_skills = explode(',', $goal['required_skills']);
                                                foreach ($required_skills as $skill_name): 
                                                    $skill_level = 'Not Assessed';
                                                    $skill_class = 'secondary';
                                                    
                                                    foreach ($user_skills as $skill) {
                                                        if ($skill['skill_name'] === $skill_name) {
                                                            $skill_level = ucfirst($skill['level']);
                                                            $skill_class = $skill['level'] === 'beginner' ? 'secondary' : 
                                                                          ($skill['level'] === 'intermediate' ? 'primary' : 'success');
                                                            break;
                                                        }
                                                    }
                                            ?>
                                                <span class="badge bg-<?php echo $skill_class; ?> skill-badge">
                                                    <?php echo htmlspecialchars($skill_name); ?> 
                                                    (<?php echo $skill_level; ?>)
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <small class="text-muted">
                                                    <i class="bi bi-clock"></i> <?php echo htmlspecialchars($goal['estimated_time']); ?> | 
                                                    <i class="bi bi-currency-dollar"></i> <?php echo htmlspecialchars($goal['salary_range']); ?>
                                                </small>
                                            </div>
                                            <a href="career_goal.php?add=<?php echo $goal['id']; ?>" class="btn btn-sm btn-primary">
                                                Add as Goal
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Your Current Career Goals</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($user_career_goals)): ?>
                            <div class="alert alert-info">
                                <p class="mb-0">You haven't set any career goals yet. Browse the recommendations above or <a href="career_goal.php">add a career goal</a>.</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($user_career_goals as $goal): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($goal['name']); ?></h5>
                                            <span class="badge bg-<?php 
                                                echo $goal['status'] === 'active' ? 'primary' : 
                                                    ($goal['status'] === 'completed' ? 'success' : 'secondary'); 
                                            ?>">
                                                <?php echo ucfirst($goal['status']); ?>
                                            </span>
                                        </div>
                                        <p class="mb-1"><?php echo htmlspecialchars($goal['description']); ?></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="bi bi-calendar"></i> Target: <?php echo date('M j, Y', strtotime($goal['target_date'])); ?> | 
                                                <i class="bi bi-clock"></i> <?php echo htmlspecialchars($goal['estimated_time']); ?> | 
                                                <i class="bi bi-currency-dollar"></i> <?php echo htmlspecialchars($goal['salary_range']); ?>
                                            </small>
                                            <a href="career_goal.php" class="btn btn-sm btn-outline-primary">
                                                View Details
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 