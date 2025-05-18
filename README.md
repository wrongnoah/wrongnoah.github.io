# Admin Panel Website

Eine einfache Website mit Registrierung, Anmeldung und Admin-Panel-Funktionalität.

## Funktionen

- **Anmeldung**: Benutzer können sich mit ihrem Benutzernamen und Passwort anmelden
- **Registrierung**: Neue Benutzer können sich registrieren (erfordert Admin-Genehmigung)
- **Admin-Panel**: Administratoren können Registrierungsanfragen genehmigen oder ablehnen
- **Benutzer-Verwaltung**: Übersicht über alle zugelassenen Benutzer

## Erste Schritte

1. Öffne die Datei `index.html` in einem Webbrowser.
2. Melde dich mit dem Standard-Admin-Konto an:
   - Benutzername: `admin`
   - Passwort: `123`

## Für Entwickler

Die Website speichert alle Daten im localStorage des Browsers. Keine Datenbank erforderlich.

### Dateien

- `index.html` - Login-Seite
- `register.html` - Registrierungsseite
- `admin.html` - Admin-Panel
- `styles.css` - Design der Website
- `script.js` - Funktionalität der Website

## Hinweise

- Diese Website ist ein einfaches Beispiel ohne Backend.
- Alle Daten werden lokal im Browser gespeichert und gehen beim Löschen des Browser-Cache verloren.
- Für eine richtige Produktivumgebung müsste ein sicheres Backend mit Datenbank implementiert werden. 