<?php
session_start();
include 'dbConnect.php'; //imports database connection

if (!isset($_SESSION['adminID'])) {
    header('Location: AdminLogin.php'); // Redirect to login if not signed in
    exit();
}

//it generates cybersecurity tokens for the admin session to prevent CSRF attacks. If the token is not set, it generates a new one using random_bytes and stores it in the session.
if (!isset($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

// Get the action from GET or POST parameters, defaulting to 'dashboard' if not provided
$action = $_GET['action'] ?? ($_POST['action'] ?? 'dashboard');

$successMsg = $errorMsg = '';

// Table configuration
$tables = ['children' => ['table' => 'tblchildren', 
                           'id' => 'ChildID',
                           'fields' => ['fullName', 'dob', 'specialNeeds', 'EmergName', 'EmergNum']], 
                           
           'guardians' => ['table' => 'tblguardians',
                           'id' => 'guardianID', 
                           'fields' => ['fullName', 'email', 'phoneNum', 'address'], 'hasPassword' => true], 
                           
            'drivers' => ['table' => 'tbldriver', 
                                        'id' => 'driverID', 
                                        'fields' => ['fullName', 'email', 'phoneNum', 'LicenseNum', 'vehicleType'], 'hasPassword' => true], 
                           
            'routes' => ['table' => 'tblroutes',
                          'id' => 'routeID', 
                          'fields' => ['routeName', 'pickup', 'dropoff', 'routeStatus']]];


    // retrieves the schema configuration for a given admin type (like 'children', 'guardians', etc.) from the $tables array. It normalizes the type name to ensure consistency and returns the corresponding configuration or null if not found.
function getTableConfig($type) {
    global $tables;
    return $tables[normalizeAdminType($type)] ?? null;
}

//This strips prefixes like 'add_', 'edit_', or 'delete_' from the action type and maps singular forms to their plural counterparts (e.g., 'child' to 'children'). It ensures that the admin actions are consistently referenced in the code.
function normalizeAdminType($type) {
    $type = preg_replace('/^(add|edit|delete)_?/', '', $type);
    $singularTypes = [
        'child' => 'children',
        'guardian' => 'guardians',
        'driver' => 'drivers',
        'route' => 'routes',
    ];
    return $singularTypes[$type] ?? $type;
}

//executes the query to count total records in a given table and returns the count. It uses the global $conn variable for database connection and fetches the count from the result set.
function getCount($table) {
    global $conn;
    return $conn->query("SELECT COUNT(*) as c FROM $table")->fetch_assoc()['c'];
}

// logs an admin activity in the database. It uses the global $conn variable for database connection and prepares an INSERT statement to add the activity record.
function recordAdminActivity($activity) {
    global $conn;
    $stmt = $conn->prepare('INSERT INTO tbladminactivity (adminID, activity) VALUES (?, ?)');
    if ($stmt === false) {
        error_log('Unable to prepare admin activity log: ' . $conn->error);
        return false;
    }

// It retrieves the current admin's ID from the session and binds it along with the activity description to the prepared statement. It then executes the statement and logs any errors if the execution fails.
    $adminID = (int) $_SESSION['adminID'];
    $stmt->bind_param('is', $adminID, $activity);
    $logged = $stmt->execute();
    if (!$logged) {
        error_log('Unable to write admin activity log: ' . $stmt->error);
    }
    $stmt->close();
    return $logged;
}

// Handle POST requests

//The code checks if the request method is POST, indicating that a form has been submitted. 
//It retrieves the CSRF token from the POST data and compares it with the token stored in the session to prevent CSRF attacks. 
//If the tokens do not match, it sets an error message indicating that the form session has expired.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    // The code checks if the request method is POST, indicating that a form has been submitted.
    if (!is_string($csrfToken) || !hash_equals($_SESSION['admin_csrf'], $csrfToken)) { //csrfToken is compared with the session token to prevent CSRF attacks
        $errorMsg = 'Your form session expired. Please reload and try again.';
    } elseif ($action === 'add_incident') {
        $descriptionValue = $_POST['inDesc'] ?? '';
        $description = is_string($descriptionValue) ? trim($descriptionValue) : '';
        $emergency = $_POST['emergAlert'] ?? '';

        //This 
        if ($description === '' || strlen($description) > 5000 || !in_array($emergency, ['Yes', 'No'], true)) {
            $errorMsg = 'Enter an incident description and choose whether it is an emergency.';
        } else {
            $stmt = $conn->prepare(
                'INSERT INTO tblincidents (inDesc, inDate, emergAlert) VALUES (?, NOW(), ?)'
            );
            if ($stmt === false) {
                error_log('Unable to prepare incident insert: ' . $conn->error);
                $errorMsg = 'Unable to add the incident. Please try again.';
            } else {
                $stmt->bind_param('ss', $description, $emergency);
                if ($stmt->execute()) {
                    $incidentID = $stmt->insert_id;
                    $successMsg = 'Incident added.';
                    if (!recordAdminActivity("Added incident report #{$incidentID}")) {
                        $errorMsg = 'Incident added, but its activity could not be recorded.';
                    }
                } else {
                    error_log('Unable to add incident: ' . $stmt->error);
                    $errorMsg = 'Unable to add the incident. Please try again.';
                }
                $stmt->close();
            }
        }
    } elseif (strpos($action, 'delete_') === 0) {
        $type = normalizeAdminType($action);
        $config = getTableConfig($type);

// Delete record from the table
        if ($config) {
            $sql = "DELETE FROM {$config['table']} WHERE {$config['id']}=?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                $errorMsg = 'Unable to delete the record. Please try again.';
            } else {
                $recordID = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
                if ($recordID !== false && $recordID !== null && $recordID > 0) {
                    $stmt->bind_param('i', $recordID);
                }
                if ($recordID !== false && $recordID !== null && $recordID > 0 && $stmt->execute()) {
                    $successMsg = 'Record deleted!';
                    if (!recordAdminActivity("Deleted {$type} record #{$recordID}")) {
                        $errorMsg = 'Record deleted, but its activity could not be recorded.';
                    }
                } else {
                    $errorMsg = 'Unable to delete the record. Please try again.';
                }
                $stmt->close();
            }
        }
        //
    } elseif (in_array(normalizeAdminType($action), array_keys($tables), true)) {
        $type = normalizeAdminType($action);
        $config = getTableConfig($type);

        if ($config) {
            $isInsert = strpos($action, 'add_') === 0;
            $id = $_POST[$config['id']] ?? null;
            
            if ($isInsert && isset($config['hasPassword']) && !($_POST['password'] ?? null)) {
                $errorMsg = 'Password required';
            } else {
                $fields = $config['fields'];
                if (isset($config['hasPassword']) && (!$isInsert && !($_POST['password'] ?? null))) {
                    $fields = array_diff($fields, ['password']);
                }
                
                $values = [];
                foreach ($fields as $f) {
                    if ($f === 'password') {
                        $values[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    } else {
                        $values[] = $_POST[$f] ?? null;
                    }
                }
        // Preparing SQL statement        
                if ($isInsert) {
                    $sql = "INSERT INTO {$config['table']} (" . implode(',', $fields) . ") VALUES (" . str_repeat('?,', count($fields)-1) . "?)";
                } else {
                    $sql = "UPDATE {$config['table']} SET " . implode('=?,', $fields) . "=? WHERE {$config['id']}=?";
                    $values[] = $id;
                }
                
                // Execute the prepared statement
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    $errorMsg = 'Unable to save the record. Please try again.';
                } else {
                    $types = str_repeat('s', count($values)-1) . 'i';
                    $stmt->bind_param($types, ...$values);
                    if ($stmt->execute()) {
                        $recordID = $isInsert ? $stmt->insert_id : (int) $id;
                        $successMsg = ucfirst($type) . ($isInsert ? ' added!' : ' updated!');
                        $verb = $isInsert ? 'Added' : 'Updated';
                        if (!recordAdminActivity("{$verb} {$type} record #{$recordID}")) {
                            $errorMsg = ucfirst($type) . ' saved, but its activity could not be recorded.';
                        }
                    } else {
                        error_log('Unable to save admin record: ' . $stmt->error);
                        $errorMsg = 'Unable to save the record. Please try again.';
                    }
                    $stmt->close();
                }
            }
        }
    }
}
//Adds Recent Activites
$recentActivities = null;
if ($action === 'dashboard') {
    $recentActivities = $conn->query(
        'SELECT a.activity, a.createdAt, ad.fullName
         FROM tbladminactivity AS a
         LEFT JOIN tbladmin AS ad ON ad.adminID = a.adminID
         ORDER BY a.createdAt DESC, a.activityID DESC
         LIMIT 8'
    );
    if ($recentActivities === false) {
        error_log('Unable to load recent admin activity: ' . $conn->error);
        http_response_code(500);
        exit('Unable to load the admin dashboard.');
    }
}

