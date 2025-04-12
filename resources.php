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

// Get all skills
$stmt = $pdo->query("SELECT * FROM skills ORDER BY category, skill_name");
$skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get resources for each skill
$resources = [];
foreach ($skills as $skill) {
    $stmt = $pdo->prepare("SELECT * FROM resources WHERE skill_id = ? ORDER BY type");
    $stmt->execute([$skill['id']]);
    $resources[$skill['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning Resources - Career Roadmap Generator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .resource-card {
            transition: transform 0.2s;
            height: 100%;
        }
        .resource-card:hover {
            transform: translateY(-5px);
        }
        .video-thumbnail {
            position: relative;
            overflow: hidden;
            border-radius: 8px;
        }
        .video-thumbnail img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        .play-icon {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 3rem;
            opacity: 0.8;
            transition: opacity 0.2s;
        }
        .video-thumbnail:hover .play-icon {
            opacity: 1;
        }
        .pdf-card {
            background-color: #f8f9fa;
        }
        .pdf-icon {
            font-size: 3rem;
            color: #dc3545;
        }
        .nav-pills .nav-link.active {
            background-color: #4e73df;
        }
        .nav-pills .nav-link {
            color: #4e73df;
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
                        <a class="nav-link active" href="resources.php">Resources</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="career_goal.php">Career Goals</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="roadmap.php">My Roadmap</a>
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
                <h2>Learning Resources</h2>
                <p class="lead">Access curated videos and documents to enhance your skills.</p>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Skills</h5>
                    </div>
                    <div class="card-body p-2">
                        <div class="nav flex-column nav-pills">
                            <?php foreach ($skills as $index => $skill): ?>
                                <button class="nav-link <?php echo $index === 0 ? 'active' : ''; ?>" 
                                        data-bs-toggle="pill" 
                                        data-bs-target="#skill-<?php echo $skill['id']; ?>">
                                    <?php echo htmlspecialchars($skill['skill_name']); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-9">
                <div class="tab-content">
                    <?php foreach ($skills as $index => $skill): ?>
                        <div class="tab-pane fade <?php echo $index === 0 ? 'show active' : ''; ?>" 
                             id="skill-<?php echo $skill['id']; ?>">
                            
                            <h4 class="mb-4"><?php echo htmlspecialchars($skill['skill_name']); ?> Resources</h4>
                            
                            <?php if (!empty($resources[$skill['id']])): ?>
                                <div class="row">
                                    <!-- Videos Section -->
                                    <div class="col-12 mb-4">
                                        <h5>Video Tutorials</h5>
                                        <div class="row">
                                            <?php foreach ($resources[$skill['id']] as $resource): ?>
                                                <?php if ($resource['type'] === 'video'): ?>
                                                    <div class="col-md-6 mb-4">
                                                        <div class="card resource-card">
                                                            <div class="video-thumbnail">
                                                                <img src="<?php echo htmlspecialchars($resource['thumbnail_url']); ?>" 
                                                                     alt="<?php echo htmlspecialchars($resource['title']); ?>">
                                                                <div class="play-icon">
                                                                    <i class="bi bi-play-circle"></i>
                                                                </div>
                                                            </div>
                                                            <div class="card-body">
                                                                <h5 class="card-title"><?php echo htmlspecialchars($resource['title']); ?></h5>
                                                                <p class="card-text"><?php echo htmlspecialchars($resource['description']); ?></p>
                                                                <a href="<?php echo htmlspecialchars($resource['url']); ?>" 
                                                                   class="btn btn-primary" 
                                                                   target="_blank">
                                                                    Watch Video
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <!-- PDFs Section -->
                                    <div class="col-12">
                                        <h5>PDF Resources</h5>
                                        <div class="row">
                                            <?php foreach ($resources[$skill['id']] as $resource): ?>
                                                <?php if ($resource['type'] === 'pdf'): ?>
                                                    <div class="col-md-6 mb-4">
                                                        <div class="card resource-card pdf-card">
                                                            <div class="card-body text-center">
                                                                <i class="bi bi-file-pdf pdf-icon mb-3"></i>
                                                                <h5 class="card-title"><?php echo htmlspecialchars($resource['title']); ?></h5>
                                                                <p class="card-text"><?php echo htmlspecialchars($resource['description']); ?></p>
                                                                <a href="<?php echo htmlspecialchars($resource['url']); ?>" 
                                                                   class="btn btn-primary" 
                                                                   target="_blank">
                                                                    Download PDF
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    No resources available for this skill yet.
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 