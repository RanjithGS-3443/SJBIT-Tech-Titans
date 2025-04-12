<?php
require_once 'config/database.php';
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Handle profile photo upload
    $profile_photo = 'assets/img/default-profile.png'; // Default value
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_photo'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        // Validate file type
        if (!in_array($file['type'], $allowed_types)) {
            $errors[] = "Invalid file type. Only JPG, PNG and GIF images are allowed.";
        }
        // Validate file size
        elseif ($file['size'] > $max_size) {
            $errors[] = "File size too large. Maximum size allowed is 5MB.";
        }
        else {
            $upload_dir = 'uploads/profile_photos/';
            if (!file_exists($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    $errors[] = "Failed to create upload directory. Please contact administrator.";
                }
            }
            
            if (empty($errors)) {
                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $file_name = uniqid('profile_') . '.' . $file_extension;
                $target_path = $upload_dir . $file_name;
                
                // Additional security check for file type using getimagesize
                $image_info = getimagesize($file['tmp_name']);
                if ($image_info === false) {
                    $errors[] = "Invalid image file.";
                }
                else {
                    if (move_uploaded_file($file['tmp_name'], $target_path)) {
                        $profile_photo = $target_path;
                    } else {
                        $errors[] = "Failed to upload profile photo. Please try again.";
                    }
                }
            }
        }
    }

    // Validate other fields
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    // Check if email already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = "Email already exists";
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, profile_photo) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hashed_password, $profile_photo]);
            
            $pdo->commit();
            $_SESSION['success'] = "Registration successful! Please login.";
            header("Location: login.php");
            exit();
        } catch(PDOException $e) {
            $pdo->rollBack();
            error_log("Registration error: " . $e->getMessage());
            $errors[] = "Registration failed. Please try again.";
        }
    }
}

