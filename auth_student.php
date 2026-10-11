<?php
// Shared access check for student pages.
// session_start() must be called before this file is included.

// Not logged in -> go to login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Admins use their own panel, so send them there
if (($_SESSION['role'] ?? '') === 'admin') {
    header("Location: admin_dashboard.php");
    exit;
}