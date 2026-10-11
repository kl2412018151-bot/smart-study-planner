<?php
session_start();
require_once 'config/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (!empty($full_name) && !empty($email) && !empty($password) && !empty($confirm_password)) {
        if ($password !== $confirm_password) {
            $error = "Password and confirm password do not match!";
        } else {
            $stmtCheck = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmtCheck->bind_param("s", $email);
            $stmtCheck->execute();
            $resCheck = $stmtCheck->get_result();

            if ($resCheck->num_rows > 0) {
                $error = "This email is already registered!";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmtIns = $conn->prepare(
                    "INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)"
                );
                $stmtIns->bind_param("sss", $full_name, $email, $hashed_password);

                if ($stmtIns->execute()) {
                    header("Location: login.php?registered=1");
                    exit;
                } else {
                    $error = "An error occurred during registration. Please try again.";
                }
            }
        }
    } else {
        $error = "Please fill in all fields!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Smart Study Planner</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #38291f;
            background: radial-gradient(ellipse at top left, #ab805d, #795239 45%, #38291f);
        }

        .auth-card {
            width: 100%;
            max-width: 440px;
            border: 1px solid #dfcdbb;
            border-radius: 1.25rem;
            background-color: #fffaf4;
            box-shadow: 0 20px 48px rgba(35, 23, 16, 0.28);
        }

        .brand-icon {
            width: 55px;
            height: 55px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fffaf4;
            background: linear-gradient(135deg, #986d4d, #64432e);
            border-radius: 50%;
        }

        .text-muted { color: #786858 !important; }
        .form-label { color: #604935; }
        .border-top { border-color: #dfcdbb !important; }

        .form-control {
            padding: 0.65rem 0.85rem;
            border-color: #dfcdbb;
            border-radius: 0.6rem;
            background-color: #fffdf9;
            color: #38291f;
        }

        .form-control::placeholder { color: #93816f; }

        .form-control:focus {
            color: #38291f;
            background-color: #fffdf9;
            border-color: #a77950;
            box-shadow: 0 0 0 0.25rem rgba(121, 82, 57, 0.15);
        }

        .input-group-text {
            color: #786858;
            background-color: #f3ebe1;
            border-color: #dfcdbb;
            border-radius: 0.6rem;
        }

        .btn-brown {
            --bs-btn-color: #fffaf4;
            --bs-btn-bg: #795239;
            --bs-btn-border-color: #795239;
            --bs-btn-hover-color: #fffaf4;
            --bs-btn-hover-bg: #5e3e2b;
            --bs-btn-hover-border-color: #5e3e2b;
            --bs-btn-active-color: #fffaf4;
            --bs-btn-active-bg: #513522;
            --bs-btn-active-border-color: #513522;
            --bs-btn-focus-shadow-rgb: 121, 82, 57;
            padding: 0.65rem 1.5rem;
            border-radius: 2rem;
            font-weight: 600;
        }

        .auth-link { color: #795239; text-decoration: none; }
        .auth-link:hover { color: #513522; text-decoration: underline; }

        .alert-danger {
            --bs-alert-color: #874432;
            --bs-alert-bg: #f4e2da;
            --bs-alert-border-color: #dfbdae;
        }
    </style>
</head>
<body class="py-4">

<div class="container d-flex justify-content-center">
    <div class="card auth-card p-4">
        <div class="text-center mb-4">
            <div class="brand-icon mb-2">
                <i class="bi bi-person-plus-fill fs-3"></i>
            </div>
            <h4 class="fw-bold mb-0">Create New Account</h4>
            <small class="text-muted">Smart Study Planner</small>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="mb-3">
                <label for="full_name" class="form-label small fw-semibold">Full Name</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" id="full_name" name="full_name" class="form-control"
                           placeholder="e.g. Ahmad Zaki" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label small fw-semibold">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" id="email" name="email" class="form-control"
                           placeholder="name@example.com" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label small fw-semibold">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="Create a password" required>
                </div>
            </div>

            <div class="mb-4">
                <label for="confirm_password" class="form-label small fw-semibold">Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
                    <input type="password" id="confirm_password" name="confirm_password"
                           class="form-control" placeholder="Re-enter your password" required>
                </div>
            </div>

            <button type="submit" class="btn btn-brown w-100 mb-3">
                Register <i class="bi bi-person-check-fill ms-1"></i>
            </button>
        </form>

        <div class="text-center pt-3 border-top">
            <small class="text-muted">
                Already have an account?
                <a href="login.php" class="fw-bold auth-link">Login here</a>
            </small>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>