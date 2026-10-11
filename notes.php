<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$user_id = $_SESSION['user_id'];

// Security token for editing and deleting notes.
if (empty($_SESSION['notes_csrf'])) {
    $_SESSION['notes_csrf'] = bin2hex(random_bytes(32));
}

function redirectWithNoteMessage($message, $type = 'success')
{
    $_SESSION['notes_message'] = $message;
    $_SESSION['notes_message_type'] = $type;

    header('Location: notes.php');
    exit;
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    (isset($_POST['update_note']) || isset($_POST['delete_note']))
) {
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['notes_csrf'], $token)
    ) {
        redirectWithNoteMessage(
            'Your request could not be verified. Please try again.',
            'danger'
        );
    }

    $note_id = (int) ($_POST['note_id'] ?? 0);

    // Only retrieve a note owned by the current user.
    $stmt = $conn->prepare(
        'SELECT note_id, file_path
         FROM study_notes
         WHERE note_id = ? AND user_id = ?'
    );
    $stmt->bind_param('ii', $note_id, $user_id);
    $stmt->execute();
    $existingNote = $stmt->get_result()->fetch_assoc();

    if (!$existingNote) {
        redirectWithNoteMessage('Note not found.', 'danger');
    }

    if (isset($_POST['update_note'])) {
        $subject_id = (int) ($_POST['subject_id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));
        $content = trim((string) ($_POST['content'] ?? ''));

        if ($title === '' || $subject_id <= 0) {
            redirectWithNoteMessage(
                'Please select a subject and enter a title.',
                'danger'
            );
        }

        // The selected subject must also belong to this user.
        $stmt = $conn->prepare(
            'SELECT subject_id
             FROM subjects
             WHERE subject_id = ? AND user_id = ?'
        );
        $stmt->bind_param('ii', $subject_id, $user_id);
        $stmt->execute();

        if (!$stmt->get_result()->fetch_assoc()) {
            redirectWithNoteMessage(
                'Please select a valid subject.',
                'danger'
            );
        }

        $stmt = $conn->prepare(
            'UPDATE study_notes
             SET subject_id = ?, title = ?, content = ?
             WHERE note_id = ? AND user_id = ?'
        );
        $stmt->bind_param(
            'issii',
            $subject_id,
            $title,
            $content,
            $note_id,
            $user_id
        );

        if (!$stmt->execute()) {
            redirectWithNoteMessage('Unable to update the note.', 'danger');
        }

        redirectWithNoteMessage('Note updated successfully.');
    }

    if (isset($_POST['delete_note'])) {
        $stmt = $conn->prepare(
            'DELETE FROM study_notes
             WHERE note_id = ? AND user_id = ?'
        );
        $stmt->bind_param('ii', $note_id, $user_id);

        if (!$stmt->execute()) {
            redirectWithNoteMessage('Unable to delete the note.', 'danger');
        }

        $message = 'Note deleted successfully.';
        $messageType = 'success';
        $oldPath = $existingNote['file_path'];

        if (!empty($oldPath)) {
            // Do not remove an attachment referenced by another note.
            $stmt = $conn->prepare(
                'SELECT COUNT(*) AS total
                 FROM study_notes
                 WHERE file_path = ?'
            );
            $stmt->bind_param('s', $oldPath);
            $stmt->execute();
            $references = $stmt->get_result()->fetch_assoc();

            if ((int) $references['total'] === 0) {
                $uploadRoot = realpath(__DIR__ . '/uploads');
                $fileToDelete = realpath(__DIR__ . '/' . $oldPath);

                // Only delete files located inside this uploads folder.
                if (
                    $uploadRoot !== false &&
                    $fileToDelete !== false &&
                    is_file($fileToDelete) &&
                    strpos(
                        $fileToDelete,
                        $uploadRoot . DIRECTORY_SEPARATOR
                    ) === 0
                ) {
                    if (!@unlink($fileToDelete)) {
                        $message =
                            'Note deleted, but its attachment could not be removed.';
                        $messageType = 'warning';
                    }
                }
            }
        }

        redirectWithNoteMessage($message, $messageType);
    }
}
// Save Note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_note'])) {
    $subject_id = intval($_POST['subject_id']);
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $file_path = null;

    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
        $fileName = rand() . '_' . basename($_FILES['file']['name']);
        $file_path = $uploadDir . $fileName;
        move_uploaded_file($_FILES['file']['tmp_name'], $file_path);
    }

    $stmt = $conn->prepare("INSERT INTO study_notes (user_id, subject_id, title, content, file_path) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $user_id, $subject_id, $title, $content, $file_path);
    $stmt->execute();
    header("Location: notes.php");
    exit;
}

