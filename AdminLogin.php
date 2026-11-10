<?php
session_start();
require 'dbConnect.php';

if (isset($_GET['logout'])) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $cookie = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httponly']);
    }
    session_destroy();
    header('Location: AdminLogin.php');
    exit();
}

if (isset($_SESSION['adminID'])) {
    header('Location: Admin.php');
    exit();
}

$adminCountResult = $conn->query('SELECT COUNT(*) AS adminCount FROM tbladmin');
if ($adminCountResult === false) {
    error_log('Unable to count admin accounts: ' . $conn->error);
    http_response_code(500);
    exit('Unable to load admin sign-in.');
}
$hasAdmins = (int) $adminCountResult->fetch_assoc()['adminCount'] > 0;

if (!isset($_SESSION['admin_login_csrf'])) {
    $_SESSION['admin_login_csrf'] = bin2hex(random_bytes(32));
}

$message = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailValue = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
    $email = is_string($emailValue) ? trim($emailValue) : '';

    if (!is_string($csrfToken) || !hash_equals($_SESSION['admin_login_csrf'] ?? '', $csrfToken)) {
        $message = 'Your form session expired. Please reload and try again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || !is_string($password)) {
        $message = 'Enter a valid email address and password.';
    } else {
        $stmt = $conn->prepare('SELECT adminID, fullName, password, roleName FROM tbladmin WHERE email = ? LIMIT 1');
        if ($stmt === false) {
            error_log('Unable to prepare admin login: ' . $conn->error);
            http_response_code(500);
            exit('Unable to sign in right now.');
        }

        $stmt->bind_param('s', $email);
        if (!$stmt->execute()) {
            error_log('Unable to execute admin login: ' . $stmt->error);
            http_response_code(500);
            exit('Unable to sign in right now.');
        }

        $admin = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($admin !== null && password_verify($password, $admin['password'])) {
            session_regenerate_id(true);
            $_SESSION['adminID'] = (int) $admin['adminID'];
            $_SESSION['adminName'] = $admin['fullName'];
            $_SESSION['adminRole'] = $admin['roleName'];
            header('Location: Admin.php');
            exit();
        }

        $message = 'Email or password is incorrect.';
    }
}

$_SESSION['admin_login_csrf'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Little Kids Transport</title>
    <link rel="stylesheet" href="Style.css">
</head>
<body>
<main class="form-container">
    <h2>Admin Login</h2>
    <?php if ($message !== ''): ?>
        <p class="guardian-message guardian-message--error" role="alert">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?> <!-- Displays the error message if there is one, ensuring that any special characters are properly escaped to prevent XSS attacks. -->
        </p>
    <?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['admin_login_csrf'], ENT_QUOTES, 'UTF-8') ?>">
         <!-- Hidden input field to include the CSRF token in the form submission, ensuring that the request is legitimate and not forged. -->
        <label for="email">Admin email</label>
        <input type="email" id="email" name="email" autocomplete="username" required
               value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" autocomplete="current-password" required>
        <button type="submit">Sign in</button>
    </form>
    <?php if (!$hasAdmins): ?>
        <p><a class="form-secondary-button" href="AdminRegister.php">New admin</a></p>
    <?php endif; ?>
    <p><a href="index.php">Back to home</a></p>
</main>
</body>
</html>
