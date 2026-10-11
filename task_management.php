<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit; 
}

$user_id = $_SESSION['user_id'];

// Fungsi Smart Priority Berdasarkan Due Date
function getSmartPriority($dueDateStr) {
    if (empty($dueDateStr)) return 'Normal';
    
    $today = new DateTime('today');
    $dueDate = new DateTime($dueDateStr);
    
    if ($dueDate <= $today) {
        return 'Urgent';
    }
    
    $interval = $today->diff($dueDate);
    $daysLeft = $interval->days;

    if ($daysLeft <= 2) {
        return 'Urgent';   // 0 hingga 2 hari lagi
    } elseif ($daysLeft <= 7) {
        return 'Upcoming'; // 3 hingga 7 hari lagi
    } else {
        return 'Normal';   // Lebih 7 hari
    }
}

// Tambah Tugasan Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_task'])) {
    $subject_id = intval($_POST['subject_id']);
    $title = trim($_POST['title']);
    $task_type = $_POST['task_type'] ?? '';

$allowed_task_types = [
    'assignment',
    'quiz',
    'test',
    'project',
    'final_exam'
];

if (!is_string($task_type) ||
    !in_array($task_type, $allowed_task_types, true)) {
    http_response_code(400);
    exit('Invalid task type. Please go back and select a valid type.');
}
    $due_date = $_POST['due_date'];
    $due_time = $_POST['due_time'];

    // Kira Priority Automatik
    $priority = getSmartPriority($due_date);

    if (!empty($title) && !empty($due_date)) {
        $stmt = $conn->prepare("INSERT INTO academic_tasks (user_id, subject_id, title, task_type, priority, due_date, due_time, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
        $stmt->bind_param("iisssss", $user_id, $subject_id, $title, $task_type, $priority, $due_date, $due_time);
        $stmt->execute();
        header("Location: task_management.php");
        exit;
    }
}

// Tukar Status Tugasan (Pending <-> Done)
if (isset($_GET['toggle_status'])) {
    $task_id = intval($_GET['toggle_status']);
    
    // Semak status terkini dari pangkalan data
    $stmtCheck = $conn->prepare("SELECT status FROM academic_tasks WHERE task_id = ? AND user_id = ?");
    $stmtCheck->bind_param("ii", $task_id, $user_id);
    $stmtCheck->execute();
    $resCheck = $stmtCheck->get_result();
    
    if ($row = $resCheck->fetch_assoc()) {
        $currentStatus = $row['status'];
        // Jika Done / Completed, tukar ke Pending. Jika Pending, tukar ke Done.
        $new_status = ($currentStatus === 'Done' || $currentStatus === 'Completed') ? 'Pending' : 'Done';
        
        $stmtUpdate = $conn->prepare("UPDATE academic_tasks SET status = ? WHERE task_id = ? AND user_id = ?");
        $stmtUpdate->bind_param("sii", $new_status, $task_id, $user_id);
        $stmtUpdate->execute();
    }
    
    header("Location: task_management.php");
    exit;
}

// Padam Tugasan
if (isset($_GET['delete'])) {
    $task_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM academic_tasks WHERE task_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $task_id, $user_id);
    $stmt->execute();
    header("Location: task_management.php");
    exit;
}

// Dapatkan Senarai Tugasan
$tasks = [];
$stmt = $conn->prepare("SELECT t.*, s.subject_code, s.subject_name FROM academic_tasks t LEFT JOIN subjects s ON t.subject_id = s.subject_id WHERE t.user_id = ? ORDER BY t.due_date ASC, t.due_time ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) { 
    $tasks[] = $r; 
}

