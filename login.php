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
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email)) {
        $errors[] = "Email is required";
    }
    if (empty($password)) {
        $errors[] = "Password is required";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            header("Location: dashboard.php");
            exit();
        } else {
            $errors[] = "Invalid email or password";
        }
    }
}

$page_title = 'Login';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Career Roadmap Generator</title>
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
                            <div class="col-lg-6 d-none d-lg-block bg-login-image">
                                <div class="p-5 text-white d-flex flex-column justify-content-center h-100" style="background: linear-gradient(135deg, rgba(78,115,223,0.9) 0%, rgba(26,35,126,0.9) 100%);">
                                    <h1 class="display-4 fw-bold mb-4">Welcome Back!</h1>
                                    <p class="lead">Track your career progress, set goals, and achieve your dreams with Career Roadmap Generator.</p>
                                    <div class="mt-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <i class="bi bi-check-circle-fill me-3 fs-4"></i>
                                            <span>Assess your skills</span>
                                        </div>
                                        <div class="d-flex align-items-center mb-3">
                                            <i class="bi bi-check-circle-fill me-3 fs-4"></i>
                                            <span>Set career goals</span>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-check-circle-fill me-3 fs-4"></i>
                                            <span>Track your progress</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="p-5">
                                    <div class="text-center mb-4">
                                        <h2 class="auth-logo">
                                            <i class="bi bi-compass"></i>
                                        </h2>
                                        <h1 class="h4 text-gray-900 mb-4">Login to Your Account</h1>
                                    </div>

                                    <?php if (isset($_SESSION['success'])): ?>
                                        <div class="alert alert-success fade-in">
                                            <?php 
                                            echo $_SESSION['success'];
                                            unset($_SESSION['success']);
                                            ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($errors)): ?>
                                        <div class="alert alert-danger fade-in">
                                            <ul class="mb-0">
                                                <?php foreach ($errors as $error): ?>
                                                    <li><?php echo htmlspecialchars($error); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>

                                    <form method="POST" action="" class="needs-validation user" novalidate>
                                        <div class="form-floating mb-3">
                                            <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                                            <label for="email">Email address</label>
                                            <div class="invalid-feedback">
                                                Please enter your email address.
                                            </div>
                                        </div>
                                        <div class="form-floating mb-3">
                                            <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                                            <label for="password">Password</label>
                                            <div class="invalid-feedback">
                                                Please enter your password.
                                            </div>
                                        </div>
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="rememberMe" name="remember">
                                            <label class="form-check-label" for="rememberMe">
                                                Remember me
                                            </label>
                                        </div>
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary btn-lg">
                                                <i class="bi bi-box-arrow-in-right me-2"></i> Login
                                            </button>
                                        </div>
                                    </form>
                                    <hr>
                                    <div class="text-center">
                                        <a class="small" href="forgot-password.php">Forgot Password?</a>
                                    </div>
                                    <div class="text-center">
                                        <span class="small">Don't have an account?</span>
                                        <a class="small" href="register.php">Create an Account!</a>
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
</body>
</html> 