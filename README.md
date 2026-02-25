# ECCM – Ethernet Cable Connection Manager (MySQL Edition)

## Übersicht

MySQL-basierte Version des ECCM mit Benutzerauthentifizierung.

## Features

- **Login-System** mit sicherer Passwort-Hashung (bcrypt)
- **Passwort-Zurücksetzung** per E-Mail-Link
- **MySQL-Speicherung** aller Profile und Einstellungen pro Benutzer
- **Admin-Panel** für Benutzerverwaltung und DB-Konfiguration
- **CSRF-Schutz** auf allen Formularen
- Alle Original-ECCM-Funktionen (Geräte, Ports, Verbindungen, Themes usw.)

## Voraussetzungen

- PHP 7.4+ (empfohlen: PHP 8.x)
- MySQL 5.7+ oder MariaDB 10.3+
- Apache mit mod_rewrite (oder nginx)
- PHP-Erweiterungen: `pdo`, `pdo_mysql`, `mbstring`

## Installation

1. **Dateien auf den Webserver kopieren**
2. **`install.php` im Browser aufrufen**: `http://dein-server/eccm/install.php`
3. **DB-Verbindung und Admin-Konto eingeben** → "Installieren" klicken
4. **`install.php` löschen** (Sicherheit!)
5. **Einloggen unter** `http://dein-server/eccm/login.php`

## Dateistruktur

```
eccm/
├── index.php              # Hauptanwendung (erfordert Login)
├── login.php              # Login-Seite
├── logout.php             # Logout
├── forgot_password.php    # Passwort vergessen
├── reset_password.php     # Neues Passwort setzen (via Token)
├── admin.php              # Admin-Panel (nur für Admins)
├── install.php            # Installer (nach Setup löschen!)
├── .htaccess              # Apache-Sicherheitsregeln
├── database.sql           # SQL-Schema (Referenz)
├── api/
│   ├── profiles.php       # AJAX: Profil laden/speichern
│   └── admin.php          # AJAX: Benutzer-/DB-Verwaltung
├── includes/
│   ├── config.php         # Standard-Konfiguration
│   ├── config.local.php   # Lokale DB-Konfiguration (auto-generiert)
│   ├── db.php             # PDO-Verbindung
│   └── auth.php           # Authentifizierungsfunktionen
└── assets/
    └── eccm-core.js       # ECCM-Anwendungslogik
```

## Standard-Login

Nach der Installation:
- **Benutzername:** `admin`
- **Passwort:** `admin123`

⚠️ **Bitte sofort nach dem ersten Login ändern!**

## Admin-Funktionen

Im Admin-Panel (`/admin.php`) können Administratoren:
- Neue Benutzer erstellen
- Benutzer bearbeiten (Name, E-Mail, Passwort, Rolle)
- Benutzer löschen
- Die MySQL-Verbindung ändern und testen

## Passwort zurücksetzen

- Auf der Login-Seite "Passwort vergessen?" klicken
- E-Mail-Adresse eingeben
- Reset-Link wird per E-Mail gesendet (PHP `mail()`)
- Link ist 1 Stunde gültig

Für SMTP-Konfiguration: `includes/config.php` → `$mail_config` anpassen.
