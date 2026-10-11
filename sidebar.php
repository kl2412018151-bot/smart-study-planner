<?php
// session_start() dipanggil oleh halaman sebelum sidebar dimasukkan.
$currentPage = basename($_SERVER['PHP_SELF']);
$sidebar_name = $_SESSION['full_name'] ?? 'Student';

$mainMenu = [
    'dashboard.php'          => ['bi-grid-fill', 'Dashboard'],
    'subject_management.php' => ['bi-journal-plus', 'Subjects'],
    'timetable.php'          => ['bi-calendar-week', 'Timetable'],
    'task_management.php'    => ['bi-check2-square', 'Tasks'],
    'focus_timer.php'        => ['bi-stopwatch', 'Focus Timer'],
    'notes.php'              => ['bi-folder2-open', 'Notes & Materials'],
    'academic_progress.php'  => ['bi-graph-up-arrow', 'Academic Progress'],
    
];

$helpMenu = [
    'user_manual.php' => ['bi-book', 'User Guide'],
    'about_us.php'    => ['bi-info-circle', 'Project Info'],
    'faq.php'                => ['bi-question-circle', 'FAQ'],
];
?>

<style>
    .card.sidebar-card {
        border: none;
        border-radius: 1.25rem;
        min-height: calc(100vh - 40px);
        color: #38291f;
        background-color: #fffaf4 !important;
    }

    .sidebar-card .nav-link-custom {
        color: #786858;
        font-weight: 600;
        padding: 0.75rem 1.25rem;
        border-radius: 0.75rem;
        transition: all 0.2s ease-in-out;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        text-decoration: none;
    }

    .sidebar-card .nav-link-custom:hover {
        background-color: #f3ebe1;
        color: #5e3e2b !important;
        transform: translateX(3px);
    }

    .sidebar-card .nav-link-custom.active,
    .sidebar-card .nav-link-custom.active:hover {
        background: #795239;
        color: #fffaf4 !important;
        box-shadow: 0 4px 12px rgba(121, 82, 57, 0.25);
    }

    .sidebar-card .nav-link-custom:focus-visible {
        outline: 3px solid #a77950;
        outline-offset: 2px;
    }

    .sidebar-card .bg-primary {
        background-color: #795239 !important;
    }

    .sidebar-card .bg-primary.bg-gradient {
        background-image: linear-gradient(
            135deg,
            #986d4d,
            #64432e
        ) !important;
    }

    .sidebar-card .bg-light {
        background-color: #f3ebe1 !important;
    }

    .sidebar-card .text-primary {
        color: #795239 !important;
    }

    .sidebar-card .text-dark {
        color: #38291f !important;
    }

    .sidebar-card .text-muted {
        color: #786858 !important;
    }

    .sidebar-card .text-white {
        color: #fffaf4 !important;
    }

    .sidebar-card .border-top,
    .sidebar-card .border-bottom {
        border-color: #dfcdbb !important;
    }

    .sidebar-card.shadow-sm {
        box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .075) !important;
    }

    .sidebar-card .btn-outline-danger {
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

    @media (max-width: 767.98px) {
        .card.sidebar-card {
            min-height: auto;
            position: static;
        }
    }
</style>

<div class="col-md-4 col-lg-3">
    <div class="card sidebar-card shadow-sm p-3 bg-white sticky-top"
         style="top: 20px;">

        <!-- Logo & System Title -->
        <div class="d-flex align-items-center gap-2 px-2 py-3 mb-2 border-bottom">
            <img
    src="assets/image/logo.png"
    alt="Smart Study Planner Logo"
    style="width: 42px; height: 42px; object-fit: contain; flex-shrink: 0;"
>
            <div>
                <h6 class="fw-bold text-dark mb-0">Smart Study Planner</h6>
                <small class="text-primary fw-semibold"
                       style="font-size: 0.75rem;">
                    User Panel
                </small>
            </div>
        </div>

        <!-- Student Profile -->
        <div class="p-2 mb-3 bg-light rounded-3 d-flex align-items-center gap-2">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold"
                 style="width: 36px; height: 36px; font-size: 0.85rem;">
                <?= htmlspecialchars(strtoupper(substr($sidebar_name, 0, 1))) ?>
            </div>

            <div class="overflow-hidden">
                <small class="d-block text-muted" style="font-size: 0.7rem;">
                    Welcome,
                </small>
                <span class="fw-bold text-dark text-truncate d-block small">
                    <?= htmlspecialchars($sidebar_name) ?>
                </span>
            </div>
        </div>

        <!-- Main Menu -->
        <small class="text-uppercase text-muted fw-bold px-3 mb-2 d-block"
               style="font-size: 0.68rem; letter-spacing: 0.5px;">
            MAIN MENU
        </small>

        <div class="nav flex-column gap-1 mb-3">
            <?php foreach ($mainMenu as $file => [$icon, $label]): ?>
                <a href="<?= $file ?>"
                   class="nav-link-custom <?= $currentPage === $file ? 'active' : '' ?>"
                   <?= $currentPage === $file ? 'aria-current="page"' : '' ?>>
                    <i class="bi <?= $icon ?>"></i>
                    <?= $label ?>
                </a>
            <?php endforeach; ?>
        </div>

        <hr class="my-2 text-muted opacity-25">

        <!-- Help & Info -->
        <small class="text-uppercase text-muted fw-bold px-3 mb-2 d-block"
               style="font-size: 0.68rem; letter-spacing: 0.5px;">
            HELP & INFO
        </small>

        <div class="nav flex-column gap-1">
            <?php foreach ($helpMenu as $file => [$icon, $label]): ?>
                <a href="<?= $file ?>"
                   class="nav-link-custom <?= $currentPage === $file ? 'active' : '' ?>"
                   <?= $currentPage === $file ? 'aria-current="page"' : '' ?>>
                    <i class="bi <?= $icon ?> <?= $currentPage === $file ? '' : 'text-primary' ?>"></i>
                    <?= $label ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Logout -->
        <div class="pt-3 border-top mt-auto">
            <a href="logout.php"
               class="btn btn-outline-danger w-100 rounded-pill btn-sm fw-bold">
                <i class="bi bi-box-arrow-right me-1"></i> Log Out
            </a>
        </div>
    </div>
</div>