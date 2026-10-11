<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $del_id = intval($_POST['delete_user']);

    $chk = $conn->prepare(
        "SELECT user_id FROM users
         WHERE user_id = ? AND user_id != ? AND (role != 'admin' OR role IS NULL)"
    );
    $chk->bind_param("ii", $del_id, $_SESSION['user_id']);
    $chk->execute();

    if ($chk->get_result()->num_rows === 1) {
        $conn->begin_transaction();

        try {
            $stF = $conn->prepare(
                "SELECT file_path FROM study_notes
                 WHERE user_id = ? AND file_path IS NOT NULL"
            );
            $stF->bind_param("i", $del_id);
            $stF->execute();
            $resF = $stF->get_result();

            while ($row = $resF->fetch_assoc()) {
                if (!empty($row['file_path'])
                    && strpos($row['file_path'], '..') === false
                    && is_file($row['file_path'])) {
                    unlink($row['file_path']);
                }
            }

            foreach ([
                'assessment_results', 'study_sessions', 'study_notes',
                'study_schedules', 'timetable', 'academic_tasks', 'subjects'
            ] as $table) {
                $d = $conn->prepare("DELETE FROM $table WHERE user_id = ?");
                $d->bind_param("i", $del_id);
                $d->execute();
            }

            $d = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            $d->bind_param("i", $del_id);
            $d->execute();

            $conn->commit();
            $flash = "Student account deleted successfully.";
        } catch (Throwable $e) {
            $conn->rollback();
            $flash = "Could not delete this account. Please try again.";
        }
    } else {
        $flash = "This account cannot be deleted.";
    }
}

$resStudents = $conn->query(
    "SELECT COUNT(*) as total FROM users WHERE role != 'admin' OR role IS NULL"
);
$total_students = $resStudents ? $resStudents->fetch_assoc()['total'] : 0;

$resSubjects = $conn->query("SELECT COUNT(*) as total FROM subjects");
$total_subjects = $resSubjects ? $resSubjects->fetch_assoc()['total'] : 0;

$resTasks = $conn->query("SELECT COUNT(*) as total FROM academic_tasks");
$total_tasks = $resTasks ? $resTasks->fetch_assoc()['total'] : 0;

$total_notes = 0;
$checkNotesTable = $conn->query("SHOW TABLES LIKE 'notes'");

if ($checkNotesTable && $checkNotesTable->num_rows > 0) {
    $resNotes = $conn->query("SELECT COUNT(*) as total FROM notes");
    $total_notes = $resNotes ? $resNotes->fetch_assoc()['total'] : 0;
}

$students_query = "
    SELECT u.user_id, u.full_name, u.email, u.last_login,
           (SELECT COUNT(*) FROM subjects s WHERE s.user_id = u.user_id) as subject_count,
           (SELECT COUNT(*) FROM academic_tasks t WHERE t.user_id = u.user_id) as task_count
    FROM users u
    WHERE u.role != 'admin' OR u.role IS NULL
    ORDER BY u.user_id DESC
";

$resAllStudents = $conn->query($students_query);
$students = [];

if ($resAllStudents) {
    while ($row = $resAllStudents->fetch_assoc()) {
        $students[] = $row;
    }
}

$selected_student = null;
$student_subjects = [];
$student_tasks = [];
$student_notes = [];

