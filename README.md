# 🔌 ECCM – Ethernet Cable Connection Manager

**ECCM** ist ein webbasiertes Tool zur Dokumentation und Verwaltung physischer Netzwerkverkabelungen. Es bildet Switches, Patchfelder und andere Netzwerkgeräte mit ihren Ports visuell ab und ermöglicht die Verwaltung von Verbindungen, VLANs, Port-Konfigurationen und mehr.

> **[🇬🇧 English version below](#-eccm--ethernet-cable-connection-manager-1)**

---

## ✨ Features

### Geräte & Ports
- Geräte (Switches, Patchfelder etc.) mit frei wählbarer Portanzahl anlegen
- Individuelle Gerätefarben zur visuellen Unterscheidung
- Flexible Port-Layouts: Einreihig, zweireihig, Dual-Link-Modus
- Konfigurierbare Port-Nummerierung (Links→Rechts, Oben↓Unten, Unten↑Oben)
- Port-Aliase und benutzerdefinierte Port-Namen

### Verbindungen
- Zwei freie Ports anklicken um eine Verbindung zu erstellen
- Verbindungstabelle mit Suchfunktion
- Klick auf eine Verbindung hebt beide Enden visuell hervor
- Automatische Peer-Erkennung und -Anzeige

### VLAN-Management
- VLANs pro Profil definieren (ID 1–4094, Name, Farbe)
- Mehrere VLANs gleichzeitig einem Port zuweisen (Multi-Select)
- Farbige VLAN-Indikatoren direkt auf den Ports
- VLAN-Synchronisation: Zuweisung auf einer Seite wird automatisch auf die Gegenseite übertragen
- VLANs werden in der Verbindungstabelle angezeigt

### Port-Konfiguration (Rechtsklick-Menü)
- Linkgeschwindigkeit (100 Mbit – 100 Gbit)
- Individuelle Port-Farben mit Sync zur Gegenseite
- Notizen (bis 150 Zeichen)
- VLAN-Zuweisung per Checkbox
- „Verbunden mit"-Feld (manuell überschreibbar)
- Reservierte Ports (z. B. WAN, Management)

### Hover-Tooltip
- Vollständige Port-Informationen beim Überfahren jedes Ports
- Zeigt: Port, Alias, Peer, Status, Speed, VLANs, Notiz

### Benutzerverwaltung & Berechtigungen
- Login-System mit bcrypt-Passwort-Hashung
- Rollen: Admin und Benutzer
- Granulare Berechtigungen pro Profil und Benutzer:
  - Ansehen, Patchen, Patch hinzufügen, Gerät bearbeiten, Gerät hinzufügen, Löschen, Verwalten, Export, Backup
- Profil-Sharing zwischen Benutzern
- Passwort-Zurücksetzung per E-Mail

### Profile
- Unbegrenzte Profile pro Benutzer
- Erstellen, Umbenennen, Duplizieren, Löschen
- Export einzelner Profile als JSON
- Profil-Eigentümer mit vollständigen Rechten

### Admin-Panel
- Benutzerverwaltung (CRUD)
- SMTP / E-Mail-Konfiguration
- E-Mail-Vorlagen-Editor mit Platzhaltern
- Datenbank-Konfiguration und -Test
- Backup aller Profile und Wiederherstellung
- Profil-Import aus JSON-Dateien
- Allgemeine Einstellungen (App-Name, Standard-Sprache)

### Internationalisierung (i18n)
- Vollständig übersetzt: **Deutsch** und **Englisch**
- Globale Standard-Sprache konfigurierbar
- Pro-Benutzer Spracheinstellung
- Alle UI-Elemente, Tooltips, Fehlermeldungen und Admin-Bereiche übersetzt

### E-Mail-Benachrichtigungen
- Benachrichtigungen bei Profil-Änderungen (Geräte, Verbindungen)
- Abonnierbare Benachrichtigungen pro Profil
- Anpassbare E-Mail-Vorlagen

### Themes
- Dark Theme (Standard)
- Light Theme
- Per-Benutzer Einstellung

---

## 📋 Voraussetzungen

- **PHP** 7.4+ (empfohlen: PHP 8.x)
- **MySQL** 5.7+ oder **MariaDB** 10.3+
- **Apache** mit `mod_rewrite` (oder nginx)
- PHP-Erweiterungen: `pdo`, `pdo_mysql`, `mbstring`

---

## 🚀 Installation

1. Dateien auf den Webserver kopieren (z. B. nach `/var/www/html/eccm/`)
2. `install.php` im Browser aufrufen: `http://dein-server/eccm/install.php`
3. Datenbank-Verbindung und Admin-Konto konfigurieren → „Installieren" klicken
4. **`install.php` löschen** (Sicherheitsempfehlung!)
5. Einloggen: `http://dein-server/eccm/`

> 💡 Bei der Installation wird das Admin-Konto direkt konfiguriert – es gibt keinen festen Standard-Login.

---

## 🖱️ Bedienung

| Aktion | Beschreibung |
|--------|-------------|
| **Klick** auf freien Port | Port auswählen (zweiten Port klicken zum Verbinden) |
| **Rechtsklick** auf Port | Port-Einstellungen öffnen (Speed, VLANs, Farbe, Notiz) |
| **Alt+Klick** auf Port | Alias vergeben |
| **Strg+Klick** auf Port | Als reserviert markieren |
| **Hover** über Port | Tooltip mit allen Port-Informationen |
| Klick auf Verbindungszeile | Beide Enden hervorheben |

---

## 📁 Dateistruktur

```
eccm/
├── index.php                # Hauptanwendung
├── login.php                # Login-Seite
├── logout.php               # Logout
├── forgot_password.php      # Passwort vergessen
├── reset_password.php       # Passwort zurücksetzen (Token)
├── admin.php                # Admin-Panel
├── install.php              # Installer (nach Setup löschen!)
├── .htaccess                # Apache-Sicherheitsregeln
├── database.sql             # SQL-Schema (Referenz)
├── api/
│   ├── profiles.php         # AJAX: Profile, VLANs, Berechtigungen
│   ├── admin.php            # AJAX: Benutzer, SMTP, Templates
│   └── notifications.php    # AJAX: E-Mail-Benachrichtigungen
├── includes/
│   ├── config.php           # Standard-Konfiguration
│   ├── config.local.php     # Lokale Konfiguration (auto-generiert)
│   ├── db.php               # PDO-Datenbankverbindung
│   ├── auth.php             # Authentifizierung & Session
│   ├── i18n.php             # Übersetzungen (DE/EN)
│   ├── mailer.php           # SMTP-Mailer
│   └── notifications.php    # Benachrichtigungs-Logik
└── assets/
    └── eccm-core.js         # ECCM Rendering-Engine
```

---

## 🔧 Konfiguration

### SMTP (E-Mail-Versand)

Im Admin-Panel unter **E-Mail / SMTP** konfigurierbar. Unterstützt:
- PHP `mail()` (Standard, kein SMTP nötig)
- SMTP mit TLS/SSL (z. B. Gmail, Office 365, eigener Mailserver)

### Datenbank

Im Admin-Panel unter **Datenbank** konfigurierbar. Änderungen werden in `includes/config.local.php` gespeichert.

---

## 🔒 Sicherheit

- Passwörter werden mit bcrypt gehasht
- CSRF-Schutz auf allen Formularen
- Session-basierte Authentifizierung (7 Tage Lifetime)
- Automatischer Redirect zum Login bei abgelaufener Session
- `.htaccess` schützt sensible Dateien und Verzeichnisse
- Prepared Statements gegen SQL-Injection

---

## 📸 Screenshots

*Kommt bald*

---

## 📄 Lizenz

MIT License – siehe [LICENSE](LICENSE)

---
---

# 🔌 ECCM – Ethernet Cable Connection Manager

**ECCM** is a web-based tool for documenting and managing physical network cabling. It visually represents switches, patch panels, and other network devices with their ports, enabling management of connections, VLANs, port configurations, and more.

---

## ✨ Features

### Devices & Ports
- Create devices (switches, patch panels, etc.) with any number of ports
- Individual device colors for visual distinction
- Flexible port layouts: single-row, dual-row, dual-link mode
- Configurable port numbering (Left→Right, Top↓Bottom, Bottom↑Top)
- Port aliases and custom port names

### Connections
- Click two free ports to create a connection
- Connection table with search functionality
- Click a connection to visually highlight both ends
- Automatic peer detection and display

### VLAN Management
- Define VLANs per profile (ID 1–4094, name, color)
- Assign multiple VLANs to a port simultaneously (multi-select)
- Colored VLAN indicators directly on ports
- VLAN sync: assignment on one side is automatically mirrored to the peer
- VLANs displayed in the connection table

### Port Configuration (Right-Click Menu)
- Link speed (100 Mbit – 100 Gbit)
- Individual port colors with peer-side sync
- Notes (up to 150 characters)
- VLAN assignment via checkboxes
- "Linked to" field (manually overridable)
- Reserved ports (e.g. WAN, Management)

### Hover Tooltip
- Full port information on hover over any port
- Shows: Port, Alias, Peer, Status, Speed, VLANs, Note

### User Management & Permissions
- Login system with bcrypt password hashing
- Roles: Admin and User
- Granular per-profile, per-user permissions:
  - View, Patch, Add Patch, Edit Device, Add Device, Delete, Manage, Export, Backup
- Profile sharing between users
- Password reset via email

### Profiles
- Unlimited profiles per user
- Create, rename, duplicate, delete
- Export individual profiles as JSON
- Profile owner with full permissions

### Admin Panel
- User management (CRUD)
- SMTP / email configuration
- Email template editor with placeholders
- Database configuration and testing
- Backup all profiles and restore
- Profile import from JSON files
- General settings (app name, default language)

### Internationalization (i18n)
- Fully translated: **German** and **English**
- Global default language configurable
- Per-user language preference
- All UI elements, tooltips, error messages, and admin areas translated

### Email Notifications
- Notifications on profile changes (devices, connections)
- Subscribable notifications per profile
- Customizable email templates

### Themes
- Dark theme (default)
- Light theme
- Per-user preference

---

## 📋 Requirements

- **PHP** 7.4+ (recommended: PHP 8.x)
- **MySQL** 5.7+ or **MariaDB** 10.3+
- **Apache** with `mod_rewrite` (or nginx)
- PHP extensions: `pdo`, `pdo_mysql`, `mbstring`

---

## 🚀 Installation

1. Copy files to your web server (e.g. `/var/www/html/eccm/`)
2. Open `install.php` in your browser: `http://your-server/eccm/install.php`
3. Configure database connection and admin account → click "Install"
4. **Delete `install.php`** (security recommendation!)
5. Log in: `http://your-server/eccm/`

> 💡 The admin account is configured during installation – there is no fixed default login.

---

## 🖱️ Usage

| Action | Description |
|--------|------------|
| **Click** on free port | Select port (click second port to connect) |
| **Right-click** on port | Open port settings (speed, VLANs, color, note) |
| **Alt+Click** on port | Set alias |
| **Ctrl+Click** on port | Mark as reserved |
| **Hover** over port | Tooltip with full port information |
| Click on connection row | Highlight both ends |

---

## 📁 File Structure

```
eccm/
├── index.php                # Main application
├── login.php                # Login page
├── logout.php               # Logout
├── forgot_password.php      # Forgot password
├── reset_password.php       # Reset password (token-based)
├── admin.php                # Admin panel
├── install.php              # Installer (delete after setup!)
├── .htaccess                # Apache security rules
├── database.sql             # SQL schema (reference)
├── api/
│   ├── profiles.php         # AJAX: Profiles, VLANs, permissions
│   ├── admin.php            # AJAX: Users, SMTP, templates
│   └── notifications.php    # AJAX: Email notifications
├── includes/
│   ├── config.php           # Default configuration
│   ├── config.local.php     # Local config (auto-generated)
│   ├── db.php               # PDO database connection
│   ├── auth.php             # Authentication & sessions
│   ├── i18n.php             # Translations (DE/EN)
│   ├── mailer.php           # SMTP mailer
│   └── notifications.php    # Notification logic
└── assets/
    └── eccm-core.js         # ECCM rendering engine
```

---

## 🔧 Configuration

### SMTP (Email)

Configurable in the admin panel under **Email / SMTP**. Supports:
- PHP `mail()` (default, no SMTP required)
- SMTP with TLS/SSL (e.g. Gmail, Office 365, self-hosted)

### Database

Configurable in the admin panel under **Database**. Changes are saved to `includes/config.local.php`.

---

## 🔒 Security

- Passwords hashed with bcrypt
- CSRF protection on all forms
- Session-based authentication (7-day lifetime)
- Automatic redirect to login on expired sessions
- `.htaccess` protects sensitive files and directories
- Prepared statements against SQL injection

---

## 📸 Screenshots

*Coming soon*

---

## 📄 License

MIT License – see [LICENSE](LICENSE)

##

P.S.: Edited with claude