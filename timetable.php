<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$user_id = $_SESSION['user_id'];

// Add Timetable Entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_schedule'])) {
    $subject_id = intval($_POST['subject_id']);
    $day = $_POST['day_of_week'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];
    $type = $_POST['class_type'];
    $location = trim($_POST['location']);

    $stmt = $conn->prepare("INSERT INTO timetable (user_id, subject_id, day_of_week, start_time, end_time, class_type, location) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssss", $user_id, $subject_id, $day, $start, $end, $type, $location);
    $stmt->execute();
    header("Location: timetable.php");
    exit;
}

// Delete Schedule
if (isset($_GET['delete'])) {
    $schedule_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM timetable WHERE schedule_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $schedule_id, $user_id);
    $stmt->execute();
    header("Location: timetable.php");
    exit;
}

// Group Schedules by Day
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$schedulesByDay = array_fill_keys($days, []);

$stmt = $conn->prepare("SELECT t.*, s.subject_code, s.subject_name FROM timetable t JOIN subjects s ON t.subject_id = s.subject_id WHERE t.user_id = ? ORDER BY t.start_time ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $schedulesByDay[$row['day_of_week']][] = $row;
}

// Fetch Subjects for dropdown
$subjects = [];
$stmtSub = $conn->prepare("SELECT subject_id, subject_code, subject_name FROM subjects WHERE user_id = ?");
$stmtSub->bind_param("i", $user_id);
$stmtSub->execute();
$resSub = $stmtSub->get_result();
while ($row = $resSub->fetch_assoc()) { $subjects[] = $row; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Timetable - Smart Study Planner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { color: #38291f; background: radial-gradient(ellipse at top left, #ab805d 0%, #795239 45%, #38291f 100%); }
        .sidebar-card, .content-card { border: none; border-radius: 1.25rem; }
        .nav-link-custom { color: #786858; font-weight: 600; padding: 0.75rem 1.25rem; border-radius: 0.75rem; display: flex; align-items: center; gap: 0.75rem; text-decoration: none; }
        .nav-link-custom:hover, .nav-link-custom.active { background-color: #795239; color: #fffaf4 !important; }
        .timetable-col { min-width: 180px; flex: 1; }
        .class-card { border-left: 4px solid #795239; background-color: #fffdf9; border-radius: 0.5rem; transition: all 0.2s; }
        .class-card:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(121,82,57,0.08); }
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
                        <h4 class="fw-bold mb-0"><i class="bi bi-calendar-week text-primary me-2"></i>Weekly Timetable Grid</h4>
                        <small class="text-muted">Overview of your scheduled classes for the week</small>
                    </div>
                    <button class="btn btn-primary rounded-pill btn-sm px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg me-1"></i> Add Class</button>
                </div>

                <!-- HORIZONTAL TIMETABLE GRID -->
                <div class="table-responsive">
                    <div class="d-flex gap-2 pb-2" style="min-width: 1100px;">
                        <?php foreach($days as $day): ?>
                            <div class="timetable-col bg-light p-2 rounded-3">
                                <div class="fw-bold text-center py-2 bg-white rounded shadow-sm text-primary mb-3">
                                    <?= $day ?>
                                </div>
                                
                                <?php if(empty($schedulesByDay[$day])): ?>
                                    <div class="text-center text-muted py-4 small">No classes</div>
                                <?php else: ?>
                                    <div class="d-flex flex-column gap-2">
                                        <?php foreach($schedulesByDay[$day] as $sc): ?>
                                            <div class="class-card p-2 position-relative">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <span class="badge bg-primary text-wrap mb-1"><?= htmlspecialchars($sc['subject_code']) ?></span>
                                                    <a href="timetable.php?delete=<?= $sc['schedule_id'] ?>" onclick="return confirm('Remove class?')" class="text-danger small"><i class="bi bi-x-circle"></i></a>
                                                </div>
                                                <div class="fw-bold small text-dark"><?= htmlspecialchars($sc['subject_name']) ?></div>
                                                <div class="small text-muted mt-1">
                                                    <i class="bi bi-clock me-1"></i><?= date('h:i A', strtotime($sc['start_time'])) ?> - <?= date('h:i A', strtotime($sc['end_time'])) ?>
                                                </div>
                                                <?php if($sc['location']): ?>
                                                    <div class="small text-secondary mt-1"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($sc['location']) ?></div>
                                                <?php endif; ?>
                                                <span class="badge bg-secondary mt-1 small"><?= htmlspecialchars($sc['class_type']) ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content rounded-4 border-0 shadow" method="POST">
            <div class="modal-header border-bottom-0"><h5 class="modal-title fw-bold">Add Class Schedule</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="small fw-semibold">Subject</label>
                    <select name="subject_id" class="form-select" required>
                        <?php foreach($subjects as $sb): ?>
                            <option value="<?= $sb['subject_id'] ?>"><?= htmlspecialchars($sb['subject_code'].' - '.$sb['subject_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="small fw-semibold">Day of Week</label>
                    <select name="day_of_week" class="form-select" required>
                        <?php foreach($days as $d): ?>
                            <option value="<?= $d ?>"><?= $d ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6"><label class="small fw-semibold">Start Time</label><input type="time" name="start_time" class="form-control" required></div>
                    <div class="col-6"><label class="small fw-semibold">End Time</label><input type="time" name="end_time" class="form-control" required></div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6"><label class="small fw-semibold">Type</label><input type="text" name="class_type" class="form-control" placeholder="Lecture/Lab" value="Lecture"></div>
                    <div class="col-6"><label class="small fw-semibold">Location</label><input type="text" name="location" class="form-control" placeholder="Room / Hall"></div>
                </div>
            </div>
            <div class="modal-footer border-top-0"><button type="submit" name="add_schedule" class="btn btn-primary rounded-pill px-4 fw-bold">Save Schedule</button></div>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>