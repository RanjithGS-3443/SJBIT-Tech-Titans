<?php
require_once 'config/database.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Create uploads directory if it doesn't exist
$upload_dir = 'uploads/portfolio/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Get all skills for the dropdown
$stmt = $pdo->prepare("SELECT * FROM skills ORDER BY skill_name");
$stmt->execute();
$all_skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Initialize variables with default values
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $image_url = $_POST['image_url'] ?? '';
        $project_url = $_POST['project_url'] ?? '';
        $completion_date = $_POST['completion_date'] ?? '';
        $image_path = '';
        $project_files = [];

        // Start transaction
        $pdo->beginTransaction();

        // Process project image
        if (isset($_FILES['project_image']) && $_FILES['project_image']['error'] === UPLOAD_ERR_OK) {
            $image_info = getimagesize($_FILES['project_image']['tmp_name']);
            if ($image_info !== false) {
                $image_ext = strtolower(pathinfo($_FILES['project_image']['name'], PATHINFO_EXTENSION));
                $allowed_image_types = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($image_ext, $allowed_image_types)) {
                    $image_name = uniqid() . '.' . $image_ext;
                    $image_path = $upload_dir . $image_name;
                    move_uploaded_file($_FILES['project_image']['tmp_name'], $image_path);
                }
            }
        }

        // Process project files
        if (isset($_FILES['project_files'])) {
            foreach ($_FILES['project_files']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['project_files']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_name = $_FILES['project_files']['name'][$key];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $allowed_file_types = ['pdf', 'doc', 'docx', 'zip', 'rar'];
                    
                    if (in_array($file_ext, $allowed_file_types)) {
                        $new_file_name = uniqid() . '.' . $file_ext;
                        $file_path = $upload_dir . $new_file_name;
                        move_uploaded_file($tmp_name, $file_path);
                        $project_files[] = [
                            'name' => $file_name,
                            'path' => $file_path
                        ];
                    }
                }
            }
        }

        // First, check if the image_path column exists, if not, add it
        $check_column = $pdo->query("SHOW COLUMNS FROM portfolio_items LIKE 'image_path'");
        if ($check_column->rowCount() == 0) {
            $pdo->exec("ALTER TABLE portfolio_items ADD COLUMN image_path VARCHAR(255) AFTER image_url");
        }

        // Insert portfolio item
        $stmt = $pdo->prepare("
            INSERT INTO portfolio_items (user_id, title, description, image_url, project_url, completion_date, image_path)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id,
            $title,
            $description,
            $image_url,
            $project_url,
            $completion_date,
            $image_path
        ]);

        $portfolio_id = $pdo->lastInsertId();

        // Check if portfolio_files table exists, if not, create it
        $check_table = $pdo->query("SHOW TABLES LIKE 'portfolio_files'");
        if ($check_table->rowCount() == 0) {
            $pdo->exec("
                CREATE TABLE portfolio_files (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    portfolio_id INT,
                    file_name VARCHAR(255),
                    file_path VARCHAR(255),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (portfolio_id) REFERENCES portfolio_items(id)
                )
            ");
        }

        // Insert project files
        if (!empty($project_files)) {
            $file_stmt = $pdo->prepare("
                INSERT INTO portfolio_files (portfolio_id, file_name, file_path)
                VALUES (?, ?, ?)
            ");
            foreach ($project_files as $file) {
                $file_stmt->execute([$portfolio_id, $file['name'], $file['path']]);
            }
        }

        // Insert skills
        if (!empty($_POST['skills'])) {
            $skill_stmt = $pdo->prepare("
                INSERT INTO portfolio_skills (portfolio_id, skill_id)
                VALUES (?, ?)
            ");
            foreach ($_POST['skills'] as $skill_id) {
                $skill_stmt->execute([$portfolio_id, $skill_id]);
            }
        }

        $pdo->commit();
        header("Location: portfolio.php");
        exit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = "Error adding portfolio item: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Portfolio Item - Career Roadmap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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

        .top-banner {
            background: linear-gradient(to right, #2563eb, #1d4ed8);
            padding: 2rem 0;
            margin-bottom: 3rem;
        }

        .top-banner h1 {
            color: white;
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }

        .top-banner p {
            color: rgba(255, 255, 255, 0.8);
            margin: 0;
        }

        .portfolio-form {
            background: var(--surface);
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--text);
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .form-control {
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            padding: 0.75rem;
            font-size: 0.875rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .section-title {
            color: var(--text);
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--border);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border-radius: 0.5rem;
            font-size: 0.875rem;
        }

        .btn-primary {
            background-color: var(--primary);
            border: none;
        }

        .btn-primary:hover {
            background-color: #1d4ed8;
        }

        .btn-secondary {
            background-color: var(--secondary);
            border: none;
        }

        .btn-secondary:hover {
            background-color: #475569;
        }

        .select2-container .select2-selection--multiple {
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            min-height: 42px;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 0.25rem;
            padding: 0.25rem 0.5rem;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: white;
            margin-right: 0.25rem;
        }

        .form-hint {
            color: var(--secondary);
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }

        .required::after {
            content: '*';
            color: var(--danger);
            margin-left: 0.25rem;
        }

        .alert {
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border: none;
        }

        .alert-danger {
            background-color: #fef2f2;
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        .form-section {
            background-color: #f8fafc;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .action-bar {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            justify-content: flex-end;
        }

        @media (max-width: 768px) {
            .portfolio-form {
                padding: 1.5rem;
            }

            .action-bar {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }

        .file-upload-container {
            border: 2px dashed var(--border);
            border-radius: 0.5rem;
            padding: 2rem;
            text-align: center;
            margin-bottom: 1rem;
            background-color: #f8fafc;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-upload-container:hover {
            border-color: var(--primary);
            background-color: #f1f5f9;
        }

        .file-upload-container i {
            font-size: 2rem;
            color: var(--secondary);
            margin-bottom: 1rem;
        }

        .file-upload-container p {
            margin: 0;
            color: var(--secondary);
        }

        .file-preview {
            margin-top: 1rem;
        }

        .file-preview-item {
            display: flex;
            align-items: center;
            padding: 0.5rem;
            background-color: white;
            border-radius: 0.25rem;
            margin-bottom: 0.5rem;
        }

        .file-preview-item i {
            margin-right: 0.5rem;
            color: var(--secondary);
        }

        .file-preview-item .file-name {
            flex-grow: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .file-preview-item .remove-file {
            color: var(--danger);
            cursor: pointer;
        }

        .preview-image {
            max-width: 200px;
            max-height: 200px;
            margin-top: 1rem;
            border-radius: 0.5rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="top-banner">
        <div class="container">
            <h1>Add Portfolio Project</h1>
            <p>Showcase your work and highlight your skills</p>
        </div>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="portfolio-form">
                    <form method="POST" class="needs-validation" novalidate enctype="multipart/form-data">
                        <div class="form-section">
                            <h3 class="section-title">Project Information</h3>
                            <div class="form-group">
                                <label for="title" class="form-label required">Project Title</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>

                            <div class="form-group">
                                <label for="description" class="form-label required">Project Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3 class="section-title">Project Media</h3>
                            <div class="form-group">
                                <label for="project_image" class="form-label">Project Image</label>
                                <div class="file-upload-container" onclick="document.getElementById('project_image').click()">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p>Click to upload project image</p>
                                    <p class="small">(JPG, PNG, GIF - Max 5MB)</p>
                                </div>
                                <input type="file" id="project_image" name="project_image" accept="image/*" class="d-none" onchange="previewImage(this)">
                                <div id="imagePreview" class="text-center"></div>
                            </div>

                            <div class="form-group">
                                <label for="project_files" class="form-label">Project Files</label>
                                <div class="file-upload-container" onclick="document.getElementById('project_files').click()">
                                    <i class="fas fa-file-upload"></i>
                                    <p>Click to upload project files</p>
                                    <p class="small">(PDF, DOC, ZIP - Max 10MB each)</p>
                                </div>
                                <input type="file" id="project_files" name="project_files[]" multiple class="d-none" onchange="previewFiles(this)">
                                <div id="filePreview" class="file-preview"></div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3 class="section-title">Project Links</h3>
                            <div class="form-group">
                                <label for="project_url" class="form-label">Project URL</label>
                                <input type="url" class="form-control" id="project_url" name="project_url">
                                <div class="form-hint">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Link to your live project or repository
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3 class="section-title">Additional Details</h3>
                            <div class="form-group">
                                <label for="completion_date" class="form-label required">Completion Date</label>
                                <input type="date" class="form-control" id="completion_date" name="completion_date" required>
                            </div>

                            <div class="form-group">
                                <label for="skills" class="form-label required">Skills Used</label>
                                <select class="form-control" id="skills" name="skills[]" multiple required>
                                    <?php foreach ($all_skills as $skill): ?>
                                        <option value="<?php echo $skill['id']; ?>">
                                            <?php echo htmlspecialchars($skill['skill_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="action-bar">
                            <a href="portfolio.php" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus-circle me-2"></i>Add Project
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#skills').select2({
                theme: 'classic',
                placeholder: 'Select the skills used in this project',
                allowClear: true
            });

            // Form validation
            var forms = document.querySelectorAll('.needs-validation');
            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        });

        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'preview-image';
                    preview.appendChild(img);
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function previewFiles(input) {
            const preview = document.getElementById('filePreview');
            preview.innerHTML = '';
            
            if (input.files) {
                Array.from(input.files).forEach(file => {
                    const div = document.createElement('div');
                    div.className = 'file-preview-item';
                    
                    const icon = document.createElement('i');
                    icon.className = getFileIcon(file.name);
                    
                    const name = document.createElement('span');
                    name.className = 'file-name';
                    name.textContent = file.name;
                    
                    const remove = document.createElement('i');
                    remove.className = 'fas fa-times remove-file';
                    remove.onclick = function() {
                        div.remove();
                    };
                    
                    div.appendChild(icon);
                    div.appendChild(name);
                    div.appendChild(remove);
                    preview.appendChild(div);
                });
            }
        }

        function getFileIcon(filename) {
            const ext = filename.split('.').pop().toLowerCase();
            switch(ext) {
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
    </script>
</body>
</html> 