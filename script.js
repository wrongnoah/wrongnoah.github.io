// API-Endpunkte
const API_URL = {
    login: 'api/login.php',
    register: 'api/register.php',
    users: 'api/admin/users.php'
};

// Event-Listener für Login-Form
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
    
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegister);
    }
    
    // Admin-Seite initialisieren, wenn wir uns auf der Admin-Seite befinden
    const adminPanel = document.getElementById('adminPanel');
    if (adminPanel) {
        initAdminPanel();
    }
    
    // Prüfen, ob der Benutzer angemeldet ist
    checkAuthStatus();
});

// Login-Handler
async function handleLogin(event) {
    event.preventDefault();
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const messageElement = document.getElementById('message');
    
    try {
        const response = await fetch(API_URL.login, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ username, password })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            // Login erfolgreich
            messageElement.textContent = data.message;
            messageElement.className = 'message success';
            
            // User-Daten im Session Storage speichern
            sessionStorage.setItem('currentUser', JSON.stringify(data.user));
            
            // Weiterleitung zur entsprechenden Seite
            if (data.user.role === 'admin') {
                window.location.href = 'admin.html';
            } else {
                // Hier könnte eine User-Seite sein
                window.location.href = 'index.html';
            }
        } else {
            // Login fehlgeschlagen
            messageElement.textContent = data.error;
            messageElement.className = 'message error';
        }
    } catch (error) {
        console.error('Login-Fehler:', error);
        messageElement.textContent = 'Ein Fehler ist aufgetreten. Bitte versuche es später erneut.';
        messageElement.className = 'message error';
    }
}

// Registrierung-Handler
async function handleRegister(event) {
    event.preventDefault();
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const email = document.getElementById('email').value;
    const messageElement = document.getElementById('message');
    
    try {
        const response = await fetch(API_URL.register, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ username, password, email })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            // Registrierung erfolgreich
            messageElement.textContent = data.message;
            messageElement.className = 'message success';
            
            // Nach 3 Sekunden zur Login-Seite weiterleiten
            setTimeout(() => {
                window.location.href = 'index.html';
            }, 3000);
        } else {
            // Registrierung fehlgeschlagen
            messageElement.textContent = data.error;
            messageElement.className = 'message error';
        }
    } catch (error) {
        console.error('Registrierungs-Fehler:', error);
        messageElement.textContent = 'Ein Fehler ist aufgetreten. Bitte versuche es später erneut.';
        messageElement.className = 'message error';
    }
}

// Admin-Panel initialisieren
async function initAdminPanel() {
    const currentUser = JSON.parse(sessionStorage.getItem('currentUser'));
    
    // Prüfen ob Benutzer angemeldet ist und Admin-Rechte hat
    if (!currentUser || currentUser.role !== 'admin') {
        window.location.href = 'index.html';
        return;
    }
    
    // Admin-Name anzeigen
    const adminNameElement = document.getElementById('adminName');
    if (adminNameElement) {
        adminNameElement.textContent = currentUser.username;
    }
    
    // Benutzer laden
    loadUsers();
}

// Benutzer laden
async function loadUsers() {
    const pendingUsersTable = document.getElementById('pendingUsers');
    const approvedUsersTable = document.getElementById('approvedUsers');
    
    try {
        const response = await fetch(API_URL.users, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        const users = await response.json();
        
        if (response.ok) {
            // Tabellen leeren
            pendingUsersTable.querySelector('tbody').innerHTML = '';
            approvedUsersTable.querySelector('tbody').innerHTML = '';
            
            // Benutzer nach Status filtern und anzeigen
            users.forEach(user => {
                if (user.status === 'pending') {
                    appendUserToTable(user, pendingUsersTable);
                } else if (user.status === 'approved') {
                    appendUserToTable(user, approvedUsersTable);
                }
            });
        }
    } catch (error) {
        console.error('Fehler beim Laden der Benutzer:', error);
    }
}

// Benutzer zur Tabelle hinzufügen
function appendUserToTable(user, table) {
    const tbody = table.querySelector('tbody');
    const row = document.createElement('tr');
    
    // Zeile mit Benutzerdaten füllen
    row.innerHTML = `
        <td>${user.username}</td>
        <td>${user.email}</td>
        <td>${formatDate(user.created_at)}</td>
        <td class="actions">
            ${user.status === 'pending' ? 
                `<button class="btn approve" data-id="${user.id}">Genehmigen</button>
                 <button class="btn reject" data-id="${user.id}">Ablehnen</button>` :
                `<span class="status ${user.status}">${user.status === 'approved' ? 'Genehmigt' : 'Abgelehnt'}</span>`
            }
        </td>
    `;
    
    // Event-Listener für die Buttons hinzufügen
    if (user.status === 'pending') {
        row.querySelector('.approve').addEventListener('click', () => {
            updateUserStatus(user.id, 'approved');
        });
        
        row.querySelector('.reject').addEventListener('click', () => {
            updateUserStatus(user.id, 'rejected');
        });
    }
    
    tbody.appendChild(row);
}

// Benutzerstatus aktualisieren
async function updateUserStatus(userId, status) {
    try {
        const response = await fetch(API_URL.users, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: userId, status })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            // Status erfolgreich geändert, Benutzerliste neu laden
            loadUsers();
        } else {
            console.error('Fehler beim Aktualisieren des Benutzerstatus:', data.error);
        }
    } catch (error) {
        console.error('Fehler beim Aktualisieren des Benutzerstatus:', error);
    }
}

// Datum formatieren
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Prüfen, ob der Benutzer angemeldet ist
function checkAuthStatus() {
    const currentUser = sessionStorage.getItem('currentUser');
    const isLoginPage = window.location.pathname.endsWith('index.html') || 
                        window.location.pathname === '/' || 
                        window.location.pathname === '';
    const isRegisterPage = window.location.pathname.endsWith('register.html');
    
    if (!currentUser) {
        // Nicht angemeldet, zur Login-Seite weiterleiten, wenn nicht bereits dort oder auf der Registrierungsseite
        if (!isLoginPage && !isRegisterPage) {
            window.location.href = 'index.html';
        }
    } else {
        // Angemeldet, vom Login wegnavigieren
        const user = JSON.parse(currentUser);
        
        if (isLoginPage || isRegisterPage) {
            // Weiterleiten basierend auf der Rolle
            if (user.role === 'admin') {
                window.location.href = 'admin.html';
            } else {
                // Hier könnte man zu einer Benutzer-Seite weiterleiten
                window.location.href = 'admin.html';
            }
        }
    }
}

// Abmelden
function logout() {
    sessionStorage.removeItem('currentUser');
    window.location.href = 'index.html';
}

// Event-Listener für Logout-Button
document.addEventListener('DOMContentLoaded', function() {
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', logout);
    }
}); 