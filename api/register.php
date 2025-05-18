<?php
// CORS-Header setzen
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

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
if (!isset($data->username) || !isset($data->password) || !isset($data->email)) {
    http_response_code(400);
    echo json_encode(["error" => "Alle Felder müssen ausgefüllt werden"]);
    exit();
}

// Prüfen, ob Benutzername bereits existiert
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $data->username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    http_response_code(409); // Conflict
    echo json_encode(["error" => "Benutzername existiert bereits"]);
    exit();
}

// Passwort hashen
$hashed_password = password_hash($data->password, PASSWORD_DEFAULT);

// Benutzer in Datenbank einfügen
$stmt = $conn->prepare("INSERT INTO users (username, password, email, role, status) VALUES (?, ?, ?, 'user', 'pending')");
$stmt->bind_param("sss", $data->username, $hashed_password, $data->email);

if ($stmt->execute()) {
    http_response_code(201); // Created
    echo json_encode([
        "message" => "Benutzer erfolgreich registriert. Dein Konto muss von einem Administrator freigegeben werden."
    ]);
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode([
        "error" => "Fehler beim Registrieren des Benutzers"
    ]);
}

$stmt->close();
$conn->close();
?> 