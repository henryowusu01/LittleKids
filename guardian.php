<?php

include 'dbConnect.php';

$message = '';

if (isset($_POST['register'])) {
    $name = trim($_POST['fullname'] ?? '');
    $phone = preg_replace('/[\s()-]/', '', $_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = implode(', ', array_filter([
        trim($_POST['address_line_1'] ?? ''),
        trim($_POST['address_line_2'] ?? ''),
        trim($_POST['suburb'] ?? ''),
        trim($_POST['city'] ?? ''),
    ]));
    $plainPassword = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Enter a valid email address.';
    } elseif (!preg_match('/^\+27[1-9][0-9]{8}$/D', $phone)) {
        $message = 'Enter a South African phone number in +27 format, followed by 9 digits.';
    } else {
        $password = password_hash($plainPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare(
            'INSERT INTO tblguardians (phoneNum, fullName, email, password, address) VALUES (?, ?, ?, ?, ?)'
        );

        if ($stmt === false) {
            $message = 'Registration could not be completed. Please try again.';
        } else {
            $stmt->bind_param('sssss', $phone, $name, $email, $password, $address);

            if ($stmt->execute()) {
                $message = 'Registration successful.';
            } else {
                $message = 'Registration could not be completed. Please try again.';
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guardian Registration</title>
</head>
<body>
<form method="POST">

<input type="text" name="fullname" placeholder="Full Name" required>

<input type="tel" name="phone" placeholder="+27821234567"
       pattern="\+27[1-9][0-9]{8}" maxlength="12"
       title="Enter +27 followed by 9 digits (for example, +27821234567)." required>

<input type="email" name="email" placeholder="Email" required>

<label for="address_line_1">Street address</label>
<input type="text" id="address_line_1" name="address_line_1"
       placeholder="Street address" autocomplete="address-line1" required>

<label for="address_line_2">Apartment, unit, or additional address details (optional)</label>
<input type="text" id="address_line_2" name="address_line_2"
       placeholder="Apartment, unit, etc." autocomplete="address-line2">

<label for="suburb">Suburb</label>
<input type="text" id="suburb" name="suburb"
       placeholder="Suburb" autocomplete="address-level3">

<label for="city">City</label>
<input type="text" id="city" name="city"
       placeholder="City" autocomplete="address-level2">

<input type="password" name="password" placeholder="Password" required>

<button type="submit" name="register">
Register
</button>

<?php if ($message !== ''): ?>
    <p role="alert"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

</form>
</body>
</html>