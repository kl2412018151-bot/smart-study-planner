<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Smart Study Planner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --bs-primary-rgb: 121, 82, 57;
            --bs-dark-rgb: 56, 41, 31;
            --bs-border-color: #dfcdbb;
        }

        body {
            background-color: #f4ede4;
            color: #38291f;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .card { border: none; border-radius: 1rem; background-color: #fffaf4; }
        .bg-white { background-color: #fffaf4 !important; }
        .text-muted { color: #786858 !important; }
        .shadow-sm { box-shadow: 0 3px 14px rgba(79, 52, 34, 0.09) !important; }

        .hero {
            background: linear-gradient(125deg, #795239, #513522);
            color: #fffaf4;
        }

        .tech-badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            border-radius: 50rem;
            background-color: #eee1d3;
            color: #68472f;
            border: 1px solid #cfb69b;
        }

        .tech-badge:nth-child(2) { background-color: #f2e8dc; }
        .tech-badge:nth-child(3) { background-color: #ead4b9; }
        .tech-badge:nth-child(4) { background-color: #e5dace; }
        .tech-badge:nth-child(5) { background-color: #ded0bf; }

        .btn-light {
            --bs-btn-color: #513522;
            --bs-btn-bg: #fffaf4;
            --bs-btn-border-color: #fffaf4;
            --bs-btn-hover-color: #38291f;
            --bs-btn-hover-bg: #eee1d3;
            --bs-btn-hover-border-color: #eee1d3;
            --bs-btn-active-bg: #dfcdbb;
            --bs-btn-active-border-color: #dfcdbb;
        }

        .btn-outline-light {
            --bs-btn-color: #fffaf4;
            --bs-btn-border-color: #ead8c4;
            --bs-btn-hover-color: #513522;
            --bs-btn-hover-bg: #fffaf4;
            --bs-btn-hover-border-color: #fffaf4;
            --bs-btn-active-color: #513522;
            --bs-btn-active-bg: #eee1d3;
            --bs-btn-active-border-color: #eee1d3;
        }
    </style>
</head>
<body class="py-3">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm mb-4">
    <div class="container gap-2">
        <a class="navbar-brand fw-bold" href="dashboard.php">
            <i class="bi bi-journal-bookmark-fill me-2"></i>Smart Study Planner
        </a>
        <div class="d-flex align-items-center gap-2">
            <a href="dashboard.php" class="btn btn-light btn-sm rounded-pill px-3 fw-semibold">
                <i class="bi bi-arrow-left me-1"></i> Dashboard
            </a>
            <a href="logout.php" class="btn btn-outline-light btn-sm rounded-pill px-3">Logout</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-md-4">
    <div class="row g-4">

        <?php include __DIR__ . '/sidebar.php'; ?>

        <div class="col-md-8 col-lg-9">
            <div class="container my-4" style="max-width: 900px;">
    <div class="card hero p-4 p-md-5 shadow-sm mb-4 rounded-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <span class="badge bg-white text-primary rounded-pill px-3 py-2 fw-bold mb-3">
                    Final Year Project (FYP)
                </span>
                <h2 class="fw-bold display-6 mb-2">Smart Study Planner</h2>
                <p class="lead mb-0">
                    A Web-Based Study Management & Smart Learning System for Students.
                </p>
            </div>
            <div class="col-md-4 text-center d-none d-md-block">
                <i class="bi bi-laptop display-1 opacity-75"></i>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card p-4 shadow-sm h-100 bg-white">
                <h5 class="fw-bold text-dark mb-3">
                    <i class="bi bi-bullseye text-primary me-2"></i>Project Objective
                </h5>
                <p class="text-muted small lh-lg mb-0">
                    <strong>Smart Study Planner</strong> was developed to help students
                    manage their time, tasks, and study schedules systematically.
                    The system combines various academic management features in one
                    centralized platform to reduce academic stress and improve student productivity.
                </p>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card p-4 shadow-sm h-100 bg-white">
                <h5 class="fw-bold text-dark mb-3">
                    <i class="bi bi-person-workspace text-primary me-2"></i>Developer Profile
                </h5>
                <ul class="list-unstyled mb-0 text-muted small lh-lg">
                    <li class="mb-2"><strong>Student Name:</strong> MUHAMMAD RIDUAN BIN JAFRI</li>
                    <li class="mb-2"><strong>Matric No.:</strong> AM2412018151</li>
                    <li class="mb-2"><strong>Program:</strong> FYP 3024 (FCOM)</li>
                    <li class="mb-2"><strong>Supervisor:</strong> MADAM NORFAZLINA BINTI JOHAR</li>
                    <li><strong>Institution:</strong> UNIVERSITY POLY-TECH MALAYSIA (UPTM)</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="card p-4 shadow-sm bg-white mb-4">
        <h5 class="fw-bold text-dark mb-3">
            <i class="bi bi-code-slash text-primary me-2"></i>
            Development Technologies (Tech Stack)
        </h5>
        <div class="d-flex flex-wrap gap-2">
            <span class="badge tech-badge"><i class="bi bi-filetype-php me-1"></i> PHP</span>
            <span class="badge tech-badge"><i class="bi bi-database me-1"></i> MySQL Database</span>
            <span class="badge tech-badge"><i class="bi bi-bootstrap me-1"></i> Bootstrap</span>
            <span class="badge tech-badge"><i class="bi bi-filetype-html me-1"></i> HTML & CSS</span>
            <span class="badge tech-badge"><i class="bi bi-filetype-js me-1"></i> JavaScript</span>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>