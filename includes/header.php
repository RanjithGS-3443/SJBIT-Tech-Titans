<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);

if ($is_logged_in) {
    require_once 'config/database.php';
    // Get user's profile photo
    $stmt = $pdo->prepare("SELECT name, profile_photo FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    $user_name = $user['name'];
    $profile_photo = $user['profile_photo'];
} else {
    $user_name = 'User';
    $profile_photo = 'assets/img/default-profile.png';
}

// Get current page for active nav highlighting
$current_page = basename($_SERVER['PHP_SELF']);

$current_page = $current_page ?? '';
$user_name = $_SESSION['user_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Career Roadmap Generator</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    
    <!-- Page-specific CSS -->
    <?php if (isset($page_css)): ?>
        <link href="<?php echo $page_css; ?>" rel="stylesheet">
    <?php endif; ?>

    <style>
        .navbar {
            background:rgb(7, 3, 131) !important;
            padding: 1rem;
        }
        .navbar-brand {
            font-weight: 600;
            font-size: 1.25rem;
        }
        .profile-section {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 16px;
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .profile-section:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        .profile-img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }
        .profile-name {
            color: white;
            font-weight: 500;
            margin-right: 8px;
        }
        .dropdown-toggle::after {
            display: none;
        }
        .dropdown-menu {
            margin-top: 10px;
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 8px;
        }
        .dropdown-item {
            padding: 10px 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s ease;
        }
        .dropdown-item:hover {
            background-color: #f8f9fa;
        }
        .dropdown-item.text-danger:hover {
            background-color: #fff5f5;
        }
        .dropdown-divider {
            margin: 8px 0;
        }
        .nav-link {
            color: rgba(255, 255, 255, 0.8) !important;
            font-weight: 500;
            padding: 8px 16px !important;
            transition: all 0.3s ease;
        }
        .nav-link:hover, .nav-link.active {
            color: white !important;
        }
        .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
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
    <?php if ($is_logged_in): ?>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-compass me-2"></i>Career Roadmap
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'skill_assessment.php' ? 'active' : ''; ?>" href="skill_assessment.php">
                            Skills
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'career_goal.php' ? 'active' : ''; ?>" href="career_goal.php">
                            Goals
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'roadmap.php' ? 'active' : ''; ?>" href="roadmap.php">
                            Roadmap
                        </a>
                    </li>
                </ul>
                
                <!-- Profile Dropdown -->
                <div class="dropdown">
                    <div class="profile-section" data-bs-toggle="dropdown">
                        <img src="<?php echo htmlspecialchars($profile_photo); ?>" alt="Profile" class="profile-img">
                        <span class="profile-name"><?php echo htmlspecialchars($user_name); ?></span>
                        <i class="fas fa-chevron-down text-white"></i>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <div class="container mt-4">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success animate-fade-in">
                <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger animate-fade-in">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize all dropdowns
        var dropdowns = document.querySelectorAll('[data-bs-toggle="dropdown"]');
        dropdowns.forEach(function(dropdown) {
            new bootstrap.Dropdown(dropdown);
        });
    });
    </script>
</body>
</html> 