<?php
session_start();
require 'dbConnect.php';

if (!isset($_SESSION['guardianID'])) {
    header('Location: LKLogIn.php');
    exit();
}

$guardianID = (int) $_SESSION['guardianID'];
$message = '';
$isError = false;
$readPostText = static function ($key) {
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
};
$child = [
    'fullName' => '',
    'dob' => '',
    'specialNeeds' => '',
    'EmergName' => '',
    'EmergNum' => '',
];
$editID = filter_input(INPUT_GET, 'edit_id', FILTER_VALIDATE_INT);
$editing = $editID !== null && $editID !== false && $editID > 0;

if (!isset($_SESSION['childreninfo_csrf'])) {
    $_SESSION['childreninfo_csrf'] = bin2hex(random_bytes(32));
}

if ($editing) {
    $stmt = $conn->prepare(
        'SELECT fullName, dob, specialNeeds, EmergName, EmergNum
         FROM tblchildren WHERE ChildID = ? AND guardianID = ?'
    );
    if ($stmt === false) {
        error_log('Unable to prepare child lookup: ' . $conn->error);
        http_response_code(500);
        exit('Unable to load child information.');
    }

    $stmt->bind_param('ii', $editID, $guardianID);
    if (!$stmt->execute()) {
        error_log('Unable to load child: ' . $stmt->error);
        http_response_code(500);
        exit('Unable to load child information.');
    }

    $result = $stmt->get_result();
    $savedChild = $result->fetch_assoc();
    $stmt->close();

    if ($savedChild === null) {
        http_response_code(404);
        exit('Child not found.');
    }
    $child = $savedChild;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedID = filter_input(INPUT_POST, 'child_id', FILTER_VALIDATE_INT);
    $isEditingPost = ($_POST['action'] ?? '') === 'edit' &&
        $submittedID !== null && $submittedID !== false && $submittedID > 0;

    $child = [
        'fullName' => $readPostText('fullName'),
        'dob' => $readPostText('dob'),
        'specialNeeds' => $readPostText('specialNeeds'),
        'EmergName' => $readPostText('EmergName'),
        'EmergNum' => $readPostText('EmergNum'),
    ];

    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!is_string($csrfToken) || !hash_equals($_SESSION['childreninfo_csrf'], $csrfToken)) {
        $message = 'Your form session expired. Please reload and try again.';
        $isError = true;
    } elseif (!$isEditingPost && ($_POST['action'] ?? '') !== 'add') {
        $message = 'Invalid form request.';
        $isError = true;
    } elseif (
        $child['fullName'] === '' ||
        $child['dob'] === '' ||
        $child['EmergName'] === '' ||
        $child['EmergNum'] === ''
    ) {
        $message = 'Complete the child name, date of birth, and emergency contact fields.';
        $isError = true;
    } elseif (
        strlen($child['fullName']) > 100 ||
        strlen($child['specialNeeds']) > 255 ||
        strlen($child['EmergName']) > 100 ||
        strlen($child['EmergNum']) > 20
    ) {
        $message = 'One or more fields exceed the allowed length.';
        $isError = true;
    } else {
        $dob = DateTime::createFromFormat('!Y-m-d', $child['dob']);
        if ($dob === false || $dob->format('Y-m-d') !== $child['dob'] || $dob > new DateTime('today')) {
            $message = 'Enter a valid date of birth that is not in the future.';
            $isError = true;
        } else {
            if ($isEditingPost) {
                $stmt = $conn->prepare(
                    'UPDATE tblchildren
                     SET fullName = ?, dob = ?, specialNeeds = ?, EmergName = ?, EmergNum = ?
                     WHERE ChildID = ? AND guardianID = ?'
                );
                if ($stmt !== false) {
                    $stmt->bind_param(
                        'sssssii',
                        $child['fullName'],
                        $child['dob'],
                        $child['specialNeeds'],
                        $child['EmergName'],
                        $child['EmergNum'],
                        $submittedID,
                        $guardianID
                    );
                }
            } else {
                $stmt = $conn->prepare(
                    'INSERT INTO tblchildren (guardianID, fullName, dob, specialNeeds, EmergName, EmergNum)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                if ($stmt !== false) {
                    $stmt->bind_param(
                        'isssss',
                        $guardianID,
                        $child['fullName'],
                        $child['dob'],
                        $child['specialNeeds'],
                        $child['EmergName'],
                        $child['EmergNum']
                    );
                }
            }

            if ($stmt === false) {
                error_log('Unable to prepare child save: ' . $conn->error);
                $message = 'Unable to save child information. Please try again.';
                $isError = true;
            } elseif (!$stmt->execute()) {
                error_log('Unable to save child: ' . $stmt->error);
                $message = 'Unable to save child information. Please try again.';
                $isError = true;
                $stmt->close();
            } else {
                $stmt->close();
                header('Location: childreninfo.php?saved=1');
                exit();
            }
        }
    }
}

