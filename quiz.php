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

// Handle quiz submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        $skill_id = $_POST['skill_id'];
        $score = 0;
        $total_questions = count($_POST['questions']);
        
        // Calculate score
        foreach ($_POST['questions'] as $question_id => $answer) {
            $stmt = $pdo->prepare("SELECT correct_answer FROM quiz_questions WHERE id = ?");
            $stmt->execute([$question_id]);
            $correct_answer = $stmt->fetchColumn();
            
            if ($answer == $correct_answer) {
                $score++;
            }
        }
        
        // Calculate percentage
        $percentage = ($score / $total_questions) * 100;
        
        // Determine skill level based on percentage
        $level = 'beginner';
        if ($percentage >= 80) {
            $level = 'advanced';
        } elseif ($percentage >= 50) {
            $level = 'intermediate';
        }
        
        // Update user skills
        $stmt = $pdo->prepare("
            INSERT INTO user_skills (user_id, skill_id, level) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE level = ?
        ");
        $stmt->execute([$user_id, $skill_id, $level, $level]);
        
        // Save quiz result
        $stmt = $pdo->prepare("
            INSERT INTO quiz_results (user_id, skill_id, score, total_questions, percentage, level) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $skill_id, $score, $total_questions, $percentage, $level]);
        
        $pdo->commit();
        $success_message = "Quiz completed! Your skill level has been updated to: " . ucfirst($level);
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error_message = "Error saving quiz results: " . $e->getMessage();
    }
}

// Get all skills for quiz selection
$stmt = $pdo->query("SELECT * FROM skills ORDER BY category, skill_name");
$skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get quiz questions if skill is selected
$selected_skill = null;
$questions = [];
if (isset($_GET['skill_id'])) {
    $skill_id = (int)$_GET['skill_id'];
    
    // Get skill details
    $stmt = $pdo->prepare("SELECT * FROM skills WHERE id = ?");
    $stmt->execute([$skill_id]);
    $selected_skill = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($selected_skill) {
        // Get quiz questions for this skill
        $stmt = $pdo->prepare("SELECT * FROM quiz_questions WHERE skill_id = ? ORDER BY RAND() LIMIT 10");
        $stmt->execute([$skill_id]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Get user's quiz history
$stmt = $pdo->prepare("
    SELECT qr.*, s.skill_name, s.category
    FROM quiz_results qr
    JOIN skills s ON qr.skill_id = s.id
    WHERE qr.user_id = ?
    ORDER BY qr.created_at DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$quiz_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skill Quiz - Career Roadmap Generator</title>
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
                        <a class="nav-link active" href="quiz.php">Skill Quiz</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="career_goal.php">Career Goals</a>
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
                <h2>Skill Quiz</h2>
                <p class="lead">Take quizzes to assess your skill levels more accurately.</p>
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
                        <h5 class="mb-0">Select a Skill</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET">
                            <div class="mb-3">
                                <label for="skill_id" class="form-label">Choose a skill to quiz yourself on:</label>
                                <select name="skill_id" id="skill_id" class="form-select" required>
                                    <option value="">Select a skill</option>
                                    <?php foreach ($skills as $skill): ?>
                                        <option value="<?php echo $skill['id']; ?>" <?php echo (isset($_GET['skill_id']) && $_GET['skill_id'] == $skill['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($skill['skill_name']); ?> 
                                            (<?php echo htmlspecialchars($skill['category']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Start Quiz</button>
                        </form>
                    </div>
                </div>

                <?php if (!empty($quiz_history)): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">Recent Quiz Results</h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                <?php foreach ($quiz_history as $result): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($result['skill_name']); ?></h6>
                                            <small><?php echo date('M j, Y', strtotime($result['created_at'])); ?></small>
                                        </div>
                                        <p class="mb-1">
                                            Score: <?php echo $result['score']; ?>/<?php echo $result['total_questions']; ?> 
                                            (<?php echo round($result['percentage']); ?>%)
                                        </p>
                                        <small class="text-muted">Level: <?php echo ucfirst($result['level']); ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-md-8">
                <?php if ($selected_skill && !empty($questions)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><?php echo htmlspecialchars($selected_skill['skill_name']); ?> Quiz</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="skill_id" value="<?php echo $selected_skill['id']; ?>">
                                
                                <?php foreach ($questions as $index => $question): ?>
                                    <div class="mb-4">
                                        <h5>Question <?php echo $index + 1; ?></h5>
                                        <p><?php echo htmlspecialchars($question['question_text']); ?></p>
                                        
                                        <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                            <?php 
                                                $options = json_decode($question['options'], true);
                                                foreach ($options['options'] as $key => $option): 
                                            ?>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="radio" name="questions[<?php echo $question['id']; ?>]" id="q<?php echo $question['id']; ?>_<?php echo $key; ?>" value="<?php echo htmlspecialchars($option); ?>" required>
                                                    <label class="form-check-label" for="q<?php echo $question['id']; ?>_<?php echo $key; ?>">
                                                        <?php echo htmlspecialchars($option); ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php elseif ($question['question_type'] === 'true_false'): ?>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="radio" name="questions[<?php echo $question['id']; ?>]" id="q<?php echo $question['id']; ?>_true" value="true" required>
                                                <label class="form-check-label" for="q<?php echo $question['id']; ?>_true">True</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="radio" name="questions[<?php echo $question['id']; ?>]" id="q<?php echo $question['id']; ?>_false" value="false" required>
                                                <label class="form-check-label" for="q<?php echo $question['id']; ?>_false">False</label>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" name="submit_quiz" class="btn btn-primary">Submit Quiz</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php elseif (isset($_GET['skill_id']) && $selected_skill): ?>
                    <div class="alert alert-info">
                        <h4 class="alert-heading">No Quiz Available</h4>
                        <p>There are no quiz questions available for <?php echo htmlspecialchars($selected_skill['skill_name']); ?> yet.</p>
                    </div>
                <?php elseif (isset($_GET['skill_id'])): ?>
                    <div class="alert alert-danger">
                        <h4 class="alert-heading">Skill Not Found</h4>
                        <p>The selected skill could not be found.</p>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <h4 class="alert-heading">Select a Skill</h4>
                        <p>Please select a skill from the dropdown to start a quiz.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 