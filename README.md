# Admin Panel Website

Eine einfache Website mit Registrierung, Anmeldung und Admin-Panel-Funktionalität.

## Funktionen

- **Anmeldung**: Benutzer können sich mit ihrem Benutzernamen und Passwort anmelden
- **Registrierung**: Neue Benutzer können sich registrieren (erfordert Admin-Genehmigung)
- **Admin-Panel**: Administratoren können Registrierungsanfragen genehmigen oder ablehnen
- **Benutzer-Verwaltung**: Übersicht über alle zugelassenen Benutzer
- **MySQL-Datenbank**: Persistente Datenspeicherung in einer MySQL-Datenbank

## Webseite aufrufen

Die Webseite ist über GitHub Pages verfügbar:
- https://wrongnoah.github.io

## Datenbankeinrichtung

1. Erstelle eine MySQL-Datenbank
2. Passe die Datenbankverbindungsdaten in `config.php` an:
   ```php
   $db_host = "localhost"; // Datenbankserver
   $db_user = "root";      // Datenbankbenutzer 
   $db_pass = "";          // Datenbankpasswort
   $db_name = "admin_panel"; // Datenbankname
   ```
3. Führe das Setup-Skript aus, um die Datenbankstruktur zu erstellen:
   ```
   php db_setup.php
   ```

## Erste Schritte

1. Besuche die Webseite unter https://wrongnoah.github.io
2. Melde dich mit dem Standard-Admin-Konto an:
   - Benutzername: `admin`
   - Passwort: `123`

## Für Entwickler

Die Website verwendet PHP als Backend und MySQL für die Datenspeicherung.

### Dateien

- `index.html` - Login-Seite
- `register.html` - Registrierungsseite
- `admin.html` - Admin-Panel
- `styles.css` - Design der Website
- `script.js` - Frontend-Funktionalität
- `config.php` - Datenbankverbindungskonfiguration
- `db_setup.php` - Skript zur Datenbankeinrichtung
- `api/login.php` - API-Endpunkt für Login
- `api/register.php` - API-Endpunkt für Registrierung
- `api/admin/users.php` - API-Endpunkt für Benutzerverwaltung

## GitHub Pages Einrichtung

Diese Webseite ist für die Bereitstellung über GitHub Pages konfiguriert. Nach jedem Push zum main Branch wird die Webseite automatisch aktualisiert.

Für die vollständige Funktionalität mit der MySQL-Datenbank wird jedoch ein PHP-fähiger Webserver benötigt.

## Hinweise

- Die Frontend-Oberfläche kann über GitHub Pages angezeigt werden, aber die vollständige Funktionalität (mit Datenbank) erfordert einen PHP-fähigen Webserver.
- Stelle sicher, dass die `config.php` Datei mit den richtigen Datenbankverbindungsdaten konfiguriert ist.
- Für eine Produktivumgebung sollten zusätzliche Sicherheitsmaßnahmen implementiert werden (z.B. HTTPS, sesssion token, etc.). 