if (isset($_GET['saved'])) {
    $message = 'Child information saved.';
}

$childrenStmt = $conn->prepare(
    'SELECT ChildID, fullName, dob, specialNeeds, EmergName, EmergNum
     FROM tblchildren WHERE guardianID = ? ORDER BY fullName'
);
if ($childrenStmt === false) {
    error_log('Unable to prepare child list: ' . $conn->error);
    http_response_code(500);
    exit('Unable to load children.');
}
$childrenStmt->bind_param('i', $guardianID);
if (!$childrenStmt->execute()) {
    error_log('Unable to load children: ' . $childrenStmt->error);
    http_response_code(500);
    exit('Unable to load children.');
}
$children = $childrenStmt->get_result();

function escapeChildValue($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Children</title>
    <link rel="stylesheet" href="Style.css">
</head>
<body>
<main class="children-page">
    <h2>Manage Children</h2>
    <p><a href="dashboard.php">Back to dashboard</a></p>

    <?php if ($message !== ''): ?>
        <p role="<?= $isError ? 'alert' : 'status' ?>">
            <?= escapeChildValue($message) ?>
        </p>
    <?php endif; ?>

    <h3><?= $editing ? 'Edit Child' : 'Add Child' ?></h3>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= escapeChildValue($_SESSION['childreninfo_csrf']) ?>">
        <input type="hidden" name="action" value="<?= $editing ? 'edit' : 'add' ?>">
        <?php if ($editing): ?>
            <input type="hidden" name="child_id" value="<?= (int) $editID ?>">
        <?php endif; ?>

        <label for="fullName">Child's full name</label>
        <input type="text" id="fullName" name="fullName" maxlength="100" required
               value="<?= escapeChildValue($child['fullName']) ?>">

        <label for="dob">Date of birth</label>
        <input type="date" id="dob" name="dob" max="<?= date('Y-m-d') ?>" required
               value="<?= escapeChildValue($child['dob']) ?>">

        <label for="specialNeeds">Special needs or notes (optional)</label>
        <textarea id="specialNeeds" name="specialNeeds" maxlength="255"><?= escapeChildValue($child['specialNeeds']) ?></textarea>

        <label for="EmergName">Emergency contact name</label>
        <input type="text" id="EmergName" name="EmergName" maxlength="100" required
               value="<?= escapeChildValue($child['EmergName']) ?>">

        <label for="EmergNum">Emergency contact phone</label>
        <input type="tel" id="EmergNum" name="EmergNum" maxlength="20" required
               value="<?= escapeChildValue($child['EmergNum']) ?>">

        <button type="submit"><?= $editing ? 'Save Changes' : 'Add Child' ?></button>
        <?php if ($editing): ?>
            <a href="childreninfo.php">Cancel</a>
        <?php endif; ?>
    </form>

    <h3>Children on your account</h3>
    <?php if ($children->num_rows === 0): ?>
        <p>No children have been added yet.</p>
    <?php else: ?>
        <div class="children-page__table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Date of birth</th>
                        <th>Special needs</th>
                        <th>Emergency contact</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $children->fetch_assoc()): ?>
                    <tr>
                        <td><?= escapeChildValue($row['fullName']) ?></td>
                        <td><?= escapeChildValue($row['dob']) ?></td>
                        <td><?= escapeChildValue($row['specialNeeds'] ?: 'None') ?></td>
                        <td><?= escapeChildValue($row['EmergName']) ?> — <?= escapeChildValue($row['EmergNum']) ?></td>
                        <td><a href="childreninfo.php?edit_id=<?= (int) $row['ChildID'] ?>">Edit</a></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
<?php $childrenStmt->close(); ?>
