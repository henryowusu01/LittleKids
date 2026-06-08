<?php
session_start();
include 'dbConnect.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? 'dashboard');

$successMsg = $errorMsg = '';

// Table configuration
$tables = ['children' => ['table' => 'tblchildren', 'id' => 'ChildID', 'fields' => ['fullName', 'dob', 'specialNeeds', 'EmergName', 'EmergNum']], 'guardians' => ['table' => 'tblguardians', 'id' => 'guardianID', 'fields' => ['fullName', 'email', 'phoneNum', 'address'], 'hasPassword' => true], 'drivers' => ['table' => 'tbldriver', 'id' => 'driverID', 'fields' => ['fullName', 'email', 'phoneNum', 'LicenseNum', 'vehicleType'], 'hasPassword' => true], 'routes' => ['table' => 'tblroutes', 'id' => 'routeID', 'fields' => ['routeName', 'pickup', 'dropoff', 'routeStatus']]];

function getTableConfig($type) {
    global $tables;
    return $tables[str_replace('_', '', $type)] ?? null;
}

function getCount($table) {
    global $conn;
    return $conn->query("SELECT COUNT(*) as c FROM $table")->fetch_assoc()['c'];
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (strpos($action, 'delete_') === 0) {
        $type = str_replace('delete_', '', $action);
        $config = getTableConfig($type);
        if ($config) {
            $sql = "DELETE FROM {$config['table']} WHERE {$config['id']}=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $_POST['id']);
            $successMsg = $stmt->execute() ? 'Record deleted!' : 'Error: ' . $stmt->error;
            $stmt->close();
        }
    } elseif (in_array(str_replace('_', '', $action), array_keys($tables))) {
        $type = str_replace('_', '', $action);
        $config = getTableConfig($type);
        if ($config) {
            $isInsert = strpos($action, 'add') !== false;
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
                
                if ($isInsert) {
                    $sql = "INSERT INTO {$config['table']} (" . implode(',', $fields) . ") VALUES (" . str_repeat('?,', count($fields)-1) . "?)";
                } else {
                    $sql = "UPDATE {$config['table']} SET " . implode('=?,', $fields) . "=? WHERE {$config['id']}=?";
                    $values[] = $id;
                }
                
                $stmt = $conn->prepare($sql);
                $types = str_repeat('s', count($values)-1) . 'i';
                $stmt->bind_param($types, ...$values);
                $successMsg = $stmt->execute() ? ucfirst($type) . ($isInsert ? ' added!' : ' updated!') : 'Error: ' . $stmt->error;
                $stmt->close();
            }
        }
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
            <a href="index.php">Logout</a>
        </nav>
    </header>

    <div class="container">
        <aside class="sidebar">
            <h3>Menu</h3>
            <ul>
                <?php foreach(['dashboard'=>'Dashboard','children'=>'Children','guardians'=>'Guardians','drivers'=>'Drivers','routes'=>'Routes','incidents'=>'Incidents'] as $a=>$l): ?>
                <li><a href="Admin.php?action=<?=$a?>" class="<?=$action==$a?'active':''?>"><?=$l?></a></li>
                <?php endforeach; ?>
            </ul>
        </aside>

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

            <?php elseif ($action === 'children'): $cfg = getTableConfig('children'); ?>
                <h2>Manage Children</h2>
                <div class="btn-group"><button class="btn" onclick="toggleForm()">+ Add Child</button></div>
                <div id="form-container" class="admin-form-container" style="display:none;">
                    <form method="POST" class="admin-form">
                        <input type="hidden" name="action" value="<?=isset($_GET['edit_id'])?'edit_child':'add_child'?>">
                        <?php if(isset($_GET['edit_id'])): ?><input type="hidden" name="childID" value="<?=$_GET['edit_id']?>"><?php endif; ?>
                        <div class="form-row">
                            <div><label>Full Name:</label><input type="text" name="fullName" required value="<?=$editData['fullName']??''?>"></div>
                            <div><label>DOB:</label><input type="date" name="dob" required value="<?=$editData['dob']??''?>"></div>
                        </div>
                        <div class="form-row">
                            <div><label>Special Needs:</label><textarea name="specialNeeds"><?=$editData['specialNeeds']??''?></textarea></div>
                            <div><label>Emergency Contact:</label><input type="text" name="EmergName" required value="<?=$editData['EmergName']??''?>"></div>
                        </div>
                        <div><label>Emergency Phone:</label><input type="tel" name="EmergNum" required value="<?=$editData['EmergNum']??''?>"></div>
                        <button type="submit" class="btn btn-success">Save</button>
                        <button type="button" class="btn btn-secondary" onclick="toggleForm()">Cancel</button>
                    </form>
                </div>
                <table class="admin-table"><thead><tr><th>ID</th><th>Name</th><th>DOB</th><th>Needs</th><th>Contact</th><th>Actions</th></tr></thead><tbody>
                    <?php $r = $conn->query("SELECT * FROM {$cfg['table']}"); while($row = $r->fetch_assoc()): ?>
                    <tr><td><?=$row['ChildID']?></td><td><?=$row['fullName']?></td><td><?=$row['dob']?></td><td><?=$row['specialNeeds']?></td><td><?=$row['EmergName']?> - <?=$row['EmergNum']?></td><td class="actions">
                        <a href="Admin.php?action=children&edit_id=<?=$row['ChildID']?>&edit_table=children" class="btn">Edit</a>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete?')"><input type="hidden" name="action" value="delete_child"><input type="hidden" name="id" value="<?=$row['ChildID']?>"><button type="submit" class="btn btn-danger">Delete</button></form>
                    </td></tr>
                    <?php endwhile; ?>
                </tbody></table>

            <?php elseif ($action === 'guardians'): $cfg = getTableConfig('guardians'); ?>
                <h2>Manage Guardians</h2>
                <div class="btn-group"><button class="btn" onclick="toggleForm()">+ Add Guardian</button></div>
                <div id="form-container" class="admin-form-container" style="display:none;">
                    <form method="POST" class="admin-form">
                        <input type="hidden" name="action" value="<?=isset($_GET['edit_id'])?'edit_guardian':'add_guardian'?>">
                        <?php if(isset($_GET['edit_id'])): ?><input type="hidden" name="guardianID" value="<?=$_GET['edit_id']?>"><?php endif; ?>
                        <div class="form-row">
                            <div><label>Full Name:</label><input type="text" name="fullName" required value="<?=$editData['fullName']??''?>"></div>
                            <div><label>Email:</label><input type="email" name="email" required value="<?=$editData['email']??''?>"></div>
                        </div>
                        <div class="form-row">
                            <div><label>Phone:</label><input type="tel" name="phoneNum" required value="<?=$editData['phoneNum']??''?>"></div>
                            <div><label><?=isset($_GET['edit_id'])?'Password (opt)':'Password'?>:</label><input type="password" name="password" <?=!isset($_GET['edit_id'])?'required':''?>></div>
                        </div>
                        <div><label>Address:</label><textarea name="address" required><?=$editData['address']??''?></textarea></div>
                        <button type="submit" class="btn btn-success">Save</button>
                        <button type="button" class="btn btn-secondary" onclick="toggleForm()">Cancel</button>
                    </form>
                </div>
                <table class="admin-table"><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Address</th><th>Actions</th></tr></thead><tbody>
                    <?php $r = $conn->query("SELECT * FROM {$cfg['table']}"); while($row = $r->fetch_assoc()): ?>
                    <tr><td><?=$row['guardianID']?></td><td><?=$row['fullName']?></td><td><?=$row['email']?></td><td><?=$row['phoneNum']?></td><td><?=$row['address']?></td><td class="actions">
                        <a href="Admin.php?action=guardians&edit_id=<?=$row['guardianID']?>&edit_table=guardians" class="btn">Edit</a>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete?')"><input type="hidden" name="action" value="delete_guardian"><input type="hidden" name="id" value="<?=$row['guardianID']?>"><button type="submit" class="btn btn-danger">Delete</button></form>
                    </td></tr>
                    <?php endwhile; ?>
                </tbody></table>

            <?php elseif ($action === 'drivers'): $cfg = getTableConfig('drivers'); ?>
                <h2>Manage Drivers</h2>
                <div class="btn-group"><button class="btn" onclick="toggleForm()">+ Add Driver</button></div>
                <div id="form-container" class="admin-form-container" style="display:none;">
                    <form method="POST" class="admin-form">
                        <input type="hidden" name="action" value="<?=isset($_GET['edit_id'])?'edit_driver':'add_driver'?>">
                        <?php if(isset($_GET['edit_id'])): ?><input type="hidden" name="driverID" value="<?=$_GET['edit_id']?>"><?php endif; ?>
                        <div class="form-row">
                            <div><label>Full Name:</label><input type="text" name="fullName" required value="<?=$editData['fullName']??''?>"></div>
                            <div><label>Email:</label><input type="email" name="email" required value="<?=$editData['email']??''?>"></div>
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
                <table class="admin-table"><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>License</th><th>Vehicle</th><th>Actions</th></tr></thead><tbody>
                    <?php $r = $conn->query("SELECT * FROM {$cfg['table']}"); while($row = $r->fetch_assoc()): ?>
                    <tr><td><?=$row['driverID']?></td><td><?=$row['fullName']?></td><td><?=$row['email']?></td><td><?=$row['phoneNum']?></td><td><?=$row['LicenseNum']?></td><td><?=$row['vehicleType']?></td><td class="actions">
                        <a href="Admin.php?action=drivers&edit_id=<?=$row['driverID']?>&edit_table=drivers" class="btn">Edit</a>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete?')"><input type="hidden" name="action" value="delete_driver"><input type="hidden" name="id" value="<?=$row['driverID']?>"><button type="submit" class="btn btn-danger">Delete</button></form>
                    </td></tr>
                    <?php endwhile; ?>
                </tbody></table>

            <?php elseif ($action === 'routes'): $cfg = getTableConfig('routes'); ?>
                <h2>Manage Routes</h2>
                <div class="btn-group"><button class="btn" onclick="toggleForm()">+ Add Route</button></div>
                <div id="form-container" class="admin-form-container" style="display:none;">
                    <form method="POST" class="admin-form">
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
                    <tr><td><?=$row['routeID']?></td><td><?=$row['routeName']?></td><td><?=$row['pickup']?></td><td><?=$row['dropoff']?></td><td><span style="background:<?=$row['routeStatus']=='Active'?'#27ae60':'#e74c3c'?>;color:white;padding:5px 10px;border-radius:3px;"><?=$row['routeStatus']?></span></td><td class="actions">
                        <a href="Admin.php?action=routes&edit_id=<?=$row['routeID']?>&edit_table=routes" class="btn">Edit</a>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete?')"><input type="hidden" name="action" value="delete_route"><input type="hidden" name="id" value="<?=$row['routeID']?>"><button type="submit" class="btn btn-danger">Delete</button></form>
                    </td></tr>
                    <?php endwhile; ?>
                </tbody></table>

            <?php elseif ($action === 'incidents'): ?>
                <h2>Incidents Report</h2>
                <table class="admin-table"><thead><tr><th>ID</th><th>Description</th><th>Date/Time</th><th>Emergency</th></tr></thead><tbody>
                    <?php $r = $conn->query("SELECT * FROM tblincidents ORDER BY inDate DESC"); while($row = $r->fetch_assoc()): ?>
                    <tr><td><?=$row['incidentID']?></td><td><?=$row['inDesc']?></td><td><?=$row['inDate']?></td><td><span style="background:<?=$row['emergAlert']=='Yes'?'#e74c3c':'#27ae60'?>;color:white;padding:5px 10px;border-radius:3px;"><?=$row['emergAlert']?></span></td></tr>
                    <?php endwhile; ?>
                </tbody></table>
            <?php endif; ?>
        </main>
    </div>

    <script>
        function toggleForm(){const f=document.getElementById('form-container');f.style.display=f.style.display==='none'?'block':'none';}
        <?php if(isset($_GET['edit_id'])): ?>document.getElementById('form-container').style.display='block';<?php endif; ?>
        setTimeout(()=>document.querySelectorAll('.alert').forEach(e=>e.style.display='none'),5000);
    </script>
</body>
</html>
