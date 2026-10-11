<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$user_id =$_SESSION['user_id'];

// Log Completed Session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_session'])) {
    $subject_id = intval($_POST['subject_id']);
    $minutes = intval($_POST['duration_minutes']);

    if ($subject_id > 0 &&$minutes > 0) {
        $stmt =$conn->prepare("INSERT INTO study_sessions (user_id, subject_id, duration_minutes) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $user_id,$subject_id, $minutes);$stmt->execute();
    }
    header("Location: focus_timer.php");
    exit;
}

$subjects = [];
$stmt =$conn->prepare("SELECT subject_id, subject_code, subject_name FROM subjects WHERE user_id = ?");
$stmt->bind_param("i", $user_id);$stmt->execute();
$res =$stmt->get_result();
while ($r =$res->fetch_assoc()) { $subjects[] =$r; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Focus Timer - Smart Study Planner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { color: #38291f; background: radial-gradient(ellipse at top left, #ab805d 0%, #795239 45%, #38291f 100%); }
        .sidebar-card, .content-card { border: none; border-radius: 1.25rem; }
        .nav-link-custom { color: #786858; font-weight: 600; padding: 0.75rem 1.25rem; border-radius: 0.75rem; display: flex; align-items: center; gap: 0.75rem; text-decoration: none; }
        .nav-link-custom:hover, .nav-link-custom.active { background-color: #795239; color: #fffaf4 !important; }
        .timer-display { font-size: 5rem; font-weight: 800; color: #795239; letter-spacing: -2px; }
        .mode-btn { border-radius: 2rem; padding: 0.5rem 1.5rem; font-weight: 600; }
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
            <div class="card content-card shadow-sm p-4 bg-white text-center">
                <h4 class="fw-bold text-start border-bottom pb-3 mb-4"><i class="bi bi-stopwatch text-primary me-2"></i>Pomodoro Focus Timer</h4>
                
                <!-- 3 PRESET OPTIONS -->
                <div class="d-flex justify-content-center gap-2 mb-4">
                    <button id="btn25" class="btn btn-primary mode-btn" onclick="setMode(25, 'Study Session')"><i class="bi bi-book me-1"></i> 25 Min Study</button>
                    <button id="btn5" class="btn btn-outline-success mode-btn" onclick="setMode(5, 'Short Break')"><i class="bi bi-cup-hot me-1"></i> 5 Min Break</button>
                    <button id="btn15" class="btn btn-outline-info mode-btn" onclick="setMode(15, 'Long Break')"><i class="bi bi-pause-circle me-1"></i> 15 Min Break</button>
                </div>

                <form id="sessionForm" method="POST" action="focus_timer.php" class="mx-auto mb-3" style="max-width: 400px;">
                    <div class="mb-3 text-start">
                        <label class="small fw-semibold">Select Subject for Focus Logging</label>
                        <select name="subject_id" class="form-select" required>
                            <?php foreach($subjects as$s): ?>
                                <option value="<?= $s['subject_id'] ?>"><?= htmlspecialchars($s['subject_code'].' - '.$s['subject_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="hidden" name="duration_minutes" id="durationInput" value="25">
                    <input type="hidden" name="log_session" value="1">
                </form>

                <div class="timer-display my-2" id="timer">25:00</div>
                <div class="text-muted fw-semibold mb-4" id="modeLabel">Focus Mode: 25 Min Study</div>

                <div class="d-flex justify-content-center gap-2">
                    <button id="startBtn" class="btn btn-primary rounded-pill px-4 fw-bold py-2" onclick="startTimer()"><i class="bi bi-play-fill me-1"></i> Start</button>
                    <button id="pauseBtn" class="btn btn-warning rounded-pill px-4 fw-bold py-2" onclick="pauseTimer()" disabled><i class="bi bi-pause-fill me-1"></i> Pause</button>
                    <button id="resetBtn" class="btn btn-secondary rounded-pill px-4 fw-bold py-2" onclick="resetTimer()"><i class="bi bi-arrow-counterclockwise me-1"></i> Reset</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentMinutes = 25;
let timeLeft = currentMinutes * 60;
let timerId = null;
let endTime = 0;
let currentModeName = '25 Min Study';
let isFinishing = false;
let audioContext = null;

function updateDisplay() {
    const minutes = Math.floor(timeLeft / 60);
    const seconds = timeLeft % 60;

    document.getElementById('timer').innerText =
        `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}

function enableAlarm() {
    try {
        const AudioClass =
            window.AudioContext || window.webkitAudioContext;

        if (!AudioClass) return;

        if (!audioContext) {
            audioContext = new AudioClass();
        }

        if (audioContext.state === 'suspended') {
            audioContext.resume().catch(() => {});
        }
    } catch (error) {
    }
}

function playAlarm() {
    try {
        if (!audioContext || audioContext.state !== 'running') return;

        const start = audioContext.currentTime;

        for (let i = 0; i < 8; i++) {
            const oscillator = audioContext.createOscillator();
            const volume = audioContext.createGain();
            const at = start + i * 0.38;

            oscillator.type = 'sine';
            oscillator.frequency.value = i % 2 === 0 ? 880 : 660;

            volume.gain.setValueAtTime(0, at);
            volume.gain.linearRampToValueAtTime(0.22, at + 0.02);
            volume.gain.linearRampToValueAtTime(0, at + 0.25);

            oscillator.connect(volume);
            volume.connect(audioContext.destination);

            oscillator.onended = () => {
                oscillator.disconnect();
                volume.disconnect();
            };

            oscillator.start(at);
            oscillator.stop(at + 0.27);
        }
    } catch (error) {
        
    }
}

function finishTimer() {
    if (isFinishing) return;

    isFinishing = true;
    clearInterval(timerId);
    timerId = null;
    timeLeft = 0;
    updateDisplay();

    const studySession = currentMinutes === 25;
    const form = document.getElementById('sessionForm');
    const subject = form.querySelector('[name="subject_id"]');

    const buttons = [
        'startBtn', 'pauseBtn', 'resetBtn',
        'btn25', 'btn5', 'btn15'
    ];

    buttons.forEach(id => {
        document.getElementById(id).disabled = true;
    });

    subject.disabled = true;

    document.getElementById('modeLabel').innerText = studySession
        ? 'Study complete! Saving after the alarm...'
        : 'Break finished!';

    playAlarm();

    // Tunggu alarm selesai sebelum halaman reload.
    setTimeout(() => {
        subject.disabled = false;

        if (studySession) {
            document.getElementById('durationInput').value = 25;
            form.submit();
        } else {
            isFinishing = false;

            buttons.forEach(id => {
                document.getElementById(id).disabled = false;
            });

            document.getElementById('pauseBtn').disabled = true;
        }
    }, 3200);
}

function setMode(mins, modeName) {
    if (isFinishing) return;

    pauseTimer();
    if (isFinishing) return;

    currentMinutes = mins;
    timeLeft = mins * 60;
    currentModeName = modeName;

    document.getElementById('durationInput').value = mins;
    document.getElementById('modeLabel').innerText =
        'Focus Mode: ' + modeName;

    document.getElementById('btn25').className =
        'btn mode-btn ' +
        (mins === 25 ? 'btn-primary' : 'btn-outline-primary');

    document.getElementById('btn5').className =
        'btn mode-btn ' +
        (mins === 5 ? 'btn-success' : 'btn-outline-success');

    document.getElementById('btn15').className =
        'btn mode-btn ' +
        (mins === 15 ? 'btn-info' : 'btn-outline-info');

    updateDisplay();
}

function startTimer() {
    if (timerId !== null || isFinishing) return;

    if (
        currentMinutes === 25 &&
        !document.getElementById('sessionForm').reportValidity()
    ) {
        return;
    }

    enableAlarm();

    if (timeLeft <= 0) {
        timeLeft = currentMinutes * 60;
    }

    endTime = Date.now() + timeLeft * 1000;

    document.getElementById('startBtn').disabled = true;
    document.getElementById('pauseBtn').disabled = false;
    document.getElementById('modeLabel').innerText =
        'Focus Mode: ' + currentModeName;

    updateDisplay();

    timerId = setInterval(() => {
        timeLeft = Math.max(
            0,
            Math.ceil((endTime - Date.now()) / 1000)
        );

        updateDisplay();

        if (timeLeft === 0) {
            finishTimer();
        }
    }, 250);
}

function pauseTimer() {
    if (isFinishing) return;

    if (timerId !== null) {
        timeLeft = Math.max(
            0,
            Math.ceil((endTime - Date.now()) / 1000)
        );

        if (timeLeft === 0) {
            finishTimer();
            return;
        }
    }

    clearInterval(timerId);
    timerId = null;

    document.getElementById('startBtn').disabled = false;
    document.getElementById('pauseBtn').disabled = true;

    updateDisplay();
}

function resetTimer() {
    if (isFinishing) return;

    pauseTimer();
    if (isFinishing) return;

    timeLeft = currentMinutes * 60;

    document.getElementById('modeLabel').innerText =
        'Focus Mode: ' + currentModeName;

    updateDisplay();
}
</script>
</body>
</html>