if (isset($_GET['monitor_id'])) {
    $m_id = intval($_GET['monitor_id']);

    $stmtS = $conn->prepare("SELECT user_id, full_name, email FROM users WHERE user_id = ?");
    $stmtS->bind_param("i", $m_id);
    $stmtS->execute();
    $selected_student = $stmtS->get_result()->fetch_assoc();

    if ($selected_student) {
        $stmtSubj = $conn->prepare("SELECT * FROM subjects WHERE user_id = ?");
        $stmtSubj->bind_param("i", $m_id);
        $stmtSubj->execute();
        $resSubj = $stmtSubj->get_result();

        while ($r = $resSubj->fetch_assoc()) {
            $student_subjects[] = $r;
        }

        $stmtTask = $conn->prepare(
            "SELECT t.*, s.subject_code
             FROM academic_tasks t LEFT JOIN subjects s ON t.subject_id = s.subject_id
             WHERE t.user_id = ? ORDER BY t.due_date ASC"
        );
        $stmtTask->bind_param("i", $m_id);
        $stmtTask->execute();
        $resTask = $stmtTask->get_result();

        while ($r = $resTask->fetch_assoc()) {
            $student_tasks[] = $r;
        }

        if ($checkNotesTable && $checkNotesTable->num_rows > 0) {
            $stmtNote = $conn->prepare("SELECT * FROM notes WHERE user_id = ?");
            $stmtNote->bind_param("i", $m_id);
            $stmtNote->execute();
            $resNote = $stmtNote->get_result();

            while ($r = $resNote->fetch_assoc()) {
                $student_notes[] = $r;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Smart Study Planner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --bs-primary-rgb: 121, 82, 57;
            --bs-secondary-rgb: 119, 100, 84;
            --bs-success-rgb: 101, 80, 60;
            --bs-warning-rgb: 217, 179, 130;
            --bs-info-rgb: 214, 193, 169;
            --bs-danger-rgb: 147, 77, 57;
            --bs-light-rgb: 243, 235, 225;
            --bs-dark-rgb: 56, 41, 31;
            --bs-border-color: #dfcdbb;
            --bs-body-color: #38291f;
            --bs-link-color: #795239;
            --bs-link-hover-color: #513522;
        }

        body {
            background: #f4ede4;
            color: #38291f;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .card { --bs-card-bg: #fffaf4; --bs-card-color: #38291f; }
        .bg-white { background-color: #fffaf4 !important; }
        .text-muted { color: #786858 !important; }
        .text-warning { color: #8a603d !important; }
        .sidebar-card, .content-card { border: none; border-radius: 1.25rem; }
        .stat-card { border: none; border-radius: 1rem; }
        .shadow-sm { box-shadow: 0 3px 14px rgba(79, 52, 34, 0.09) !important; }

        .nav-link-custom {
            color: #68513e;
            font-weight: 600;
            padding: 0.75rem 1.25rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
        }

        .nav-link-custom:hover,
        .nav-link-custom.active {
            background-color: #795239;
            color: #fffaf4 !important;
        }

        .btn-outline-primary {
            --bs-btn-color: #795239;
            --bs-btn-border-color: #a88a6d;
            --bs-btn-hover-color: #fffaf4;
            --bs-btn-hover-bg: #795239;
            --bs-btn-hover-border-color: #795239;
            --bs-btn-active-color: #fffaf4;
            --bs-btn-active-bg: #513522;
            --bs-btn-active-border-color: #513522;
            --bs-btn-focus-shadow-rgb: 121, 82, 57;
        }

        .btn-outline-danger {
            --bs-btn-color: #934d39;
            --bs-btn-border-color: #c99b86;
            --bs-btn-hover-color: #fffaf4;
            --bs-btn-hover-bg: #934d39;
            --bs-btn-hover-border-color: #934d39;
            --bs-btn-active-color: #fffaf4;
            --bs-btn-active-bg: #793e2d;
            --bs-btn-active-border-color: #793e2d;
            --bs-btn-focus-shadow-rgb: 147, 77, 57;
        }

        .btn-light {
            --bs-btn-color: #513522;
            --bs-btn-bg: #fffaf4;
            --bs-btn-border-color: #dfcdbb;
            --bs-btn-hover-bg: #eee1d3;
            --bs-btn-hover-border-color: #dfcdbb;
            --bs-btn-active-bg: #e5d3bf;
            --bs-btn-active-border-color: #dfcdbb;
        }

        .table {
            --bs-table-color: #38291f;
            --bs-table-bg: transparent;
            --bs-table-border-color: #e5d7c8;
            --bs-table-hover-bg: #f3ebe1;
            --bs-table-hover-color: #38291f;
        }

        .table-light, .table-primary {
            --bs-table-color: #513522;
            --bs-table-bg: #eee1d3;
            --bs-table-border-color: #dfcdbb;
            --bs-table-hover-bg: #e5d3bf;
            --bs-table-hover-color: #38291f;
        }

        .alert-primary, .alert-info {
            --bs-alert-color: #60432e;
            --bs-alert-bg: #eee1d3;
            --bs-alert-border-color: #dfcdbb;
        }

        .list-group {
            --bs-list-group-bg: #fffaf4;
            --bs-list-group-color: #38291f;
            --bs-list-group-border-color: #dfcdbb;
        }
    </style>
</head>
<body class="py-3">

<div class="container-fluid px-md-4">
    <div class="row g-4">
        <div class="col-md-4 col-lg-3">
            <div class="card sidebar-card shadow-sm p-3 bg-white">
                <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                    <div class="bg-primary text-white rounded-3 p-2 me-2">
                        <i class="bi bi-shield-lock-fill fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">Smart Study Planner</h6>
                        <small class="text-danger fw-semibold">Admin Panel</small>
                    </div>
                </div>

                <div class="nav flex-column gap-1">
                    <a href="admin_dashboard.php" class="nav-link-custom active">
                        <i class="bi bi-speedometer2"></i> Dashboard Overview
                    </a>
                </div>

                <hr>

                <div class="mb-3 px-2">
                    <small class="text-muted d-block">Log In As:</small>
                    <span class="fw-bold text-dark">
                        <?= htmlspecialchars($_SESSION['full_name']) ?>
                    </span>
                </div>

                <a href="logout.php" class="btn btn-outline-danger w-100 rounded-pill btn-sm fw-bold">
                    <i class="bi bi-box-arrow-right me-1"></i> Logout
                </a>
            </div>
        </div>

        <div class="col-md-8 col-lg-9">
            <div class="card content-card shadow-sm p-4 bg-white">
                <div class="mb-4 border-bottom pb-3">
                    <h4 class="fw-bold mb-0">
                        <i class="bi bi-pie-chart-fill text-primary me-2"></i>
                        Admin Student Monitoring
                    </h4>

                    <?php if ($flash): ?>
                        <div class="alert alert-info small mt-2 mb-0">
                            <?= htmlspecialchars($flash) ?>
                        </div>
                    <?php endif; ?>

                    <small class="text-muted">
                        Track student registration, enrolled subjects, tasks, and study materials
                    </small>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-sm-6 col-xl-4">
                        <div class="card stat-card bg-primary text-white p-3 shadow-sm">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-uppercase fw-bold">Students</small>
                                    <h3 class="fw-bold mb-0 mt-1"><?= $total_students ?></h3>
                                </div>
                                <i class="bi bi-people-fill fs-1 opacity-50"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-xl-4">
                        <div class="card stat-card bg-success text-white p-3 shadow-sm">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-uppercase fw-bold">Subjects</small>
                                    <h3 class="fw-bold mb-0 mt-1"><?= $total_subjects ?></h3>
                                </div>
                                <i class="bi bi-journal-bookmark-fill fs-1 opacity-50"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-xl-4">
                        <div class="card stat-card bg-warning text-dark p-3 shadow-sm">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-uppercase fw-bold">Academic Tasks</small>
                                    <h3 class="fw-bold mb-0 mt-1"><?= $total_tasks ?></h3>
                                </div>
                                <i class="bi bi-check2-square fs-1 opacity-50"></i>
                            </div>
                        </div>
                    </div>

                    

                <?php if ($selected_student): ?>
                    <div class="alert alert-primary d-flex flex-wrap gap-2 justify-content-between align-items-center rounded-3 mb-4">
                        <div>
                            <i class="bi bi-search me-2"></i>
                            Currently Monitoring:
                            <strong><?= htmlspecialchars($selected_student['full_name']) ?></strong>
                            (<?= htmlspecialchars($selected_student['email']) ?>)
                        </div>

                        <a href="admin_dashboard.php" class="btn btn-sm btn-light fw-bold rounded-pill">
                            <i class="bi bi-x-circle me-1"></i> Close Monitor View
                        </a>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="card border p-3 rounded-3 h-100 bg-light">
                                <h6 class="fw-bold text-dark mb-3">
                                    <i class="bi bi-journal-check me-2 text-primary"></i>
                                    Enrolled Subjects (<?= count($student_subjects) ?>)
                                </h6>

                                <?php if (empty($student_subjects)): ?>
                                    <p class="small text-muted mb-0">No subjects registered by this student.</p>
                                <?php else: ?>
                                    <ul class="list-group list-group-flush rounded">
                                        <?php foreach ($student_subjects as $sb): ?>
                                            <li class="list-group-item">
                                                <span class="fw-bold">
                                                    <?= htmlspecialchars($sb['subject_code']) ?>
                                                </span>
                                                -
                                                <small class="text-muted">
                                                    <?= htmlspecialchars($sb['subject_name']) ?>
                                                </small>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card border p-3 rounded-3 h-100 bg-light">
                                <h6 class="fw-bold text-dark mb-3">
                                    <i class="bi bi-list-task me-2 text-warning"></i>
                                    Tasks & Deadlines (<?= count($student_tasks) ?>)
                                </h6>

                                <?php if (empty($student_tasks)): ?>
                                    <p class="small text-muted mb-0">No academic tasks created by this student.</p>
                                <?php else: ?>
                                    <div class="table-responsive" style="max-height: 250px;">
                                        <table class="table table-sm table-borderless align-middle mb-0">
                                            <tbody>
                                            <?php foreach ($student_tasks as $tk): ?>
                                                <tr>
                                                    <td class="fw-semibold small">
                                                        <?= htmlspecialchars($tk['title']) ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary">
                                                            <?= htmlspecialchars($tk['subject_code'] ?: 'Gen') ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?= date('d M', strtotime($tk['due_date'])) ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?= $tk['status'] === 'Done' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                                            <?= htmlspecialchars($tk['status']) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <h5 class="fw-bold mb-3">
                    <i class="bi bi-people me-2"></i>
                    Registered Students Directory
                </h5>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Student ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th class="text-center">Subjects</th>
                                <th class="text-center">Tasks</th>
                                <th>Last Login</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    No registered students found.
                                </td>
                            </tr>
                        <?php else: foreach ($students as $st): ?>
                            <tr class="<?= ($selected_student && $selected_student['user_id'] == $st['user_id']) ? 'table-primary' : '' ?>">
                                <td>
                                    <span class="badge bg-light text-dark border">#<?= $st['user_id'] ?></span>
                                </td>
                                <td class="fw-bold"><?= htmlspecialchars($st['full_name']) ?></td>
                                <td><?= htmlspecialchars($st['email']) ?></td>
                                <td class="text-center">
                                    <span class="badge bg-primary rounded-pill"><?= $st['subject_count'] ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-warning text-dark rounded-pill"><?= $st['task_count'] ?></span>
                                </td>
                                <td>
                                    <?php if ($st['last_login']): ?>
                                        <small><?= date('d M Y, h:i A', strtotime($st['last_login'])) ?></small>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Never</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="admin_dashboard.php?monitor_id=<?= $st['user_id'] ?>"
                                       class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        <i class="bi bi-eye-fill me-1"></i> Monitor
                                    </a>

                                    <form method="POST" class="d-inline"
                                          onsubmit="return confirm('Permanently delete this student account and all their data? This cannot be undone.')">
                                        <button type="submit" name="delete_user" value="<?= $st['user_id'] ?>"
                                                class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                            <i class="bi bi-trash me-1"></i> Delete
                                        </button>
                                    </form>
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