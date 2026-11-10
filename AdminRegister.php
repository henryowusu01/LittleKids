<?php
session_start();
require 'dbConnect.php';

$adminCountResult = $conn->query('SELECT COUNT(*) AS adminCount FROM tbladmin');
if ($adminCountResult === false) {
    error_log('Unable to count admin accounts: ' . $conn->error);
    http_response_code(500);
    exit('Unable to load admin registration.');
}
$hasAdmins = (int) $adminCountResult->fetch_assoc()['adminCount'] > 0;
$isSignedInAdmin = isset($_SESSION['adminID']);

if ($hasAdmins && !$isSignedInAdmin) {
    header('Location: AdminLogin.php');
    exit();
}

if (!isset($_SESSION['admin_register_csrf'])) {
    $_SESSION['admin_register_csrf'] = bin2hex(random_bytes(32));
}

$message = '';
$isError = false;
$registrationSucceeded = false;
$fullName = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $fullNameValue = $_POST['fullName'] ?? '';
    $emailValue = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $fullName = is_string($fullNameValue) ? trim($fullNameValue) : '';
    $email = is_string($emailValue) ? trim($emailValue) : '';

    if (!is_string($csrfToken) || !hash_equals($_SESSION['admin_register_csrf'], $csrfToken)) {
        $message = 'Your form session expired. Please reload and try again.';
        $isError = true;
    } elseif (
        $fullName === '' ||
        strlen($fullName) > 100 ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        !is_string($password) ||
        strlen($password) < 12
    ) {
        $message = 'Enter a valid name and email, and use a password of at least 12 characters.';
        $isError = true;
    } else {
        $registrationLockAcquired = false;
        if (!$isSignedInAdmin) {
            $lockResult = $conn->query("SELECT GET_LOCK('littlekids_admin_setup', 5) AS acquired");
            $registrationLockAcquired = $lockResult !== false &&
                (int) $lockResult->fetch_assoc()['acquired'] === 1;
            if (!$registrationLockAcquired) {
                $message = 'Admin setup is busy. Please try again.';
                $isError = true;
            } else {
                $checkAdminCount = $conn->query('SELECT COUNT(*) AS adminCount FROM tbladmin');
                if ($checkAdminCount === false) {
                    error_log('Unable to recheck admin accounts: ' . $conn->error);
                    $message = 'Unable to register this admin. Please try again.';
                    $isError = true;
                } elseif ((int) $checkAdminCount->fetch_assoc()['adminCount'] > 0) {
                    $message = 'An admin account already exists. Sign in to register another admin.';
                    $isError = true;
                }
            }
        }

        $stmt = $isError
            ? false
            : $conn->prepare('INSERT INTO tbladmin (fullName, email, password, roleName) VALUES (?, ?, ?, ?)');
        if ($stmt === false) {
            if (!$isError) {
                error_log('Unable to prepare admin registration: ' . $conn->error);
                $message = 'Unable to register this admin. Please try again.';
                $isError = true;
            }
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $role = 'Admin';
            $stmt->bind_param('ssss', $fullName, $email, $passwordHash, $role);

            if ($stmt->execute()) {
                $message = 'Successfully Added';
                $newAdminID = $stmt->insert_id;
                $createdAdminName = $fullName;
                $fullName = '';
                $email = '';
                $_SESSION['admin_register_csrf'] = bin2hex(random_bytes(32));
                $registrationSucceeded = true;

                $activity = $conn->prepare(
                    'INSERT INTO tbladminactivity (adminID, activity) VALUES (?, ?)'
                );
                if ($activity === false) {
                    error_log('Unable to prepare admin registration activity: ' . $conn->error);
                    $message .= '. Activity logging failed.';
                } else {
                    $activityText = "Registered admin account #{$newAdminID}";
                    $currentAdminID = $isSignedInAdmin ? (int) $_SESSION['adminID'] : $newAdminID;
                    $activity->bind_param('is', $currentAdminID, $activityText);
                    if (!$activity->execute()) {
                        error_log('Unable to record admin registration activity: ' . $activity->error);
                        $message .= '. Activity logging failed.';
                    }
                    $activity->close();
                }
                if (!$isSignedInAdmin) {
                    session_regenerate_id(true);
                    $_SESSION['adminID'] = (int) $newAdminID;
                    $_SESSION['adminName'] = $createdAdminName;
                    $_SESSION['adminRole'] = $role;
                }
            } elseif ($stmt->errno === 1062) {
                $message = 'An admin account already uses that email address.';
                $isError = true;
            } else {
                error_log('Unable to create admin: ' . $stmt->error);
                $message = 'Unable to register this admin. Please try again.';
                $isError = true;
            }
            $stmt->close();
        }
        if ($registrationLockAcquired) {
            $conn->query("SELECT RELEASE_LOCK('littlekids_admin_setup')");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Admin - Little Kids Transport</title>
    <link rel="stylesheet" href="Style.css">
</head>
<body>
<main class="form-container">
    <h2>Register Admin</h2>
    <?php if ($message !== '' && !$registrationSucceeded): ?>
        <p class="<?= $isError ? 'guardian-message guardian-message--error' : 'guardian-message guardian-message--success' ?>"
           role="<?= $isError ? 'alert' : 'status' ?>">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>
    <?php if (!$registrationSucceeded): ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_register_csrf'], ENT_QUOTES, 'UTF-8') ?>">
        <label for="fullName">Full name</label>
        <input type="text" id="fullName" name="fullName" maxlength="100" autocomplete="name" required
               value="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>">

        <label for="email">Email</label>
        <input type="email" id="email" name="email" autocomplete="email" required
               value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">

        <label for="password">Password (at least 12 characters)</label>
        <input type="password" id="password" name="password" minlength="12" autocomplete="new-password" required>
        
        <button type="submit">Create admin account</button>
    </form>
    <p><a href="<?= $isSignedInAdmin ? 'Admin.php' : 'AdminLogin.php' ?>">
        <?= $isSignedInAdmin ? 'Back to admin dashboard' : 'Back to admin login' ?>
    </a></p>
    <?php endif; ?>
</main>
<?php if ($registrationSucceeded): ?>
    <div class="admin-success-overlay">
        <section class="admin-success-dialog" role="dialog" aria-modal="true"
                 aria-labelledby="admin-success-title">
            <h2 id="admin-success-title"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></h2>
            <a class="btn" href="Admin.php">OK</a>
        </section>
    </div>
<?php endif; ?>
</body>
</html>
