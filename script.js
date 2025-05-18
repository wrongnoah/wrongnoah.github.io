// Überprüfen, ob wir die Seite zum ersten Mal initialisieren
if (!localStorage.getItem('initialized')) {
    // Standard-Admin erstellen
    const defaultAdmin = {
        username: 'admin',
        password: '123',
        isApproved: true,
        isAdmin: true
    };

    // Benutzer und Registrierungsanfragen als Arrays speichern
    localStorage.setItem('users', JSON.stringify([defaultAdmin]));
    localStorage.setItem('pendingRequests', JSON.stringify([]));
    localStorage.setItem('initialized', 'true');
}

// Aktuellen Benutzer beim ersten Laden auf null setzen
if (!localStorage.getItem('currentUser')) {
    localStorage.setItem('currentUser', '');
}

// Aktuelle Seite bestimmen
const currentPath = window.location.pathname;
const page = currentPath.split('/').pop();

// Funktionen für die Login-Seite
if (page === 'index.html' || page === '') {
    // Überprüfe, ob der Benutzer bereits eingeloggt ist
    if (localStorage.getItem('currentUser') && localStorage.getItem('currentUser') !== '') {
        window.location.href = 'admin.html';
    }

    const loginForm = document.getElementById('loginForm');
    const message = document.getElementById('message');

    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        
        // Benutzer aus dem localStorage holen
        const users = JSON.parse(localStorage.getItem('users'));
        
        // Benutzer suchen
        const user = users.find(u => u.username === username && u.password === password);
        
        if (user) {
            if (user.isApproved) {
                // Benutzer einloggen
                localStorage.setItem('currentUser', username);
                window.location.href = 'admin.html';
            } else {
                message.textContent = 'Dein Konto wurde noch nicht freigegeben.';
                message.className = 'message error';
            }
        } else {
            message.textContent = 'Ungültiger Benutzername oder Passwort.';
            message.className = 'message error';
        }
    });
}

// Funktionen für die Registrierungsseite
if (page === 'register.html') {
    const registerForm = document.getElementById('registerForm');
    const message = document.getElementById('message');
    
    registerForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const username = document.getElementById('newUsername').value;
        const password = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        
        // Überprüfen, ob die Passwörter übereinstimmen
        if (password !== confirmPassword) {
            message.textContent = 'Die Passwörter stimmen nicht überein.';
            message.className = 'message error';
            return;
        }
        
        // Benutzer und Anfragen aus dem localStorage holen
        const users = JSON.parse(localStorage.getItem('users'));
        let pendingRequests = JSON.parse(localStorage.getItem('pendingRequests'));
        
        // Überprüfen, ob der Benutzername bereits vergeben ist
        if (users.some(u => u.username === username) || 
            pendingRequests.some(r => r.username === username)) {
            message.textContent = 'Dieser Benutzername ist bereits vergeben.';
            message.className = 'message error';
            return;
        }
        
        // Neue Registrierungsanfrage erstellen
        const newRequest = {
            username: username,
            password: password,
            isApproved: false,
            isAdmin: false
        };
        
        // Anfrage zum Array hinzufügen und speichern
        pendingRequests.push(newRequest);
        localStorage.setItem('pendingRequests', JSON.stringify(pendingRequests));
        
        message.textContent = 'Registrierung erfolgreich! Warte auf Freigabe durch einen Administrator.';
        message.className = 'message success';
        
        // Formular zurücksetzen
        registerForm.reset();
    });
}

