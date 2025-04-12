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
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'add') {
                $stmt = $pdo->prepare("INSERT INTO user_career_goals (user_id, career_goal_id, target_date, status) VALUES (?, ?, ?, 'active')");
                $stmt->execute([$user_id, $_POST['career_goal_id'], $_POST['target_date']]);
                $success_message = "Career goal added successfully!";
            } elseif ($_POST['action'] === 'update_status') {
                $stmt = $pdo->prepare("UPDATE user_career_goals SET status = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$_POST['status'], $_POST['goal_id'], $user_id]);
                $success_message = "Career goal status updated successfully!";
            } elseif ($_POST['action'] === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM user_career_goals WHERE id = ? AND user_id = ?");
                $stmt->execute([$_POST['goal_id'], $user_id]);
                $success_message = "Career goal deleted successfully!";
            }
        }
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Get all available career goals
$stmt = $pdo->query("SELECT * FROM career_goals ORDER BY name");
$available_goals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's current career goals
$stmt = $pdo->prepare("
    SELECT ucg.*, cg.name, cg.description, cg.required_skills, cg.estimated_time, cg.salary_range 
    FROM user_career_goals ucg 
    JOIN career_goals cg ON ucg.career_goal_id = cg.id 
    WHERE ucg.user_id = ? 
    ORDER BY ucg.target_date
");
$stmt->execute([$user_id]);
$user_goals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Career Goals - Career Roadmap Generator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
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
                        <a class="nav-link active" href="career_goal.php">Career Goals</a>
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
                <h2>Career Goals</h2>
                <p class="lead">Set and manage your career goals to create a personalized roadmap.</p>
            </div>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success mt-3"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger mt-3"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Add New Goal</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="add">
                            <div class="mb-3">
                                <label for="career_goal_id" class="form-label">Select Career Goal</label>
                                <select name="career_goal_id" id="career_goal_id" class="form-select" required>
                                    <option value="">Choose a career goal...</option>
                                    <?php foreach ($available_goals as $goal): ?>
                                        <option value="<?php echo $goal['id']; ?>">
                                            <?php echo htmlspecialchars($goal['name']); ?> 
                                            (<?php echo htmlspecialchars($goal['estimated_time']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="target_date" class="form-label">Target Date</label>
                                <input type="date" name="target_date" id="target_date" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Goal</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">My Career Goals</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($user_goals)): ?>
                            <p class="text-muted">You haven't set any career goals yet.</p>
                        <?php else: ?>
                            <?php foreach ($user_goals as $goal): ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($goal['name']); ?></h5>
                                        <p class="card-text"><?php echo htmlspecialchars($goal['description']); ?></p>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Required Skills:</strong> <?php echo htmlspecialchars($goal['required_skills']); ?></p>
                                                <p><strong>Estimated Time:</strong> <?php echo htmlspecialchars($goal['estimated_time']); ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Salary Range:</strong> <?php echo htmlspecialchars($goal['salary_range']); ?></p>
                                                <p><strong>Target Date:</strong> <?php echo date('F j, Y', strtotime($goal['target_date'])); ?></p>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="goal_id" value="<?php echo $goal['id']; ?>">
                                                <select name="status" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                                    <option value="active" <?php echo $goal['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                                    <option value="completed" <?php echo $goal['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                    <option value="abandoned" <?php echo $goal['status'] === 'abandoned' ? 'selected' : ''; ?>>Abandoned</option>
                                                </select>
                                            </form>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this goal?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="goal_id" value="<?php echo $goal['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 