$page_title = 'Register';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Career Roadmap Generator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Custom styles for form fields */
        .form-floating {
            position: relative;
            margin-bottom: 1rem;
        }
        
        .form-floating input {
            height: calc(3.5rem + 2px);
            padding: 1rem 0.75rem;
        }
        
        .form-floating label {
            position: absolute;
            top: 0;
            left: 0;
            padding: 1rem 0.75rem;
            pointer-events: none;
            transform-origin: 0 0;
            transition: opacity .1s ease-in-out,transform .1s ease-in-out;
        }
        
        .form-floating > .form-control:focus,
        .form-floating > .form-control:not(:placeholder-shown) {
            padding-top: 1.625rem;
            padding-bottom: 0.625rem;
        }
        
        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            opacity: .65;
            transform: scale(.85) translateY(-0.5rem) translateX(0.15rem);
        }
        
        /* Ensure proper z-index */
        .form-floating input,
        .form-floating label {
            z-index: auto;
        }
        
        /* Ensure proper background */
        .form-control {
            background-color: #fff !important;
        }
        
        /* Ensure proper text color */
        .form-control,
        .form-floating label {
            color: #000 !important;
        }
        
        /* Remove animations that might cause issues */
        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            transition: all 0.2s ease-in-out;
        }
        
        /* Profile upload styles */
        .profile-upload {
            width: 150px;
            margin: 0 auto 20px;
        }
        
        .upload-container {
            position: relative;
            width: 150px;
            height: 150px;
            border: 3px dashed rgba(0,0,0,0.1);
            border-radius: 50%;
            cursor: pointer;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .upload-container:hover {
            border-color: #4e73df;
        }
        
        .preview-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }
        
        .upload-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .upload-overlay i {
            color: white;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .upload-text {
            color: white;
            font-size: 0.875rem;
        }
        
        .upload-container:hover .upload-overlay {
            opacity: 1;
        }
    </style>
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts - Nunito -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-gradient-primary">
    <div class="container">
        <div class="row justify-content-center min-vh-100 align-items-center">
            <div class="col-xl-10 col-lg-12 col-md-9">
                <div class="card auth-card o-hidden border-0 shadow-lg slide-in">
                    <div class="card-body p-0">
                        <div class="row">
                            <div class="col-lg-5 d-none d-lg-block bg-register-image">
                                <div class="p-5 text-white d-flex flex-column justify-content-center h-100" style="background: linear-gradient(135deg, rgba(28,200,138,0.9) 0%, rgba(23,166,115,0.9) 100%);">
                                    <h1 class="display-4 fw-bold mb-4">Start Your Journey!</h1>
                                    <p class="lead">Create your account and begin your path to career success with Career Roadmap Generator.</p>
                                    <div class="mt-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <i class="bi bi-check-circle-fill me-3 fs-4"></i>
                                            <span>Free account creation</span>
                                        </div>
                                        <div class="d-flex align-items-center mb-3">
                                            <i class="bi bi-check-circle-fill me-3 fs-4"></i>
                                            <span>Personalized roadmap</span>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-check-circle-fill me-3 fs-4"></i>
                                            <span>Expert resources</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-7">
                                <div class="p-5">
                                    <div class="text-center mb-4">
                                        <h2 class="auth-logo">
                                            <i class="bi bi-compass"></i>
                                        </h2>
                                        <h1 class="h4 text-gray-900 mb-4">Create an Account</h1>
                                    </div>

                                    <?php if (!empty($errors)): ?>
                                        <div class="alert alert-danger fade-in">
                                            <ul class="mb-0">
                                                <?php foreach ($errors as $error): ?>
                                                    <li><?php echo htmlspecialchars($error); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>

                                    <form method="POST" action="" class="needs-validation user" novalidate enctype="multipart/form-data">
                                        <!-- Profile Photo Upload -->
                                        <div class="profile-upload mb-4">
                                            <div class="upload-container" onclick="document.getElementById('profile-photo').click();">
                                                <img src="assets/img/default-profile.png" alt="Profile Preview" id="profile-preview" class="preview-image">
                                                <div class="upload-overlay">
                                                    <i class="bi bi-camera-fill"></i>
                                                    <span class="upload-text">Choose Photo</span>
                                                </div>
                                            </div>
                                            <input type="file" name="profile_photo" id="profile-photo" accept="image/*" style="display: none;">
                                            <div class="text-center mt-2">
                                                <small class="text-muted">Click to upload profile photo (Optional)</small>
                                                <div class="invalid-feedback">
                                                    Please select a valid image file (JPG, PNG, or GIF).
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-12 mb-3">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="name" name="name" placeholder="Your Name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                                                    <label for="name">Full Name</label>
                                                    <div class="invalid-feedback">
                                                        Please enter your name.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 mb-3">
                                                <div class="form-floating">
                                                    <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                                                    <label for="email">Email address</label>
                                                    <div class="invalid-feedback">
                                                        Please enter a valid email address.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <div class="form-floating mb-3">
                                                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required minlength="8">
                                                    <label for="password">Password</label>
                                                    <div class="invalid-feedback">
                                                        Password must be at least 8 characters long.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="form-floating mb-3">
                                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                                                    <label for="confirm_password">Confirm Password</label>
                                                    <div class="invalid-feedback">
                                                        Please confirm your password.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                                            <label class="form-check-label" for="terms">
                                                I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                                            </label>
                                            <div class="invalid-feedback">
                                                You must agree to the terms and conditions.
                                            </div>
                                        </div>

                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-primary btn-lg">
                                                <i class="bi bi-person-plus me-2"></i> Create Account
                                            </button>
                                        </div>
                                    </form>

                                    <hr>
                                    <div class="text-center">
                                        <span class="small">Already have an account?</span>
                                        <a class="small" href="login.php">Login here!</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="js/main.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const profileUpload = document.querySelector('.upload-container');
        const profileInput = document.getElementById('profile-photo');
        const profilePreview = document.getElementById('profile-preview');
        const form = document.querySelector('form');
        
        // Handle file input change
        profileInput.addEventListener('change', function(e) {
            const file = this.files[0];
            if (file) {
                handleFile(file);
            }
        });
        
        // Handle click on the upload container
        profileUpload.addEventListener('click', function(e) {
            profileInput.click();
        });
        
        function handleFile(file) {
            // Check if file is selected
            if (!file) {
                return;
            }
            
            // Check if file is an image
            if (!file.type.match('image.*')) {
                alert('Please select an image file (JPG, PNG, or GIF)');
                profileInput.value = '';
                return;
            }
            
            // Check file size (max 5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('Image file size must be less than 5MB');
                profileInput.value = '';
                return;
            }
            
            // Create FileReader to preview image
            const reader = new FileReader();
            reader.onload = function(e) {
                profilePreview.src = e.target.result;
            };
            reader.onerror = function() {
                alert('Error reading file');
                profileInput.value = '';
            };
            reader.readAsDataURL(file);
        }
        
        // Form validation
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
        
        // Password validation
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        
        function validatePassword() {
            if (password.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Passwords do not match');
            } else {
                confirmPassword.setCustomValidity('');
            }
        }
        
        password.addEventListener('change', validatePassword);
        confirmPassword.addEventListener('keyup', validatePassword);
    });
    </script>
</body>
</html> 