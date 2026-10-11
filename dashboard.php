<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id']) || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin')) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'BALQIS';
$user_name = $full_name;

$stmtSubj = $conn->prepare("SELECT COUNT(*) as total FROM subjects WHERE user_id = ?");
$stmtSubj->bind_param("i", $user_id);
$stmtSubj->execute();
$total_subjects = $stmtSubj->get_result()->fetch_assoc()['total'] ?? 0;

$stmtPending = $conn->prepare(
    "SELECT COUNT(*) as total FROM academic_tasks
     WHERE user_id = ? AND (status = 'Pending' OR status != 'Done')"
);
$stmtPending->bind_param("i", $user_id);
$stmtPending->execute();
$total_pending_tasks = $stmtPending->get_result()->fetch_assoc()['total'] ?? 0;

$stmtDone = $conn->prepare(
    "SELECT COUNT(*) as total FROM academic_tasks WHERE user_id = ? AND status = 'Done'"
);
$stmtDone->bind_param("i", $user_id);
$stmtDone->execute();
$total_completed_tasks = $stmtDone->get_result()->fetch_assoc()['total'] ?? 0;

$recent_tasks = [];
$stmtTasks = $conn->prepare(
    "SELECT t.*, s.subject_code
     FROM academic_tasks t
     LEFT JOIN subjects s ON t.subject_id = s.subject_id
     WHERE t.user_id = ? AND t.status != 'Done'
     ORDER BY t.due_date ASC, t.due_time ASC LIMIT 5"
);
$stmtTasks->bind_param("i", $user_id);
$stmtTasks->execute();
$resTasks = $stmtTasks->get_result();

