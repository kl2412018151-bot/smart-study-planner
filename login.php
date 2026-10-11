<?php
session_start();
require_once 'config/db.php';

if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        $stmt = $conn->prepare(
            "SELECT user_id, full_name, password, role
             FROM users WHERE email = ?"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['full_name'] = $row['full_name'];
                $_SESSION['role'] = $row['role'];

                // last login
                $stmtLogin = $conn->prepare(
                    "UPDATE users SET last_login = NOW() WHERE user_id = ?"
                );
                $stmtLogin->bind_param("i", $row['user_id']);
                $stmtLogin->execute();

                if ($row['role'] === 'admin') {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit;
            } else {
                $error = "Invalid Password!";
            }
        } else {
            $error = "Email not found!";
        }
    } else {
        $error = "Please fill out this field!";
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Log In - Smart Study Planner</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"
        rel="stylesheet"
    >

    <style>
        :root {
            --brown-dark: #38291f;
            --brown-main: #795239;
            --brown-hover: #5e3e2b;
            --brown-muted: #786858;
            --cream: #fffaf4;
            --cream-soft: #f3ebe1;
            --brown-border: #dfcdbb;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--brown-dark);
            background: radial-gradient(
                ellipse at top left,
                #ab805d 0%,
                #795239 45%,
                #38291f 100%
            );
        }

        .auth-card {
            width: 100%;
            max-width: 420px;
            border: 1px solid rgba(223, 205, 187, 0.7);
            border-radius: 1.25rem;
            background-color: var(--cream);
            box-shadow: 0 20px 48px rgba(35, 23, 16, 0.28);
        }

        .brand-icon {
            width: 55px;
            height: 55px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--cream);
            background: linear-gradient(135deg, #986d4d, #64432e);
            border-radius: 50%;
            box-shadow: 0 5px 14px rgba(121, 82, 57, 0.2);
        }

        .text-dark {
            color: var(--brown-dark) !important;
        }

        .text-muted {
            color: var(--brown-muted) !important;
        }

        .form-label {
            color: #604935;
        }

        .form-control {
            padding: 0.65rem 0.85rem;
            border-color: var(--brown-border);
            border-radius: 0.6rem;
            background-color: #fffdf9;
            color: var(--brown-dark);
        }

        .form-control::placeholder {
            color: #93816f;
        }

        .form-control:focus {
            color: var(--brown-dark);
            background-color: #fffdf9;
            border-color: #a77950;
            box-shadow: 0 0 0 0.25rem rgba(121, 82, 57, 0.15);
        }

        .input-group-text {
            color: var(--brown-muted);
            background-color: var(--cream-soft);
            border-color: var(--brown-border);
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
            --bs-btn-active-bg: #4d3223;
            --bs-btn-active-border-color: #4d3223;
            --bs-btn-disabled-color: #fffaf4;
            --bs-btn-disabled-bg: #795239;
            --bs-btn-disabled-border-color: #795239;
            --bs-btn-focus-shadow-rgb: 121, 82, 57;

            padding: 0.65rem 1.5rem;
            border-radius: 2rem;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(121, 82, 57, 0.16);
        }

        .register-link {
            color: var(--brown-main);
            text-decoration: none;
        }

        .register-link:hover {
            color: var(--brown-hover);
            text-decoration: underline;
        }

        .border-top {
            border-color: var(--brown-border) !important;
        }

        .alert-success {
            --bs-alert-color: #594632;
            --bs-alert-bg: #ece4d8;
            --bs-alert-border-color: #d8c8b4;
        }

        .alert-danger {
            --bs-alert-color: #874432;
            --bs-alert-bg: #f4e2da;
            --bs-alert-border-color: #dfbdae;
        }

        a:focus-visible {
            outline: 3px solid #a77950;
            outline-offset: 3px;
            border-radius: 3px;
        }

        ::selection {
            color: var(--brown-dark);
            background-color: #dfc3a4;
        }
    </style>
</head>

<body class="py-4">

<div class="container d-flex justify-content-center">
    <div class="card auth-card p-4">

        <!-- Header Branding -->
        <div class="text-center mb-4">
            <img
    src="assets/image/logo.png"
    alt="Smart Study Planner Logo"
    style="width: 42px; height: 42px; object-fit: contain; flex-shrink: 0;"
>

            <h4 class="fw-bold text-dark mb-0">Log In</h4>
            <small class="text-muted">Smart Study Planner</small>
        </div>

        <?php if (isset($_GET['registered']) && $_GET['registered'] == 1): ?>
            <div
                class="alert alert-success alert-dismissible fade show small"
                role="alert"
            >
                <i class="bi bi-check-circle-fill me-1"></i>
                Registration successful ! Please log in.

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Close"
                ></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div
                class="alert alert-danger alert-dismissible fade show small"
                role="alert"
            >
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                <?= htmlspecialchars($error) ?>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Close"
                ></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="mb-3">
                <label
                    for="email"
                    class="form-label small fw-semibold"
                >
                    Email Address
                </label>

                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-envelope"></i>
                    </span>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="name@example.com"
                        required
                    >
                </div>
            </div>

            <div class="mb-4">
                <label
                    for="password"
                    class="form-label small fw-semibold"
                >
                    Password
                </label>

                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-lock"></i>
                    </span>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="••••••••"
                        required
                    >
                </div>
            </div>

            <button
                type="submit"
                class="btn btn-brown w-100 mb-3"
            >
                Login
                <i class="bi bi-arrow-right-short ms-1"></i>
            </button>
        </form>

        <!-- Link ke Register -->
        <div class="text-center pt-3 border-top">
            <small class="text-muted">
                Don't have an account?
                <a href="register.php" class="fw-bold register-link">
                    Register here
                </a>
            </small>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>