<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$faqCategories = [
    [
        'title' => 'Getting Started',
        'icon' => 'bi-rocket-takeoff',
        'items' => [
            [
                'q' => 'How do I create an account?',
                'a' => 'On the login page, click <strong>Register here</strong>. Enter your full name, email address, and a password, then confirm the password and press Register. After that you can log in.'
            ],
            [
                'q' => 'I forgot my password. What should I do?',
                'a' => 'Password reset is not available in the system yet. Please contact your system administrator for help.'
            ],
            [
                'q' => 'Where should I start after logging in?',
                'a' => 'Start with the <strong>Subjects</strong> module and add the subjects you are taking this semester. The Timetable, Tasks, Focus Timer, Notes, and Academic Progress modules all use your subject list.'
            ],
        ],
    ],
    [
        'title' => 'Subjects & Timetable',
        'icon' => 'bi-calendar-week',
        'items' => [
            [
                'q' => 'How do I add a subject?',
                'a' => 'Open <strong>Subjects</strong>, click <strong>Add Subject</strong>, enter the subject code, subject name, and semester, then press <strong>Save Subject</strong>.'
            ],
            [
                'q' => 'Why is the subject dropdown empty when I add a task, class, or note?',
                'a' => 'The dropdown lists the subjects you have registered. If it is empty, add at least one subject in the <strong>Subjects</strong> module first.'
            ],
            [
                'q' => 'How do I add a class to my timetable?',
                'a' => 'Open <strong>Timetable</strong> and click <strong>Add Class</strong>. Choose the subject and day, set the start and end time, then enter the class type (for example Lecture or Lab) and the location.'
            ],
            [
                'q' => 'How do I remove a class from my timetable?',
                'a' => 'Find the class in the weekly grid and click the small cross icon at its top-right corner, then confirm.'
            ],
        ],
    ],
    [
        'title' => 'Tasks & Deadlines',
        'icon' => 'bi-check2-square',
        'items' => [
            [
                'q' => 'How is task priority decided?',
                'a' => 'Priority is calculated automatically from the due date. A task is <strong>Urgent</strong> if it is due within 2 days (or already due), <strong>Upcoming</strong> if it is due within 3 to 7 days, and <strong>Normal</strong> if it is more than 7 days away.'
            ],
            [
                'q' => 'How do I mark a task as finished?',
                'a' => 'In the <strong>Tasks</strong> module, click the <strong>Done</strong> button on the task. If you clicked it by mistake, press <strong>Undo</strong> to set it back to Pending.'
            ],
            [
                'q' => 'Which tasks appear on my Dashboard?',
                'a' => 'The Dashboard shows your next five unfinished tasks, ordered by the nearest due date, together with your subject, pending, and completed totals.'
            ],
        ],
    ],
    [
        'title' => 'Focus Timer',
        'icon' => 'bi-stopwatch',
        'items' => [
            [
                'q' => 'Are my study sessions recorded?',
                'a' => 'Yes, but only the <strong>25 Min Study</strong> mode is recorded. When the timer reaches zero, the session is saved automatically for the subject you selected. Breaks are not recorded.'
            ],
            [
                'q' => 'What happens if I pause or reset the timer?',
                'a' => 'Pausing stops the countdown so you can continue later. Resetting returns the timer to the full time of the selected mode. Neither action saves a study session.'
            ],
        ],
    ],
    [
        'title' => 'Notes & Academic Progress',
        'icon' => 'bi-folder2-open',
        'items' => [
            [
                'q' => 'What can I upload in Notes & Materials?',
                'a' => 'You can write a short text summary and attach one file (PDF or image) to each note. Every note is filed under one of your subjects.'
            ],
            [
                'q' => 'How do I open a file I attached to a note?',
                'a' => 'Click <strong>View Attachment</strong> on the note. The file opens in a new tab.'
            ],
            [
                'q' => 'How are my assessment results calculated?',
                'a' => 'In <strong>Academic Progress</strong>, click <strong>Add Result</strong> and enter the marks obtained and the full marks. The percentage is calculated for you. Scores of 50% or above use a mocha shade, while lower scores use a reddish-brown shade.'
            ],
        ],
    ],
    [
        'title' => 'Account',
        'icon' => 'bi-person-circle',
        'items' => [
            [
                'q' => 'Why was my account removed?',
                'a' => 'To keep the system tidy, an administrator may delete accounts that have not been used for a long time (currently 90 days without logging in). The account and all its data are removed permanently, so please log in regularly if you want to keep your records.'
            ],
            [
                'q' => 'How do I log out?',
                'a' => 'Click the <strong>Log Out</strong> button at the bottom of the sidebar on the Dashboard, or <strong>Logout</strong> in the top-right corner of this page.'
            ],
        ],
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ - Smart Study Planner</title>
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

        .accordion {
            --bs-accordion-color: #38291f;
            --bs-accordion-bg: #fffaf4;
            --bs-accordion-border-color: #dfcdbb;
            --bs-accordion-btn-color: #38291f;
            --bs-accordion-btn-bg: #fffaf4;
            --bs-accordion-active-color: #513522;
            --bs-accordion-active-bg: #eee1d3;
            --bs-accordion-btn-focus-border-color: #a77950;
            --bs-accordion-btn-focus-box-shadow: 0 0 0 0.25rem rgba(121, 82, 57, 0.15);
        }

        .accordion-button:not(.collapsed) { font-weight: 600; }
        .accordion-button::after { filter: grayscale(1) sepia(1); }

        .btn-primary {
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
        }

        .btn-light {
            --bs-btn-color: #513522;
            --bs-btn-bg: #fffaf4;
            --bs-btn-border-color: #fffaf4;
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
    <div class="text-center mb-5">
        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
             style="width: 70px; height: 70px;">
            <i class="bi bi-question-circle fs-1"></i>
        </div>
        <h2 class="fw-bold text-dark">Frequently Asked Questions</h2>
        <p class="text-muted">Quick answers to common questions. Click a question to see the answer.</p>
    </div>

    <?php foreach ($faqCategories as $c => $category): ?>
        <h5 class="fw-bold text-dark mb-3 mt-4">
            <i class="bi <?= $category['icon'] ?> text-primary me-2"></i>
            <?= htmlspecialchars($category['title']) ?>
        </h5>

        <div class="accordion shadow-sm rounded-4 overflow-hidden mb-4" id="faqAccordion<?= $c ?>">
            <?php foreach ($category['items'] as $i => $item): ?>
                <?php $id = "faq{$c}_{$i}"; ?>

                <div class="accordion-item border-0 <?= $i < count($category['items']) - 1 ? 'border-bottom' : '' ?>">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed py-3" type="button"
                                data-bs-toggle="collapse" data-bs-target="#<?= $id ?>"
                                aria-expanded="false" aria-controls="<?= $id ?>">
                            <?= htmlspecialchars($item['q']) ?>
                        </button>
                    </h2>

                    <div id="<?= $id ?>" class="accordion-collapse collapse"
                         data-bs-parent="#faqAccordion<?= $c ?>">
                        <div class="accordion-body bg-white text-muted lh-lg">
                            <?= $item['a'] ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

    <div class="card p-4 shadow-sm bg-white text-center mb-5">
        <h6 class="fw-bold mb-1">Still need help?</h6>
        <p class="text-muted small mb-3">
            Check the step-by-step guide or contact your system administrator.
        </p>
        <div>
            <a href="user_manual.php" class="btn btn-primary btn-sm rounded-pill px-4 fw-semibold">
                <i class="bi bi-book me-1"></i> Open User Manual
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>