while ($r = $resTasks->fetch_assoc()) {
    $recent_tasks[] = $r;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Smart Study Planner System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
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
            color: var(--brown-dark);
            background: radial-gradient(ellipse at top left, #ab805d 0%, #795239 45%, #38291f 100%);
        }

        .content-card {
            color: var(--brown-dark);
            background-color: var(--cream) !important;
            border-color: var(--brown-border);
        }

        .content-card .text-muted { color: var(--brown-muted) !important; }
        .content-card .text-dark { color: var(--brown-dark) !important; }
        .content-card .text-white { color: var(--cream) !important; }
        .content-card .text-white-50 { color: #f3ebe1 !important; }
        .content-card .text-danger { color: #874432 !important; }
        .content-card .border,
        .content-card .border-bottom { border-color: var(--brown-border) !important; }

        .content-card .stat-card { border-color: var(--brown-border); }
        .content-card .stat-card.bg-primary { background-color: var(--brown-main) !important; }
        .content-card .stat-card.bg-warning { background-color: #dfc3a4 !important; }
        .content-card .stat-card.bg-success { background-color: var(--brown-hover) !important; }

        .content-card .badge.bg-light { background-color: var(--cream-soft) !important; }
        .content-card .badge.bg-info { background-color: #dfc3a4 !important; }

        .content-card .table {
            --bs-table-color: var(--brown-dark);
            --bs-table-bg: var(--cream);
            --bs-table-border-color: var(--brown-border);
            --bs-table-hover-color: var(--brown-dark);
            --bs-table-hover-bg: var(--cream-soft);
        }

        .content-card .table-light {
            --bs-table-color: var(--brown-dark);
            --bs-table-bg: var(--cream-soft);
            --bs-table-border-color: var(--brown-border);
            --bs-table-hover-color: var(--brown-dark);
            --bs-table-hover-bg: #ece4d8;
        }

        .content-card .btn-outline-primary {
            --bs-btn-color: var(--brown-main);
            --bs-btn-border-color: var(--brown-main);
            --bs-btn-hover-color: var(--cream);
            --bs-btn-hover-bg: var(--brown-main);
            --bs-btn-hover-border-color: var(--brown-main);
            --bs-btn-active-color: var(--cream);
            --bs-btn-active-bg: var(--brown-hover);
            --bs-btn-active-border-color: var(--brown-hover);
            --bs-btn-disabled-color: var(--brown-main);
            --bs-btn-disabled-border-color: var(--brown-main);
            --bs-btn-focus-shadow-rgb: 121, 82, 57;
        }

        .content-card .btn-success {
            --bs-btn-color: var(--cream);
            --bs-btn-bg: var(--brown-main);
            --bs-btn-border-color: var(--brown-main);
            --bs-btn-hover-color: var(--cream);
            --bs-btn-hover-bg: var(--brown-hover);
            --bs-btn-hover-border-color: var(--brown-hover);
            --bs-btn-active-color: var(--cream);
            --bs-btn-active-bg: #4d3223;
            --bs-btn-active-border-color: #4d3223;
            --bs-btn-disabled-color: var(--cream);
            --bs-btn-disabled-bg: var(--brown-main);
            --bs-btn-disabled-border-color: var(--brown-main);
            --bs-btn-focus-shadow-rgb: 121, 82, 57;
        }

        .content-card ::selection {
            color: var(--brown-dark);
            background-color: #dfc3a4;
        }
    </style>
</head>
<body class="py-3">

<div class="container-fluid px-md-4">
    <div class="row g-4">
        <?php include __DIR__ . '/sidebar.php'; ?>

        <div class="col-md-8 col-lg-9">
            <div class="card content-card shadow-sm p-4 bg-white">
                <div class="mb-4 border-bottom pb-3">
                    <h4 class="fw-bold mb-0">
                        Welcome back, <?= htmlspecialchars($user_name) ?>! 👋
                    </h4>
                    <small class="text-muted">
                        Here is an overview of your academic study planner today.
                    </small>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card stat-card bg-primary text-white p-3 shadow-sm">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-white-50 text-uppercase fw-bold">Enrolled Subjects</small>
                                    <h3 class="fw-bold mb-0 mt-1"><?= $total_subjects ?></h3>
                                </div>
                                <i class="bi bi-journal-text fs-1 opacity-50"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card stat-card bg-warning text-dark p-3 shadow-sm">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-uppercase fw-bold">Pending Tasks</small>
                                    <h3 class="fw-bold mb-0 mt-1"><?= $total_pending_tasks ?></h3>
                                </div>
                                <i class="bi bi-clock-history fs-1 opacity-50"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card stat-card bg-success text-white p-3 shadow-sm">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-white-50 text-uppercase fw-bold">Completed Tasks</small>
                                    <h3 class="fw-bold mb-0 mt-1"><?= $total_completed_tasks ?></h3>
                                </div>
                                <i class="bi bi-check-circle-fill fs-1 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-exclamation-circle text-danger me-2"></i>
                        Upcoming Priority Deadlines
                    </h5>
                    <a href="task_management.php"
                       class="btn btn-sm btn-outline-primary rounded-pill fw-bold">
                        View All Tasks
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Subject</th>
                                <th>Task Title</th>
                                <th>Due Date</th>
                                <th>Type</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($recent_tasks)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="bi bi-emoji-smile fs-3 d-block mb-1"></i>
                                    No upcoming pending tasks!
                                </td>
                            </tr>
                        <?php else: foreach ($recent_tasks as $task): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= htmlspecialchars($task['subject_code'] ?: 'General') ?>
                                    </span>
                                </td>
                                <td class="fw-bold"><?= htmlspecialchars($task['title']) ?></td>
                                <td>
                                    <small>
                                        <i class="bi bi-calendar me-1"></i>
                                        <?= date('d M Y', strtotime($task['due_date'])) ?>
                                    </small><br>
                                    <small class="text-muted">
                                        <i class="bi bi-clock me-1"></i>
                                        <?= date('h:i A', strtotime($task['due_time'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-info text-dark">
                                        <?= htmlspecialchars($task['task_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="task_management.php"
                                       class="btn btn-sm btn-success rounded-pill px-3">
                                        Manage
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>