// Funktionen für die Admin-Seite
if (page === 'admin.html') {
    // Überprüfen, ob der Benutzer eingeloggt ist
    const currentUser = localStorage.getItem('currentUser');
    if (!currentUser || currentUser === '') {
        window.location.href = 'index.html';
    }
    
    // Aktuellen Benutzernamen anzeigen
    document.getElementById('currentUser').textContent = currentUser;
    
    // Abmelden-Funktion
    document.getElementById('logoutBtn').addEventListener('click', function() {
        localStorage.setItem('currentUser', '');
        window.location.href = 'index.html';
    });
    
    // Funktion zum Laden und Anzeigen der ausstehenden Registrierungen
    function loadPendingRegistrations() {
        const pendingRegistrations = document.getElementById('pendingRegistrations');
        const noPendingMsg = document.getElementById('noPendingMsg');
        const pendingRequests = JSON.parse(localStorage.getItem('pendingRequests'));
        
        // Container leeren
        while (pendingRegistrations.firstChild) {
            if (pendingRegistrations.firstChild === noPendingMsg) break;
            pendingRegistrations.removeChild(pendingRegistrations.firstChild);
        }
        
        // Meldung anzeigen, wenn keine Anfragen vorhanden sind
        if (pendingRequests.length === 0) {
            noPendingMsg.style.display = 'block';
            return;
        } else {
            noPendingMsg.style.display = 'none';
        }
        
        // Anfragen anzeigen
        pendingRequests.forEach(request => {
            const requestItem = document.createElement('div');
            requestItem.className = 'request-item';
            
            const userInfo = document.createElement('div');
            userInfo.textContent = request.username;
            
            const actions = document.createElement('div');
            actions.className = 'request-actions';
            
            const approveBtn = document.createElement('button');
            approveBtn.className = 'btn btn-success';
            approveBtn.textContent = 'Zulassen';
            approveBtn.addEventListener('click', function() {
                approveUser(request.username);
            });
            
            const rejectBtn = document.createElement('button');
            rejectBtn.className = 'btn btn-danger';
            rejectBtn.textContent = 'Ablehnen';
            rejectBtn.addEventListener('click', function() {
                rejectUser(request.username);
            });
            
            actions.appendChild(approveBtn);
            actions.appendChild(rejectBtn);
            requestItem.appendChild(userInfo);
            requestItem.appendChild(actions);
            
            pendingRegistrations.insertBefore(requestItem, noPendingMsg);
        });
    }
    
    // Funktion zum Anzeigen zugelassener Benutzer
    function loadApprovedUsers() {
        const approvedUsers = document.getElementById('approvedUsers');
        const noUsersMsg = document.getElementById('noUsersMsg');
        const users = JSON.parse(localStorage.getItem('users'));
        const currentUser = localStorage.getItem('currentUser');
        
        // Container leeren
        while (approvedUsers.firstChild) {
            if (approvedUsers.firstChild === noUsersMsg) break;
            approvedUsers.removeChild(approvedUsers.firstChild);
        }
        
        // Alle Benutzer außer dem aktuellen anzeigen
        const otherUsers = users.filter(user => user.username !== currentUser);
        
        if (otherUsers.length === 0) {
            noUsersMsg.style.display = 'block';
        } else {
            noUsersMsg.style.display = 'none';
            
            otherUsers.forEach(user => {
                const userItem = document.createElement('div');
                userItem.className = 'user-item';
                userItem.textContent = user.username + (user.isAdmin ? ' (Admin)' : '');
                approvedUsers.insertBefore(userItem, noUsersMsg);
            });
        }
    }
    
    // Benutzer zulassen
    function approveUser(username) {
        let pendingRequests = JSON.parse(localStorage.getItem('pendingRequests'));
        let users = JSON.parse(localStorage.getItem('users'));
        
        const userIndex = pendingRequests.findIndex(request => request.username === username);
        
        if (userIndex !== -1) {
            const user = pendingRequests[userIndex];
            user.isApproved = true;
            
            // Benutzer zu zugelassenen Benutzern hinzufügen
            users.push(user);
            
            // Anfrage entfernen
            pendingRequests.splice(userIndex, 1);
            
            // Änderungen speichern
            localStorage.setItem('pendingRequests', JSON.stringify(pendingRequests));
            localStorage.setItem('users', JSON.stringify(users));
            
            // Listen aktualisieren
            loadPendingRegistrations();
            loadApprovedUsers();
        }
    }
    
    // Anfrage ablehnen
    function rejectUser(username) {
        let pendingRequests = JSON.parse(localStorage.getItem('pendingRequests'));
        
        const userIndex = pendingRequests.findIndex(request => request.username === username);
        
        if (userIndex !== -1) {
            // Anfrage entfernen
            pendingRequests.splice(userIndex, 1);
            
            // Änderungen speichern
            localStorage.setItem('pendingRequests', JSON.stringify(pendingRequests));
            
            // Liste aktualisieren
            loadPendingRegistrations();
        }
    }
    
    // Laden der Daten beim Seitenstart
    loadPendingRegistrations();
    loadApprovedUsers();
} 