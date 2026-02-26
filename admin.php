<?php
require_once __DIR__ . '/includes/auth.php';
requireAdmin();
require_once __DIR__ . '/includes/i18n.php';
detectLanguage();
$t = function($k){ return __t($k); };

// Detect user's theme preference
$_userTheme = 'dark';
try {
    $db = getDB();
    $stmt = $db->prepare('SELECT settings FROM user_settings WHERE user_id = :uid');
    $stmt->execute(['uid' => currentUserId()]);
    $row = $stmt->fetch();
    if ($row) {
        $s = json_decode($row['settings'], true);
        if (isset($s['theme']) && $s['theme'] === 'bright') $_userTheme = 'bright';
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="<?=($GLOBALS['ECCM_LANG']??'de')?>"<?=$_userTheme==='bright'?' class="theme-bright"':''?>>
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?=$t('admin_title')?></title>
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
.tpl-section{border:1px solid var(--line);border-radius:10px;padding:14px;margin-bottom:16px;background:#0f131b}
.tpl-section h3{margin:0 0 10px;font-size:15px;font-weight:650}
.tpl-grid{display:grid;grid-template-columns:1fr 240px;gap:14px}
@media(max-width:768px){.tpl-grid{grid-template-columns:1fr}}
.tpl-fields label{margin-top:8px}
.tpl-fields input[type=text],.tpl-fields textarea{width:100%;max-width:100%;background:#171a21;color:var(--ink);border:1px solid var(--line);border-radius:8px;padding:8px 10px;outline:none;font-size:13px;font-family:monospace}
.tpl-fields textarea{min-height:180px;resize:vertical}
.tpl-placeholders{background:var(--panel);border:1px solid var(--line);border-radius:8px;padding:10px}
.tpl-placeholders h4{margin:0 0 8px;font-size:13px;color:var(--accent)}
.tpl-ph{font-size:12px;margin:3px 0;display:flex;gap:6px;align-items:flex-start}
.tpl-ph code{background:#1a1f2e;padding:2px 6px;border-radius:4px;cursor:pointer;white-space:nowrap;font-size:11px;border:1px solid var(--line)}
.tpl-ph code:hover{border-color:var(--accent);color:var(--accent)}
.tpl-ph span{color:var(--muted);font-size:11px}
.tpl-preview{margin-top:10px;background:#171a21;border:1px solid var(--line);border-radius:8px;padding:10px;white-space:pre-wrap;font-size:12px;font-family:monospace;max-height:200px;overflow:auto;display:none}
/* ── Light Theme ── */
html.theme-bright{--bg:#f5f6f8;--panel:#fff;--ink:#111;--line:#d8dee8;--muted:#666}
html.theme-bright body{background:var(--bg);color:var(--ink)}
html.theme-bright header{background:#fff!important;border-bottom:1px solid #e0e3e8!important;color:#111!important}
html.theme-bright header h1,html.theme-bright header a{color:#111!important}
html.theme-bright header .badge{background:#f0f1f3!important;color:#555!important;border-color:#d8dee8!important}
html.theme-bright .card{background:#fff!important;border-color:#d8dee8!important;color:#111!important}
html.theme-bright .card h2{color:#111!important}
html.theme-bright .card p.hint{color:#666!important}
html.theme-bright label{color:#555!important}
html.theme-bright input[type=text],html.theme-bright input[type=password],html.theme-bright input[type=email],html.theme-bright input[type=number],html.theme-bright select{background:#fff!important;color:#111!important;border-color:#ccc!important}
html.theme-bright button,html.theme-bright .btn{background:#f5f6f8!important;color:#111!important;border-color:#ccc!important}
html.theme-bright .btn-primary{background:var(--accent)!important;color:#fff!important;border-color:var(--accent)!important}
html.theme-bright .btn-danger{background:transparent!important;color:#dc2626!important;border-color:#fca5a5!important}
html.theme-bright table{border-color:#d8dee8!important}
html.theme-bright th{background:#f6f7f9!important;color:#555!important;border-color:#d8dee8!important}
html.theme-bright td{border-color:#eee!important;color:#111!important}
html.theme-bright .tab{color:#888!important;border-color:#d8dee8!important}
html.theme-bright .tab.active{background:#fff!important;color:#111!important;border-bottom-color:#fff!important}
html.theme-bright hr{border-color:#e0e3e8!important}
html.theme-bright .tpl-section{background:#f8f9fb!important;border-color:#d8dee8!important}
html.theme-bright .tpl-fields input[type=text],html.theme-bright .tpl-fields textarea{background:#fff!important;color:#111!important;border-color:#ccc!important}
html.theme-bright .tpl-placeholders{background:#f8f9fb!important;border-color:#d8dee8!important}
html.theme-bright .tpl-ph code{background:#eef1f6!important;color:#333!important;border-color:#ccc!important}
html.theme-bright .tpl-ph span{color:#666!important}
html.theme-bright .tpl-preview{background:#f8f9fb!important;color:#111!important;border-color:#d8dee8!important}
html.theme-bright .msg.ok{background:rgba(34,197,94,.1)!important;color:#166534!important;border-color:#86efac!important}
html.theme-bright .msg.error{background:rgba(239,68,68,.1)!important;color:#991b1b!important;border-color:#fca5a5!important}
html.theme-bright #editModal .card{background:#fff!important;color:#111!important;border-color:#d8dee8!important}
</style>
</head>
<body>
<header><h1>🔌 <?=$t('admin_title')?></h1><span class="badge"><?=$t('admin')?></span><nav><a href="index.php"><?=$t('back')?></a><a href="logout.php"><?=$t('logout')?></a></nav></header>
<div class="container">

<div class="tabs">
  <div class="tab active" data-tab="general"><?=$t('tab_general')?></div>
  <div class="tab" data-tab="users"><?=$t('tab_users')?></div>
  <div class="tab" data-tab="smtp"><?=$t('tab_smtp')?></div>
  <div class="tab" data-tab="templates"><?=$t('tab_templates')?></div>
  <div class="tab" data-tab="database"><?=$t('tab_database')?></div>
  <div class="tab" data-tab="backup"><?=$t('tab_backup')?></div>
</div>

<!-- ═══════════ GENERAL TAB ═══════════ -->
<div class="tab-content active" id="tab-general">
  <div class="card">
    <h2><?=$t('general_title')?></h2>
    <p class="hint"><?=$t('general_hint')?></p>
    <label><?=$t('app_name')?></label>
    <input type="text" id="genAppName" value="ECCM" placeholder="ECCM">
    <div class="hint" style="margin-top:2px"><?=$t('app_name_hint')?></div>
    <label><?=$t('default_language')?></label>
    <select id="genDefaultLang">
      <option value="de">Deutsch</option>
      <option value="en">English</option>
    </select>
    <div class="hint" style="margin-top:2px"><?=$t('default_language_hint')?></div>
    <div style="margin-top:16px"><button class="btn-primary" onclick="saveGeneral()"><?=$t('save')?></button></div>
    <div id="generalMsg"></div>
  </div>
</div>

<!-- ═══════════ USERS TAB ═══════════ -->
<div class="tab-content" id="tab-users">
  <div class="card"><h2><?=$t('create_user')?></h2>
    <div class="row-flex">
      <div><label><?=$t('username')?></label><input type="text" id="newUsername"></div>
      <div><label><?=$t('email')?></label><input type="email" id="newEmail"></div>
      <div><label><?=$t('password')?></label><input type="password" id="newPassword"></div>
      <div><label><?=$t('role')?></label><select id="newRole"><option value="user">User</option><option value="admin">Admin</option></select></div>
      <div style="display:flex;align-items:flex-end"><button class="btn-primary" onclick="createUser()"><?=$t('create')?></button></div>
    </div><div id="createMsg"></div>
  </div>
  <div class="card"><h2><?=$t('user_list')?></h2>
    <table><thead><tr><th>ID</th><th><?=$t('username')?></th><th><?=$t('email')?></th><th><?=$t('role')?></th><th><?=$t('created_at')?></th><th><?=$t('actions')?></th></tr></thead>
    <tbody id="userTableBody"><tr><td colspan="6" style="color:var(--muted)"><?=$t('loading')?></td></tr></tbody></table>
  </div>
</div>

<!-- ═══════════ SMTP TAB ═══════════ -->
<div class="tab-content" id="tab-smtp">
  <div class="card">
    <h2><?=$t('smtp_title')?></h2>
    <p class="hint"><?=$t('smtp_hint')?></p>
    <div class="row-flex">
      <div><label><?=$t('smtp_from_name')?></label><input type="text" id="smtpFromName" value="ECCM System" style="max-width:100%"></div>
      <div><label><?=$t('smtp_from_email')?></label><input type="email" id="smtpFromEmail" value="noreply@example.com" style="max-width:100%"></div>
    </div>
    <hr><h2 style="font-size:14px;margin-bottom:8px"><?=$t('smtp_server')?></h2>
    <div class="row-flex">
      <div><label><?=$t('smtp_host')?></label><input type="text" id="smtpHost" placeholder="smtp.gmail.com"></div>
      <div style="max-width:100px"><label><?=$t('smtp_port')?></label><input type="number" id="smtpPort" value="587"></div>
      <div style="max-width:140px"><label><?=$t('smtp_encryption')?></label><select id="smtpEncryption" style="max-width:100%"><option value="tls">STARTTLS (587)</option><option value="ssl">SSL/TLS (465)</option><option value="none">None</option></select></div>
    </div>
    <div class="row-flex" style="margin-top:4px">
      <div><label><?=$t('smtp_user')?></label><input type="text" id="smtpUser" placeholder="user@example.com"></div>
      <div><label><?=$t('smtp_pass')?></label><input type="password" id="smtpPass"></div>
    </div>
    <hr><label><?=$t('smtp_base_url')?></label><input type="text" id="smtpBaseUrl" placeholder="https://eccm.example.com" style="max-width:100%">
    <div style="margin-top:14px;display:flex;gap:8px;flex-wrap:wrap">
      <button onclick="testSMTP()"><?=$t('smtp_test_conn')?></button>
      <button class="btn-primary" onclick="saveSMTP()"><?=$t('save')?></button>
    </div><div id="smtpMsg"></div>
  </div>
  <div class="card">
    <h2><?=$t('smtp_test_email_title')?></h2>
    <p class="hint"><?=$t('smtp_test_hint')?></p>
    <div class="row-flex">
      <div><label><?=$t('smtp_recipient')?></label><input type="email" id="testEmailAddr" placeholder="test@example.com" style="max-width:100%"></div>
      <div style="display:flex;align-items:flex-end"><button onclick="sendTestEmail()"><?=$t('smtp_send_test')?></button></div>
    </div><div id="testEmailMsg"></div>
  </div>
</div>

<!-- ═══════════ TEMPLATES TAB ═══════════ -->
<div class="tab-content" id="tab-templates">
  <div class="card">
    <h2><?=$t('tpl_title')?></h2>
    <p class="hint"><?=$t('tpl_hint')?></p>
    <div id="templateEditor"></div>
    <div style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap">
      <button class="btn-primary" onclick="saveTemplates()"><?=$t('tpl_save')?></button>
      <button class="btn-danger" onclick="resetTemplates()"><?=$t('tpl_reset')?></button>
    </div><div id="templateMsg"></div>
  </div>
</div>

<!-- ═══════════ DATABASE TAB ═══════════ -->
<div class="tab-content" id="tab-database">
  <div class="card"><h2><?=$t('db_title')?></h2>
    <p class="hint"><?=$t('db_hint')?></p>
    <div class="row-flex"><div><label><?=$t('db_host')?></label><input type="text" id="dbHost" value="localhost"></div><div style="max-width:120px"><label><?=$t('db_port')?></label><input type="number" id="dbPort" value="3306"></div><div><label><?=$t('db_name')?></label><input type="text" id="dbName" value="eccm_db"></div></div>
    <div class="row-flex" style="margin-top:4px"><div><label><?=$t('db_user')?></label><input type="text" id="dbUser" value="root"></div><div><label><?=$t('db_pass')?></label><input type="password" id="dbPass"></div></div>
    <div style="margin-top:14px;display:flex;gap:8px"><button onclick="testDB()"><?=$t('db_test')?></button><button class="btn-primary" onclick="saveDB()"><?=$t('save')?></button></div><div id="dbMsg"></div>
  </div>
</div>

</div>

<!-- ═══════════ BACKUP / IMPORT TAB ═══════════ -->
<div class="tab-content" id="tab-backup">
  <div class="card">
    <h2><?=$t('backup_title')?></h2>
    <p class="hint"><?=$t('backup_hint')?></p>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px">
      <button class="btn-primary" onclick="doBackupAll()"><?=$t('backup_btn')?></button>
      <button onclick="document.getElementById('restoreFile').click()"><?=$t('restore_btn')?></button>
      <input id="restoreFile" type="file" accept=".json,application/json" style="display:none" onchange="doRestoreAll(this)">
    </div>
    <div id="backupMsg" style="margin-top:8px"></div>
  </div>
  <div class="card">
    <h2><?=$t('import_title')?></h2>
    <p class="hint"><?=$t('import_hint')?></p>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px">
      <button onclick="document.getElementById('importFile').click()"><?=$t('import_btn')?></button>
      <input id="importFile" type="file" accept=".json,application/json" style="display:none" onchange="doImportProfile(this)">
    </div>
    <div id="importMsg" style="margin-top:8px"></div>
  </div>
</div>

</div>

<!-- Edit User Modal -->
<div id="editModal" style="position:fixed;inset:0;background:rgba(0,0,0,.5);display:none;align-items:center;justify-content:center;z-index:100">
<div class="card" style="min-width:340px;max-width:440px"><h2><?=$t('edit_user')?></h2><input type="hidden" id="editId"><label><?=$t('username')?></label><input type="text" id="editUsername"><label><?=$t('email')?></label><input type="email" id="editEmail"><label><?=$t('new_password_hint')?></label><input type="password" id="editPassword"><label><?=$t('role')?></label><select id="editRole"><option value="user">User</option><option value="admin">Admin</option></select><label><?=$t('language')?></label><select id="editLang"><option value="">Standard</option><option value="de">Deutsch</option><option value="en">English</option></select><div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end"><button onclick="closeEditModal()"><?=$t('cancel')?></button><button class="btn-primary" onclick="updateUser()"><?=$t('save')?></button></div><div id="editMsg"></div></div>
</div>

<script>
const API = 'api/admin.php';
const T = <?=getTranslationsJSON()?>;

// Tabs
document.querySelectorAll('.tab').forEach(t => {
  t.addEventListener('click', () => {
    document.querySelectorAll('.tab').forEach(x => x.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(x => x.classList.remove('active'));
    t.classList.add('active');
    document.getElementById('tab-' + t.dataset.tab).classList.add('active');
  });
});

function api(action, data = {}) { return fetch(API, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action, ...data }) }).then(r => r.json()); }
function showMsg(el, text, ok) { el.innerHTML = '<div class="msg ' + (ok ? 'ok' : 'error') + '">' + text + '</div>'; setTimeout(() => el.innerHTML = '', 6000); }
function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

// ── General Settings ──
function loadGeneral() {
  api('get_general_settings').then(res => {
    if (!res.ok) return;
    document.getElementById('genAppName').value = res.settings.app_name || 'ECCM';
    document.getElementById('genDefaultLang').value = res.settings.default_language || 'de';
  });
}
function saveGeneral() {
  api('save_general_settings', { settings: {
    app_name: document.getElementById('genAppName').value,
    default_language: document.getElementById('genDefaultLang').value,
  }}).then(res => showMsg(document.getElementById('generalMsg'), res.message || res.error, !!res.ok));
}

// ── Users ──
function loadUsers() {
  api('list_users').then(res => {
    const tb = document.getElementById('userTableBody');
    if (!res.ok) { tb.innerHTML = '<tr><td colspan="6">'+T.error+'</td></tr>'; return; }
    tb.innerHTML = res.users.map(u =>
      '<tr><td>'+u.id+'</td><td>'+esc(u.username)+'</td><td>'+esc(u.email)+'</td><td>'+u.role+'</td><td>'+u.created_at+'</td><td>'+
      '<button class="btn-sm" onclick=\'openEditModal('+JSON.stringify(u)+')\'>' + T.edit + '</button> '+
      '<button class="btn-sm btn-danger" onclick="deleteUser('+u.id+',\''+esc(u.username)+'\')">' + T.delete + '</button></td></tr>'
    ).join('');
  });
}
function createUser() {
  api('create_user', { username: document.getElementById('newUsername').value, email: document.getElementById('newEmail').value, password: document.getElementById('newPassword').value, role: document.getElementById('newRole').value }).then(res => {
    var el = document.getElementById('createMsg');
    if (res.ok) { showMsg(el, T.user_created, true); document.getElementById('newUsername').value=''; document.getElementById('newEmail').value=''; document.getElementById('newPassword').value=''; loadUsers(); }
    else showMsg(el, res.error || T.error, false);
  });
}
function deleteUser(id, name) { if (!confirm(T.confirm_delete_user + ' "' + name + '"')) return; api('delete_user', { id }).then(res => { if (res.ok) loadUsers(); else alert(res.error); }); }
function openEditModal(u) { document.getElementById('editId').value=u.id; document.getElementById('editUsername').value=u.username; document.getElementById('editEmail').value=u.email; document.getElementById('editPassword').value=''; document.getElementById('editRole').value=u.role; document.getElementById('editLang').value=''; document.getElementById('editMsg').innerHTML=''; document.getElementById('editModal').style.display='flex'; }
function closeEditModal() { document.getElementById('editModal').style.display='none'; }
function updateUser() {
  api('update_user', { id: +document.getElementById('editId').value, username: document.getElementById('editUsername').value, email: document.getElementById('editEmail').value, password: document.getElementById('editPassword').value, role: document.getElementById('editRole').value, language: document.getElementById('editLang').value }).then(res => {
    var el = document.getElementById('editMsg');
    if (res.ok) { showMsg(el, T.saved, true); loadUsers(); setTimeout(closeEditModal, 800); }
    else showMsg(el, res.error || T.error, false);
  });
}

// ── SMTP ──
function getSMTPForm() { return { from_name: document.getElementById('smtpFromName').value, from_email: document.getElementById('smtpFromEmail').value, smtp_host: document.getElementById('smtpHost').value, smtp_port: +document.getElementById('smtpPort').value||587, smtp_encryption: document.getElementById('smtpEncryption').value, smtp_user: document.getElementById('smtpUser').value, smtp_pass: document.getElementById('smtpPass').value, base_url: document.getElementById('smtpBaseUrl').value }; }
function loadSMTPConfig() { api('get_smtp_config').then(res => { if (!res.ok) return; var c=res.config; document.getElementById('smtpFromName').value=c.from_name||'ECCM System'; document.getElementById('smtpFromEmail').value=c.from_email||''; document.getElementById('smtpHost').value=c.smtp_host||''; document.getElementById('smtpPort').value=c.smtp_port||587; document.getElementById('smtpEncryption').value=c.smtp_encryption||'tls'; document.getElementById('smtpUser').value=c.smtp_user||''; document.getElementById('smtpPass').value=''; document.getElementById('smtpPass').placeholder=c.smtp_pass||''; document.getElementById('smtpBaseUrl').value=c.base_url||''; }); }
function testSMTP() { showMsg(document.getElementById('smtpMsg'),'…',true); api('test_smtp',getSMTPForm()).then(res=>showMsg(document.getElementById('smtpMsg'),res.message,res.ok)); }
function saveSMTP() { api('save_smtp',getSMTPForm()).then(res=>showMsg(document.getElementById('smtpMsg'),res.message||res.error,!!res.ok)); }
function sendTestEmail() { var f=getSMTPForm(); f.test_email=document.getElementById('testEmailAddr').value; showMsg(document.getElementById('testEmailMsg'),'…',true); api('send_test_email',f).then(res=>showMsg(document.getElementById('testEmailMsg'),res.message,res.ok)); }

// ── Templates ──
var currentTemplates={}, templatePlaceholders={};
var templateLabels = { notification: T.tpl_notification, password_reset: T.tpl_password_reset };
function loadTemplates() { api('get_email_templates').then(res => { if (!res.ok) return; currentTemplates=res.templates; templatePlaceholders=res.placeholders; renderTemplateEditor(); }); }
function renderTemplateEditor() {
  var c=document.getElementById('templateEditor'); c.innerHTML='';
  Object.keys(currentTemplates).forEach(key => {
    var tpl=currentTemplates[key], phs=templatePlaceholders[key]||{};
    var s=document.createElement('div'); s.className='tpl-section';
    s.innerHTML='<h3>'+(templateLabels[key]||key)+'</h3><div class="tpl-grid"><div class="tpl-fields"><label>'+T.tpl_subject+'</label><input type="text" id="tpl_subject_'+key+'" value="'+esc(tpl.subject)+'"><label>'+T.tpl_body+'</label><textarea id="tpl_body_'+key+'">'+esc(tpl.body)+'</textarea><div style="margin-top:8px"><button class="btn-sm" onclick="previewTemplate(\''+key+'\')">'+T.tpl_preview+'</button></div><div class="tpl-preview" id="tpl_preview_'+key+'"></div></div><div class="tpl-placeholders"><h4>'+T.tpl_placeholders+'</h4>'+Object.keys(phs).map(ph=>'<div class="tpl-ph"><code onclick="insertPlaceholder(\''+key+'\',\''+ph+'\')">'+ph+'</code><span>'+esc(phs[ph])+'</span></div>').join('')+'<p style="font-size:11px;color:var(--muted);margin-top:8px">'+T.tpl_click_hint+'</p></div></div>';
    c.appendChild(s);
  });
}
function insertPlaceholder(key,ph) { var t=document.getElementById('tpl_body_'+key); if(!t)return; var s=t.selectionStart,e=t.selectionEnd,v=t.value; t.value=v.substring(0,s)+ph+v.substring(e); t.focus(); t.selectionStart=t.selectionEnd=s+ph.length; }
function previewTemplate(key) { api('preview_email_template',{subject:document.getElementById('tpl_subject_'+key).value,body:document.getElementById('tpl_body_'+key).value}).then(res=>{if(!res.ok)return;var el=document.getElementById('tpl_preview_'+key);el.textContent=T.tpl_subject+': '+res.subject+'\n\n'+res.body;el.style.display='block';}); }
function saveTemplates() { var t={}; Object.keys(currentTemplates).forEach(k=>{t[k]={subject:document.getElementById('tpl_subject_'+k).value,body:document.getElementById('tpl_body_'+k).value};}); api('save_email_templates',{templates:t}).then(res=>showMsg(document.getElementById('templateMsg'),res.message||res.error,!!res.ok)); }
function resetTemplates() { if(!confirm(T.tpl_reset_confirm))return; api('reset_email_templates').then(res=>{showMsg(document.getElementById('templateMsg'),res.message||res.error,!!res.ok);if(res.ok)loadTemplates();}); }

// ── Database ──
function loadDBConfig() { api('get_db_config').then(res=>{if(!res.ok)return;var c=res.config;document.getElementById('dbHost').value=c.host||'localhost';document.getElementById('dbPort').value=c.port||3306;document.getElementById('dbName').value=c.dbname||'eccm_db';document.getElementById('dbUser').value=c.username||'root';document.getElementById('dbPass').value='';document.getElementById('dbPass').placeholder=c.password||'';}); }
function getDBForm() { return {host:document.getElementById('dbHost').value,port:+document.getElementById('dbPort').value||3306,dbname:document.getElementById('dbName').value,username:document.getElementById('dbUser').value,password:document.getElementById('dbPass').value}; }
function testDB() { api('test_db',getDBForm()).then(res=>showMsg(document.getElementById('dbMsg'),res.message,res.ok)); }
function saveDB() { api('save_db',getDBForm()).then(res=>showMsg(document.getElementById('dbMsg'),res.message||res.error,!!res.ok)); }

// ── Backup / Restore / Import ──
function doBackupAll() {
  fetch('api/profiles.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({action:'backup_all'})})
    .then(r => r.json())
    .then(res => {
      if (!res.ok) { showMsg(document.getElementById('backupMsg'), res.error || 'Error', false); return; }
      var blob = new Blob([JSON.stringify(res.data, null, 2)], {type:'application/json'});
      var url = URL.createObjectURL(blob);
      var a = document.createElement('a');
      a.href = url; a.download = 'eccm-backup-' + new Date().toISOString().slice(0,10) + '.json';
      document.body.appendChild(a); a.click(); a.remove(); URL.revokeObjectURL(url);
      showMsg(document.getElementById('backupMsg'), 'Backup downloaded (' + Object.keys(res.data.profiles).length + ' profiles)', true);
    })
    .catch(e => showMsg(document.getElementById('backupMsg'), e.message, false));
}

function doRestoreAll(input) {
  var file = input.files && input.files[0]; if (!file) return;
  var reader = new FileReader();
  reader.onload = function() {
    try {
      var parsed = JSON.parse(reader.result);
      if (!parsed.profiles) throw new Error('No profiles found in backup');
      var count = Object.keys(parsed.profiles).length;
      if (!confirm('Restore ' + count + ' profiles from backup?\nExisting profiles with the same name will be skipped.')) return;
      fetch('api/profiles.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({action:'restore_all', data: parsed})})
        .then(r => r.json())
        .then(res => {
          showMsg(document.getElementById('backupMsg'), res.message || res.error || 'Done', !!res.ok);
        });
    } catch(e) { showMsg(document.getElementById('backupMsg'), 'Error: ' + e.message, false); }
    finally { input.value = ''; }
  };
  reader.readAsText(file);
}

function doImportProfile(input) {
  var file = input.files && input.files[0]; if (!file) return;
  var reader = new FileReader();
  reader.onload = function() {
    try {
      var parsed = JSON.parse(reader.result);
      if (!Array.isArray(parsed.devices) || !Array.isArray(parsed.links)) throw new Error('Invalid profile file');
      var suggested = (parsed.profileName || '').toString().trim() || ('Import ' + new Date().toLocaleString());
      var name = prompt('Profile name:', suggested);
      if (!name || !name.trim()) return;
      fetch('api/profiles.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({action:'import_profile', name: name.trim(), data: parsed})})
        .then(r => r.json())
        .then(res => {
          showMsg(document.getElementById('importMsg'), res.message || res.error || 'Done', !!res.ok);
        });
    } catch(e) { showMsg(document.getElementById('importMsg'), 'Error: ' + e.message, false); }
    finally { input.value = ''; }
  };
  reader.readAsText(file);
}

// ── Init ──
loadGeneral(); loadUsers(); loadDBConfig(); loadSMTPConfig(); loadTemplates();
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeEditModal(); });
</script>
</body></html>