// Fetch Notes
$notes = [];
$stmt = $conn->prepare("SELECT n.*, s.subject_code, s.subject_name FROM study_notes n JOIN subjects s ON n.subject_id = s.subject_id WHERE n.user_id = ? ORDER BY n.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) { $notes[] = $r; }

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
    <title>Notes & Materials - Smart Study Planner</title>
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
                    <h4 class="fw-bold mb-0"><i class="bi bi-folder2-open text-primary me-2"></i>Study Notes & Materials</h4>
                    <button class="btn btn-primary rounded-pill btn-sm px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg me-1"></i> Add Note</button>
                </div>
<?php if (isset($_SESSION['notes_message'])): ?>
    <?php
    $messageType = $_SESSION['notes_message_type'] ?? 'success';

    if (!in_array($messageType, ['success', 'danger', 'warning'], true)) {
        $messageType = 'success';
    }
    ?>

    <div
        class="alert alert-<?= $messageType ?> alert-dismissible fade show"
        role="alert"
    >
        <?= htmlspecialchars(
            $_SESSION['notes_message'],
            ENT_QUOTES,
            'UTF-8'
        ) ?>

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert"
            aria-label="Close"
        ></button>
    </div>

    <?php
    unset($_SESSION['notes_message'], $_SESSION['notes_message_type']);
    ?>
<?php endif; ?>
                <div class="row g-3">
                    <?php if(empty($notes)): ?>
                        <div class="col-12 text-center text-muted py-4">No notes or study materials uploaded yet.</div>
                    <?php else: foreach($notes as $n): ?>
                        <div class="col-md-6">
                            <div class="card border rounded-3 p-3 shadow-sm h-100">
                                <span class="badge bg-light text-dark border align-self-start mb-2"><?= htmlspecialchars($n['subject_code'].' - '.$n['subject_name']) ?></span>
                                <h6 class="fw-bold"><?= htmlspecialchars($n['title']) ?></h6>
                                <p class="small text-muted mb-2"><?= nl2br(htmlspecialchars($n['content'])) ?></p>
                                <div class="mt-auto pt-2">
    <?php if (!empty($n['file_path'])): ?>
        <a
            href="<?= htmlspecialchars($n['file_path'], ENT_QUOTES, 'UTF-8') ?>"
            target="_blank"
            rel="noopener noreferrer"
            class="btn btn-sm btn-outline-primary rounded-pill w-100 mb-2"
        >
            <i class="bi bi-file-earmark-arrow-down me-1"></i>
            View Attachment
        </a>
    <?php endif; ?>

    <div class="d-flex gap-2">
        <button
            type="button"
            class="btn btn-sm btn-outline-primary rounded-pill flex-fill"
            data-bs-toggle="modal"
            data-bs-target="#editNoteModal<?= (int) $n['note_id'] ?>"
        >
            <i class="bi bi-pencil-square me-1"></i>
            Edit
        </button>

        <form
            method="POST"
            action="notes.php"
            class="flex-fill"
            onsubmit="return confirm('Delete this note and its attachment? This cannot be undone.');"
        >
            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['notes_csrf'], ENT_QUOTES, 'UTF-8') ?>"
            >

            <input
                type="hidden"
                name="note_id"
                value="<?= (int) $n['note_id'] ?>"
            >

            <button
                type="submit"
                name="delete_note"
                value="1"
                class="btn btn-sm btn-outline-danger rounded-pill w-100"
            >
                <i class="bi bi-trash me-1"></i>
                Delete
            </button>
        </form>
    </div>
