<?php
require_once 'config/database.php';
require_once 'includes/logger.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    Logger::warning("Unauthorized access attempt to skill_assessment.php");
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Validation functions
function validateSkillLevel($level) {
    return in_array($level, ['beginner', 'intermediate', 'advanced']);
}

function validateFileType($type) {
    return in_array($type, ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        Logger::info("Starting transaction for user $user_id");
        
        if (isset($_POST['save_skills'])) {
            Logger::info("Processing skill assessment for user $user_id");
            
            // Validate and sanitize skill data
            $valid_skills = [];
            foreach ($_POST['skills'] as $skill_id => $level) {
                $skill_id = filter_var($skill_id, FILTER_SANITIZE_NUMBER_INT);
                $level = filter_var($level, FILTER_SANITIZE_STRING);
                
                if (validateSkillLevel($level)) {
                    $valid_skills[$skill_id] = $level;
                } else {
                    Logger::error("Invalid skill level provided by user $user_id: $level");
                    throw new Exception("Invalid skill level provided");
                }
            }
            
            // Delete existing user skills
            $stmt = $pdo->prepare("DELETE FROM user_skills WHERE user_id = ?");
            $stmt->execute([$user_id]);
            Logger::info("Deleted existing skills for user $user_id");
            
            // Insert new skill assessments
            $stmt = $pdo->prepare("INSERT INTO user_skills (user_id, skill_id, level) VALUES (?, ?, ?)");
            foreach ($valid_skills as $skill_id => $level) {
                $stmt->execute([$user_id, $skill_id, $level]);
            }
            Logger::info("Inserted new skills for user $user_id");
            
            // Verify data was saved correctly
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_skills WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $count = $stmt->fetchColumn();
            
            if ($count != count($valid_skills)) {
                Logger::error("Skill count mismatch for user $user_id. Expected: " . count($valid_skills) . ", Got: $count");
                throw new Exception("Not all skills were saved correctly");
            }
            
            $_SESSION['success_message'] = "Skill assessment saved successfully!";
            Logger::info("Successfully completed skill assessment for user $user_id");
        }
        
        // Handle resume upload
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
            Logger::info("Processing resume upload for user $user_id");
            
            if (!validateFileType($_FILES['resume']['type'])) {
                Logger::error("Invalid file type attempted by user $user_id: " . $_FILES['resume']['type']);
                throw new Exception("Invalid file type. Only PDF and Word documents are allowed.");
            }
            
            $upload_dir = 'uploads/resumes/';
            if (!file_exists($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    Logger::error("Failed to create upload directory for user $user_id");
                    throw new Exception("Failed to create upload directory");
                }
            }
            
            $file_extension = pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION);
            $file_name = 'resume_' . $user_id . '_' . time() . '.' . $file_extension;
            $target_path = $upload_dir . $file_name;
            
            if (!move_uploaded_file($_FILES['resume']['tmp_name'], $target_path)) {
                Logger::error("Failed to upload resume file for user $user_id");
                throw new Exception("Failed to upload resume file");
            }
            
            $stmt = $pdo->prepare("UPDATE users SET resume_path = ? WHERE id = ?");
            $stmt->execute([$target_path, $user_id]);
            $_SESSION['success_message'] = "Resume uploaded successfully!";
            Logger::info("Successfully uploaded resume for user $user_id");
        }
        
        // Handle past courses
        if (isset($_POST['add_course'])) {
            Logger::info("Processing course addition for user $user_id");
            
            // Sanitize input data
            $course_name = filter_var($_POST['course_name'], FILTER_SANITIZE_STRING);
            $institution = filter_var($_POST['institution'], FILTER_SANITIZE_STRING);
            $completion_date = filter_var($_POST['completion_date'], FILTER_SANITIZE_STRING);
            $certificate_url = filter_var($_POST['certificate_url'], FILTER_SANITIZE_URL);
            
            if (empty($course_name) || empty($institution) || empty($completion_date)) {
                Logger::error("Missing required course information for user $user_id");
                throw new Exception("Required course information is missing");
            }
            
            $stmt = $pdo->prepare("INSERT INTO user_courses (user_id, course_name, institution, completion_date, certificate_url) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $user_id,
                $course_name,
                $institution,
                $completion_date,
                $certificate_url
            ]);
            $_SESSION['success_message'] = "Course added successfully!";
            Logger::info("Successfully added course for user $user_id");
        }
        
        $pdo->commit();
        Logger::info("Successfully committed transaction for user $user_id");
        
        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        Logger::error("Error in skill_assessment.php for user $user_id: " . $e->getMessage());
        $_SESSION['error_message'] = $e->getMessage();
    }
}

