<?php
// CORS-Header setzen
$allowed_origins = ['http://localhost:80', 'https://wrongnoah.github.io'];
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $origin);
}
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

try {
    // Daten aus dem Request-Body lesen
    $json = file_get_contents("php://input");
    $data = json_decode($json);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Ungültiges JSON-Format");
    }

    // Prüfen, ob alle Felder vorhanden sind
    if (!isset($data->username) || !isset($data->password) || !isset($data->email)) {
        throw new Exception("Alle Felder müssen ausgefüllt werden");
    }

    // Validierung der Eingaben
    if (strlen($data->username) < 3 || strlen($data->username) > 50) {
        throw new Exception("Benutzername muss zwischen 3 und 50 Zeichen lang sein");
    }

    if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Ungültige E-Mail-Adresse");
    }

    // Passwort-Komplexität prüfen
    if (strlen($data->password) < 8) {
        throw new Exception("Passwort muss mindestens 8 Zeichen lang sein");
    }
    if (!preg_match("/[A-Z]/", $data->password)) {
        throw new Exception("Passwort muss mindestens einen Großbuchstaben enthalten");
    }
    if (!preg_match("/[a-z]/", $data->password)) {
        throw new Exception("Passwort muss mindestens einen Kleinbuchstaben enthalten");
    }
    if (!preg_match("/[0-9]/", $data->password)) {
        throw new Exception("Passwort muss mindestens eine Zahl enthalten");
    }

    // Prüfen, ob Benutzername bereits existiert
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    if (!$stmt) {
        throw new Exception("Datenbankfehler: " . $conn->error);
    }
    
    $stmt->bind_param("ss", $data->username, $data->email);
    if (!$stmt->execute()) {
        throw new Exception("Datenbankfehler: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        throw new Exception("Benutzername oder E-Mail existiert bereits");
    }
    $stmt->close();

    // Passwort hashen
    $hashed_password = password_hash($data->password, PASSWORD_DEFAULT);

    // Transaktion starten
    $conn->begin_transaction();

    try {
        // Benutzer in Datenbank einfügen
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, role, status, created_at) VALUES (?, ?, ?, 'user', 'pending', NOW())");
        if (!$stmt) {
            throw new Exception("Datenbankfehler: " . $conn->error);
        }
        
        $stmt->bind_param("sss", $data->username, $hashed_password, $data->email);
        if (!$stmt->execute()) {
            throw new Exception("Datenbankfehler: " . $stmt->error);
        }

        // Transaktion bestätigen
        $conn->commit();
        
        http_response_code(201);
        echo json_encode([
            "message" => "Benutzer erfolgreich registriert. Dein Konto muss von einem Administrator freigegeben werden."
        ]);
    } catch (Exception $e) {
        // Bei Fehler Transaktion zurückrollen
        $conn->rollback();
        throw $e;
    }
} catch (Exception $e) {
    error_log("Registrierungsfehler: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(["error" => $e->getMessage()]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}
?> 