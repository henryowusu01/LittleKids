<?php
session_start();
require 'dbConnect.php';

//These lines check if the user is already logged in by verifying the presence of a session variable 'guardianID'. If the user is logged in, they are redirected to the dashboard page, preventing access to the registration page.
$message = '';
$registrationSucceeded = false;
$fullName = '';
$email = '';
$phone = '';
$address = '';

//this function reads a value from the $_POST array and trims whitespace.
// It ensures that the value is a string before trimming, returning an empty string if the value is not set or not a string.
if (!isset($_SESSION['guardian_register_csrf'])) {
    $_SESSION['guardian_register_csrf'] = bin2hex(random_bytes(32));
}

//the values of the form fields are initialized to empty strings. 
//These variables will hold the user's input and will be used to repopulate the form in case of an error, providing a better user experience.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullNameValue = $_POST['fullName'] ?? '';
    $emailValue = $_POST['email'] ?? '';
    $phoneValue = $_POST['phone'] ?? '';
    $addressValue = $_POST['address'] ?? '';
    $password = $_POST['password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    //This block checks if the form was submitted via a POST request. 
    //It retrieves the values from the form fields (full name, email, phone number, address, password, and CSRF token) and assigns them to variables. The null coalescing operator (??) is used to provide default empty strings if the fields are not set in the POST request.
    $fullName = is_string($fullNameValue) ? trim($fullNameValue) : '';
    $email = is_string($emailValue) ? trim($emailValue) : '';
    $phone = is_string($phoneValue) ? trim($phoneValue) : '';
    $address = is_string($addressValue) ? trim($addressValue) : '';

    //the token is validated to ensure that the form submission is legitimate and not a CSRF attack.
    if (!is_string($csrfToken) || !hash_equals($_SESSION['guardian_register_csrf'], $csrfToken)) {
        $message = 'Your form session expired. Please reload and try again.';
    } elseif (
        $fullName === '' ||
        strlen($fullName) > 100 ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        $phone === '' ||
        strlen($phone) > 20 ||
        $address === '' ||
        strlen($address) > 255 ||
        !is_string($password) ||
        $password === ''
    ) {
        $message = 'Please complete all fields with valid information.'; 
    } else {
        $check = $conn->prepare('SELECT guardianID FROM tblguardians WHERE email = ? LIMIT 1');
        if ($check === false) {
            error_log('Unable to prepare guardian email check: ' . $conn->error);
            $message = 'Registration could not be completed. Please try again.';
        //the code prepares an SQL statement to check if the email address provided by the user is already registered in the database. It uses a prepared statement to prevent SQL injection attacks. If the preparation fails, an error message is logged, and a user-friendly message is displayed.
        } else {
            $check->bind_param('s', $email);
            if (!$check->execute()) {
                error_log('Unable to check guardian email: ' . $check->error);
                $message = 'Registration could not be completed. Please try again.';
                $check->close();
            } else {
                $existingGuardian = $check->get_result()->fetch_assoc();
                $check->close();

                if ($existingGuardian !== null) {
                    $message = 'Email already registered!';
                } else {
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    $insert = $conn->prepare(
                        'INSERT INTO tblguardians (fullName, email, phoneNum, address, password)
                         VALUES (?, ?, ?, ?, ?)'
                    );
//the code prepares an SQL statement to insert the new guardian's information into the database.
// It uses prepared statements to prevent SQL injection attacks. If the preparation fails, an error message is logged, and a user-friendly message is displayed. If the preparation succeeds, the parameters are bound, and the statement is executed. If the execution is successful, the user is logged in by setting session variables, and a success flag is set. If any errors occur during execution, they are logged, and an error message is displayed to the user.
                    if ($insert === false) {
                        error_log('Unable to prepare guardian registration: ' . $conn->error);
                        $message = 'Registration could not be completed. Please try again.';
                    } else {
                        $insert->bind_param('sssss', $fullName, $email, $phone, $address, $passwordHash);
                        if ($insert->execute()) {
                            session_regenerate_id(true);
                            $_SESSION['guardianID'] = (int) $insert->insert_id;
                            $_SESSION['fullName'] = $fullName;
                            $_SESSION['guardian_register_csrf'] = bin2hex(random_bytes(32));
                            $registrationSucceeded = true;
                        } else {
                            error_log('Unable to create guardian account: ' . $insert->error);
                            $message = 'Registration could not be completed. Please try again.';
                        }
                        $insert->close();
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<main class="form-container">

    <h2>Guardian Registration</h2>

    <?php if ($message !== ''): ?>
        <p class="guardian-message guardian-message--error" role="alert">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <!-- The following block displays the registration form if the registration has not succeeded. It includes input fields for full name, email, phone number, address, and password, along with a hidden CSRF token field for security. The form values are repopulated with the user's previous input in case of an error, providing a better user experience. -->
    <?php if (!$registrationSucceeded): ?>
    <form method="POST">
        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars($_SESSION['guardian_register_csrf'], ENT_QUOTES, 'UTF-8') ?>">

        <input type="text" name="fullName" placeholder="Full Name" maxlength="100"
               autocomplete="name" value="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>" required>

        <input type="email" name="email" placeholder="Email" autocomplete="email"
               value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" required>

        <input type="tel" name="phone" placeholder="Phone Number" maxlength="20"
               autocomplete="tel" value="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>" required>

        <input type="text" name="address" placeholder="Address" maxlength="255"
               autocomplete="street-address" value="<?= htmlspecialchars($address, ENT_QUOTES, 'UTF-8') ?>" required>

        <input type="password" name="password" placeholder="Password" autocomplete="new-password" required>

        <button type="submit" name="register">Register</button>
    </form>
    <p>
        Already have an account?
        <a href="LKLogin.php">Login</a>
    </p>
    <?php endif; ?>

</main>
<!-- The following block displays a success overlay if the registration was successful. It provides feedback to the user that their account has been created and includes a link to proceed to the dashboard. -->
<?php if ($registrationSucceeded): ?>
    <div class="admin-success-overlay">
        <section class="admin-success-dialog" role="dialog" aria-modal="true"
                 aria-labelledby="registration-success-title">
            <h2 id="registration-success-title">User successfully created</h2>
            <a class="btn" href="dashboard.php">OK</a>
        </section>
    </div>
<?php endif; ?>

</body>
</html>