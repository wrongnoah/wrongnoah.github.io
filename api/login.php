<?php
// CORS-Header setzen
header("Access-Control-Allow-Origin: http://localhost:80");
header("Access-Control-Allow-Origin: https://wrongnoah.github.io");
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

// Daten aus dem Request-Body lesen
$data = json_decode(file_get_contents("php://input"));

// Prüfen, ob alle Felder vorhanden sind
if (!isset($data->username) || !isset($data->password)) {
    http_response_code(400);
    echo json_encode(["error" => "Benutzername und Passwort sind erforderlich"]);
    exit();
}

// SQL-Injection verhindern mit prepared statement
$stmt = $conn->prepare("SELECT id, username, password, role, status FROM users WHERE username = ?");
$stmt->bind_param("s", $data->username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    
    // Prüfen, ob der Benutzer zugelassen ist
    if ($user["status"] !== "approved") {
        http_response_code(403);
        echo json_encode(["error" => "Dein Konto wurde noch nicht freigegeben"]);
        exit();
    }
    
    // Passwort überprüfen
    if (password_verify($data->password, $user["password"])) {
        // Passwort ist korrekt, Benutzerinformationen zurücksenden
        
        // Passwort aus der Antwort entfernen
        unset($user["password"]);
        
        // Erfolgreiche Anmeldung
        http_response_code(200);
        echo json_encode([
            "message" => "Login erfolgreich",
            "user" => $user
        ]);
    } else {
        // Passwort ist falsch
        http_response_code(401);
        echo json_encode(["error" => "Ungültiger Benutzername oder Passwort"]);
    }
} else {
    // Benutzer nicht gefunden
    http_response_code(401);
    echo json_encode(["error" => "Ungültiger Benutzername oder Passwort"]);
}

$stmt->close();
$conn->close();
?> 