</div>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content rounded-4 border-0 shadow" method="POST" enctype="multipart/form-data">
            <div class="modal-header border-bottom-0"><h5 class="modal-title fw-bold">Upload Study Material</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="small fw-semibold">Subject</label>
                    <select name="subject_id" class="form-select" required>
                        <?php foreach($subjects as $s): ?>
                            <option value="<?= $s['subject_id'] ?>"><?= htmlspecialchars($s['subject_code'].' - '.$s['subject_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3"><label class="small fw-semibold">Title</label><input type="text" name="title" class="form-control" placeholder="Chapter 1 Notes" required></div>
                <div class="mb-3"><label class="small fw-semibold">Content Summary</label><textarea name="content" class="form-control" rows="3"></textarea></div>
                <div class="mb-3"><label class="small fw-semibold">File Attachment (PDF / Image)</label><input type="file" name="file" class="form-control"></div>
            </div>
            <div class="modal-footer border-top-0"><button type="submit" name="add_note" class="btn btn-primary rounded-pill px-4 fw-bold">Save Note</button></div>
        </form>
    </div>
</div>
    <?php foreach ($notes as $note): ?>
    <div
        class="modal fade"
        id="editNoteModal<?= (int) $note['note_id'] ?>"
        tabindex="-1"
        aria-labelledby="editNoteTitle<?= (int) $note['note_id'] ?>"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered">
            <form
                method="POST"
                action="notes.php"
                class="modal-content rounded-4 border-0 shadow"
            >
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION['notes_csrf'], ENT_QUOTES, 'UTF-8') ?>"
                >

                <input
                    type="hidden"
                    name="note_id"
                    value="<?= (int) $note['note_id'] ?>"
                >

                <div class="modal-header border-bottom-0">
                    <h5
                        class="modal-title fw-bold"
                        id="editNoteTitle<?= (int) $note['note_id'] ?>"
                    >
                        <i class="bi bi-pencil-square text-primary me-2"></i>
                        Edit Note
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="small fw-semibold">Subject</label>

                        <select name="subject_id" class="form-select" required>
                            <?php foreach ($subjects as $subject): ?>
                                <option
                                    value="<?= (int) $subject['subject_id'] ?>"
                                    <?= (int) $subject['subject_id'] === (int) $note['subject_id']
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= htmlspecialchars(
                                        $subject['subject_code'] . ' - ' .
                                        $subject['subject_name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-semibold">Title</label>

                        <input
                            type="text"
                            name="title"
                            class="form-control"
                            maxlength="255"
                            value="<?= htmlspecialchars($note['title'], ENT_QUOTES, 'UTF-8') ?>"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label class="small fw-semibold">Content Summary</label>

                        <textarea
                            name="content"
                            class="form-control"
                            rows="4"
                        ><?= htmlspecialchars($note['content'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <?php if (!empty($note['file_path'])): ?>
                        <div class="small text-muted">
                            <i class="bi bi-paperclip me-1"></i>
                            Your existing attachment will be kept.
                        </div>
                    <?php endif; ?>
                </div>

                <div class="modal-footer border-top-0">
                    <button
                        type="button"
                        class="btn btn-outline-primary rounded-pill px-3"
                        data-bs-dismiss="modal"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        name="update_note"
                        value="1"
                        class="btn btn-primary rounded-pill px-4 fw-bold"
                    >
                        <i class="bi bi-check-lg me-1"></i>
                        Update Note
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endforeach; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>