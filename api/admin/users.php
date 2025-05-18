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

// Session starten
session_start();

// Überprüfen der Admin-Berechtigung
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["error" => "Zugriff verweigert"]);
    exit();
}

// Datenbank-Verbindung einbinden
require_once "../../config.php";

// Überprüfen der Datenbankverbindung
if (!$conn) {
    http_response_code(500);
    echo json_encode(["error" => "Datenbankverbindung fehlgeschlagen"]);
    exit();
}

try {
    // GET: Alle Benutzer abrufen
    if ($_SERVER["REQUEST_METHOD"] === "GET") {
        $stmt = $conn->prepare("SELECT id, username, email, role, status, created_at FROM users ORDER BY created_at DESC");
        if (!$stmt) {
            throw new Exception("Datenbankfehler: " . $conn->error);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Datenbankfehler: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $users = [];
        
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        
        http_response_code(200);
        echo json_encode($users);
        $stmt->close();
    }
    // PUT: Benutzerstatus aktualisieren
    else if ($_SERVER["REQUEST_METHOD"] === "PUT") {
        // Daten aus dem Request-Body lesen
        $json = file_get_contents("php://input");
        $data = json_decode($json);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Ungültiges JSON-Format");
        }
        
        // Prüfen, ob alle Felder vorhanden sind
        if (!isset($data->id) || !isset($data->status)) {
            throw new Exception("Benutzer-ID und Status sind erforderlich");
        }
        
        // Überprüfe, ob der Status gültig ist
        if (!in_array($data->status, ["pending", "approved", "rejected"])) {
            throw new Exception("Ungültiger Status");
        }
        
        // Transaktion starten
        $conn->begin_transaction();
        
        try {
            // Status aktualisieren
            $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Datenbankfehler: " . $conn->error);
            }
            
            $stmt->bind_param("si", $data->status, $data->id);
            if (!$stmt->execute()) {
                throw new Exception("Datenbankfehler: " . $stmt->error);
            }
            
            if ($stmt->affected_rows === 0) {
                throw new Exception("Benutzer nicht gefunden");
            }
            
            // Transaktion bestätigen
            $conn->commit();
            
            http_response_code(200);
            echo json_encode(["message" => "Benutzerstatus erfolgreich aktualisiert"]);
        } catch (Exception $e) {
            // Bei Fehler Transaktion zurückrollen
            $conn->rollback();
            throw $e;
        } finally {
            $stmt->close();
        }
    } else {
        throw new Exception("Methode nicht erlaubt");
    }
} catch (Exception $e) {
    error_log("Admin error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(["error" => $e->getMessage()]);
} finally {
    $conn->close();
}
?> 