<?php
// Datenbank-Konfiguration ohne Datenbanknamen
$db_host = "localhost";
$db_user = "root";
$db_pass = "";

// Verbindung herstellen
$conn = new mysqli($db_host, $db_user, $db_pass);

// Verbindung prüfen
if ($conn->connect_error) {
    die("Verbindungsfehler: " . $conn->connect_error);
}

// Datenbank erstellen
$sql = "CREATE DATABASE IF NOT EXISTS admin_panel";
if ($conn->query($sql) === TRUE) {
    echo "Datenbank erstellt oder bereits vorhanden<br>";
} else {
    echo "Fehler beim Erstellen der Datenbank: " . $conn->error . "<br>";
    die();
}

// Datenbank auswählen
$conn->select_db("admin_panel");

// Tabelle für Benutzer erstellen
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Tabelle 'users' erstellt oder bereits vorhanden<br>";
} else {
    echo "Fehler beim Erstellen der Tabelle: " . $conn->error . "<br>";
}

// Standard-Admin-Benutzer erstellen
$admin_username = "admin";
$admin_password = password_hash("123", PASSWORD_DEFAULT);
$admin_email = "admin@example.com";

// Prüfen, ob Admin bereits existiert
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $admin_username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Admin erstellen
    $stmt = $conn->prepare("INSERT INTO users (username, password, email, role, status) VALUES (?, ?, ?, 'admin', 'approved')");
    $stmt->bind_param("sss", $admin_username, $admin_password, $admin_email);
    
    if ($stmt->execute()) {
        echo "Standard-Admin-Benutzer wurde erstellt<br>";
    } else {
        echo "Fehler beim Erstellen des Admin-Benutzers: " . $stmt->error . "<br>";
    }
} else {
    echo "Admin-Benutzer existiert bereits<br>";
}

$conn->close();
echo "Datenbank-Setup abgeschlossen!";
?> 