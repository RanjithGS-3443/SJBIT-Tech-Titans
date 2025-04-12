<?php
require_once 'config/database.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's skills
$stmt = $pdo->prepare("
    SELECT s.skill_name, us.level, 
        CASE us.level
            WHEN 'advanced' THEN 90
            WHEN 'intermediate' THEN 60
            WHEN 'beginner' THEN 30
        END as skill_percentage
    FROM user_skills us
    JOIN skills s ON us.skill_id = s.id
    WHERE us.user_id = ?
    ORDER BY skill_percentage DESC
");
$stmt->execute([$user_id]);
$skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's portfolio items
$stmt = $pdo->prepare("
    SELECT p.*, COUNT(pf.id) as file_count, 
           GROUP_CONCAT(DISTINCT s.skill_name) as skills
    FROM portfolio_items p
    LEFT JOIN portfolio_files pf ON p.id = pf.portfolio_id
    LEFT JOIN portfolio_skills ps ON p.id = ps.portfolio_id
    LEFT JOIN skills s ON ps.skill_id = s.id
    WHERE p.user_id = ?
    GROUP BY p.id
    ORDER BY p.completion_date DESC
");
$stmt->execute([$user_id]);
$portfolio_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Portfolio - Career Roadmap</title>
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

        .portfolio-header {
            background: linear-gradient(to right, #2563eb, #1d4ed8);
            padding: 3rem 0;
            margin-bottom: 2rem;
            color: white;
        }

        .portfolio-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .portfolio-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 0;
        }

        .portfolio-card {
            background: var(--surface);
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .portfolio-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .portfolio-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 1rem 1rem 0 0;
        }

        .portfolio-card-body {
            padding: 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .portfolio-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: var(--text);
        }

        .portfolio-description {
            color: var(--secondary);
            font-size: 0.875rem;
            margin-bottom: 1rem;
            flex-grow: 1;
        }

        .portfolio-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            color: var(--secondary);
        }

        .portfolio-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .portfolio-meta-item i {
            color: var(--primary);
        }

        .portfolio-skills {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .skill-badge {
            background-color: #e0e7ff;
            color: var(--primary);
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .portfolio-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: auto;
        }

        .btn-view {
            background-color: var(--primary);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: background-color 0.2s ease;
        }

        .btn-view:hover {
            background-color: #1d4ed8;
            color: white;
        }

        .btn-visit {
            background-color: transparent;
            color: var(--primary);
            border: 1px solid var(--primary);
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }

        .btn-visit:hover {
            background-color: var(--primary);
            color: white;
        }

        .empty-portfolio {
            text-align: center;
            padding: 3rem;
            background: var(--surface);
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .empty-portfolio i {
            font-size: 3rem;
            color: var(--secondary);
            margin-bottom: 1rem;
        }

        .empty-portfolio h3 {
            color: var(--text);
            margin-bottom: 1rem;
        }

        .empty-portfolio p {
            color: var(--secondary);
            margin-bottom: 1.5rem;
        }

        .btn-add-project {
            background-color: var(--primary);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: background-color 0.2s ease;
        }

        .btn-add-project:hover {
            background-color: #1d4ed8;
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="portfolio-header">
        <div class="container">
            <h1>My Portfolio</h1>
            <p>Showcase your projects and achievements</p>
        </div>
    </div>

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4 mb-0">Projects</h2>
            <a href="add_portfolio.php" class="btn-add-project">
                <i class="fas fa-plus-circle"></i>
                Add New Project
            </a>
        </div>

        <?php if (empty($portfolio_items)): ?>
            <div class="empty-portfolio">
                <i class="fas fa-folder-open"></i>
                <h3>No Projects Yet</h3>
                <p>Start building your portfolio by adding your first project</p>
                <a href="add_portfolio.php" class="btn-add-project">
                    <i class="fas fa-plus-circle"></i>
                    Add Your First Project
                </a>
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach ($portfolio_items as $item): ?>
                    <div class="col">
                        <div class="portfolio-card">
                            <?php if ($item['image_path']): ?>
                                <img src="<?php echo htmlspecialchars($item['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                     class="portfolio-image">
                            <?php endif; ?>
                            
                            <div class="portfolio-card-body">
                                <h3 class="portfolio-title">
                                    <?php echo htmlspecialchars($item['title']); ?>
                                </h3>
                                
                                <p class="portfolio-description">
                                    <?php echo htmlspecialchars($item['description']); ?>
                                </p>

                                <div class="portfolio-meta">
                                    <div class="portfolio-meta-item">
                                        <i class="fas fa-calendar"></i>
                                        <span><?php echo date('M Y', strtotime($item['completion_date'])); ?></span>
                                    </div>
                                    <?php if ($item['file_count'] > 0): ?>
                                        <div class="portfolio-meta-item">
                                            <i class="fas fa-file"></i>
                                            <span><?php echo $item['file_count']; ?> files</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if ($item['skills']): ?>
                                    <div class="portfolio-skills">
                                        <?php foreach (explode(',', $item['skills']) as $skill): ?>
                                            <span class="skill-badge">
                                                <?php echo htmlspecialchars(trim($skill)); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="portfolio-actions">
                                    <a href="view_project.php?id=<?php echo $item['id']; ?>" 
                                       class="btn-view">
                                        <i class="fas fa-eye"></i>
                                        View Details
                                    </a>
                                    <?php if ($item['project_url']): ?>
                                        <a href="<?php echo htmlspecialchars($item['project_url']); ?>" 
                                           class="btn-visit" 
                                           target="_blank">
                                            <i class="fas fa-external-link-alt"></i>
                                            Visit
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 