// Dapatkan Senarai Subjek
$subjects = [];
$stmtS = $conn->prepare("SELECT subject_id, subject_code, subject_name FROM subjects WHERE user_id = ?");
$stmtS->bind_param("i", $user_id);
$stmtS->execute();
$resS = $stmtS->get_result();
while ($r = $resS->fetch_assoc()) { 
    $subjects[] = $r; 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Task Management - Smart Study Planner</title>
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
        .bg-white { background-color: #fffaf4 !important; }
        .bg-light { background-color: #f3ebe1 !important; }
        .bg-secondary { background-color: #786858 !important; }
        .bg-success { background-color: #5e3e2b !important; }
        .bg-warning { background-color: #dfc3a4 !important; }
        .bg-danger { background-color: #874432 !important; }
        .badge.bg-secondary, .badge.bg-success, .badge.bg-danger { color: #fffaf4; }
        .text-secondary { color: #786858 !important; }
        .text-danger { color: #874432 !important; }
        .border { border-color: #dfcdbb !important; }
        .content-card label { color: #604935; }
        .form-control:disabled, .form-select:disabled { background-color: #f3ebe1; color: #786858; }
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
        .btn-outline-primary {
            --bs-btn-color: #795239;
            --bs-btn-bg: transparent;
            --bs-btn-border-color: #795239;
            --bs-btn-hover-color: #fffaf4;
            --bs-btn-hover-bg: #5e3e2b;
            --bs-btn-hover-border-color: #5e3e2b;
            --bs-btn-active-color: #fffaf4;
            --bs-btn-active-bg: #4d3223;
            --bs-btn-active-border-color: #4d3223;
            --bs-btn-disabled-color: #795239;
            --bs-btn-disabled-bg: transparent;
            --bs-btn-disabled-border-color: #795239;
            --bs-btn-focus-shadow-rgb: 121, 82, 57;
        }
        .btn-success {
            --bs-btn-color: #fffaf4;
            --bs-btn-bg: #5e3e2b;
            --bs-btn-border-color: #5e3e2b;
            --bs-btn-hover-color: #fffaf4;
            --bs-btn-hover-bg: #4d3223;
            --bs-btn-hover-border-color: #4d3223;
            --bs-btn-active-color: #fffaf4;
            --bs-btn-active-bg: #38291f;
            --bs-btn-active-border-color: #38291f;
            --bs-btn-disabled-color: #fffaf4;
            --bs-btn-disabled-bg: #5e3e2b;
            --bs-btn-disabled-border-color: #5e3e2b;
            --bs-btn-focus-shadow-rgb: 121, 82, 57;
        }
        .btn-outline-success {
            --bs-btn-color: #5e3e2b;
            --bs-btn-bg: transparent;
            --bs-btn-border-color: #5e3e2b;
            --bs-btn-hover-color: #fffaf4;
            --bs-btn-hover-bg: #4d3223;
            --bs-btn-hover-border-color: #4d3223;
            --bs-btn-active-color: #fffaf4;
            --bs-btn-active-bg: #38291f;
            --bs-btn-active-border-color: #38291f;
            --bs-btn-disabled-color: #5e3e2b;
            --bs-btn-disabled-bg: transparent;
            --bs-btn-disabled-border-color: #5e3e2b;
            --bs-btn-focus-shadow-rgb: 121, 82, 57;
        }
        .btn-info {
            --bs-btn-color: #38291f;
            --bs-btn-bg: #dfc3a4;
            --bs-btn-border-color: #dfc3a4;
            --bs-btn-hover-color: #38291f;
            --bs-btn-hover-bg: #d4b391;
            --bs-btn-hover-border-color: #d4b391;
            --bs-btn-active-color: #38291f;
            --bs-btn-active-bg: #c8a37a;
            --bs-btn-active-border-color: #c8a37a;
            --bs-btn-disabled-color: #38291f;
            --bs-btn-disabled-bg: #dfc3a4;
            --bs-btn-disabled-border-color: #dfc3a4;
            --bs-btn-focus-shadow-rgb: 121, 82, 57;
        }
        .btn-outline-info {
            --bs-btn-color: #604935;
            --bs-btn-bg: transparent;
            --bs-btn-border-color: #dfc3a4;
            --bs-btn-hover-color: #38291f;
            --bs-btn-hover-bg: #d4b391;
            --bs-btn-hover-border-color: #d4b391;
            --bs-btn-active-color: #38291f;
            --bs-btn-active-bg: #c8a37a;
            --bs-btn-active-border-color: #c8a37a;
            --bs-btn-disabled-color: #604935;
            --bs-btn-disabled-bg: transparent;
            --bs-btn-disabled-border-color: #dfc3a4;
            --bs-btn-focus-shadow-rgb: 121, 82, 57;
        }
        .btn-warning {
            --bs-btn-color: #38291f;
            --bs-btn-bg: #dfc3a4;
            --bs-btn-border-color: #dfc3a4;
            --bs-btn-hover-color: #38291f;
            --bs-btn-hover-bg: #d4b391;
            --bs-btn-hover-border-color: #d4b391;
            --bs-btn-active-color: #38291f;
            --bs-btn-active-bg: #c8a37a;
            --bs-btn-active-border-color: #c8a37a;
            --bs-btn-disabled-color: #38291f;
            --bs-btn-disabled-bg: #dfc3a4;
            --bs-btn-disabled-border-color: #dfc3a4;
            --bs-btn-focus-shadow-rgb: 121, 82, 57;
        }
        .btn-outline-warning {
            --bs-btn-color: #604935;
            --bs-btn-bg: transparent;
            --bs-btn-border-color: #dfc3a4;
            --bs-btn-hover-color: #38291f;
            --bs-btn-hover-bg: #d4b391;
            --bs-btn-hover-border-color: #d4b391;
            --bs-btn-active-color: #38291f;
            --bs-btn-active-bg: #c8a37a;
            --bs-btn-active-border-color: #c8a37a;
            --bs-btn-disabled-color: #604935;
            --bs-btn-disabled-bg: transparent;
            --bs-btn-disabled-border-color: #dfc3a4;
            --bs-btn-focus-shadow-rgb: 121, 82, 57;
        }
        .btn-secondary {
            --bs-btn-color: #fffaf4;
            --bs-btn-bg: #786858;
            --bs-btn-border-color: #786858;
            --bs-btn-hover-color: #fffaf4;
            --bs-btn-hover-bg: #604935;
            --bs-btn-hover-border-color: #604935;
            --bs-btn-active-color: #fffaf4;
            --bs-btn-active-bg: #513522;
            --bs-btn-active-border-color: #513522;
            --bs-btn-disabled-color: #fffaf4;
            --bs-btn-disabled-bg: #786858;
            --bs-btn-disabled-border-color: #786858;
            --bs-btn-focus-shadow-rgb: 121, 82, 57;
        }
        .btn-outline-secondary {
            --bs-btn-color: #786858;
            --bs-btn-bg: transparent;
            --bs-btn-border-color: #786858;
            --bs-btn-hover-color: #fffaf4;
            --bs-btn-hover-bg: #604935;
            --bs-btn-hover-border-color: #604935;
            --bs-btn-active-color: #fffaf4;
            --bs-btn-active-bg: #513522;
            --bs-btn-active-border-color: #513522;
            --bs-btn-disabled-color: #786858;
            --bs-btn-disabled-bg: transparent;
            --bs-btn-disabled-border-color: #786858;
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
                    <div>
                        <h4 class="fw-bold mb-0"><i class="bi bi-check2-square text-primary me-2"></i>Task & Deadline Management</h4>
                        <small class="text-muted">Smart Priority is automatically calculated based on Due Date</small>
                    </div>
                    <button class="btn btn-primary rounded-pill btn-sm px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg me-1"></i> Add Task</button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Subject</th>
                                <th>Task Title</th>
                                <th>Deadline</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($tasks)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">No tasks found. Click "Add Task" to create one.</td></tr>
                            <?php else: foreach($tasks as $t): 
                                $isCompleted = ($t['status'] === 'Done' || $t['status'] === 'Completed');
                                $smartPriority = getSmartPriority($t['due_date']);
                            ?>
                                <tr>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($t['subject_code'] ?: 'General') ?></span></td>
                                    <td class="fw-bold <?= $isCompleted ? 'text-decoration-line-through text-muted' : '' ?>">
                                        <?= htmlspecialchars($t['title']) ?>
                                    </td>
                                    <td>
                                        <small><i class="bi bi-calendar me-1"></i><?= date('d M Y', strtotime($t['due_date'])) ?></small><br>
                                        <small class="text-muted"><i class="bi bi-clock me-1"></i><?= date('h:i A', strtotime($t['due_time'])) ?></small>
                                    </td>
                                    <td>
    <?php if (!$isCompleted): ?>
        <?php if ($smartPriority === 'Urgent'): ?>
            <span class="badge bg-danger">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>Urgent
            </span>
        <?php elseif ($smartPriority === 'Upcoming'): ?>
            <span class="badge bg-warning text-dark">
                <i class="bi bi-clock-history me-1"></i>Upcoming
            </span>
        <?php else: ?>
            <span class="badge bg-secondary">Normal</span>
        <?php endif; ?>
    <?php endif; ?>
</td>
                                    <td>
                                        <?php if($isCompleted): ?>
                                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Done</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="task_management.php?toggle_status=<?= $t['task_id'] ?>" class="btn btn-sm <?= $isCompleted ? 'btn-outline-secondary' : 'btn-success' ?> rounded-pill px-3 me-1">
                                            <?= $isCompleted ? 'Undo' : 'Done' ?>
                                        </a>
                                        <a href="task_management.php?delete=<?= $t['task_id'] ?>" onclick="return confirm('Delete task?')" class="btn btn-sm btn-outline-danger rounded-circle"><i class="bi bi-trash"></i></a>
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

<!-- Modal Add Task -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content rounded-4 border-0 shadow" method="POST">
            <div class="modal-header border-bottom-0"><h5 class="modal-title fw-bold">Add New Task</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="small fw-semibold">Subject</label>
                    <select name="subject_id" class="form-select" required>
                        <?php foreach($subjects as $sb): ?>
                            <option value="<?= $sb['subject_id'] ?>"><?= htmlspecialchars($sb['subject_code'].' - '.$sb['subject_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3"><label class="small fw-semibold">Task Title</label><input type="text" name="title" class="form-control" placeholder="e.g. Chapter 1 Quiz" required></div>
                <div class="mb-3">
                    <label class="small fw-semibold">Type</label>
                    <select name="task_type" class="form-select" required>
    <option value="" disabled selected>Select task type</option>
    <option value="assignment">Assignment</option>
    <option value="quiz">Quiz</option>
    <option value="test">Test</option>
    <option value="project">Project</option>
    <option value="final_exam">Final Exam</option>
</select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6"><label class="small fw-semibold">Due Date</label><input type="date" name="due_date" class="form-control" required></div>
                    <div class="col-6"><label class="small fw-semibold">Due Time</label><input type="time" name="due_time" class="form-control" value="23:59" required></div>
                </div>
            </div>
            <div class="modal-footer border-top-0"><button type="submit" name="add_task" class="btn btn-primary rounded-pill px-4 fw-bold">Save Task</button></div>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>