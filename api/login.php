<?php
// CORS-Header setzen
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// OPTIONS-Anfragen für CORS-Preflight behandeln
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

// Nur POST-Anfragen zulassen
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "Methode nicht erlaubt"]);
    exit();
}

// Datenbank-Verbindung einbinden
require_once "../config.php";

// Überprüfen der Datenbankverbindung
if (!$conn) {
    http_response_code(500);
    echo json_encode(["error" => "Datenbankverbindung fehlgeschlagen"]);
    exit();
}

// Session starten
session_start();

// Rate Limiting
$ip = $_SERVER['REMOTE_ADDR'];
$time = time();
$timeout = 300; // 5 Minuten
$max_attempts = 5;

if (isset($_SESSION['login_attempts'][$ip])) {
    $attempts = $_SESSION['login_attempts'][$ip];
    if ($attempts['count'] >= $max_attempts && ($time - $attempts['time']) < $timeout) {
        http_response_code(429);
        echo json_encode([
            "error" => "Zu viele Anmeldeversuche. Bitte warte " . ceil(($timeout - ($time - $attempts['time'])) / 60) . " Minuten."
        ]);
        exit();
    }
    if (($time - $attempts['time']) >= $timeout) {
        unset($_SESSION['login_attempts'][$ip]);
    }
}

try {
    // Daten aus dem Request-Body lesen
    $json = file_get_contents("php://input");
    $data = json_decode($json);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Ungültiges JSON-Format");
    }

    // Prüfen, ob alle Felder vorhanden sind
    if (!isset($data->username) || !isset($data->password)) {
        throw new Exception("Benutzername und Passwort sind erforderlich");
    }

    // Validierung der Eingaben
    if (strlen($data->username) < 3 || strlen($data->username) > 50) {
        throw new Exception("Ungültiger Benutzername");
    }

    // SQL-Injection verhindern mit prepared statement
    $stmt = $conn->prepare("SELECT id, username, password, role, status FROM users WHERE username = ?");
    if (!$stmt) {
        throw new Exception("Datenbankfehler: " . $conn->error);
    }
    
    $stmt->bind_param("s", $data->username);
    if (!$stmt->execute()) {
        throw new Exception("Datenbankfehler: " . $stmt->error);
    }
    
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Prüfen, ob der Benutzer zugelassen ist
        if ($user["status"] !== "approved") {
            throw new Exception("Dein Konto wurde noch nicht freigegeben");
        }
        
        // Passwort überprüfen
        if (password_verify($data->password, $user["password"])) {
            // Passwort ist korrekt, Benutzerinformationen zurücksenden
            
            // Passwort aus der Antwort entfernen
            unset($user["password"]);
            
            // Login-Versuche zurücksetzen
            unset($_SESSION['login_attempts'][$ip]);
            
            // Session-Daten setzen
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Erfolgreiche Anmeldung
            http_response_code(200);
            echo json_encode([
                "message" => "Login erfolgreich",
                "user" => $user
            ]);
        } else {
            // Passwort ist falsch
            incrementLoginAttempts($ip, $time);
            throw new Exception("Ungültiger Benutzername oder Passwort");
        }
    } else {
        // Benutzer nicht gefunden
        incrementLoginAttempts($ip, $time);
        throw new Exception("Ungültiger Benutzername oder Passwort");
    }
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    http_response_code(401);
    echo json_encode(["error" => $e->getMessage()]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}

// Hilfsfunktion für Login-Versuche
function incrementLoginAttempts($ip, $time) {
    if (!isset($_SESSION['login_attempts'][$ip])) {
        $_SESSION['login_attempts'][$ip] = [
            'count' => 1,
            'time' => $time
        ];
    } else {
        $_SESSION['login_attempts'][$ip]['count']++;
        $_SESSION['login_attempts'][$ip]['time'] = $time;
    }
}
?> 