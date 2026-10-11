<?php
session_start();

// Check if the user is not logged in
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
    <title>User Manual - Smart Study Planner</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #eee1d3; }
        .card { border: none; border-radius: 1rem; }
        .accordion-button:not(.collapsed) {
            background-color: #eee1d3;
            color: #513522;
            font-weight: 600;
        }
        :root {
            --bs-primary-rgb: 121, 82, 57;
            --bs-dark-rgb: 56, 41, 31;
            --bs-border-color: #dfcdbb;
        }
        body { background-color: #f4ede4; color: #38291f; }
        .bg-white { background-color: #fffaf4 !important; }
        .text-muted, .text-secondary { color: #786858 !important; }
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
        .accordion-button::after { filter: grayscale(1) sepia(1); }
        ::selection { color: #38291f; background-color: #dfc3a4; }
    </style>
</head>
<body class="py-3">



<div class="container-fluid px-md-4">
    <div class="row g-4">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="col-md-8 col-lg-9">
            <div class="container my-4" style="max-width: 900px;">
    
    <!-- Header -->
    <div class="text-center mb-5">
        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
            <i class="bi bi-book-half fs-1"></i>
        </div>
        <h2 class="fw-bold text-dark">User Manual</h2>
        <p class="text-muted">Follow this simple guide to make the most of every module in Smart Study Planner.</p>
    </div>

    <!-- Accordion Manual -->
    <div class="accordion shadow-sm rounded-4 overflow-hidden mb-5" id="manualAccordion">

        <!-- Module 1: Subjects -->
        <div class="accordion-item border-0 border-bottom">
            <h2 class="accordion-header" id="headingOne">
                <button class="accordion-button py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                    <i class="bi bi-journal-plus text-primary fs-5 me-3"></i> 1. Subject Management (Subjects)
                </button>
            </h2>
            <div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#manualAccordion">
                <div class="accordion-body bg-white p-4">
                    <p class="text-secondary">This module is used to register the academic subjects you are taking for the current semester.</p>
                    <ol class="mb-0 text-muted lh-lg">
                        <li>Open the <strong>Subjects</strong> module from the navigation menu.</li>
                        <li>Enter the <strong>Subject Code</strong> (e.g. <em>CSC530</em>) and <strong>Subject Name</strong> (e.g. <em>Web Application Development</em>).</li>
                        <li>Choose a theme color for the subject so it is easy to identify in your timetable.</li>
                        <li>Click the <strong>Add Subject</strong> button. Saved subjects will be used automatically in the Tasks and Timetable modules.</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Module 2: Timetable -->
        <div class="accordion-item border-0 border-bottom">
            <h2 class="accordion-header" id="headingTwo">
                <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                    <i class="bi bi-calendar-week text-primary fs-5 me-3"></i> 2. Class Timetable (Timetable)
                </button>
            </h2>
            <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                <div class="accordion-body bg-white p-4">
                    <p class="text-secondary">Organize your weekly class schedule and lecture room information.</p>
                    <ol class="mb-0 text-muted lh-lg">
                        <li>Select a subject from the dropdown list of subjects you have registered.</li>
                        <li>Set the <strong>Day</strong>, <strong>Start Time</strong>, and <strong>End Time</strong>.</li>
                        <li>Enter the location/lecture room (e.g. <em>Computer Lab 3 / Lecture Hall A</em>).</li>
                        <li>The system will arrange and display your weekly class schedule visually.</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Module 3: Tasks -->
        <div class="accordion-item border-0 border-bottom">
            <h2 class="accordion-header" id="headingThree">
                <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">
                    <i class="bi bi-check2-square text-primary fs-5 me-3"></i> 3. Task Management (Tasks & To-Do)
                </button>
            </h2>
            <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                <div class="accordion-body bg-white p-4">
                    <p class="text-secondary">Manage assignments, quizzes, and projects so they are submitted on time.</p>
                    <ol class="mb-0 text-muted lh-lg">
                        <li>Add a new task by setting the task title, the related subject, and the <strong>Deadline</strong>.</li>
                        <li>Use the status marker to mark a task as <em>Pending</em> or <em>Completed</em>.</li>
                        <li>Tasks that are close to their deadline will be highlighted on the main Dashboard page.</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Module 4: Focus Timer -->
        <div class="accordion-item border-0 border-bottom">
            <h2 class="accordion-header" id="headingFour">
                <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour">
                    <i class="bi bi-stopwatch text-primary fs-5 me-3"></i> 4. Focus Timer (Pomodoro)
                </button>
            </h2>
            <div id="collapseFour" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                <div class="accordion-body bg-white p-4">
                    <p class="text-secondary">Helps improve study productivity using the Pomodoro technique (25 minutes of study + 5 minutes of rest).</p>
                    <ol class="mb-0 text-muted lh-lg">
                        <li>Select the timer mode (<strong>Study Session</strong> or <strong>Short Break</strong>).</li>
                        <li>Press the <strong>Start</strong> button to begin a focus session. You can <em>Pause</em> or <em>Reset</em> the timer at any time.</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Module 5: Notes & Materials -->
        <div class="accordion-item border-0 border-bottom">
            <h2 class="accordion-header" id="headingFive">
                <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive">
                    <i class="bi bi-folder2-open text-primary fs-5 me-3"></i> 5. Notes & Study Materials (Notes & Materials)
                </button>
            </h2>
            <div id="collapseFive" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                <div class="accordion-body bg-white p-4">
                    <p class="text-secondary">A storage center for short notes and uploaded study documents.</p>
                    <ol class="mb-0 text-muted lh-lg">
                        <li>Write short text notes for quick reference.</li>
                        <li>Upload supporting documents such as PDF notes, presentation slides, or reference materials by subject.</li>
                        <li>Use the download button to access your files at any time.</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Module 6: Marks Tracker -->
        <div class="accordion-item border-0">
            <h2 class="accordion-header" id="headingSix">
                <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSix">
                    <i class="bi bi-graph-up-arrow text-primary fs-5 me-3"></i> 6. Academic Results Tracker (Academic Progress)
                </button>
            </h2>
            <div id="collapseSix" class="accordion-collapse collapse" data-bs-parent="#manualAccordion">
                <div class="accordion-body bg-white p-4">
                    <p class="text-secondary">Calculate and estimate your overall marks as well as your GPA/CGPA.</p>
                    <ol class="mb-0 text-muted lh-lg">
                        <li>Enter the grade or assessment marks for each subject.</li>
                        <li>The system automatically calculates your cumulative marks and gives an overview of your academic performance through a simple chart.</li>
                    </ol>
                </div>
            </div>
        </div>

    </div>
</div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>