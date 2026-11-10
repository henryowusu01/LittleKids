<?php
session_start();
require 'dbConnect.php';

if (!isset($_SESSION['guardianID'])) {
    header('Location: LKLogIn.php');
    exit();
}

$sampleIncidents = [
    [
        'description' => 'Sample incident: Morning traffic caused a delay on the Soweto to Johannesburg CBD route.',
        'alert' => 'Route delay',
    ],
    [
        'description' => 'Sample incident: A vehicle safety inspection delayed the Midrand to Sandton service.',
        'alert' => 'Service update',
    ],
    [
        'description' => 'Sample incident: Heavy rain required slower travel on the Centurion to Pretoria CBD route.',
        'alert' => 'Weather advisory',
    ],
];

$findIncident = $conn->prepare('SELECT incidentID FROM tblincidents WHERE inDesc = ? LIMIT 1');
$addIncident = $conn->prepare(
    'INSERT INTO tblincidents (inDesc, inDate, emergAlert) VALUES (?, NOW(), ?)'
);

if ($findIncident === false || $addIncident === false) {
    error_log('Unable to prepare sample incident operations: ' . $conn->error);
    http_response_code(500);
    exit('Unable to load incidents.');
}

foreach ($sampleIncidents as $incident) {
    $description = $incident['description'];
    $alert = $incident['alert'];
    $findIncident->bind_param('s', $description);
    if (!$findIncident->execute()) {
        error_log('Unable to check sample incident: ' . $findIncident->error);
        http_response_code(500);
        exit('Unable to load incidents.');
    }

    if ($findIncident->get_result()->num_rows === 0) {
        $addIncident->bind_param('ss', $description, $alert);
        if (!$addIncident->execute()) {
            error_log('Unable to create sample incident: ' . $addIncident->error);
            http_response_code(500);
            exit('Unable to load incidents.');
        }
    }
}
$findIncident->close();
$addIncident->close();

$result = $conn->query('SELECT inDesc, inDate, emergAlert FROM tblincidents ORDER BY inDate DESC');
if ($result === false) {
    error_log('Unable to load incidents: ' . $conn->error);
    http_response_code(500);
    exit('Unable to load incidents.');
}

function escapeIncidentValue($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transport Incidents</title>
    <link rel="stylesheet" href="Style.css">
</head>
<body>
<main class="form-container">
    <h2>Transport Incidents</h2>
    <p><a href="dashboard.php">Back to dashboard</a></p>
    <?php while ($row = $result->fetch_assoc()): ?>
        <article>
            <h3><?= escapeIncidentValue($row['emergAlert']) ?></h3>
            <p><?= escapeIncidentValue($row['inDesc']) ?></p>
            <p><time><?= escapeIncidentValue($row['inDate']) ?></time></p>
        </article>
    <?php endwhile; ?>
</main>
</body>
</html>