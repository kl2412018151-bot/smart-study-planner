<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$user_id = $_SESSION['user_id'];

if (empty($_SESSION['assessment_csrf'])) {
    $_SESSION['assessment_csrf'] = bin2hex(random_bytes(32));
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['delete_result'])
) {
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['assessment_csrf'], $token)
    ) {
        http_response_code(403);
        exit('Invalid request. Please reload the page and try again.');
    }

    $result_id = intval($_POST['delete_result']);

    $stmtDelete = $conn->prepare(
        "DELETE FROM assessment_results
         WHERE result_id = ? AND user_id = ?"
    );

    $stmtDelete->bind_param("ii", $result_id, $user_id);
    $stmtDelete->execute();

    header("Location: academic_progress.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_result'])) {
    $subject_id = intval($_POST['subject_id']);
    $type = $_POST['assessment_type'];
    $marks = floatval($_POST['marks_obtained']);
    $full = floatval($_POST['full_marks']);

    $stmt = $conn->prepare("INSERT INTO assessment_results (user_id, subject_id, assessment_type, marks_obtained, full_marks) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisdd", $user_id, $subject_id, $type, $marks, $full);
    $stmt->execute();
    header("Location: academic_progress.php");
    exit;
}

// Fetch Results
$results = [];
$stmt = $conn->prepare("SELECT r.*, s.subject_code, s.subject_name FROM assessment_results r JOIN subjects s ON r.subject_id = s.subject_id WHERE r.user_id = ? ORDER BY r.result_id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) { $results[] = $r; }

$subjects = [];
$stmtS = $conn->prepare("SELECT subject_id, subject_code, subject_name FROM subjects WHERE user_id = ?");
$stmtS->bind_param("i", $user_id);
$stmtS->execute();
$resS = $stmtS->get_result();
while ($r = $resS->fetch_assoc()) { $subjects[] = $r; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Academic Progress - Smart Study Planner</title>
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
        .form-control, .form-select {
            color: #38291f;
            background-color: #fffdf9;
            border-color: #dfcdbb;
        }
        .form-control::placeholder { color: #93816f; }
        .form-control:focus, .form-select:focus {
            color: #38291f;
            background-color: #fffdf9;
            border-color: #a77950;
            box-shadow: 0 0 0 0.25rem rgba(121, 82, 57, 0.15);
        }
        .btn-close:focus { box-shadow: 0 0 0 0.25rem rgba(121, 82, 57, 0.25); }
        .modal-backdrop { --bs-backdrop-bg: #38291f; }
        ::selection { color: #38291f; background-color: #dfc3a4; }
        .bg-light { background-color: #f3ebe1 !important; }
        .bg-secondary { background-color: #786858 !important; }
        .badge.bg-secondary { color: #fffaf4; }
        .text-success { color: #5e3e2b !important; }
        .text-danger { color: #874432 !important; }
        .border { border-color: #dfcdbb !important; }
        .content-card .card {
            color: #38291f;
            background-color: #fffdf9;
            border-color: #dfcdbb;
        }
        .form-control::file-selector-button {
            color: #604935;
            background-color: #f3ebe1;
            border-color: #dfcdbb;
        }
        .form-control:hover:not(:disabled):not([readonly])::file-selector-button {
            background-color: #ece4d8;
        }
        .form-control:disabled, .form-select:disabled {
            color: #786858;
            background-color: #f3ebe1;
        }
        .btn-outline-primary {
            --bs-btn-color: #795239;
            --bs-btn-border-color: #795239;
            --bs-btn-hover-color: #fffaf4;
            --bs-btn-hover-bg: #795239;
            --bs-btn-hover-border-color: #795239;
            --bs-btn-active-color: #fffaf4;
            --bs-btn-active-bg: #5e3e2b;
            --bs-btn-active-border-color: #5e3e2b;
            --bs-btn-disabled-color: #795239;
            --bs-btn-disabled-border-color: #795239;
            --bs-btn-focus-shadow-rgb: 121, 82, 57;
        }
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
                    <h4 class="fw-bold mb-0"><i class="bi bi-graph-up-arrow text-primary me-2"></i>Academic Assessment Results</h4>
                    <button class="btn btn-primary rounded-pill btn-sm px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg me-1"></i> Add Result</button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
    <th>#</th>
    <th>Subject</th>
    <th>Type</th>
    <th>Score</th>
    <th>Percentage</th>
    <th class="text-center">Action</th>
</tr>
                        </thead>
                        <tbody>
                            <?php if(empty($results)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">No assessment results recorded yet.</td></tr>
                            <?php else: foreach($results as $i => $r): 
                                $pct = ($r['marks_obtained'] / $r['full_marks']) * 100;
                            ?>
                                <tr>
                                    <td><?= $i+1 ?></td>
                                    <td><span class="fw-bold"><?= htmlspecialchars($r['subject_code']) ?></span> - <?= htmlspecialchars($r['subject_name']) ?></td>
                                    <td><span class="text-capitalize badge bg-secondary"><?= htmlspecialchars($r['assessment_type']) ?></span></td>
                                    <td><?= number_format($r['marks_obtained'], 2) ?> / <?= number_format($r['full_marks'], 2) ?></td>
                                    <td><span class="fw-bold <?= $pct >= 50 ? 'text-success' : 'text-danger' ?>"><?= number_format($pct, 1) ?>%</span></td>
                                    <td class="text-center">
    <form method="POST"
          action="academic_progress.php"
          class="d-inline"
          onsubmit="return confirm('Delete this assessment result? This cannot be undone.');">

        <input type="hidden"
               name="csrf_token"
               value="<?= htmlspecialchars($_SESSION['assessment_csrf'], ENT_QUOTES, 'UTF-8') ?>">

        <button type="submit"
                name="delete_result"
                value="<?= (int) $r['result_id'] ?>"
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

<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content rounded-4 border-0 shadow" method="POST">
            <div class="modal-header border-bottom-0"><h5 class="modal-title fw-bold">Record Assessment Score</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="small fw-semibold">Subject</label>
                    <select name="subject_id" class="form-select" required>
                        <?php foreach($subjects as $s): ?>
                            <option value="<?= $s['subject_id'] ?>"><?= htmlspecialchars($s['subject_code'].' - '.$s['subject_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="small fw-semibold">Assessment Type</label>
                    <select name="assessment_type" class="form-select" required>
                        <option value="quiz">Quiz</option>
                        <option value="test">Test</option>
                        <option value="assignment">Assignment</option>
                        <option value="final_exam">Final Exam</option>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6"><label class="small fw-semibold">Marks Obtained</label><input type="number" step="0.01" name="marks_obtained" class="form-control" required></div>
                    <div class="col-6"><label class="small fw-semibold">Full Marks</label><input type="number" step="0.01" name="full_marks" class="form-control" value="100.00" required></div>
                </div>
            </div>
            <div class="modal-footer border-top-0"><button type="submit" name="add_result" class="btn btn-primary rounded-pill px-4 fw-bold">Save Result</button></div>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>