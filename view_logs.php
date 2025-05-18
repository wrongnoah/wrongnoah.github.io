<?php
// Sicherheitscheck
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('HTTP/1.0 403 Forbidden');
    echo "Zugriff verweigert";
    exit();
}

// Log-Datei lesen
$logFile = 'app.log';
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    // HTML-Formatierung
    $logs = nl2br(htmlspecialchars($logs));
} else {
    $logs = "Keine Logs vorhanden.";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>System Logs</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .log-container {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .log-entry {
            margin: 5px 0;
            padding: 5px;
            border-bottom: 1px solid #eee;
        }
        .error {
            color: #d32f2f;
        }
        .warning {
            color: #f57c00;
        }
        .info {
            color: #1976d2;
        }
        .debug {
            color: #7b1fa2;
        }
        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class="log-container">
        <h1>System Logs</h1>
        <div class="log-entries">
            <?php echo $logs; ?>
        </div>
    </div>
</body>
</html> 