// Get all available skills
$stmt = $pdo->query("SELECT * FROM skills ORDER BY category, skill_name");
$skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's existing skill assessments
$stmt = $pdo->prepare("SELECT skill_id, level FROM user_skills WHERE user_id = ?");
$stmt->execute([$user_id]);
$user_skills = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Get user's courses
$stmt = $pdo->prepare("SELECT * FROM user_courses WHERE user_id = ? ORDER BY completion_date DESC");
$stmt->execute([$user_id]);
$user_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's portfolio items
$stmt = $pdo->prepare("SELECT * FROM portfolio_items WHERE user_id = ? ORDER BY completion_date DESC");
$stmt->execute([$user_id]);
$portfolio_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's resume
$stmt = $pdo->prepare("SELECT resume_path FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$resume_path = $stmt->fetchColumn();

// Display success/error messages
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : null;
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : null;

// Clear messages after displaying
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skill Assessment - Career Roadmap Generator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .nav-pills .nav-link {
            color: #4e73df;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .nav-pills .nav-link.active {
            background-color: #4e73df;
            color: Green;
        }
        .skill-card {
            transition: transform 0.2s;
        }
        .skill-card:hover {
            transform: translateY(-5px);
        }
        .feature-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #4e73df;
        }
        /* Custom button styles */
        .card-body{
            background-color:rgb(20, 28, 52) !important;
        }
        .btn-primary {
            background-color: #4e73df !important;
            border-color: #4e73df !important;
            color: white !important;
        }
        .btn-primary:hover {
            background-color: #2e59d9 !important;
            border-color: #2e59d9 !important;
        }
        .btn-outline-primary {
            color: #4e73df !important;
            border-color: #4e73df !important;
        }
        .btn-outline-primary:hover {
            background-color: #4e73df !important;
            color: white !important;
        }
        .btn-secondary {
            background-color: #858796 !important;
            border-color: #858796 !important;
        }
        .btn-secondary:hover {
            background-color: #717384 !important;
            border-color: #717384 !important;
        }
        .btn-outline-success {
            color: #1cc88a !important;
            border-color: #1cc88a !important;
        }
        .btn-outline-success:hover {
            background-color: #1cc88a !important;
            color: white !important;
        }
        /* Add shadow effect to all buttons */
        .btn {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
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
        
        <div class="row">
            <div class="col-12 mb-4">
                <h2><i class="bi bi-clipboard-data me-2"></i>Skill Assessment</h2>
                <p class="lead">Evaluate your skills, showcase your portfolio, and track your progress.</p>
            </div>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-3">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <ul class="nav nav-pills flex-column" id="skillTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="assessment-tab" data-bs-toggle="pill" href="#assessment">
                                    <i class="bi bi-check-circle me-2"></i>Skill Assessment
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="quizzes-tab" data-bs-toggle="pill" href="#quizzes">
                                    <i class="bi bi-question-circle me-2"></i>Skill Quizzes
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="portfolio-tab" data-bs-toggle="pill" href="#portfolio">
                                    <i class="bi bi-briefcase me-2"></i>Portfolio
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="resume-tab" data-bs-toggle="pill" href="#resume">
                                    <i class="bi bi-file-text me-2"></i>Resume
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="courses-tab" data-bs-toggle="pill" href="#courses">
                                    <i class="bi bi-mortarboard me-2"></i>Past Courses
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-md-9">
                <div class="tab-content">
                    <!-- Skill Assessment Tab -->
                    <div class="tab-pane fade show active" id="assessment">
                        <form method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="save_skills" value="1">
                            <?php
                            $current_category = '';
                            foreach ($skills as $skill):
                                if ($current_category !== $skill['category']):
                                    if ($current_category !== '') {
                                        echo '</div></div>';
                                    }
                                    $current_category = $skill['category'];
                            ?>
                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0"><?php echo htmlspecialchars($skill['category']); ?></h5>
                                    </div>
                                    <div class="card-body">
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label class="form-label d-flex justify-content-between">
                                    <span><?php echo htmlspecialchars($skill['skill_name']); ?></span>
                                    <small class="text-muted"><?php echo htmlspecialchars($skill['description']); ?></small>
                                </label>
                                <select name="skills[<?php echo $skill['id']; ?>]" class="form-select">
                                    <option value="beginner" <?php echo (isset($user_skills[$skill['id']]) && $user_skills[$skill['id']] === 'beginner') ? 'selected' : ''; ?>>Beginner</option>
                                    <option value="intermediate" <?php echo (isset($user_skills[$skill['id']]) && $user_skills[$skill['id']] === 'intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                                    <option value="advanced" <?php echo (isset($user_skills[$skill['id']]) && $user_skills[$skill['id']] === 'advanced') ? 'selected' : ''; ?>>Advanced</option>
                                </select>
                            </div>
                            <?php endforeach; ?>
                            </div></div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-2"></i>Save Assessment
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Quizzes Tab -->
                    <div class="tab-pane fade" id="quizzes">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Available Skill Quizzes</h5>
                                <div class="row g-4">
                                    <?php foreach ($skills as $skill): ?>
                                    <div class="col-md-4">
                                        <div class="card skill-card h-100">
                                            <div class="card-body text-center">
                                                <div class="feature-icon">
                                                    <i class="bi bi-lightbulb"></i>
                                                </div>
                                                <h5 class="card-title"><?php echo htmlspecialchars($skill['skill_name']); ?></h5>
                                                <p class="card-text small text-muted"><?php echo htmlspecialchars($skill['category']); ?></p>
                                                <a href="quiz.php?skill_id=<?php echo $skill['id']; ?>" class="btn btn-outline-primary">
                                                    Take Quiz
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Portfolio Tab -->
                    <div class="tab-pane fade" id="portfolio">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="card-title mb-0">Your Portfolio</h5>
                                    <a href="add_portfolio.php" class="btn btn-primary">
                                        <i class="bi bi-plus-circle me-2"></i>Add Project
                                    </a>
                                </div>
                                <div class="row g-4">
                                    <?php foreach ($portfolio_items as $item): ?>
                                    <div class="col-md-6">
                                        <div class="card h-100">
                                            <?php if ($item['image_url']): ?>
                                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" class="card-img-top" alt="Project Preview">
                                            <?php endif; ?>
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo htmlspecialchars($item['title']); ?></h5>
                                                <p class="card-text"><?php echo htmlspecialchars($item['description']); ?></p>
                                                <?php if ($item['project_url']): ?>
                                                <a href="view_project.php"  class="btn btn-outline-primary" target="_blank">
                                                    View Project
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                            <div class="card-footer text-muted">
                                                Completed: <?php echo date('M Y', strtotime($item['completion_date'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resume Tab -->
                    <div class="tab-pane fade" id="resume">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Resume Management</h5>
                                <?php if ($resume_path): ?>
                                <div class="alert alert-success">
                                    <i class="bi bi-file-earmark-check me-2"></i>
                                    Your resume is uploaded
                                    <a href="<?php echo htmlspecialchars($resume_path); ?>" class="btn btn-sm btn-outline-success ms-3" target="_blank">
                                        View Resume
                                    </a>
                                </div>
                                <?php endif; ?>
                                
                                <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                                    <div class="mb-3">
                                        <label for="resume" class="form-label">Upload Resume (PDF or Word)</label>
                                        <input type="file" class="form-control" id="resume" name="resume" accept=".pdf,.doc,.docx" required>
                                        <div class="form-text">Maximum file size: 5MB</div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-cloud-upload me-2"></i>Upload Resume
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Past Courses Tab -->
                    <div class="tab-pane fade" id="courses">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="card-title mb-0">Past Courses & Certifications</h5>
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                                        <i class="bi bi-plus-circle me-2"></i>Add Course
                                    </button>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Course Name</th>
                                                <th>Institution</th>
                                                <th>Completion Date</th>
                                                <th>Certificate</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($user_courses as $course): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                                <td><?php echo htmlspecialchars($course['institution']); ?></td>
                                                <td><?php echo date('M Y', strtotime($course['completion_date'])); ?></td>
                                                <td>
                                                    <?php if ($course['certificate_url']): ?>
                                                    <a href="<?php echo htmlspecialchars($course['certificate_url']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        View Certificate
                                                    </a>
                                                    <?php else: ?>
                                                    <span class="badge bg-secondary">No Certificate</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Course Modal -->
    <div class="modal fade" id="addCourseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="add_course" value="1">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="course_name" class="form-label">Course Name</label>
                            <input type="text" class="form-control" id="course_name" name="course_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="institution" class="form-label">Institution</label>
                            <input type="text" class="form-control" id="institution" name="institution" required>
                        </div>
                        <div class="mb-3">
                            <label for="completion_date" class="form-label">Completion Date</label>
                            <input type="date" class="form-control" id="completion_date" name="completion_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="certificate_url" class="form-label">Certificate URL (Optional)</label>
                            <input type="url" class="form-control" id="certificate_url" name="certificate_url">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Course</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Form validation
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            var forms = document.getElementsByClassName('needs-validation');
            Array.prototype.filter.call(forms, function(form) {
                form.addEventListener('submit', function(event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        });
    })();
    </script>
</body>
</html> 