<?php
// CORS-Header setzen
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, PUT");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Datenbank-Verbindung einbinden
require_once "../../config.php";

// GET: Alle Benutzer abrufen
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    // Überprüfe Admin-Berechtigung hier (vereinfacht dargestellt)
    // In einer echten Anwendung würde hier eine Session- oder Token-Überprüfung stattfinden
    
    // Alle Benutzer aus der Datenbank abrufen
    $sql = "SELECT id, username, email, role, status, created_at FROM users ORDER BY created_at DESC";
    $result = $conn->query($sql);
    
    if ($result) {
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        
        // Erfolgreicher Abruf
        http_response_code(200);
        echo json_encode($users);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Fehler beim Abrufen der Benutzer"]);
    }
}

// PUT: Benutzerstatus aktualisieren
else if ($_SERVER["REQUEST_METHOD"] === "PUT") {
    // Daten aus dem Request-Body lesen
    $data = json_decode(file_get_contents("php://input"));
    
    // Prüfen, ob alle Felder vorhanden sind
    if (!isset($data->id) || !isset($data->status)) {
        http_response_code(400);
        echo json_encode(["error" => "Benutzer-ID und Status sind erforderlich"]);
        exit();
    }
    
    // Überprüfe, ob der Status gültig ist
    if (!in_array($data->status, ["pending", "approved", "rejected"])) {
        http_response_code(400);
        echo json_encode(["error" => "Ungültiger Status"]);
        exit();
    }
    
    // Status aktualisieren
    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $data->status, $data->id);
    
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(["message" => "Benutzerstatus erfolgreich aktualisiert"]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Fehler beim Aktualisieren des Benutzerstatus"]);
    }
    
    $stmt->close();
} 

// Andere Methoden nicht erlaubt
else {
    http_response_code(405);
    echo json_encode(["error" => "Methode nicht erlaubt"]);
}

$conn->close();
?> 