<?php
require_once 'config/database.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get project ID from URL
$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($project_id <= 0) {
    header("Location: portfolio.php");
    exit();
}

// Get project details
$stmt = $pdo->prepare("
    SELECT p.*, u.name as username 
    FROM portfolio_items p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.id = ?
");
$stmt->execute([$project_id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    header("Location: portfolio.php");
    exit();
}

// Get project files
$stmt = $pdo->prepare("
    SELECT * FROM portfolio_files 
    WHERE portfolio_id = ?
");
$stmt->execute([$project_id]);
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get project skills
$stmt = $pdo->prepare("
    SELECT s.skill_name 
    FROM skills s 
    JOIN portfolio_skills ps ON s.id = ps.skill_id 
    WHERE ps.portfolio_id = ?
");
$stmt->execute([$project_id]);
$skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($project['title']); ?> - Career Roadmap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --secondary: #64748b;
            --success: #059669;
            --danger: #dc2626;
            --background: #f8fafc;
            --surface: #ffffff;
            --text: #1e293b;
            --border: #e2e8f0;
        }

        body {
            background-color: var(--background);
            color: var(--text);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        .project-header {
            background: linear-gradient(to right, #2563eb, #1d4ed8);
            padding: 3rem 0;
            margin-bottom: 2rem;
            color: white;
        }

        .project-content {
            background: var(--surface);
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .project-image {
            max-width: 100%;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .skill-badge {
            background-color: var(--primary);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            display: inline-block;
        }

        .file-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            background-color: #f8fafc;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }

        .file-item i {
            font-size: 1.5rem;
            margin-right: 1rem;
            color: var(--secondary);
        }

        .file-item .file-info {
            flex-grow: 1;
        }

        .file-item .file-name {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }

        .file-item .file-size {
            color: var(--secondary);
            font-size: 0.875rem;
        }

        .btn-download {
            background-color: var(--primary);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        .btn-download i {
            margin-right: 0.5rem;
        }

        .project-meta {
            display: flex;
            gap: 2rem;
            margin-bottom: 1.5rem;
            color: var(--secondary);
        }

        .project-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .project-meta-item i {
            color: var(--primary);
        }

        .section-title {
            color: var(--text);
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--border);
        }

        .back-button {
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .back-button:hover {
            color: rgba(255, 255, 255, 0.8);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="project-header">
        <div class="container">
            <a href="portfolio.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
                Back to Portfolio
            </a>
            <h1><?php echo htmlspecialchars($project['title']); ?></h1>
            <div class="project-meta">
                <div class="project-meta-item">
                    <i class="fas fa-user"></i>
                    <span><?php echo htmlspecialchars($project['username']); ?></span>
                </div>
                <div class="project-meta-item">
                    <i class="fas fa-calendar"></i>
                    <span><?php echo date('F Y', strtotime($project['completion_date'])); ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <div class="project-content">
                    <?php if ($project['image_path']): ?>
                        <img src="<?php echo htmlspecialchars($project['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($project['title']); ?>" 
                             class="project-image">
                    <?php endif; ?>

                    <h3 class="section-title">Project Description</h3>
                    <div class="project-description">
                        <?php echo nl2br(htmlspecialchars($project['description'])); ?>
                    </div>

                    <?php if ($project['project_url']): ?>
                        <div class="mt-4">
                            <a href="<?php echo htmlspecialchars($project['project_url']); ?>" 
                               class="btn btn-primary" 
                               target="_blank">
                                <i class="fas fa-external-link-alt me-2"></i>
                                View Project
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="project-content">
                    <h3 class="section-title">Skills Used</h3>
                    <div class="skills-list">
                        <?php foreach ($skills as $skill): ?>
                            <span class="skill-badge">
                                <?php echo htmlspecialchars($skill['skill_name']); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>

                    <?php if (!empty($files)): ?>
                        <h3 class="section-title mt-4">Project Files</h3>
                        <div class="files-list">
                            <?php foreach ($files as $file): ?>
                                <div class="file-item">
                                    <i class="<?php echo getFileIcon($file['file_name']); ?>"></i>
                                    <div class="file-info">
                                        <div class="file-name"><?php echo htmlspecialchars($file['file_name']); ?></div>
                                        <div class="file-size">
                                            <?php echo formatFileSize(filesize($file['file_path'])); ?>
                                        </div>
                                    </div>
                                    <a href="<?php echo htmlspecialchars($file['file_path']); ?>" 
                                       class="btn-download" 
                                       download>
                                        <i class="fas fa-download"></i>
                                        Download
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <?php
    function getFileIcon($filename) {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        switch($ext) {
            case 'pdf':
                return 'fas fa-file-pdf';
            case 'doc':
            case 'docx':
                return 'fas fa-file-word';
            case 'zip':
            case 'rar':
                return 'fas fa-file-archive';
            default:
                return 'fas fa-file';
        }
    }

    function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    ?>
</body>
</html> 