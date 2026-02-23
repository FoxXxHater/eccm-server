<?php
require_once __DIR__ . '/includes/auth.php';
requireAdmin();
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>ECCM – Admin</title>
<link rel="icon" type="image/png" sizes="32x32" href="https://img.icons8.com/stickers/32/ethernet-on.png">
<style>
:root{--bg:#0f1115;--panel:#171a21;--ink:#e8eaf1;--line:#262a33;--accent:#3b82f6;--danger:#ef4444;--success:#22c55e;--muted:#a6adbb}
*,*::before,*::after{box-sizing:border-box}
html,body{height:100%;margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif}
body{background:var(--bg);color:var(--ink)}
header{padding:14px 18px;border-bottom:1px solid var(--line);display:flex;align-items:center;gap:12px;background:linear-gradient(180deg,#12151b,#10131a)}
header h1{font-size:18px;margin:0;font-weight:650}
header .badge{color:var(--muted);font-size:12px;border:1px solid var(--line);padding:2px 8px;border-radius:999px}
header nav{margin-left:auto;display:flex;gap:8px}
header a{color:var(--ink);text-decoration:none;padding:6px 12px;border:1px solid var(--line);border-radius:8px;font-size:13px;font-weight:600}
header a:hover{border-color:#2a2f3b}
.container{max-width:960px;margin:24px auto;padding:0 16px}
.card{background:var(--panel);border:1px solid var(--line);border-radius:12px;padding:16px;margin-bottom:20px}
.card h2{margin:0 0 12px;font-size:16px;font-weight:650}
.card p.hint{font-size:13px;color:var(--muted);margin:0 0 12px}
label{display:block;font-size:13px;color:var(--muted);margin:10px 0 4px}
input[type=text],input[type=password],input[type=email],input[type=number],select{width:100%;max-width:360px;background:#0f131b;color:var(--ink);border:1px solid var(--line);border-radius:8px;padding:8px 10px;outline:none;font-size:14px}
input:focus,select:focus{border-color:var(--accent)}
button,.btn{background:#11151d;color:var(--ink);border:1px solid var(--line);padding:8px 14px;border-radius:8px;cursor:pointer;font-weight:600;font-size:13px}
button:hover{border-color:#2a2f3b}
.btn-primary{background:var(--accent);color:#fff;border-color:var(--accent)}.btn-primary:hover{opacity:.9}
.btn-danger{background:transparent;border-color:#3b2222;color:#f87171}.btn-danger:hover{background:rgba(239,68,68,.1)}
.btn-sm{padding:5px 10px;font-size:12px}
.msg{border-radius:8px;padding:10px;font-size:13px;margin:10px 0;text-align:center}
.msg.ok{background:rgba(34,197,94,.15);border:1px solid var(--success);color:#86efac}
.msg.error{background:rgba(239,68,68,.15);border:1px solid var(--danger);color:#fca5a5}
table{width:100%;border-collapse:collapse;border:1px solid var(--line);border-radius:8px;overflow:hidden}
th,td{text-align:left;padding:8px 10px;border-bottom:1px solid var(--line);font-size:13px}
th{background:#121722;color:var(--muted);font-weight:600}
.row-flex{display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end}.row-flex>div{flex:1;min-width:140px}
.tabs{display:flex;gap:4px;margin-bottom:16px;flex-wrap:wrap}
.tab{padding:8px 16px;border:1px solid var(--line);border-radius:8px 8px 0 0;cursor:pointer;font-weight:600;font-size:14px;background:transparent;color:var(--muted)}
.tab.active{background:var(--panel);color:var(--ink);border-bottom-color:var(--panel)}
.tab-content{display:none}.tab-content.active{display:block}
hr{border:none;border-top:1px solid var(--line);margin:16px 0}
</style>
</head>
<body>
<header><h1>🔌 ECCM Admin</h1><span class="badge">Admin</span><nav><a href="index.php">← Zurück</a><a href="logout.php">Abmelden</a></nav></header>
<div class="container">

<div class="tabs">
  <div class="tab active" data-tab="users">Benutzer</div>
  <div class="tab" data-tab="smtp">E-Mail / SMTP</div>
  <div class="tab" data-tab="database">Datenbank</div>
</div>

<!-- ═══════════ USERS TAB ═══════════ -->
<div class="tab-content active" id="tab-users">
  <div class="card"><h2>Neuen Benutzer erstellen</h2>
    <div class="row-flex">
      <div><label>Benutzername</label><input type="text" id="newUsername"></div>
      <div><label>E-Mail</label><input type="email" id="newEmail"></div>
      <div><label>Passwort</label><input type="password" id="newPassword"></div>
      <div><label>Rolle</label><select id="newRole"><option value="user">User</option><option value="admin">Admin</option></select></div>
      <div style="display:flex;align-items:flex-end"><button class="btn-primary" onclick="createUser()">Erstellen</button></div>
    </div><div id="createMsg"></div>
  </div>
  <div class="card"><h2>Benutzerliste</h2>
    <table><thead><tr><th>ID</th><th>Benutzername</th><th>E-Mail</th><th>Rolle</th><th>Erstellt</th><th>Aktionen</th></tr></thead>
    <tbody id="userTableBody"><tr><td colspan="6" style="color:var(--muted)">Laden…</td></tr></tbody></table>
  </div>
</div>

<!-- ═══════════ SMTP TAB ═══════════ -->
<div class="tab-content" id="tab-smtp">
  <div class="card">
    <h2>📧 E-Mail / SMTP-Konfiguration</h2>
    <p class="hint">Diese Einstellungen werden für Passwort-Zurücksetzungen und Benachrichtigungen verwendet. Wenn kein SMTP-Host angegeben wird, wird PHP <code>mail()</code> verwendet.</p>

    <div class="row-flex">
      <div><label>Absender-Name</label><input type="text" id="smtpFromName" value="ECCM System" style="max-width:100%"></div>
      <div><label>Absender-E-Mail</label><input type="email" id="smtpFromEmail" value="noreply@example.com" style="max-width:100%"></div>
    </div>

    <hr>
    <h2 style="font-size:14px;margin-bottom:8px">SMTP-Server</h2>

    <div class="row-flex">
      <div><label>SMTP-Host</label><input type="text" id="smtpHost" placeholder="z.B. smtp.gmail.com"></div>
      <div style="max-width:100px"><label>Port</label><input type="number" id="smtpPort" value="587"></div>
      <div style="max-width:140px"><label>Verschlüsselung</label>
        <select id="smtpEncryption" style="max-width:100%">
          <option value="tls">STARTTLS (587)</option>
          <option value="ssl">SSL/TLS (465)</option>
          <option value="none">Keine</option>
        </select>
      </div>
    </div>
    <div class="row-flex" style="margin-top:4px">
      <div><label>SMTP-Benutzer</label><input type="text" id="smtpUser" placeholder="user@example.com"></div>
      <div><label>SMTP-Passwort</label><input type="password" id="smtpPass"></div>
    </div>

    <hr>
    <label>Basis-URL (für Links in E-Mails, leer = automatisch)</label>
    <input type="text" id="smtpBaseUrl" placeholder="https://eccm.example.com" style="max-width:100%">

    <div style="margin-top:14px;display:flex;gap:8px;flex-wrap:wrap">
      <button onclick="testSMTP()">Verbindung testen</button>
      <button class="btn-primary" onclick="saveSMTP()">Speichern</button>
    </div>
    <div id="smtpMsg"></div>
  </div>

  <div class="card">
    <h2>📨 Test-E-Mail senden</h2>
    <p class="hint">Sendet eine Test-E-Mail mit den aktuell eingegebenen Einstellungen (auch ungespeicherte).</p>
    <div class="row-flex">
      <div><label>Empfänger</label><input type="email" id="testEmailAddr" placeholder="test@example.com" style="max-width:100%"></div>
      <div style="display:flex;align-items:flex-end"><button onclick="sendTestEmail()">Test senden</button></div>
    </div>
    <div id="testEmailMsg"></div>
  </div>
</div>

<!-- ═══════════ DATABASE TAB ═══════════ -->
<div class="tab-content" id="tab-database">
  <div class="card"><h2>MySQL-Datenbankverbindung</h2>
    <p class="hint">Konfiguration wird in <code>includes/config.local.php</code> gespeichert.</p>
    <div class="row-flex"><div><label>Host</label><input type="text" id="dbHost" value="localhost"></div><div style="max-width:120px"><label>Port</label><input type="number" id="dbPort" value="3306"></div><div><label>Datenbank</label><input type="text" id="dbName" value="eccm_db"></div></div>
    <div class="row-flex" style="margin-top:4px"><div><label>Benutzername</label><input type="text" id="dbUser" value="root"></div><div><label>Passwort</label><input type="password" id="dbPass"></div></div>
    <div style="margin-top:14px;display:flex;gap:8px"><button onclick="testDB()">Verbindung testen</button><button class="btn-primary" onclick="saveDB()">Speichern</button></div>
    <div id="dbMsg"></div>
  </div>
</div>

</div><!-- /container -->

<!-- Edit User Modal -->
<div id="editModal" style="position:fixed;inset:0;background:rgba(0,0,0,.5);display:none;align-items:center;justify-content:center;z-index:100">
<div class="card" style="min-width:340px;max-width:440px"><h2>Benutzer bearbeiten</h2><input type="hidden" id="editId"><label>Benutzername</label><input type="text" id="editUsername"><label>E-Mail</label><input type="email" id="editEmail"><label>Neues Passwort (leer = unverändert)</label><input type="password" id="editPassword"><label>Rolle</label><select id="editRole"><option value="user">User</option><option value="admin">Admin</option></select><div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end"><button onclick="closeEditModal()">Abbrechen</button><button class="btn-primary" onclick="updateUser()">Speichern</button></div><div id="editMsg"></div></div>
</div>

<script>
const API = 'api/admin.php';

// ── Tabs ──
document.querySelectorAll('.tab').forEach(t => {
  t.addEventListener('click', () => {
    document.querySelectorAll('.tab').forEach(x => x.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(x => x.classList.remove('active'));
    t.classList.add('active');
    document.getElementById('tab-' + t.dataset.tab).classList.add('active');
  });
});

function api(action, data = {}) {
  return fetch(API, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action, ...data }) }).then(r => r.json());
}
function showMsg(el, text, ok) {
  el.innerHTML = '<div class="msg ' + (ok ? 'ok' : 'error') + '">' + text + '</div>';
  setTimeout(() => el.innerHTML = '', 6000);
}
function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

// ── Users ──
function loadUsers() {
  api('list_users').then(res => {
    const tb = document.getElementById('userTableBody');
    if (!res.ok) { tb.innerHTML = '<tr><td colspan="6">Fehler</td></tr>'; return; }
    tb.innerHTML = res.users.map(u =>
      '<tr><td>' + u.id + '</td><td>' + esc(u.username) + '</td><td>' + esc(u.email) + '</td><td>' + u.role + '</td><td>' + u.created_at + '</td><td>' +
      '<button class="btn-sm" onclick=\'openEditModal(' + JSON.stringify(u) + ')\'>Bearbeiten</button> ' +
      '<button class="btn-sm btn-danger" onclick="deleteUser(' + u.id + ',\'' + esc(u.username) + '\')">Löschen</button></td></tr>'
    ).join('');
  });
}
function createUser() {
  api('create_user', { username: document.getElementById('newUsername').value, email: document.getElementById('newEmail').value, password: document.getElementById('newPassword').value, role: document.getElementById('newRole').value }).then(res => {
    var el = document.getElementById('createMsg');
    if (res.ok) { showMsg(el, 'Erstellt!', true); document.getElementById('newUsername').value = ''; document.getElementById('newEmail').value = ''; document.getElementById('newPassword').value = ''; loadUsers(); }
    else showMsg(el, res.error || 'Fehler', false);
  });
}
function deleteUser(id, name) { if (!confirm('Benutzer "' + name + '" löschen?')) return; api('delete_user', { id }).then(res => { if (res.ok) loadUsers(); else alert(res.error); }); }
function openEditModal(u) { document.getElementById('editId').value = u.id; document.getElementById('editUsername').value = u.username; document.getElementById('editEmail').value = u.email; document.getElementById('editPassword').value = ''; document.getElementById('editRole').value = u.role; document.getElementById('editMsg').innerHTML = ''; document.getElementById('editModal').style.display = 'flex'; }
function closeEditModal() { document.getElementById('editModal').style.display = 'none'; }
function updateUser() {
  api('update_user', { id: +document.getElementById('editId').value, username: document.getElementById('editUsername').value, email: document.getElementById('editEmail').value, password: document.getElementById('editPassword').value, role: document.getElementById('editRole').value }).then(res => {
    var el = document.getElementById('editMsg');
    if (res.ok) { showMsg(el, 'Gespeichert!', true); loadUsers(); setTimeout(closeEditModal, 800); }
    else showMsg(el, res.error || 'Fehler', false);
  });
}

// ── SMTP ──
function getSMTPForm() {
  return {
    from_name: document.getElementById('smtpFromName').value,
    from_email: document.getElementById('smtpFromEmail').value,
    smtp_host: document.getElementById('smtpHost').value,
    smtp_port: +document.getElementById('smtpPort').value || 587,
    smtp_encryption: document.getElementById('smtpEncryption').value,
    smtp_user: document.getElementById('smtpUser').value,
    smtp_pass: document.getElementById('smtpPass').value,
    base_url: document.getElementById('smtpBaseUrl').value,
  };
}
function loadSMTPConfig() {
  api('get_smtp_config').then(res => {
    if (!res.ok) return;
    var c = res.config;
    document.getElementById('smtpFromName').value = c.from_name || 'ECCM System';
    document.getElementById('smtpFromEmail').value = c.from_email || '';
    document.getElementById('smtpHost').value = c.smtp_host || '';
    document.getElementById('smtpPort').value = c.smtp_port || 587;
    document.getElementById('smtpEncryption').value = c.smtp_encryption || 'tls';
    document.getElementById('smtpUser').value = c.smtp_user || '';
    document.getElementById('smtpPass').value = '';
    document.getElementById('smtpPass').placeholder = c.smtp_pass || '(leer)';
    document.getElementById('smtpBaseUrl').value = c.base_url || '';
  });
}
function testSMTP() {
  showMsg(document.getElementById('smtpMsg'), 'Verbinde…', true);
  api('test_smtp', getSMTPForm()).then(res => showMsg(document.getElementById('smtpMsg'), res.message, res.ok));
}
function saveSMTP() { api('save_smtp', getSMTPForm()).then(res => showMsg(document.getElementById('smtpMsg'), res.message || res.error, !!res.ok)); }
function sendTestEmail() {
  var form = getSMTPForm();
  form.test_email = document.getElementById('testEmailAddr').value;
  showMsg(document.getElementById('testEmailMsg'), 'Sende…', true);
  api('send_test_email', form).then(res => showMsg(document.getElementById('testEmailMsg'), res.message, res.ok));
}

// ── Database ──
function loadDBConfig() {
  api('get_db_config').then(res => {
    if (!res.ok) return;
    var c = res.config;
    document.getElementById('dbHost').value = c.host || 'localhost';
    document.getElementById('dbPort').value = c.port || 3306;
    document.getElementById('dbName').value = c.dbname || 'eccm_db';
    document.getElementById('dbUser').value = c.username || 'root';
    document.getElementById('dbPass').value = '';
    document.getElementById('dbPass').placeholder = c.password || '(leer)';
  });
}
function getDBForm() { return { host: document.getElementById('dbHost').value, port: +document.getElementById('dbPort').value || 3306, dbname: document.getElementById('dbName').value, username: document.getElementById('dbUser').value, password: document.getElementById('dbPass').value }; }
function testDB() { api('test_db', getDBForm()).then(res => showMsg(document.getElementById('dbMsg'), res.message, res.ok)); }
function saveDB() { api('save_db', getDBForm()).then(res => showMsg(document.getElementById('dbMsg'), res.message || res.error, !!res.ok)); }

// ── Init ──
loadUsers();
loadDBConfig();
loadSMTPConfig();
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeEditModal(); });
</script>
</body></html>
