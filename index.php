<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/includes/i18n.php';
detectLanguage();
$isAdmin = (currentUserRole() === 'admin');
$username = htmlspecialchars(currentUsername());
$userId = currentUserId();
$t = function($k){ return __t($k); };
$appName = getAppName();
?>
<!doctype html>
<html lang="<?=($GLOBALS['ECCM_LANG']??'de')?>">
<head>
<link type="image/png" sizes="32x32" rel="icon" href="https://img.icons8.com/stickers/32/ethernet-on.png">
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Ethernet Cable Connection Manager</title>
<style>
:root{--bg:#0f1115;--panel:#171a21;--ink:#e8eaf1;--paper:#0E1117;--muted:#a6adbb;--line:#262a33;--prtPortH:75px;--portGapFull:9px;--portGapHalf:8px;--portW12:60px;--portW6:60px;--reserved-bg:#555;--accent:#3b82f6}
html,body{height:100%}
body{margin:0;background:var(--bg);color:var(--ink);font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif}
*,*::before,*::after{box-sizing:border-box}
header{padding:14px 18px;border-bottom:1px solid var(--line);display:flex;align-items:center;gap:12px;flex-wrap:wrap;background:linear-gradient(180deg,#12151b,#10131a)}
header h1{font-size:18px;margin:0;font-weight:650}
.badge{color:var(--muted);font-size:12px;border:1px solid var(--line);padding:2px 8px;border-radius:999px}
.user-info{color:var(--muted);font-size:13px;display:flex;align-items:center;gap:8px}
.user-info a{color:var(--ink);text-decoration:none;padding:4px 10px;border:1px solid var(--line);border-radius:6px;font-size:12px;font-weight:600}
.user-info a:hover{border-color:#2a2f3b}
.wrap{position:relative;display:grid;grid-template-columns:340px 1fr;gap:0;height:calc(100% - 62px)}
@media(max-width:1024px){.wrap{grid-template-columns:1fr;height:auto}}
aside,main{padding:16px}
aside{border-right:1px solid var(--line);background:var(--panel)}
main{overflow:auto;position:relative}
.card{background:#141821;border:1px solid var(--line);border-radius:10px;padding:12px;margin-bottom:12px}
.card h3{margin:0 0 8px 0;font-size:14px;font-weight:650}
label{display:block;font-size:12px;color:var(--muted);margin:8px 0 4px}
.muted{color:var(--muted)}.small{font-size:12px}
.mini{padding:4px 8px;font-size:12px;border-radius:6px}
.setting-row{display:flex;align-items:center;justify-content:space-between;gap:.75rem;margin:.5rem 0}
.setting-row select{padding:.35rem .5rem;border-radius:.5rem}
input[type=text],input[type=number],select{width:100%;max-width:100%;background:#0f131b;color:var(--ink);border:1px solid var(--line);border-radius:8px;padding:8px 10px;outline:none}
button,.btn{background:#11151d;color:var(--ink);border:1px solid var(--line);padding:8px 10px;border-radius:8px;cursor:pointer;font-weight:600}
button:hover{border-color:#2a2f3b}
.btn-primary{background:var(--accent);color:#fff;border-color:var(--accent)}
.btn-primary:hover{opacity:.9}
.btn-danger{border-color:#3b2222}
.dev-rows{display:flex;flex-direction:column;gap:12px}
.dev-row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.device-wrap{display:block;width:100%}.device-wrap.full{grid-column:1/-1}
.device{background:#131722;border:1px solid var(--line);border-radius:12px;padding:12px;position:relative;width:100%}
.device-head{display:flex;align-items:baseline;justify-content:space-between;gap:8px}
.device-title{font-size:15px;font-weight:650;display:flex;align-items:center;gap:8px}
.swatch{width:12px;height:12px;border-radius:3px;border:1px solid #0006;display:inline-block}
.device-actions{display:flex;gap:6px;flex-wrap:wrap}
.meta{font-size:12px;color:var(--muted);margin-top:2px}
.meta .inline-controls{margin-left:8px;display:inline-flex;gap:6px}
.ports-rows{display:flex;flex-direction:column;gap:8px;margin-top:10px}
.port-row{display:grid;gap:var(--portGapHalf)}
.port{height:var(--prtPortH)!important;position:relative;border:1px solid var(--line);background:#0f141d;border-radius:10px;padding:4px 6px 6px;cursor:pointer;text-align:center;user-select:none;transition:background .12s}
.port .num{font-size:12px;margin-top:2px;min-height:1.4em;display:inline-flex;align-items:center;justify-content:center;gap:4px;line-height:1}
.port .num .num-label{line-height:1}
.port .alias{font-size:12px;margin-top:2px;min-height:1.4em;white-space:nowrap;overflow:hidden;text-align:center;max-width:100%}
.port .peer{margin-top:2px;font-size:12px;font-weight:700;min-height:1.4em;white-space:nowrap;overflow:hidden;text-align:center;max-width:100%;position:relative}
.port .peer>span{display:inline-block;white-space:nowrap;position:relative;will-change:transform}
.port .peer.scroll>span{animation:eccm-peer-bounce 7s ease-in-out infinite;animation-delay:2s}
@keyframes eccm-peer-bounce{0%{transform:translateX(0)}35%{transform:translateX(calc(-1*var(--peer-diff)))}50%{transform:translateX(calc(-1*var(--peer-diff)))}85%{transform:translateX(0)}100%{transform:translateX(0)}}
.port:not(.connected):not(.reserved) .num,.port:not(.connected):not(.reserved) .alias,.port:not(.connected):not(.reserved) .peer{color:#fff}
.port.reserved .num,.port.reserved .alias,.port.reserved .peer{color:#fff!important}
.port .ind{width:5px;height:5px;display:inline-block;border-radius:1px;background:currentColor;opacity:.9;line-height:0;flex:0 0 auto;vertical-align:middle}
.port .ind.ind--slow{width:10px;height:10px;border-radius:2px;background:#fff;color:#e11;display:inline-flex;align-items:center;justify-content:center;font-size:8px;font-weight:700}
.port .ind.vlan-diamond{transform:rotate(45deg)}.port .ind.ind--off{opacity:0;pointer-events:none}
.slow-warn{font-size:10px;line-height:1;display:inline-block;vertical-align:middle}
.grid-slim{width:100%;border-collapse:collapse;border:1px solid var(--line);border-radius:8px;overflow:hidden;table-layout:fixed}
.grid-slim th,.grid-slim td{font-size:13px;text-align:left;padding:8px 10px;border-bottom:1px solid var(--line);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.grid-slim th{background:#121722;color:var(--muted);font-weight:600}
.conn-row.highlight td{background:#1e293b}
.conn-row td.deviceA .devName,.conn-row td.deviceB .devName{color:#fff;font-weight:400}
.conn-row.highlight td.deviceA .devName,.conn-row.highlight td.deviceB .devName{color:var(--devColor);font-weight:700}
.conn-row.reserved-row td{background:#121622}.conn-row.reserved-row:hover td{background:#151b2a}
.color-picker-row{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.swatch-lg{width:24px;height:24px;border-radius:6px;border:1px solid #0006;display:inline-block}
.color-btn{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;background:#0f141d;border:1px solid var(--line);border-radius:8px;cursor:pointer;font-weight:600;color:var(--ink)}.color-btn:hover{border-color:#2a2f3b}
.palette-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.5);display:none;align-items:center;justify-content:center;z-index:999}
.palette{background:#141821;border:1px solid var(--line);border-radius:12px;padding:12px;min-width:280px;max-width:90vw}
.palette h4{margin:0 0 10px 0;font-size:14px}
.palette-grid{display:grid;grid-template-columns:repeat(4,36px);gap:8px;justify-content:center}
.chip{width:36px;height:36px;border-radius:8px;border:1px solid rgba(0,0,0,.35);cursor:pointer}
.palette .actions{display:flex;justify-content:flex-end;margin-top:12px}
.modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.5);display:none;align-items:center;justify-content:center;z-index:60}
.modal{background:#141821;border:1px solid var(--line);border-radius:12px;padding:14px;min-width:300px;max-width:90vw}
.modal h4{margin:0 0 10px 0;font-size:14px}
.modal .row{display:flex;gap:8px;align-items:center;margin:6px 0}
.modal label{margin:0;color:var(--ink);font-size:13px}
.modal select{background:#0f131b;color:var(--ink);border:1px solid var(--line);border-radius:8px;padding:6px 10px}
.modal .actions{display:flex;gap:8px;justify-content:flex-end;margin-top:12px}
/* Theme bright overrides */
html.theme-bright body,html.theme-bright .card,html.theme-bright aside,html.theme-bright main,html.theme-bright #settingsPanel{background:#fff!important;color:#111!important;border-color:#ccc!important;--muted:#222;--paper:#fff}
html.theme-bright .slideover__panel,html.theme-bright .slideover__header,html.theme-bright .slideover__footer,html.theme-bright .slideover__body{background:#fff!important;color:#111!important;border-color:#ddd!important;--paper:#fff;--ink:#111}
html.theme-bright .slideover__panel .card{background:#f8f9fa!important;border-color:#ddd!important}
html.theme-bright .slideover__panel label,html.theme-bright .slideover__panel h3{color:#111!important}
html.theme-bright .slideover__panel select,html.theme-bright .slideover__panel input{background:#fff!important;color:#111!important;border-color:#ccc!important}
html.theme-bright header{background:#fff!important;border-bottom:1px solid #e5e7eb!important;color:#111!important}
html.theme-bright header h1{color:#111!important}
html.theme-bright header .badge{background:#fff!important;color:#444!important;border-color:#e0e6ef!important}
html.theme-bright header button,html.theme-bright header .btn,html.theme-bright header select{background:#fff!important;color:#111!important;border:1px solid #d8dee8!important}
html.theme-bright .device{background:#fff!important;border-color:#ccc!important}
html.theme-bright .port:not(.connected):not(.reserved){background:#fff!important;color:#000!important}
html.theme-bright .port:not(.connected):not(.reserved) .num,html.theme-bright .port:not(.connected):not(.reserved) .alias,html.theme-bright .port:not(.connected):not(.reserved) .peer{color:#000!important}
html.theme-bright .port.reserved{background:#555!important;color:#fff!important}
html.theme-bright button:not(.chip),html.theme-bright .btn,html.theme-bright input[type="text"],html.theme-bright input[type="number"],html.theme-bright select{background:#fff!important;color:#111!important;border:1px solid #ccc!important}
html.theme-bright .grid-slim th{background:#f6f7f9!important;color:#111!important;border-color:#ccc!important}
html.theme-bright .grid-slim td{background:#fff!important;color:#111!important;border-color:#ddd!important}
html.theme-bright .conn-row.highlight td{background:#e8f0fe!important;color:#000!important}
html.theme-bright .palette{background:#fff!important;border-color:#ccc!important;color:#000!important}
html.theme-bright .modal{background:#fff!important;border:1px solid #ccc!important;color:#111!important}
html.theme-bright .modal h4,html.theme-bright .modal label{color:#111!important}
html.theme-bright .modal-backdrop .modal{background:#fff!important;color:#111!important}
html.theme-bright .perm-table th,html.theme-bright .notif-table th{background:#f6f7f9!important;color:#444!important;border-color:#ddd!important}
html.theme-bright .perm-table td,html.theme-bright .notif-table td{border-color:#eee!important;color:#111!important}
html.theme-bright .perm-table,html.theme-bright .notif-table{border-color:#ddd!important}
html.theme-bright .modal input[type=text],html.theme-bright .modal input[type=number],html.theme-bright .modal select{background:#fff!important;color:#111!important;border:1px solid #ccc!important}
html.theme-bright .modal .actions button{background:#fff!important;color:#111!important;border:1px solid #ccc!important}
html.theme-bright .modal .actions .btn-primary{background:#3b82f6!important;color:#fff!important;border-color:#3b82f6!important}
html.theme-bright .modal .small.muted,html.theme-bright .notif-hint{color:#666!important}
html.theme-bright .notif-table td:nth-child(2){color:#666!important}
/* Slideover */
.slideover{position:fixed;inset:0;z-index:5000;display:none;font:inherit;color:var(--ink)}.slideover.is-open{display:block}
.slideover__backdrop{position:absolute;inset:0;background:rgba(0,0,0,.35);opacity:0;transition:opacity .2s ease}.slideover.is-open .slideover__backdrop{opacity:1}
.slideover__panel{position:absolute;top:0;right:0;bottom:0;width:clamp(320px,36vw,480px);background:var(--paper);color:var(--ink);border-left:1px solid var(--line);box-shadow:-12px 0 32px rgba(0,0,0,.28);display:flex;flex-direction:column;transform:translateX(100%);transition:transform .25s ease}.slideover.is-open .slideover__panel{transform:translateX(0)}
.slideover__header,.slideover__footer{background:var(--paper);color:var(--ink);padding:12px 16px}
.slideover__header{display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--line)}
.slideover__footer{border-top:1px solid var(--line)}
.slideover__body{padding:12px 16px;overflow:auto;flex:1 1 auto;background:var(--paper);color:var(--ink)}
.theme-toggle{display:inline-flex;border-radius:10px;overflow:hidden}
.theme-toggle button{padding:6px 12px;border:1px solid var(--line);background:#11151d;color:var(--ink);cursor:pointer;font-weight:600;line-height:1;border-right:none}
.theme-toggle button:first-child{border-top-left-radius:10px;border-bottom-left-radius:10px}
.theme-toggle button:last-child{border-right:1px solid var(--line);border-top-right-radius:10px;border-bottom-right-radius:10px}
.theme-toggle button.active{box-shadow:inset 0 0 0 2px #e5e7eb}
html.theme-bright .theme-toggle button{background:#fff;color:#111;border-color:#ccc}
html.theme-bright .theme-toggle button.active{box-shadow:inset 0 0 0 2px #000}
main::-webkit-scrollbar{width:10px}main::-webkit-scrollbar-track{background:var(--panel)}main::-webkit-scrollbar-thumb{background-color:#555;border-radius:6px;border:2px solid var(--panel)}
html.theme-bright main::-webkit-scrollbar-track{background:#fff!important}html.theme-bright main::-webkit-scrollbar-thumb{background-color:#bbb!important;border:2px solid #fff!important}
:root{--hover-bg:#141821;--hover-border:#2b3140;--hover-ink:#e8eaf1}
html.theme-bright{--hover-bg:#fffffe;--hover-border:#cfd6e4;--hover-ink:#111}
.hovercard{position:fixed;z-index:9999;display:none;min-width:220px;max-width:320px;padding:8px 10px;font-size:12px;line-height:1.3;color:var(--hover-ink);background:var(--hover-bg);border:1px solid var(--hover-border);border-radius:10px;box-shadow:0 6px 20px rgba(0,0,0,.25);pointer-events:none;white-space:nowrap}
.hovercard .hc-title{font-weight:700;margin-bottom:4px}.hovercard .hc-row{display:flex;gap:8px}.hovercard .hc-k{color:var(--muted);min-width:72px}
.speed-menu{position:fixed;z-index:9999;background:#141821;color:#e8eaf1;border:1px solid var(--line);border-radius:8px;padding:6px;box-shadow:0 8px 24px rgba(0,0,0,.35)}
.speed-menu select{background:#0f131b;color:#e8eaf1;border:1px solid var(--line);border-radius:6px;padding:6px 8px;font-size:12px}
html.theme-bright .speed-menu{background:#fff;color:#111;border-color:#ccc}
html.theme-bright .speed-menu select{background:#fff;color:#111;border-color:#ccc}
html.theme-bright .user-info a{background:#fff!important;color:#111!important;border:1px solid #ccc!important}
html.theme-bright .port .ind.ind--slow{background:#000;color:#e11}
html.theme-bright .port .ind{opacity:.8}
/* Save indicator */
.save-indicator{position:fixed;bottom:16px;right:16px;background:#22c55e;color:#000;padding:6px 14px;border-radius:8px;font-size:13px;font-weight:600;opacity:0;transition:opacity .3s;z-index:9999;pointer-events:none}
.save-indicator.show{opacity:1}.save-indicator.error{background:#ef4444;color:#fff}
/* Permission modal */
.perm-modal{max-width:620px;width:90vw}
.perm-table{width:100%;border-collapse:collapse;font-size:12px;margin-top:8px}
.perm-table th,.perm-table td{padding:5px 6px;border-bottom:1px solid var(--line);text-align:center}
.perm-table th{background:#121722;color:var(--muted);font-weight:600;font-size:11px}
.perm-table td:first-child,.perm-table th:first-child{text-align:left}
.perm-table input[type=checkbox]{width:16px;height:16px}
.profile-owner{font-size:11px;color:var(--muted);margin-top:2px}
.perm-badge{display:inline-block;font-size:10px;padding:1px 6px;border-radius:4px;background:#1e293b;color:#94a3b8;margin-left:4px}
html.theme-bright .perm-badge{background:#e2e8f0;color:#475569}
/* Notification modal */
.notif-modal{max-width:660px;width:92vw}
.notif-table{width:100%;border-collapse:collapse;font-size:12px;margin-top:8px}
.notif-table th,.notif-table td{padding:5px 6px;border-bottom:1px solid var(--line);text-align:center}
.notif-table th{background:#121722;color:var(--muted);font-weight:600;font-size:11px}
.notif-table td:first-child,.notif-table th:first-child{text-align:left}
.notif-table td:nth-child(2),.notif-table th:nth-child(2){text-align:left;font-size:11px;color:var(--muted)}
.notif-table input[type=checkbox]{width:16px;height:16px}
.notif-hint{font-size:12px;color:var(--muted);margin:6px 0 10px}
</style>
</head>
<body>

<header>
  <h1><?=htmlspecialchars($appName)?></h1>
  <span class="badge"><?=$t('app_version')?></span>
  <div style="margin-left:auto;display:flex;gap:8px;align-items:center">
    <div class="user-info">
      👤 <?=$username?>
      <?php if($isAdmin):?><a href="admin.php"><?=$t('admin')?></a><?php endif;?>
      <a href="logout.php"><?=$t('logout')?></a>
    </div>
    <button id="btnNotifications" class="btn" title="<?=$t('notifications')?>">🔔</button>
    <button id="btnSettings" class="btn"><?=$t('settings')?></button>
    <button id="printSheet"><?=$t('print_layout')?></button>
  </div>
</header>

<div class="wrap">
<aside>
  <div class="card">
    <h3><?=$t('profiles')?></h3>
    <label for="profileSelect"><?=$t('active_profile')?></label>
    <select id="profileSelect"></select>
    <div id="profileInfo" class="profile-owner"></div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px">
      <button id="newProfileBtn"><?=$t('new')?></button>
      <button id="renameProfileBtn"><?=$t('rename')?></button>
      <button id="duplicateProfileBtn"><?=$t('duplicate')?></button>
      <button id="deleteProfileBtn" class="btn-danger"><?=$t('delete')?></button>
      <button id="permProfileBtn" class="btn" title="<?=$t('manage_perms')?>"><?=$t('permissions')?></button>
      <button id="exportProfileBtn" class="btn"><?=$t('export')?></button>
      <button id="importProfileBtn" class="btn"><?=$t('import')?></button>
      <input id="importProfileFile" type="file" accept=".json,application/json" style="display:none">
    </div>
  </div>
  <div class="card">
    <h3><?=$t('add_device')?></h3>
    <label for="devName"><?=$t('device_name')?></label>
    <input id="devName" type="text" placeholder="<?=$t('device_name')?>" />
    <div class="row"><div><label for="devPorts"><?=$t('device_ports')?></label><input id="devPorts" type="number" inputmode="numeric" min="1" max="9999" value="24" /></div></div>
    <label><?=$t('device_colour')?></label>
    <div class="color-picker-row">
      <button type="button" class="color-btn" id="openPalette"><?=$t('select_colour')?></button>
      <span id="devColorPreview" class="swatch swatch-lg" title="<?=$t('device_colour')?>"></span>
    </div>
    <div style="margin-top:10px;display:flex;gap:8px">
      <button id="addBtn"><?=$t('add_device_btn')?></button>
      <button id="clearAll" class="btn-danger" title="<?=$t('clear_all')?>"><?=$t('clear_all')?></button>
    </div>
    <div class="small muted" style="margin-top:6px"><?=$t('port_help')?><br><strong><?=$t('unlink_help')?></strong><br><strong><?=$t('alias_help')?></strong><br><strong><?=$t('reserve_help')?></strong></div>
  </div>
  <div class="card">
    <h3><?=$t('backup_restore')?></h3>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <button id="backupAllBtn" class="btn"><?=$t('backup_all')?></button>
      <button id="restoreAllBtn" class="btn"><?=$t('restore_all')?></button>
      <input id="restoreAllFile" type="file" accept=".json,application/json" style="display:none">
    </div>
  </div>
  <div class="card">
    <h3><?=$t('find_connection')?></h3>
    <input id="searchBox" type="text" placeholder="<?=$t('search_placeholder')?>" />
  </div>
</aside>

<main>
  <div class="card"><h3><?=$t('devices')?></h3><div id="devRows" class="dev-rows"></div></div>
  <div class="card">
    <h3><?=$t('connections')?></h3>
    <table class="grid-slim" id="connTable">
      <thead><tr><th>#</th><th><?=$t('conn_device_a')?></th><th><?=$t('conn_port')?></th><th><?=$t('conn_alias')?></th><th>⇄</th><th><?=$t('conn_device_b')?></th><th><?=$t('conn_port')?></th><th><?=$t('conn_alias')?></th><th></th></tr></thead>
      <tbody id="connBody"></tbody>
    </table>
    <div class="small muted"><?=$t('conn_highlight_hint')?></div>
  </div>
</main>
</div>

<div class="save-indicator" id="saveIndicator"></div>

<!-- Settings Slide-Over -->
<div id="settingsModal" class="slideover" aria-hidden="true">
  <div class="slideover__backdrop" data-close></div>
  <aside class="slideover__panel" role="dialog" aria-modal="true" tabindex="-1">
    <header class="slideover__header"><h3><?=$t('settings')?></h3></header>
    <div class="slideover__body">
      <div class="card"><div class="setting-row"><label><?=$t('theme')?></label><div class="theme-toggle" role="group"><button type="button" id="themeDark" data-theme="dark" aria-pressed="false">🌙 <?=$t('dark')?></button><button type="button" id="themeLight" data-theme="bright" aria-pressed="false">☀️ <?=$t('light')?></button></div></div></div>
      <div class="card">
        <div class="setting-row"><label for="enablePortRename"><?=$t('enable_port_rename')?></label><input id="enablePortRename" type="checkbox" /></div>
        <div class="setting-row"><label for="maxPorts" style="flex:1;"><?=$t('max_ports_device')?></label><input id="maxPorts" type="number" min="0" max="9999" style="flex:1;max-width:120px;" /></div>
        <div class="setting-row"><label for="userLang" style="flex:1;"><?=$t('language')?></label><select id="userLang" style="flex:1;max-width:160px;"><option value="de">Deutsch</option><option value="en">English</option></select></div>
        <div style="margin-top:10px"><button id="saveSettingsBtn" class="btn"><?=$t('save')?></button></div>
      </div>
    </div>
    <footer class="slideover__footer"><button class="btn" data-close><?=$t('close')?></button></footer>
  </aside>
</div>

<!-- Palette -->
<div class="palette-backdrop" id="paletteModal"><div class="palette"><h4><?=$t('select_colour')?></h4><div style="display:flex;gap:20px;justify-content:center;align-items:flex-start;flex-wrap:wrap"><div class="palette-grid" id="paletteGrid"></div></div><div class="actions"><button type="button" id="paletteClose"><?=$t('close')?></button></div></div></div>

<!-- Layout modal -->
<div class="modal-backdrop" id="layoutModal"><div class="modal"><h4><?=$t('layout_options')?></h4><div class="row"><label style="min-width:110px"><?=$t('layout_device')?></label><span id="layoutDeviceName" class="small muted"></span></div><div class="row"><label style="min-width:110px"><?=$t('layout_row_width')?></label><select id="layoutFullRow"><option value="auto"><?=$t('layout_auto')?></option><option value="full"><?=$t('layout_force_full')?></option></select></div><div class="row" id="optMidWrap"><label style="min-width:110px"><?=$t('layout_13_24')?></label><select id="layoutMidWrap"><option value="balanced"><?=$t('layout_balanced')?></option><option value="twelve"><?=$t('layout_twelve')?></option></select></div><div class="row" id="optSmallWrap"><label style="min-width:110px"><?=$t('layout_12_or_less')?></label><select id="layoutSmallWrap"><option value="single"><?=$t('layout_single_row')?></option><option value="split"><?=$t('layout_split')?></option></select></div><div class="row" id="optDualLink"><label style="min-width:110px"><?=$t('layout_dual_link')?></label><select id="layoutDualLink"><option value="off"><?=$t('layout_normal')?></option><option value="on"><?=$t('layout_dual_on')?></option></select></div><div class="row" id="optNumbering"><label style="min-width:110px"><?=$t('layout_numbering')?></label><select id="layoutNumbering"><option value="row"><?=$t('layout_left_right')?></option><option value="column"><?=$t('layout_top_bottom')?></option><option value="column-bt"><?=$t('layout_bottom_top')?></option></select></div><div class="actions"><button id="layoutCancel"><?=$t('cancel')?></button><button id="layoutSave"><?=$t('save')?></button></div></div></div>

<!-- Edit Device Modal -->
<div class="modal-backdrop" id="editDeviceModal"><div class="modal"><h4><?=$t('edit')?> <?=$t('layout_device')?></h4><div class="row"><label for="editDevName" style="min-width:100px"><?=$t('device_name')?></label><input id="editDevName" type="text" /></div><div class="row"><label for="editDevPorts" style="min-width:100px"><?=$t('device_ports')?></label><input id="editDevPorts" type="number" min="1" max="9999" /></div><div class="row"><label style="min-width:100px"><?=$t('device_colour')?></label><div class="color-picker-row"><button type="button" class="color-btn" id="editOpenPalette"><?=$t('select_colour')?></button><span id="editDevColorPreview" class="swatch swatch-lg"></span></div></div><div class="actions"><button id="editDevCancel"><?=$t('cancel')?></button><button id="editDevSave" class="btn"><?=$t('save')?></button></div></div></div>

<!-- Create Profile Modal -->
<div class="modal-backdrop" id="newProfileModal"><div class="modal perm-modal">
  <h4><?=$t('new_profile')?></h4>
  <label><?=$t('profile_name')?></label>
  <input id="newProfName" type="text" placeholder="<?=$t('profile_name_ph')?>" />
  <h4 style="margin-top:14px"><?=$t('perms_for_others')?></h4>
  <div class="small muted" style="margin-bottom:8px"><?=$t('perms_owner_hint')?></div>
  <table class="perm-table" id="newProfPermsTable">
    <thead><tr><th><?=$t('username')?></th><th><?=$t('perm_view')?></th><th><?=$t('perm_patch')?></th><th><?=$t('perm_add_patch')?></th><th><?=$t('perm_edit_device')?></th><th><?=$t('perm_add_device')?></th><th><?=$t('perm_delete')?></th><th><?=$t('perm_manage')?></th></tr></thead>
    <tbody id="newProfPermsBody"></tbody>
  </table>
  <div class="actions" style="margin-top:14px"><button id="newProfCancel"><?=$t('cancel')?></button><button id="newProfCreate" class="btn-primary"><?=$t('create')?></button></div>
</div></div>

<!-- Manage Permissions Modal -->
<div class="modal-backdrop" id="permModal"><div class="modal perm-modal">
  <h4><?=$t('manage_perms')?>: <span id="permProfileName"></span></h4>
  <div class="small muted" style="margin-bottom:8px"><?=$t('owner_admin_hint')?></div>
  <table class="perm-table">
    <thead><tr><th><?=$t('username')?></th><th><?=$t('perm_view')?></th><th><?=$t('perm_patch')?></th><th><?=$t('perm_add_patch')?></th><th><?=$t('perm_edit_device')?></th><th><?=$t('perm_add_device')?></th><th><?=$t('perm_delete')?></th><th><?=$t('perm_manage')?></th></tr></thead>
    <tbody id="permModalBody"></tbody>
  </table>
  <div class="actions" style="margin-top:14px"><button id="permCancel"><?=$t('cancel')?></button><button id="permSave" class="btn-primary"><?=$t('save')?></button></div>
</div></div>

<!-- Notification Settings Modal -->
<div class="modal-backdrop" id="notifModal"><div class="modal notif-modal">
  <h4><?=$t('notif_title')?></h4>
  <p class="notif-hint"><?=$t('notif_hint')?></p>
  <table class="notif-table" id="notifTable">
    <thead><tr>
      <th><?=$t('profiles')?></th><th><?=$t('owner')?></th>
      <th><?=$t('notif_device_change')?></th>
      <th><?=$t('notif_device_add')?></th>
      <th><?=$t('notif_patch_change')?></th>
      <th><?=$t('notif_patch_add')?></th>
    </tr></thead>
    <tbody id="notifTableBody"></tbody>
  </table>
  <div class="actions" style="margin-top:14px">
    <button id="notifCancel"><?=$t('cancel')?></button>
    <button id="notifSave" class="btn-primary"><?=$t('save')?></button>
  </div>
</div></div>

<script>
/* ===================== GLOBALS ===================== */
var CURRENT_USER_ID = <?=$userId?>;
var IS_ADMIN = <?=$isAdmin?'true':'false'?>;
var T = <?=getTranslationsJSON()?>;

var defaultState = {devices:[],links:[],portAliases:{},reservedPorts:{},portSpeeds:{},portVlans:{},portLinkedTo:{}};
function deepClone(o){return JSON.parse(JSON.stringify(o));}
function uid(){return 'id_'+Math.random().toString(36).slice(2,10);}

var store = {current:'Default',profiles:{'Default':deepClone(defaultState)},settings:{maxPorts:512,enablePortRename:false}};
var profileMeta = {}; // {name: {id, owner, owner_id, is_owner, perms:{...}}}
var state = store.profiles[store.current];

function normalizeState(st){
  st.devices=Array.isArray(st.devices)?st.devices:[];
  st.links=Array.isArray(st.links)?st.links:[];
  st.portAliases=st.portAliases||{};st.reservedPorts=st.reservedPorts||{};
  st.portSpeeds=st.portSpeeds||{};st.portLinkedTo=st.portLinkedTo||{};
  st.devices.forEach(function(d){
    if(d.forceFullRow===undefined)d.forceFullRow=false;
    if(d.midWrapMode===undefined)d.midWrapMode='balanced';
    if(d.smallWrap===undefined)d.smallWrap=false;
    if(d.dualLink===undefined)d.dualLink=false;
    if(d.numbering===undefined)d.numbering='row';
    if(d.numbering!=='row'&&d.numbering!=='column'&&d.numbering!=='column-bt')d.numbering='row';
  });
}

/* ── Current profile permissions ── */
function currentPerms(){
  var m=profileMeta[store.current];
  if(!m)return{can_view:1,can_patch:1,can_add_patch:1,can_edit_device:1,can_add_device:1,can_delete:1,can_manage:1};
  return m.perms||{};
}

/* ===================== SERVER PERSISTENCE ===================== */
var __saveTimer=null;
function saveStore(){
  clearTimeout(__saveTimer);
  __saveTimer=setTimeout(function(){
    var payload={
      action:'save',
      profileName: store.current,
      data: store.profiles[store.current],
      settings: store.settings||{}
    };
    fetch('api/profiles.php',{
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify(payload)
    }).then(function(r){return r.json();}).then(function(res){
      showSaveIndicator(!res.ok);
    }).catch(function(){showSaveIndicator(true);});
  },600);
}

function showSaveIndicator(isError){
  var el=document.getElementById('saveIndicator');
  el.textContent=isError?T.save_error:T.saved;
  el.classList.toggle('error',isError);el.classList.add('show');
  setTimeout(function(){el.classList.remove('show');},1500);
}

function apiCall(action,data){
  return fetch('api/profiles.php',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify(Object.assign({action:action},data||{}))
  }).then(function(r){return r.json();});
}

/* ===================== DOM HELPERS ===================== */
function $(s){return document.querySelector(s);}
function $all(s){return Array.prototype.slice.call(document.querySelectorAll(s));}
function el(tag,attrs){var node=document.createElement(tag);attrs=attrs||{};Object.keys(attrs).forEach(function(k){var v=attrs[k];if(k==='dataset'){Object.keys(v).forEach(function(dk){if(v[dk]!==undefined)node.dataset[dk]=v[dk];})}else if(k==='class'){node.className=v}else if(k.indexOf('on')===0&&typeof v==='function'){node.addEventListener(k.slice(2),v)}else{node.setAttribute(k,v)}});for(var i=2;i<arguments.length;i++){var c=arguments[i];if(c==null)continue;node.appendChild(c.nodeType?c:document.createTextNode(c))}return node;}
function deviceById(id){for(var i=0;i<state.devices.length;i++){if(state.devices[i].id===id)return state.devices[i];}return null;}
function indexById(id){return state.devices.findIndex(function(d){return d.id===id;});}

/* ===================== HIGHLIGHT TUNING ===================== */
const HI_OUTLINE = 0;
const HI_RING    = 5;
const HI_BLUR    = 0;
</script>

<!-- Core ECCM rendering engine (devices, ports, connections, palette, etc.) -->
<script src="assets/eccm-core.js"></script>

<script>
/* ===================== PROFILES UI (server-backed) ===================== */
function refreshProfileSelect(){
  var sel=document.getElementById('profileSelect');
  sel.innerHTML='';
  Object.keys(store.profiles).forEach(function(name){
    var opt=document.createElement('option');opt.value=name;opt.textContent=name;
    if(name===store.current)opt.selected=true;
    sel.appendChild(opt);
  });
  sel.value=store.current;
  updateProfileInfo();
}

function updateProfileInfo(){
  var info=document.getElementById('profileInfo');
  var m=profileMeta[store.current];
  if(!m){info.textContent='';return;}
  var badges='';
  var p=m.perms||{};
  if(m.is_owner)badges+='<span class="perm-badge">Owner</span>';
  else{
    if(p.can_patch||p.can_add_patch)badges+='<span class="perm-badge">Patch</span>';
    if(p.can_edit_device||p.can_add_device)badges+='<span class="perm-badge">Devices</span>';
    if(!p.can_patch&&!p.can_add_patch&&!p.can_edit_device&&!p.can_add_device)badges+='<span class="perm-badge">Nur Lesen</span>';
  }
  info.innerHTML=T.owner+': '+m.owner+' '+badges;
}

function switchProfile(name){
  if(!store.profiles[name])return;
  store.current=name;state=store.profiles[name];normalizeState(state);
  apiCall('switch_profile',{name:name});
  refreshProfileSelect();render();
}

document.getElementById('profileSelect').addEventListener('change',function(){switchProfile(this.value);});

/* ── New Profile (with permissions modal) ── */
document.getElementById('newProfileBtn').addEventListener('click',function(){
  document.getElementById('newProfName').value='';
  // Load users for permission table
  apiCall('list_users').then(function(res){
    if(!res.ok)return;
    var tb=document.getElementById('newProfPermsBody');tb.innerHTML='';
    res.users.forEach(function(u){
      if(u.id==CURRENT_USER_ID)return; // skip self (owner always has full)
      var tr=document.createElement('tr');tr.dataset.userId=u.id;
      tr.innerHTML='<td>'+u.username+'</td>'
        +'<td><input type="checkbox" data-p="can_view" checked></td>'
        +'<td><input type="checkbox" data-p="can_patch"></td>'
        +'<td><input type="checkbox" data-p="can_add_patch"></td>'
        +'<td><input type="checkbox" data-p="can_edit_device"></td>'
        +'<td><input type="checkbox" data-p="can_add_device"></td>'
        +'<td><input type="checkbox" data-p="can_delete"></td>'
        +'<td><input type="checkbox" data-p="can_manage"></td>';
      tb.appendChild(tr);
    });
    document.getElementById('newProfileModal').style.display='flex';
  });
});
document.getElementById('newProfCancel').addEventListener('click',function(){document.getElementById('newProfileModal').style.display='none';});
document.getElementById('newProfCreate').addEventListener('click',function(){
  var name=document.getElementById('newProfName').value.trim();
  if(!name){alert(T.enter_profile_name);return;}
  // Collect permissions
  var perms=[];
  document.querySelectorAll('#newProfPermsBody tr').forEach(function(tr){
    var uid=parseInt(tr.dataset.userId);
    var p={user_id:uid};
    tr.querySelectorAll('input[type=checkbox]').forEach(function(cb){p[cb.dataset.p]=cb.checked?1:0;});
    if(p.can_view)perms.push(p); // only add if at least view
  });
  apiCall('create_profile',{name:name,permissions:perms}).then(function(res){
    if(res.error){alert(res.error);return;}
    document.getElementById('newProfileModal').style.display='none';
    loadFromServer(); // full reload
  });
});

/* ── Rename ── */
document.getElementById('renameProfileBtn').addEventListener('click',function(){
  var cur=store.current;
  var name=prompt(T.rename+' "'+cur+'":',cur);
  if(!name||!name.trim()||name.trim()===cur)return;
  apiCall('rename_profile',{oldName:cur,newName:name.trim()}).then(function(res){
    if(res.error){alert(res.error);return;}
    loadFromServer();
  });
});

/* ── Duplicate ── */
document.getElementById('duplicateProfileBtn').addEventListener('click',function(){
  var name=prompt(T.duplicate+':',store.current+' (Copy)');
  if(!name||!name.trim())return;
  apiCall('duplicate_profile',{sourceName:store.current,newName:name.trim()}).then(function(res){
    if(res.error){alert(res.error);return;}
    loadFromServer();
  });
});

/* ── Delete ── */
document.getElementById('deleteProfileBtn').addEventListener('click',function(){
  if(Object.keys(store.profiles).length<=1){alert(T.min_one_profile);return;}
  if(!confirm(T.confirm_delete_profile+' "'+store.current+'"?'))return;
  apiCall('delete_profile',{name:store.current}).then(function(res){
    if(res.error){alert(res.error);return;}
    loadFromServer();
  });
});

/* ── Permissions modal ── */
document.getElementById('permProfileBtn').addEventListener('click',function(){
  var m=profileMeta[store.current];
  if(!m||(!m.is_owner&&!(m.perms||{}).can_manage&&!IS_ADMIN)){alert(T.only_owner_perms);return;}
  document.getElementById('permProfileName').textContent=store.current;
  apiCall('get_permissions',{name:store.current}).then(function(res){
    if(res.error){alert(res.error);return;}
    // Also get user list
    apiCall('list_users').then(function(ur){
      if(!ur.ok)return;
      var existingPerms={};
      (res.permissions||[]).forEach(function(p){existingPerms[p.user_id]=p;});
      var tb=document.getElementById('permModalBody');tb.innerHTML='';
      var ownerUid=m.owner_id;
      ur.users.forEach(function(u){
        if(u.id==ownerUid)return; // owner always has full
        var ep=existingPerms[u.id]||{};
        var tr=document.createElement('tr');tr.dataset.userId=u.id;
        tr.innerHTML='<td>'+u.username+'</td>'
          +'<td><input type="checkbox" data-p="can_view" '+(ep.can_view?'checked':'')+'></td>'
          +'<td><input type="checkbox" data-p="can_patch" '+(ep.can_patch?'checked':'')+'></td>'
          +'<td><input type="checkbox" data-p="can_add_patch" '+(ep.can_add_patch?'checked':'')+'></td>'
          +'<td><input type="checkbox" data-p="can_edit_device" '+(ep.can_edit_device?'checked':'')+'></td>'
          +'<td><input type="checkbox" data-p="can_add_device" '+(ep.can_add_device?'checked':'')+'></td>'
          +'<td><input type="checkbox" data-p="can_delete" '+(ep.can_delete?'checked':'')+'></td>'
          +'<td><input type="checkbox" data-p="can_manage" '+(ep.can_manage?'checked':'')+'></td>';
        tb.appendChild(tr);
      });
      document.getElementById('permModal').style.display='flex';
    });
  });
});
document.getElementById('permCancel').addEventListener('click',function(){document.getElementById('permModal').style.display='none';});
document.getElementById('permSave').addEventListener('click',function(){
  var perms=[];
  document.querySelectorAll('#permModalBody tr').forEach(function(tr){
    var uid=parseInt(tr.dataset.userId);
    var p={user_id:uid};
    tr.querySelectorAll('input[type=checkbox]').forEach(function(cb){p[cb.dataset.p]=cb.checked?1:0;});
    perms.push(p);
  });
  apiCall('set_permissions',{name:store.current,permissions:perms}).then(function(res){
    if(res.error){alert(res.error);return;}
    document.getElementById('permModal').style.display='none';
    loadFromServer();
  });
});

/* ── Export / Import ── */
document.getElementById('exportProfileBtn').addEventListener('click',function(){
  var data={profileName:store.current,devices:state.devices,links:state.links,portAliases:state.portAliases,reservedPorts:state.reservedPorts,exportedAt:new Date().toISOString()};
  var blob=new Blob([JSON.stringify(data,null,2)],{type:'application/json'});
  var url=URL.createObjectURL(blob);var a=document.createElement('a');
  a.href=url;a.download='eccm-profile-'+store.current.replace(/[^a-z0-9_-]+/gi,'_')+'.json';
  document.body.appendChild(a);a.click();a.remove();URL.revokeObjectURL(url);
});
document.getElementById('importProfileBtn').addEventListener('click',function(){document.getElementById('importProfileFile').click();});
document.getElementById('importProfileFile').addEventListener('change',function(e){
  var file=e.target.files&&e.target.files[0];if(!file)return;
  var reader=new FileReader();
  reader.onload=function(){
    try{
      var parsed=JSON.parse(reader.result);
      if(!Array.isArray(parsed.devices)||!Array.isArray(parsed.links))throw new Error('Invalid');
      var suggested=(parsed.profileName||'').toString().trim()||('Import '+new Date().toLocaleString());
      var name=prompt(T.profile_name+':',suggested);
      if(!name||!name.trim())return;
      apiCall('create_profile',{name:name.trim(),permissions:[]}).then(function(res){
        if(res.error){alert(res.error);return;}
        // Now save data into it
        store.profiles[name.trim()]=parsed;store.current=name.trim();
        state=store.profiles[store.current];normalizeState(state);
        saveStore();loadFromServer();
      });
    }catch(err){alert(T.error+': '+err.message);}
    finally{e.target.value='';}
  };
  reader.readAsText(file);
});

/* ── Backup/Restore ── */
document.getElementById('backupAllBtn').addEventListener('click',function(){
  var blob=new Blob([JSON.stringify({current:store.current,profiles:store.profiles},null,2)],{type:'application/json'});
  var url=URL.createObjectURL(blob);var a=document.createElement('a');
  a.href=url;a.download='eccm-all-profiles-backup.json';
  document.body.appendChild(a);a.click();a.remove();URL.revokeObjectURL(url);
});
document.getElementById('restoreAllBtn').addEventListener('click',function(){document.getElementById('restoreAllFile').click();});
document.getElementById('restoreAllFile').addEventListener('change',function(e){
  var file=e.target.files&&e.target.files[0];if(!file)return;
  var reader=new FileReader();
  reader.onload=function(){
    try{
      var parsed=JSON.parse(reader.result);
      if(!parsed.profiles)throw new Error('Keine Profile gefunden');
      alert('Restore: import profiles as new profiles.');
      Object.keys(parsed.profiles).forEach(function(name){
        var safeName=name;var i=1;
        while(store.profiles[safeName])safeName=name+' ('+i++ +')';
        apiCall('create_profile',{name:safeName,permissions:[]}).then(function(){
          // Save data
          store.profiles[safeName]=parsed.profiles[name];normalizeState(store.profiles[safeName]);
          var payload={action:'save',profileName:safeName,data:store.profiles[safeName],settings:store.settings};
          fetch('api/profiles.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
        });
      });
      setTimeout(loadFromServer,2000);
    }catch(err){alert(T.error+': '+err.message);}
    finally{e.target.value='';}
  };
  reader.readAsText(file);
});

/* ── Search + Print + Clear All ── */
document.getElementById('searchBox').addEventListener('input',renderConnections);
document.getElementById('printSheet').addEventListener('click',function(){openPrintSheet(true);});
document.getElementById('clearAll').addEventListener('click',function(){
  if(!confirm(T.clear_all_confirm+' "'+store.current+'"?'))return;
  store.profiles[store.current]=deepClone(defaultState);state=store.profiles[store.current];
  saveStore();render();
});

/* ── Global click to clear selection ── */
document.addEventListener('click',function(e){
  if(e.target.closest('.port')||e.target.closest('.conn-row')||e.target.closest('.palette-backdrop')||e.target.closest('.modal-backdrop')||e.target.closest('.device-actions')||e.target.closest('button, input, select, label'))return;
  pendingPort=null;highlightLink(null);clearSelectionOutlines();
},true);

/* ===================== SETTINGS ===================== */
if(!store.settings)store.settings={maxPorts:512,enablePortRename:false};
function getMaxPortsSetting(){return Math.max(1,Number(store.settings&&store.settings.maxPorts)||512);}
function saveSettings(){saveStore();applySettings();}
function applySettings(){
  var mp=getMaxPortsSetting();
  var dp=document.getElementById('devPorts');if(dp){dp.max=String(mp);if(Number(dp.value)>mp)dp.value=String(mp);}
  var mi=document.getElementById('maxPorts');if(mi)mi.value=String(mp);
  var ep=document.getElementById('enablePortRename');if(ep)ep.checked=!!store.settings.enablePortRename;
  var ul=document.getElementById('userLang');if(ul)ul.value=store.settings.language||'de';
}
applySettings();
var saveSettingsBtn=document.getElementById('saveSettingsBtn');
if(saveSettingsBtn){
  saveSettingsBtn.addEventListener('click',function(){
    store.settings.maxPorts=Math.max(1,Number(document.getElementById('maxPorts').value)||9999);
    store.settings.enablePortRename=!!document.getElementById('enablePortRename').checked;
    var newLang=document.getElementById('userLang').value;
    var langChanged=(store.settings.language||'de')!==newLang;
    store.settings.language=newLang;
    saveSettings();
    var m=document.getElementById('settingsModal');if(m){m.classList.remove('is-open');m.setAttribute('aria-hidden','true');}
    if(langChanged)setTimeout(function(){window.location.reload();},800);
  });
}
var __orig_changePorts=changePorts;
changePorts=function(d,nc){var mp=getMaxPortsSetting();return __orig_changePorts(d,Math.max(1,Math.min(mp,Number(nc)||1)));};

/* ===================== THEME ===================== */
(function(){
  function applyTheme(t){document.documentElement.classList.toggle('theme-bright',t==='bright');}
  function setTheme(t){store.settings=store.settings||{};store.settings.theme=t;applyTheme(t);updateBtns(t);saveStore();try{render();}catch(e){}}
  function updateBtns(t){var d=document.getElementById('themeDark'),l=document.getElementById('themeLight');if(!d||!l)return;d.classList.toggle('active',t!=='bright');l.classList.toggle('active',t==='bright');}
  document.getElementById('themeDark').addEventListener('click',function(){setTheme('dark');});
  document.getElementById('themeLight').addEventListener('click',function(){setTheme('bright');});
  var cur=(store.settings&&store.settings.theme==='bright')?'bright':'dark';applyTheme(cur);updateBtns(cur);
})();

/* ===================== SETTINGS SLIDEOVER ===================== */
(function(){
  var btn=document.getElementById('btnSettings'),modal=document.getElementById('settingsModal'),panel=modal?modal.querySelector('.slideover__panel'):null;
  function open(){if(!modal)return;modal.classList.add('is-open');modal.setAttribute('aria-hidden','false');applySettings();}
  function close(){if(!modal)return;modal.classList.remove('is-open');modal.setAttribute('aria-hidden','true');}
  if(btn)btn.addEventListener('click',open);
  if(modal)modal.querySelectorAll('[data-close]').forEach(function(el){el.addEventListener('click',close);});
  document.addEventListener('keydown',function(e){if(e.key==='Escape'&&modal&&modal.classList.contains('is-open'))close();});
})();

/* ===================== MODAL ESCAPE ===================== */
(function(){
  var modals={palette:document.getElementById('paletteModal'),edit:document.getElementById('editDeviceModal'),layout:document.getElementById('layoutModal'),newProf:document.getElementById('newProfileModal'),perm:document.getElementById('permModal'),notif:document.getElementById('notifModal')};
  function isOpen(el){return el&&el.style.display==='flex';}
  document.addEventListener('keydown',function(e){
    if(e.key==='Escape'){
      for(var k of['notif','perm','newProf','palette','edit','layout']){if(isOpen(modals[k])){modals[k].style.display='none';e.stopPropagation();e.preventDefault();return;}}
    }
  });
})();

/* ===================== NOTIFICATIONS UI ===================== */
function notifApi(action,data){
  return fetch('api/notifications.php',{
    method:'POST',headers:{'Content-Type':'application/json'},
    body:JSON.stringify(Object.assign({action:action},data||{}))
  }).then(function(r){return r.json();});
}

document.getElementById('btnNotifications').addEventListener('click',function(){
  // Load all accessible profiles + current subscriptions
  Promise.all([
    notifApi('get_all_profiles'),
    notifApi('get_subscriptions')
  ]).then(function(results){
    var profRes=results[0], subRes=results[1];
    if(!profRes.ok||!subRes.ok)return;

    // Index subscriptions by profile_id
    var subMap={};
    (subRes.subscriptions||[]).forEach(function(s){subMap[s.profile_id]=s;});

    var tb=document.getElementById('notifTableBody');tb.innerHTML='';
    (profRes.profiles||[]).forEach(function(p){
      var s=subMap[p.id]||{};
      var tr=document.createElement('tr');
      tr.dataset.profileId=p.id;
      tr.innerHTML='<td>'+p.name+'</td>'
        +'<td>'+p.owner_name+'</td>'
        +'<td><input type="checkbox" data-n="on_device_change" '+(s.on_device_change==1?'checked':'')+'></td>'
        +'<td><input type="checkbox" data-n="on_device_add" '+(s.on_device_add==1?'checked':'')+'></td>'
        +'<td><input type="checkbox" data-n="on_patch_change" '+(s.on_patch_change==1?'checked':'')+'></td>'
        +'<td><input type="checkbox" data-n="on_patch_add" '+(s.on_patch_add==1?'checked':'')+'></td>';
      tb.appendChild(tr);
    });
    document.getElementById('notifModal').style.display='flex';
  });
});

document.getElementById('notifCancel').addEventListener('click',function(){document.getElementById('notifModal').style.display='none';});
document.getElementById('notifSave').addEventListener('click',function(){
  var subs=[];
  document.querySelectorAll('#notifTableBody tr').forEach(function(tr){
    var pid=parseInt(tr.dataset.profileId);
    var s={profile_id:pid};
    tr.querySelectorAll('input[type=checkbox]').forEach(function(cb){s[cb.dataset.n]=cb.checked?1:0;});
    // Only add if at least one flag is set
    if(s.on_device_change||s.on_device_add||s.on_patch_change||s.on_patch_add)subs.push(s);
  });
  notifApi('save_subscriptions',{subscriptions:subs}).then(function(res){
    if(res.ok){
      document.getElementById('notifModal').style.display='none';
      showSaveIndicator(false);
    }else{
      alert(res.error||T.save_error);
    }
  });
});

/* ===================== LOAD FROM SERVER ===================== */
function loadFromServer(){
  fetch('api/profiles.php?action=load').then(function(r){return r.json();}).then(function(data){
    if(!data.ok){console.error('Load failed:',data.error);return;}
    store.current=data.current||'Default';
    store.profiles=data.profiles||{'Default':deepClone(defaultState)};
    store.settings=data.settings||{maxPorts:512,enablePortRename:false};
    profileMeta=data.profileMeta||{};
    Object.keys(store.profiles).forEach(function(name){normalizeState(store.profiles[name]);});
    state=store.profiles[store.current]||deepClone(defaultState);
    applySettings();refreshProfileSelect();render();updateUniformPortWidth();
    if(store.settings.theme==='bright')document.documentElement.classList.add('theme-bright');
  }).catch(function(err){console.error('Load error:',err);refreshProfileSelect();render();});
}
loadFromServer();
</script>
</body>
</html>