// Get edit data
$editData = null;
if (isset($_GET['edit_id'], $_GET['edit_table'])) {
    $config = getTableConfig($_GET['edit_table']);
    if ($config) {
        $sql = "SELECT * FROM {$config['table']} WHERE {$config['id']}=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $_GET['edit_id']);
        $stmt->execute();
        $editData = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Little Kids Transport</title>
    <link rel="stylesheet" href="Style.css">
</head>
<body>
    <header>
        <div class="logo">Little Kids Transport</div>
        <nav>
            <a href="Admin.php">Admin Panel</a>
            <a href="AdminRegister.php">Register Admin</a>
            <a href="AdminLogin.php?logout=1">Logout</a>
        </nav>
    </header>

    <div class="container" id="admin-layout">
        <aside class="sidebar">
            <button type="button" class="sidebar-toggle" id="sidebar-toggle"
                    aria-label="Collapse sidebar" aria-expanded="true" aria-controls="sidebar-menu">
                <span aria-hidden="true">☰</span>
            </button>
            <div class="sidebar-content" id="sidebar-content">
                <h3>Menu</h3>
                <ul id="sidebar-menu">
                    <?php foreach(['dashboard'=>'Dashboard',
                                   'children'=>'Children',
                                   'guardians'=>'Guardians',
                                   'drivers'=>'Drivers',
                                   'routes'=>'Routes',
                                   'incidents'=>'Incidents'] as $a=>$l): ?>

                    <li><a href="Admin.php?action=<?=$a?>" class="<?=$action==$a?'active':''?>"><?=$l?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </aside>

        <!-- Main content area -->
        <main class="main-content">
            <?php if ($successMsg): ?><div class="alert success"><?=$successMsg?></div><?php endif; ?>
            <?php if ($errorMsg): ?><div class="alert error"><?=$errorMsg?></div><?php endif; ?>

            <?php if ($action === 'dashboard'): ?>
                <h2>Dashboard</h2>
                <div class="dashboard-grid">
                    <?php foreach(['children'=>'Children','guardians'=>'Guardians','drivers'=>'Drivers','routes'=>'Routes'] as $t=>$l): $c = getTableConfig($t); ?>
                    <div class="card"><h4>Total <?=$l?></h4><div class="number"><?=getCount($c['table'])?></div></div>
                    <?php endforeach; ?>
                </div>
                <section class="recent-activities">
                    <h3>Recent Activities</h3>
                    <?php if ($recentActivities->num_rows === 0): ?>
                        <p>No recent activities yet.</p>
                    <?php else: ?>
                        <ul>
                            <?php while ($activity = $recentActivities->fetch_assoc()): ?>
                                <li>
                                    <span><?= htmlspecialchars($activity['activity'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <small>
                                        <?= htmlspecialchars($activity['fullName'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?>
                                        · <?= htmlspecialchars($activity['createdAt'], ENT_QUOTES, 'UTF-8') ?>
                                    </small>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php endif; ?>
                </section>

            <?php elseif ($action === 'children'): $cfg = getTableConfig('children'); ?>

                <h2>Manage Children</h2>
                <div class="btn-group"><button class="btn" onclick="toggleForm()">+ Add Child</button></div>
                <div id="form-container" class="admin-form-container" style="display:none;">

                    <form method="POST" class="admin-form">
                        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['admin_csrf'], ENT_QUOTES, 'UTF-8')?>">
                        <input type="hidden" name="action" value="<?=isset($_GET['edit_id'])?'edit_child':'add_child'?>">
                        <?php if(isset($_GET['edit_id'])): ?><input type="hidden" name="childID" value="<?=$_GET['edit_id']?>"><?php endif; ?>
                        <div class="form-row">

                            <div>
                                <label>Full Name:</label><input type="text" name="fullName" required value="<?=$editData['fullName']??''?>">
                            </div>

                            <div>
                                <label>DOB:</label><input type="date" name="dob" required value="<?=$editData['dob']??''?>">
                            </div>
                        </div>

                        //form fields for special needs and emergency contact information
                        <div class="form-row">
                            <div>
                                <label>Special Needs:</label><textarea name="specialNeeds"><?=$editData['specialNeeds']??''?></textarea>
                            </div>
                            <div>
                                <label>Emergency Contact:</label><input type="text" name="EmergName" required value="<?=$editData['EmergName']??''?>">
                            </div>
                        </div>
                        <div>
                            <label>Emergency Phone:</label><input type="tel" name="EmergNum" required value="<?=$editData['EmergNum']??''?>">
                        </div>
                        <button type="submit" class="btn btn-success">Save</button>
                        <button type="button" class="btn btn-secondary" onclick="toggleForm()">Cancel</button>
                    </form>
                </div>
                <table class="admin-table"><thead>
                    <tr><th>ID</th>
                    <th>Name</th>
                    <th>DOB</th>
                    <th>Needs</th>
                    <th>Contact</th>
                    <th>Actions</th>
                </tr></thead><tbody>

                    <?php $r = $conn->query("SELECT * FROM {$cfg['table']}"); while($row = $r->fetch_assoc()): ?>
                    <tr>
                        <td><?=$row['ChildID']?></td>
                        <td><?=$row['fullName']?></td>
                        <td><?=$row['dob']?></td>
                        <td><?=$row['specialNeeds']?></td>
                        <td><?=$row['EmergName']?> - <?=$row['EmergNum']?></td>
                        <td class="actions">
                            <a href="Admin.php?action=children&edit_id=<?=$row['ChildID']?>&edit_table=children" class="btn">Edit</a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete?')"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['admin_csrf'], ENT_QUOTES, 'UTF-8')?>"><input type="hidden" name="action" value="delete_child"><input type="hidden" name="id" value="<?=$row['ChildID']?>"><button type="submit" class="btn btn-danger">Delete</button></form>
                        </td>
                    </tr>

                    <?php endwhile; ?>
                </tbody></table>

            <?php elseif ($action === 'guardians'): $cfg = getTableConfig('guardians'); ?>

                <h2>Manage Guardians</h2>
                <div class="btn-group"><button class="btn" onclick="toggleForm()">+ Add Guardian</button></div>
                <div id="form-container" class="admin-form-container" style="display:none;">

                    <form method="POST" class="admin-form">
                        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['admin_csrf'], ENT_QUOTES, 'UTF-8')?>">
                        <input type="hidden" name="action" value="<?=isset($_GET['edit_id'])?'edit_guardian':'add_guardian'?>">
                        <?php if(isset($_GET['edit_id'])): ?><input type="hidden" name="guardianID" value="<?=$_GET['edit_id']?>"><?php endif; ?>
                       
                            <div class="form-row">
                            <div>
                                <label>Full Name:</label>
                                <input type="text" name="fullName" required value="<?=$editData['fullName']??''?>">
                            </div>
                            <div>
                                <label>Email:</label>
                                <input type="email" name="email" required value="<?=$editData['email']??''?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div>
                                <label>Phone:</label><input type="tel" name="phoneNum" required value="<?=$editData['phoneNum']??''?>">
                            </div>
                            <div>
                                <label><?=isset($_GET['edit_id'])?'Password (opt)':'Password'?>:</label>
                                <input type="password" name="password" <?=!isset($_GET['edit_id'])?'required':''?>>
                             </div>
                        </div>

                        <div>
                            <label>Address:</label><textarea name="address" required><?=$editData['address']??''?></textarea>
                        </div>

                        <button type="submit" class="btn btn-success">Save</button>
                        <button type="button" class="btn btn-secondary" onclick="toggleForm()">Cancel</button>
                    </form>
                </div>
                <table class="admin-table"><thead><tr><th>ID</th>
                                                      <th>Name</th>
                                                      <th>Email</th>
                                                      <th>Phone</th>
                                                      <th>Address</th>
                                                      <th>Actions</th>
                                                    </tr></thead><tbody>

                    <?php $r = $conn->query("SELECT * FROM {$cfg['table']}"); while($row = $r->fetch_assoc()): ?>
                    <tr><td>
                        <?=$row['guardianID']?></td><td>
                            <?=$row['fullName']?></td><td
                            ><?=$row['email']?></td><td>
                                <?=$row['phoneNum']?></td><td>
                                    <?=$row['address']?></td><td class="actions">

                        <a href="Admin.php?action=guardians&edit_id=<?=$row['guardianID']?>&edit_table=guardians" class="btn">Edit</a>

                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete?')">
                            <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['admin_csrf'], ENT_QUOTES, 'UTF-8')?>">
                            <input type="hidden" name="action" value="delete_guardian">
                            <input type="hidden" name="id" value="<?=$row['guardianID']?>">
                            <button type="submit" class="btn btn-danger">Delete</button></form>
                    </td></tr>
                    <?php endwhile; ?>
                </tbody></table>

            <?php elseif ($action === 'drivers'): $cfg = getTableConfig('drivers'); ?>

                <h2>Manage Drivers</h2>
                <div class="btn-group"><button class="btn" onclick="toggleForm()">+ Add Driver</button></div>
                <div id="form-container" class="admin-form-container" style="display:none;">
                    <form method="POST" class="admin-form">
                        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['admin_csrf'], ENT_QUOTES, 'UTF-8')?>">
                        <input type="hidden" name="action" value="<?=isset($_GET['edit_id'])?'edit_driver':'add_driver'?>">
                        <?php if(isset($_GET['edit_id'])): ?><input type="hidden" name="driverID" value="<?=$_GET['edit_id']?>"><?php endif; ?>
                        <div class="form-row">
                            <div>
                                <label>Full Name:</label>
                                <input type="text" name="fullName" required value="<?=$editData['fullName']??''?>">
                            </div>
                            <div>
                                <label>Email:</label>
                                <input type="email" name="email" required value="<?=$editData['email']??''?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div><label>Phone:</label><input type="tel" name="phoneNum" required value="<?=$editData['phoneNum']??''?>"></div>
                            <div><label>License:</label><input type="text" name="LicenseNum" required value="<?=$editData['LicenseNum']??''?>"></div>
                        </div>
                        <div class="form-row">
                            <div><label>Vehicle:</label><input type="text" name="vehicleType" required value="<?=$editData['vehicleType']??''?>"></div>
                            <div><label><?=isset($_GET['edit_id'])?'Password (opt)':'Password'?>:</label><input type="password" name="password" <?=!isset($_GET['edit_id'])?'required':''?>></div>
                        </div>
                        <button type="submit" class="btn btn-success">Save</button>
                        <button type="button" class="btn btn-secondary" onclick="toggleForm()">Cancel</button>
                    </form>

                </div>
                <table class="admin-table"><thead><tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>License</th>
                    <th>Vehicle</th>
                    <th>Actions</th>
                </tr></thead><tbody>
                    <?php $r = $conn->query("SELECT * FROM {$cfg['table']}"); while($row = $r->fetch_assoc()): ?>
                    <tr><td><?=$row['driverID']?></td><td><?=$row['fullName']?></td><td><?=$row['email']?></td><td><?=$row['phoneNum']?></td><td><?=$row['LicenseNum']?></td><td><?=$row['vehicleType']?></td><td class="actions">
                        <a href="Admin.php?action=drivers&edit_id=<?=$row['driverID']?>&edit_table=drivers" class="btn">Edit</a>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete?')">
                            <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['admin_csrf'], ENT_QUOTES, 'UTF-8')?>">
                            <input type="hidden" name="action" value="delete_driver">
                            <input type="hidden" name="id" value="<?=$row['driverID']?>">
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </td></tr>
                    <?php endwhile; ?>
                </tbody></table>

            <?php elseif ($action === 'routes'): $cfg = getTableConfig('routes'); ?>
                <h2>Manage Routes</h2>

                <div class="btn-group"><button class="btn" onclick="toggleForm()">+ Add Route</button></div>
                <div id="form-container" class="admin-form-container" style="display:none;">
                    <form method="POST" class="admin-form">
                        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['admin_csrf'], ENT_QUOTES, 'UTF-8')?>">
                        <input type="hidden" name="action" value="<?=isset($_GET['edit_id'])?'edit_route':'add_route'?>">
                        
                        <?php if(isset($_GET['edit_id'])): ?><input type="hidden" name="routeID" value="<?=$_GET['edit_id']?>"><?php endif; ?>
                        <div class="form-row">
                            <div><label>Route Name:</label><input type="text" name="routeName" required value="<?=$editData['routeName']??''?>"></div>
                            <div><label>Pickup:</label><input type="text" name="pickup" required value="<?=$editData['pickup']??''?>"></div>
                        </div>
                        <div class="form-row">
                            <div><label>Dropoff:</label><input type="text" name="dropoff" required value="<?=$editData['dropoff']??''?>"></div>
                            <div><label>Status:</label><select name="routeStatus"><option value="Active" <?=($editData['routeStatus']??'')=='Active'?'selected':''?>>Active</option><option value="Inactive" <?=($editData['routeStatus']??'')=='Inactive'?'selected':''?>>Inactive</option></select></div>
                        </div>
                        <button type="submit" class="btn btn-success">Save</button>
                        <button type="button" class="btn btn-secondary" onclick="toggleForm()">Cancel</button>
                    </form>
               
                </div>
                <table class="admin-table"><thead><tr><th>ID</th><th>Name</th><th>Pickup</th><th>Dropoff</th><th>Status</th><th>Actions</th></tr></thead><tbody>
                    
                <?php $r = $conn->query("SELECT * FROM {$cfg['table']}"); while($row = $r->fetch_assoc()): ?>
                    <tr><td><?=$row['routeID']?></td><
                    td><?=$row['routeName']?></td>
                    <td><?=$row['pickup']?></td>
                    <td><?=$row['dropoff']?></td>

                    <td><span style="background:<?=$row['routeStatus']=='Active'?'#27ae60':'#e74c3c'?>;color:white;padding:5px 10px;border-radius:3px;"><?=$row['routeStatus']?></span></td><td class="actions">
                        <a href="Admin.php?action=routes&edit_id=<?=$row['routeID']?>&edit_table=routes" class="btn">Edit</a>
                        
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete?')">
                            <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['admin_csrf'], ENT_QUOTES, 'UTF-8')?>">
                            <input type="hidden" name="action" value="delete_route">
                            <input type="hidden" name="id" value="<?=$row['routeID']?>">
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </td></tr>
                    <?php endwhile; ?>
                </tbody></table>

            <?php elseif ($action === 'incidents'): ?>

                <h2>Incidents Report</h2>
                <div class="admin-form-container">
                    <h3>Add Incident</h3>
                    <form method="POST" class="admin-form">
                        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['admin_csrf'], ENT_QUOTES, 'UTF-8')?>">
                        <input type="hidden" name="action" value="add_incident">
                        <div>
                            <label for="incident-description">Description:</label>
                            <textarea id="incident-description" name="inDesc" maxlength="5000" required></textarea>
                        </div>
                        <div>
                            <label for="incident-emergency">Emergency:</label>
                            <select id="incident-emergency" name="emergAlert" required>
                                <option value="No">No</option>
                                <option value="Yes">Yes</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success">Add Incident</button>
                    </form>
                </div>
                <table class="admin-table"><thead><tr>
                    <th>ID</th>
                    <th>Description</th>
                    <th>Date/Time</th>
                    <th>Emergency</th>
                </tr></thead><tbody>
                    
                <?php $r = $conn->query("SELECT * FROM tblincidents ORDER BY inDate DESC"); while($row = $r->fetch_assoc()): ?>
                    <tr>
                        <td><?= (int) $row['incidentID'] ?></td>
                        <td><?= htmlspecialchars($row['inDesc'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($row['inDate'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span style="background:<?=$row['emergAlert']=='Yes'?'#e74c3c':'#27ae60'?>;color:white;padding:5px 10px;border-radius:3px;"><?= htmlspecialchars($row['emergAlert'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody></table>
            <?php endif; ?>
        </main>
    </div>

    <script>
        function toggleForm(){const f=document.getElementById('form-container');f.style.display=f.style.display==='none'?'block':'none';}
        <?php if(isset($_GET['edit_id'])): ?>document.getElementById('form-container').style.display='block';<?php endif; ?>
        setTimeout(()=>document.querySelectorAll('.alert').forEach(e=>e.style.display='none'),5000);

        const adminLayout = document.getElementById('admin-layout');
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebarContent = document.getElementById('sidebar-content');
        const mobileSidebar = window.matchMedia('(max-width: 768px)');

        function setSidebarCollapsed(collapsed) {
            adminLayout.classList.toggle('sidebar-collapsed', collapsed);
            sidebarContent.setAttribute('aria-hidden', String(collapsed));
            sidebarContent.inert = collapsed;
            sidebarToggle.setAttribute('aria-expanded', String(!collapsed));
            sidebarToggle.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
        }

        setSidebarCollapsed(mobileSidebar.matches);
        mobileSidebar.addEventListener('change', function (event) {
            setSidebarCollapsed(event.matches);
        });

        sidebarToggle.addEventListener('click', function () {
            const isExpanded = sidebarToggle.getAttribute('aria-expanded') === 'true';
            setSidebarCollapsed(isExpanded);
        });
    </script>
</body>
</html>
