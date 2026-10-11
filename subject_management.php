<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'Student';
$currentPage = basename($_SERVER['PHP_SELF']);

// Add Subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {
    $code = trim($_POST['subject_code']);
    $name = trim($_POST['subject_name']);
    $semester = intval($_POST['semester']);

    if (!empty($code) && !empty($name)) {
        $stmt = $conn->prepare("INSERT INTO subjects (user_id, subject_code, subject_name, semester) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issi", $user_id, $code, $name, $semester);
        $stmt->execute();
        header("Location: subject_management.php");
        exit;
    }
}

// Delete Subject
if (isset($_GET['delete'])) {
    $sub_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM subjects WHERE subject_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $sub_id, $user_id);
    $stmt->execute();
    header("Location: subject_management.php");
    exit;
}

// Fetch Subjects
$subjects = [];
$stmt = $conn->prepare("SELECT * FROM subjects WHERE user_id = ? ORDER BY semester ASC, subject_code ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) { $subjects[] = $row; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Subject Management - Smart Study Planner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { color: #38291f; background: radial-gradient(ellipse at top left, #ab805d 0%, #795239 45%, #38291f 100%); }
        .sidebar-card, .content-card { border: none; border-radius: 1.25rem; }
        .nav-link-custom { color: #786858; font-weight: 600; padding: 0.75rem 1.25rem; border-radius: 0.75rem; display: flex; align-items: center; gap: 0.75rem; text-decoration: none; }
        .nav-link-custom:hover, .nav-link-custom.active { background-color: #795239; color: #fffaf4 !important; }
        .sidebar-card, .content-card, .modal-content {
            color: #38291f;
            background-color: #fffaf4 !important;
            border-color: #dfcdbb;
        }
        .nav-link-custom:hover { background-color: #f3ebe1; color: #5e3e2b !important; }
        .nav-link-custom.active, .nav-link-custom.active:hover { background-color: #795239; color: #fffaf4 !important; }
        .text-dark { color: #38291f !important; }
        .text-primary { color: #795239 !important; }
        .text-muted { color: #786858 !important; }
        .bg-primary { background-color: #795239 !important; }
        .badge.bg-primary { color: #fffaf4; }
        .border-bottom { border-color: #dfcdbb !important; }
        hr { color: #dfcdbb; }
        .table {
            --bs-table-color: #38291f;
            --bs-table-bg: #fffaf4;
            --bs-table-border-color: #dfcdbb;
            --bs-table-hover-color: #38291f;
            --bs-table-hover-bg: #f3ebe1;
        }
        .table-light {
            --bs-table-color: #38291f;
            --bs-table-bg: #f3ebe1;
            --bs-table-border-color: #dfcdbb;
            --bs-table-hover-color: #38291f;
            --bs-table-hover-bg: #ece4d8;
        }
        .btn-primary {
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
        }
        .btn-outline-danger {
            --bs-btn-color: #874432;
            --bs-btn-border-color: #874432;
            --bs-btn-hover-color: #fffaf4;
            --bs-btn-hover-bg: #874432;
            --bs-btn-hover-border-color: #874432;
            --bs-btn-active-color: #fffaf4;
            --bs-btn-active-bg: #5e3e2b;
            --bs-btn-active-border-color: #5e3e2b;
            --bs-btn-disabled-color: #874432;
            --bs-btn-disabled-border-color: #874432;
            --bs-btn-focus-shadow-rgb: 135, 68, 50;
        }
        .modal-body label { color: #604935; }
        .form-control {
            color: #38291f;
            background-color: #fffdf9;
            border-color: #dfcdbb;
        }
        .form-control::placeholder { color: #93816f; }
        .form-control:focus {
            color: #38291f;
            background-color: #fffdf9;
            border-color: #a77950;
            box-shadow: 0 0 0 0.25rem rgba(121, 82, 57, 0.15);
        }
        .btn-close:focus { box-shadow: 0 0 0 0.25rem rgba(121, 82, 57, 0.25); }
        .modal-backdrop { --bs-backdrop-bg: #38291f; }
        ::selection { color: #38291f; background-color: #dfc3a4; }
    </style>
</head>
<body class="py-3">
<div class="container-fluid px-md-4">
    <div class="row g-4">
        <!-- 30% SIDEBAR -->
        <?php include __DIR__ . '/sidebar.php'; ?>

        <!-- 70% MAIN CONTENT -->
        <div class="col-md-8 col-lg-9">
            <div class="card content-card shadow-sm p-4 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                    <h4 class="fw-bold mb-0"><i class="bi bi-journal-plus text-primary me-2"></i>Subject Management</h4>
                    <button class="btn btn-primary rounded-pill btn-sm px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg me-1"></i> Add Subject</button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr><th>#</th><th>Code</th><th>Subject Name</th><th>Semester</th><th class="text-center">Action</th></tr>
                        </thead>
                        <tbody>
                            <?php if(empty($subjects)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">No subjects added yet.</td></tr>
                            <?php else: foreach($subjects as $i => $s): ?>
                                <tr>
                                    <td><?= $i+1 ?></td>
                                    <td><span class="badge bg-primary"><?= htmlspecialchars($s['subject_code']) ?></span></td>
                                    <td class="fw-bold"><?= htmlspecialchars($s['subject_name']) ?></td>
                                    <td>Semester <?= htmlspecialchars($s['semester']) ?></td>
                                    <td class="text-center">
                                        <a href="subject_management.php?delete=<?= $s['subject_id'] ?>" onclick="return confirm('Delete subject?')" class="btn btn-sm btn-outline-danger rounded-circle"><i class="bi bi-trash"></i></a>
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

<!-- Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content rounded-4 border-0 shadow" method="POST">
            <div class="modal-header border-bottom-0"><h5 class="modal-title fw-bold">Add New Subject</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="small fw-semibold">Subject Code</label><input type="text" name="subject_code" class="form-control" placeholder="e.g. MAT1341" required></div>
                <div class="mb-3"><label class="small fw-semibold">Subject Name</label><input type="text" name="subject_name" class="form-control" placeholder="e.g. CALCULUS" required></div>
                <div class="mb-3"><label class="small fw-semibold">Semester</label><input type="number" name="semester" class="form-control" value="1" required></div>
            </div>
            <div class="modal-footer border-top-0"><button type="submit" name="add_subject" class="btn btn-primary rounded-pill px-4 fw-bold">Save Subject</button></div>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>