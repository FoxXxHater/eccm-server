/* ===================== LINK/RESERVED HELPERS (dual-aware) ===================== */
function keyFor(deviceId, port, sub){ return deviceId + ':' + port + ':' + (sub==null ? '' : sub); }
function aliasFor(deviceId, port, sub){ return state.portAliases[keyFor(deviceId,port,sub)] || ''; }
function reservedLabelFor(deviceId, port, sub){
  return (state.reservedPorts && state.reservedPorts[keyFor(deviceId,port,sub)]) || null;
}
function isReservedPort(deviceId, port, sub){
  return !!reservedLabelFor(deviceId, port, sub);
}
function linkForPort(deviceId, port, sub){
  for(var i=0;i<state.links.length;i++){
    var L = state.links[i];
    if((L.a.deviceId===deviceId && L.a.port===port && (L.a.sub||null)===(sub||null)) ||
       (L.b.deviceId===deviceId && L.b.port===port && (L.b.sub||null)===(sub||null))) return L;
  }
  return null;
}
function getPeer(deviceId, port, sub){
  var L = linkForPort(deviceId, port, sub); if(!L) return null;
  if (L.a.deviceId===deviceId && L.a.port===port && (L.a.sub||null)===(sub||null)) return L.b;
  return L.a;
}
function speedToMbps(s){
  if (!s) return Infinity;
  const n = Number(String(s).replace(/[^0-9.]/g,'')) || 0;
  return String(s).includes('Gbit') ? n * 1000 : n;
}

function connectPorts(a, b) {
  if (linkForPort(a.deviceId,a.port,a.sub) || linkForPort(b.deviceId,b.port,b.sub)) return false;
  if (isReservedPort(a.deviceId,a.port,a.sub) || isReservedPort(b.deviceId,b.port,b.sub)) return false;

  // Create link
  var id = uid();
  state.links.push({ id:id, a:a, b:b });

  // --- AUTO-NEGOTIATE SPEED ---
  var devA = deviceById(a.deviceId);
  var devB = deviceById(b.deviceId);

  var maxA = devA && devA.maxSpeed ? devA.maxSpeed : null;
  var maxB = devB && devB.maxSpeed ? devB.maxSpeed : null;

  var spA = speedToMbps(maxA);
  var spB = speedToMbps(maxB);

  var lowest = Math.min(spA, spB);

  if (lowest !== Infinity) {
    var label = lowest >= 1000 ? (lowest/1000 + ' Gbit') : (lowest + ' Mbit');
    setSpeedFor(a.deviceId, a.port, a.sub, label);
    setSpeedFor(b.deviceId, b.port, b.sub, label);
  }

  saveStore();
  render();
  highlightLink(id);
  return true;
}
function speedFor(deviceId, port, sub){
  return (state.portSpeeds || {})[keyFor(deviceId,port,sub)] || '';
}
function setSpeedFor(deviceId, port, sub, val){
  var k = keyFor(deviceId,port,sub);
  state.portSpeeds = state.portSpeeds || {};
  if (!val) delete state.portSpeeds[k]; else state.portSpeeds[k] = String(val);
  saveStore();
}

function vlanFor(deviceId, port, sub){
  var v = (state.portVlans || {})[keyFor(deviceId,port,sub)];
  return (v==null || v==='') ? '' : Number(v);
}
function setVlanFor(deviceId, port, sub, val){
  var k = keyFor(deviceId,port,sub);
  state.portVlans = state.portVlans || {};
  if (val==null || val==='') { delete state.portVlans[k]; }
  else {
    var n = Math.max(1, Math.min(4094, Number(val)||0));
    state.portVlans[k] = n;
  }
  saveStore();
}

/* -- Port individual color -- */
function portColorFor(deviceId, port, sub){
  return (state.portColors || {})[keyFor(deviceId,port,sub)] || '';
}
function setPortColor(deviceId, port, sub, color){
  var k = keyFor(deviceId,port,sub);
  state.portColors = state.portColors || {};
  if (!color || color === '') { delete state.portColors[k]; }
  else { state.portColors[k] = color; }
}

/* -- VLAN definitions (per profile) -- */
function getVlans(){ return state.vlans || []; }
function getVlanById(vid){ return (state.vlans||[]).find(function(v){return v.id===vid;}) || null; }

/* -- Port VLAN assignments (multiple VLANs per port) -- */
function getPortVlanIds(deviceId, port, sub){
  return (state.portVlanAssignments || {})[keyFor(deviceId,port,sub)] || [];
}
function setPortVlanIds(deviceId, port, sub, vlanIds){
  var k = keyFor(deviceId,port,sub);
  state.portVlanAssignments = state.portVlanAssignments || {};
  if (!vlanIds || !vlanIds.length) { delete state.portVlanAssignments[k]; }
  else { state.portVlanAssignments[k] = vlanIds; }
}

/* -- Port notes (max 30 chars) -- */
function portNoteFor(deviceId, port, sub){
  return (state.portNotes || {})[keyFor(deviceId,port,sub)] || '';
}
function setPortNote(deviceId, port, sub, note){
  var k = keyFor(deviceId,port,sub);
  state.portNotes = state.portNotes || {};
  if (!note || !note.trim()) { delete state.portNotes[k]; }
  else { state.portNotes[k] = note.trim().substring(0, 150); }
}
function linkedToOverrideFor(deviceId, port, sub){
  return (state.portLinkedTo || {})[keyFor(deviceId, port, sub)] || '';
}
function setLinkedToOverrideFor(deviceId, port, sub, val){
  var k = keyFor(deviceId, port, sub);
  state.portLinkedTo = state.portLinkedTo || {};
  if (!val) delete state.portLinkedTo[k];
  else state.portLinkedTo[k] = String(val);
  saveStore();
}

function fmtSpeed(val){
  // normalize display
  switch(String(val||'')){
    case '100 Mbit': return '100 Mbit';
    case '1 Gbit': return '1 Gbit';
    case '2.5 Gbit': return '2.5 Gbit';
    case '5 Gbit': return '5 Gbit';
    case '10 Gbit': return '10 Gbit';
    case '25 Gbit': return '25 Gbit';
    case '40 Gbit': return '40 Gbit';
    case '100 Gbit': return '100 Gbit';
    default: return '';
  }
}


/* ===================== COLOUR / SELECTION ===================== */
function hexToRgb(hex){ var h = String(hex||'').replace('#','').trim(); if (h.length===3) h = h.split('').map(function(c){return c+c}).join(''); var n = parseInt(h,16); if (isNaN(n)||h.length!==6) return {r:15,g:20,b:29}; return { r:(n>>16)&255, g:(n>>8)&255, b:n&255 }; }
function bestTextColorFor(bgHex){ var c = hexToRgb(bgHex); var L = 0.2126*(c.r/255) + 0.7152*(c.g/255) + 0.0722*(c.b/255); return (L > 0.6) ? '#000' : '#fff'; }
function paintPortBase(node, bgHex){
  var bg = bgHex || '#0f141d';
  node.style.background = bg;
  node.style.borderRadius = '10px';
  var txt = bestTextColorFor(bg);
  node.style.color = txt;
  var alias = node.querySelector('.alias'); if (alias) alias.style.color = txt;
  var num   = node.querySelector('.num');   if (num)   num.style.color   = txt;
  var peer  = node.querySelector('.peer');  if (peer)  peer.style.color  = txt;
}

/* CLEAR selection visuals */
function clearSelectionOutlines(){
  $all('.port.selected').forEach(function(n){
    n.classList.remove('selected');
    n.style.boxShadow = 'none';
  });
}

/* APPLY selection visuals: same on singles and dual halves */
function outlineForSelection(node){
  node.classList.add('selected');

  var isBright = document.documentElement.classList.contains('theme-bright');
  var edgeClr = isBright ? '#000' : '#fff';
  var glowClr = isBright ? 'rgba(0,0,0,.38)' : 'rgba(255,255,255,.38)';

  var o = HI_OUTLINE, r = HI_RING, b = HI_BLUR;
  node.style.boxShadow =
    '0 0 0 ' + o + 'px ' + edgeClr + ',' +
    'inset 0 0 0 ' + r + 'px ' + edgeClr + ',' +
    'inset 0 0 ' + b + 'px ' + glowClr;
}

/* ---------- Hovercard (only shows while item is highlighted) ---------- */
var __hoverEl = null;

function hcEnsure(){
  if (!__hoverEl){
    __hoverEl = document.createElement('div');
    __hoverEl.className = 'hovercard';
    document.body.appendChild(__hoverEl);
  }
  return __hoverEl;
}
function hcHide(){ if(__hoverEl) __hoverEl.style.display = 'none'; }

function hcShowForTarget(target, html, evt){
  var el = hcEnsure();
  el.innerHTML = html;
  el.style.display = 'block';
  var x = 0, y = 0;
  if (evt && typeof evt.clientX === 'number'){
    x = evt.clientX + 14;
    y = evt.clientY + 12;
  } else {
    var r = target.getBoundingClientRect();
    x = r.right + 12;
    y = r.top + 8;
  }
  // Keep in viewport
  var vw = window.innerWidth, vh = window.innerHeight;
  var ew = el.offsetWidth || 240, eh = el.offsetHeight || 100;
  if (x + ew + 8 > vw) x = vw - ew - 8;
  if (y + eh + 8 > vh) y = vh - eh - 8;
  el.style.left = x + 'px';
  el.style.top  = y + 'px';
}

/* Hide the hovercard whenever selection is cleared */
const __orig_clearSel = clearSelectionOutlines;
clearSelectionOutlines = function(){
  hcHide();
  return __orig_clearSel.apply(this, arguments);
};
/* Also hide when we un-highlight a link */
const __orig_highlightLink = highlightLink;
highlightLink = function(id, opts){
  if (!id) hcHide();
  return __orig_highlightLink.apply(this, arguments);
};


/* ===================== UNIFORM PORT WIDTHS (two gaps) ===================== */
function updateUniformPortWidth(){
  var host = document.getElementById('devRows'); if(!host) return;
  var GAP_COL = 12;
  var PAD_BRD = 26;
  var gapFull = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--portGapFull')) || 9;
  var gapHalf = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--portGapHalf')) || 8;
  var Wrows = host.getBoundingClientRect().width || 800;
  var WfullInner = Wrows - PAD_BRD;
  var WhalfInner = ((Wrows - GAP_COL)/2) - PAD_BRD;
  var portW12 = (WfullInner - (11*gapFull)) / 12;
  var portW6  = (WhalfInner - (5*gapHalf)) / 6;
  portW12 = Math.max(40, portW12);
  portW6  = Math.max(40, portW6);
  document.documentElement.style.setProperty('--portW12', portW12 + 'px');
  document.documentElement.style.setProperty('--portW6',  portW6  + 'px');
}
var __pw_timer=null;
window.addEventListener('resize', function(){ clearTimeout(__pw_timer); __pw_timer=setTimeout(updateUniformPortWidth, 60); });

/* ===================== LAYOUT RULES ===================== */
function isFullWidthDevice(d){
  if (d.forceFullRow) return true;
  if ((d.ports||1) >= 13) return true;
  if ((d.ports||1) >= 7 && !d.smallWrap) return true;
  return false;
}
function splitPortsIntoRows(n, dev){
  if (n === 12 && dev.smallWrap) return [6, 6];

  if (n >= 12){
    if (n >= 13 && n <= 24 && dev.midWrapMode === 'balanced'){
      var a = Math.ceil(n/2);
      return [Math.min(12, a), Math.min(12, n - a)];
    }
    var rows = [], rem = n;
    while (rem > 0){ rows.push(Math.min(12, rem)); rem -= 12; }
    return rows;
  }
  if (n >= 7){
    if (dev.smallWrap){ var a = Math.ceil(n/2); return [a, n - a]; }
    return [n];
  }
  return [n];
}


/* ===================== CONNECT SELECTION ===================== */
var pendingPort = null;
var highlightedLinkId = null;

/* ===================== RENDER DEVICES ===================== */
function applyPeerScrolling() {
  requestAnimationFrame(() => {
    const peers = document.querySelectorAll('.peer');

    peers.forEach(peer => {
      const span = peer.querySelector('span');
      if (!span) return;

      const sw = span.getBoundingClientRect().width;
      const cw = peer.clientWidth;

      if (sw > cw) {
        // calculate how far to bounce
        const diff = sw - cw;
        span.style.setProperty('--peer-width', `${cw}px`);
        span.style.setProperty('--peer-diff', `${diff}px`);

        peer.classList.add('scroll');
      } else {
        peer.classList.remove('scroll');
      }
    });
  });
}


function renderDevices(){
  var host = $('#devRows'); host.innerHTML='';
  if(!state.devices.length){ host.appendChild(el('div',{class:'muted'},'No devices yet — add one on the left.')); updateUniformPortWidth(); return; }

  var i=0;
  while(i < state.devices.length){
    var rowEl = el('div',{class:'dev-row'});
    var d = state.devices[i];
    if (isFullWidthDevice(d)){
      rowEl.appendChild(renderDeviceWrap(d, true)); i += 1;
    } else {
      rowEl.appendChild(renderDeviceWrap(d, false));
      if (i+1 < state.devices.length && !isFullWidthDevice(state.devices[i+1])){
        rowEl.appendChild(renderDeviceWrap(state.devices[i+1], false)); i += 2;
      } else {
        rowEl.appendChild(el('div',{class:'device-wrap'}, el('div',{class:'device', style:'visibility:hidden'}, ' '))); i += 1;
      }
    }
    host.appendChild(rowEl);
  }
  updateUniformPortWidth();
}

function renderDeviceWrap(d, fullWidth){
  var wrap = el('div',{class:'device-wrap ' + (fullWidth ? 'full' : ''), dataset:{id:d.id}});
  wrap.draggable = true;

  wrap.addEventListener('dragstart', function(e){
    wrap.classList.add('dragging');
    e.dataTransfer.setData('text/plain', d.id);
    e.dataTransfer.effectAllowed='move';
  });
  wrap.addEventListener('dragend', function(){ wrap.classList.remove('dragging'); });
  wrap.addEventListener('dragover', function(e){ e.preventDefault(); e.dataTransfer.dropEffect='move'; });
  wrap.addEventListener('drop', function(e){
    e.preventDefault();
    var draggedId = e.dataTransfer.getData('text/plain'); if (!draggedId || draggedId === d.id) return;
    var from = indexById(draggedId), to = indexById(d.id); if (from<0 || to<0) return;
    var item = state.devices.splice(from,1)[0]; if (from < to) to -= 1; state.devices.splice(to,0,item);
    saveStore(); render();
  });

  var head = el('div',{class:'device-head'},
    el('div',{class:'device-title'},
      el('span',{class:'swatch', style:'background:'+(d.color||'#888')}),
      document.createTextNode(d.name||'Unnamed')
    ),
    el('div',{class:'device-actions'},
      el('button',{title:'Layout options', onclick:function(ev){ ev.stopPropagation(); openLayoutModal(d.id); }}, 'Layout'),
		el('button', {
		  title: 'Edit device',
		  onclick: function (ev) {
			ev.stopPropagation();
			openEditDeviceModal(d.id);
		  }
		}, 'Edit'),
      el('button',{class:'btn-danger',title:'Delete', onclick:function(ev){
        ev.stopPropagation();
        if(!confirm('Delete "'+(d.name||'Unnamed')+'" and its links?')) return;
        state.links = state.links.filter(function(L){
          return String(L.a.deviceId)!==String(d.id) && String(L.b.deviceId)!==String(d.id);
        });
        state.devices = state.devices.filter(function(x){ return String(x.id)!==String(d.id); });
        Object.keys(state.portAliases).forEach(function(k){
          if(k.split(':')[0]===String(d.id)) delete state.portAliases[k];
        });
        saveStore(); render();
      }}, 'Delete')
    )
  );

  var meta = el('div',{class:'meta'},
    document.createTextNode((d.ports||1)+' ports'),
    (function(){
      var c = el('span',{class:'inline-controls'},
        el('button',{class:'mini', title:'Remove one port', onclick:function(e){ e.stopPropagation(); if ((d.ports||1) <= 1) return; changePorts(d, (d.ports||1)-1); saveStore(); render(); }}, '– Port'),
		el('button',{class:'mini', title:'Add one port', onclick:function(e){ e.stopPropagation(); changePorts(d, (d.ports||1)+1); saveStore(); render(); }}, '+ Port')        
      );
      return c;
    })()
  );

  var portsContainer = el('div',{class:'ports-rows'});
var rows = splitPortsIntoRows(d.ports||1, d);
var total = d.ports || 1;
var seqByRow = rows.map(function(cnt){ return new Array(cnt); });

function buildRowMajor(){
  var n = 1;
  rows.forEach(function(count, r){
    for (var c=0; c<count; c++) seqByRow[r][c] = n++;
  });
}

function buildColumnMajor(){
  var maxCols = Math.max.apply(null, rows);
  var n = 1;
  for (var c=0; c<maxCols && n<=total; c++){
    for (var r=0; r<rows.length && n<=total; r++){
      if (c < rows[r]) { seqByRow[r][c] = n++; }
    }
  }
  for (var r=0; r<rows.length; r++){
    for (var c=0; c<rows[r]; c++){
      if (seqByRow[r][c] == null) seqByRow[r][c] = n++;
    }
  }
}

function buildColumnMajorBottomTop(){
  var maxCols = Math.max.apply(null, rows);
  var n = 1;

  // Fill by columns, but bottom row first
  for (var c=0; c<maxCols && n<=total; c++){
    for (var r=rows.length-1; r>=0 && n<=total; r--){
      if (c < rows[r]) { seqByRow[r][c] = n++; }
    }
  }

  // Safety fill (should rarely be needed)
  for (var r=0; r<rows.length; r++){
    for (var c=0; c<rows[r]; c++){
      if (seqByRow[r][c] == null) seqByRow[r][c] = n++;
    }
  }
}


// Choose numbering mode
if (d.numbering === 'column') buildColumnMajor();
else if (d.numbering === 'column-bt') buildColumnMajorBottomTop();
else buildRowMajor();

  function createSubPort(d, p, sub){
  var linked   = !!linkForPort(d.id, p, sub);
  var peer     = linked ? getPeer(d.id,p,sub) : null;
  var peerDev  = peer ? deviceById(peer.deviceId) : null;
  var peerPort = peer ? peer.port : null;
  var alias    = aliasFor(d.id,p,sub);
  var reserved = reservedLabelFor(d.id,p,sub);

  var rawSpeed = (typeof speedFor === 'function') ? speedFor(d.id, p, sub) : '';
  var spdVal   = fmtSpeed(rawSpeed) || '';
  var vlanVal  = (typeof vlanFor === 'function') ? vlanFor(d.id, p, sub) : '';

  var hasSpeed = (rawSpeed !== '' && rawSpeed != null);
  var hasVlan  = (vlanVal  !== '' && vlanVal  != null);

// Auto-negotiate for older links that have no stored speed yet
if (linked && !hasSpeed) {
  var dev    = deviceById(d.id);
  var peerDv = peerDev; // already computed above

  if (dev && dev.maxSpeed && peerDv && peerDv.maxSpeed) {
    var spA   = speedToMbps(dev.maxSpeed);
    var spB   = speedToMbps(peerDv.maxSpeed);
    var low   = Math.min(spA, spB);

    if (low !== Infinity) {
      var label = low >= 1000 ? (low/1000 + ' Gbit') : (low + ' Mbit');

      // Persist on both ends so future renders see it
      setSpeedFor(d.id, p, sub, label);
      setSpeedFor(peer.deviceId, peer.port, peer.sub, label);

      rawSpeed = label;
      spdVal   = fmtSpeed(label);
      hasSpeed = true;
    }
  }
}


var leftInd  = el('span',{class:'ind spd-dot'}, '');

// Build VLAN dots (one colored dot per assigned VLAN)
var assignedVlans = (typeof getPortVlanIds === 'function') ? getPortVlanIds(d.id, p, sub) : [];
var vlanDotsEl = el('span',{class:'vlan-dots'});
var vlanTooltipParts = [];
assignedVlans.forEach(function(vid){
  var vdef = (typeof getVlanById === 'function') ? getVlanById(vid) : null;
  if (!vdef) return;
  var dot = el('span',{class:'vlan-dot'});
  dot.style.background = vdef.color || '#888';
  dot.title = 'VLAN ' + vdef.vid + (vdef.name ? ' - ' + vdef.name : '');
  vlanTooltipParts.push(dot.title);
  vlanDotsEl.appendChild(dot);
});

// Legacy single VLAN indicator (diamond) if no new VLANs assigned
var rightInd = el('span',{class:'ind vlan-diamond'}, '');
if (hasVlan && assignedVlans.length === 0){
  rightInd.title = String(vlanVal);
} else {
  rightInd.classList.add('ind--off');
}

if (hasSpeed){
  leftInd.title = spdVal;
} else {
  leftInd.classList.add('ind--off');
}


var portLabel = 
  (d.portNames && d.portNames[keyFor(d.id, p, sub)]) ||
  ('Port ' + p + (sub != null ? '/' + (sub + 1) : ''));

var numEl = el('div',{class:'num'},
  leftInd,
  el('span',{class:'num-label'}, portLabel),
  vlanDotsEl,
  rightInd
);

function bindMiniHover(indEl, label, value){
  // Disabled - main port hover shows all info now
}
bindMiniHover(leftInd,  'Link speed', spdVal);
bindMiniHover(rightInd, 'VLAN',  String(vlanVal));
// VLAN dots hover handled by main port hover
// Slow-link indicator: port speed < device max → add "!" before speed square
(function () {
  var dev = deviceById(d.id);
  if (!dev || !dev.maxSpeed) return;
  if (!rawSpeed) return; // no explicit port speed set → nothing to compare

  function toMbps(s) {
    var txt = String(s || '').toLowerCase();
    if (!txt) return 0;
    var num = parseFloat(txt.replace(/[^0-9.]/g, '')) || 0;
    if (!num) return 0;
    // Treat values like "1 gbit", "1 gbps" etc. as Gbit
    if (txt.indexOf('gbit') !== -1 || txt.indexOf('gbps') !== -1) return num * 1000;
    return num; // assume Mbit
  }

  var devMaxMbps = toMbps(dev.maxSpeed);
  var portMbps   = toMbps(rawSpeed);

  if (devMaxMbps && portMbps && portMbps < devMaxMbps) {
    leftInd.insertAdjacentHTML(
      'beforebegin',
      '<span class="slow-warn" style="font-weight:900;margin-right:2px;">!</span>'
    );
  }
})();

// Build peer text: Device Name + Port Name or Reserved
var defaultPeerText =
  reserved
    ? (reserved || 'Reserved')
    : (
        peer && peerDev
          ? (peerDev.name + ' - ' +
             (
               (peerDev.portNames && peerDev.portNames[keyFor(peer.deviceId, peerPort, peer.sub)]) ||
               ('Port ' + peerPort + (peer.sub != null ? '/' + (peer.sub+1) : ''))
             )
            )
          : ''
      );

// If user has overridden "Linked to", show it here too
var peerOverride = (typeof linkedToOverrideFor === 'function') ? linkedToOverrideFor(d.id, p, sub) : '';
var peerText = (peerOverride !== '' ? peerOverride : defaultPeerText);


// Create peer element with a <span> for scrolling later
var peerEl = el(
  'div',
  { class: 'peer' },
  el('span', {}, peerText)
);



  var node = el('div',{
      class:'port'+(linked?' connected':'')+(reserved?' reserved':''), 
      dataset:{deviceId:d.id, port:p, sub:(sub==null?undefined:sub)}
    },
    numEl, 
    el('div',{class:'alias'}, alias||''),
	peerEl 
  );

  if (reserved){
    paintPortBase(node, 'var(--reserved-bg)');
  } else if (linked && peerDev){
    paintPortBase(node, peerDev.color || '#22354a');
  } else {
    paintPortBase(node, '#0f141d');
  }
  // Override with individual port color if set
  var portClr = portColorFor(d.id, p, sub);
  if (portClr) { paintPortBase(node, portClr); }

  node.addEventListener('click', function(ev){
    if (ev.altKey){
      var curr = aliasFor(d.id,p,sub);
      var next = prompt('Optional port label (blank to clear):', curr||'');
      if(next===null) return;
      var k = keyFor(d.id,p,sub);
      if(!next.trim()) delete state.portAliases[k]; else state.portAliases[k] = next.trim();
      saveStore(); render(); return;
    }

    if (ev.ctrlKey){
      var k = keyFor(d.id,p,sub);
      if (state.reservedPorts && state.reservedPorts[k]){
        if(confirm('Clear reserved status for Port '+p+(sub!=null?('/'+(sub+1)):'')+'?')){
          delete state.reservedPorts[k];
        }
      } else {
        if (!state.reservedPorts) state.reservedPorts = {};
        var lbl = prompt('Reserved port label (e.g. WAN):', '');
        if (lbl===null) return;
        state.reservedPorts[k] = (lbl && lbl.trim()) ? lbl.trim() : 'Reserved';
      }
      pendingPort = null;
      saveStore(); render();
      return;
    }

    if (reserved){
      clearSelectionOutlines();
      outlineForSelection(node);
      highlightedLinkId = null; applyRowHighlight();
      pendingPort = null;
      return;
    }

    if (linkForPort(d.id, p, sub)) {
      clearSelectionOutlines();
      var L1 = linkForPort(d.id, p, sub);
      highlightLink(L1.id, { from:{deviceId:d.id, port:p, sub:sub} });
      pendingPort = null; return;
    }

    if (!pendingPort){
      pendingPort = {deviceId:d.id, port:p, sub:sub};
      clearSelectionOutlines(); outlineForSelection(node);
      highlightedLinkId = null; applyRowHighlight();
      return;
    }
    if (linkForPort(d.id, p, sub) || linkForPort(pendingPort.deviceId, pendingPort.port, pendingPort.sub) ||
        isReservedPort(d.id,p,sub) || isReservedPort(pendingPort.deviceId,pendingPort.port,pendingPort.sub)){
      alert('That port (or the previously selected one) is unavailable. Unlink/clear Reserved first.');
      pendingPort = null; clearSelectionOutlines(); highlightLink(null); return;
    }

    var devA = deviceById(pendingPort.deviceId);
    var devB = deviceById(d.id);
    var msg = 'Create link:\n' + (devA?devA.name:'A') + ' Port ' + pendingPort.port + (pendingPort.sub!=null?'/'+(pendingPort.sub+1):'')
              + ' ⇄ ' + (devB?devB.name:'B') + ' Port ' + p + (sub!=null?'/'+(sub+1):'');
    if (!confirm(msg)){ pendingPort = null; clearSelectionOutlines(); return; }
    var ok = connectPorts(pendingPort, {deviceId:d.id, port:p, sub:sub});
    pendingPort = null;
    if (!ok){ alert('Could not connect.'); clearSelectionOutlines(); highlightLink(null); }
  });

  function buildHoverHTML() {
    var liveAlias = aliasFor(d.id, p, sub) || '';
    var liveSpeed = (typeof speedFor === 'function' ? fmtSpeed(speedFor(d.id, p, sub)) : '') || '';
    var liveNote = (typeof portNoteFor === 'function' ? portNoteFor(d.id, p, sub) : '') || '';

    var vlanRaw  = (typeof vlanFor==='function' ? vlanFor(d.id, p, sub) : '');
    var vlanText = (vlanRaw === '' ? '' : String(vlanRaw));

    // VLAN assignments
    var vlanAssigns = (typeof getPortVlanIds === 'function') ? getPortVlanIds(d.id, p, sub) : [];
    var vlanLines = [];
    vlanAssigns.forEach(function(vid){
      var vdef = (typeof getVlanById === 'function') ? getVlanById(vid) : null;
      if (vdef) vlanLines.push('<span class="vlan-dot" style="background:'+(vdef.color||'#888')+';width:7px;height:7px;border-radius:50%;display:inline-block;margin-right:3px"></span>VLAN ' + vdef.vid + (vdef.name ? ' - ' + vdef.name : ''));
    });

    var liveReserved = typeof isReservedPort === 'function' && isReservedPort(d.id, p, sub);
    var liveLink = linkForPort(d.id, p, sub);
    var status = liveReserved ? (typeof T!=='undefined'&&T.status_reserved||'Reserved') : (liveLink ? (typeof T!=='undefined'&&T.status_linked||'Linked') : (typeof T!=='undefined'&&T.status_free||'Free'));

    var peerTxt = '';
    if (liveReserved) {
      peerTxt = (typeof reservedLabelFor === 'function' && reservedLabelFor(d.id, p, sub)) || 'Reserved';
    } else if (liveLink) {
      var livePeer = getPeer(d.id, p, sub);
      var livePeerDev = livePeer ? deviceById(livePeer.deviceId) : null;
      if (livePeer && livePeerDev) {
        peerTxt = livePeerDev.name + ' • Port ' + livePeer.port +
                  (livePeer.sub != null ? '/' + (livePeer.sub + 1) : '');
      }
    }

    var html = '<div class="hc-title">' + (d.name || 'Device') + '</div>' +
      '<div class="hc-row"><div class="hc-k">'+(typeof T!=='undefined'&&T.hover_port||'Port')+'</div><div>' + p + (sub != null ? '/' + (sub + 1) : '') + '</div></div>';
    if (liveAlias) html += '<div class="hc-row"><div class="hc-k">'+(typeof T!=='undefined'&&T.hover_alias||'Alias')+'</div><div>' + liveAlias + '</div></div>';
    if (peerTxt) html += '<div class="hc-row"><div class="hc-k">'+(typeof T!=='undefined'&&T.hover_peer||'Peer')+'</div><div>' + peerTxt + '</div></div>';
    html += '<div class="hc-row"><div class="hc-k">'+(typeof T!=='undefined'&&T.hover_status||'Status')+'</div><div>' + status + '</div></div>';
    if (liveSpeed) html += '<div class="hc-row"><div class="hc-k">'+(typeof T!=='undefined'&&T.hover_speed||'Speed')+'</div><div>' + liveSpeed + '</div></div>';
    if (vlanText) html += '<div class="hc-row"><div class="hc-k">VLAN</div><div>' + vlanText + '</div></div>';
    if (vlanLines.length) html += '<div class="hc-row"><div class="hc-k">VLANs</div><div>' + vlanLines.join('<br>') + '</div></div>';
    if (liveNote) html += '<div class="hc-row"><div class="hc-k">'+(typeof T!=='undefined'&&T.hover_note||'Note')+'</div><div>' + liveNote + '</div></div>';
    return html;
  }

  node.addEventListener('mouseenter', function (e) {
    if (__menuOpen) return;
    hcShowForTarget(node, buildHoverHTML(), e);
  });
  node.addEventListener('mousemove', function (e) {
    if (__menuOpen) return;
    hcShowForTarget(node, buildHoverHTML(), e);
  });

  node.addEventListener('mouseleave', function () { hcHide(); });

  node.addEventListener('contextmenu', function(ev){
    openSpeedMenu(ev, d.id, p, sub);
  });

  return node;
}


  function renderPortCell(d, p){
    if (!d.dualLink) return createSubPort(d, p, null);
    var wrapCell = document.createElement('div');
    wrapCell.className = 'dual-cell';
    wrapCell.style.display = 'flex';
    wrapCell.style.flexDirection = 'column';
    wrapCell.style.border = '1px solid var(--line)';
    wrapCell.style.borderRadius = '10px';
    wrapCell.style.overflow = 'hidden';
    wrapCell.style.background = 'transparent';
    var top = createSubPort(d, p, 0);
    top.style.border = '0';
    top.style.borderRadius = '10px 10px 0 0';
    var bottom = createSubPort(d, p, 1);
    bottom.style.border = '0';
    bottom.style.borderTop = '1px solid var(--line)';
    bottom.style.borderRadius = '0 0 10px 10px';
    wrapCell.appendChild(top); wrapCell.appendChild(bottom);
    return wrapCell;
  }

	rows.forEach(function(count, rIndex){
	  var row = el('div',{class:'port-row'});
	  row.style.gridTemplateColumns = fullWidth ? 'repeat(12, var(--portW12))' : 'repeat(6, var(--portW6))';
	  row.style.gap = fullWidth ? 'var(--portGapFull)' : 'var(--portGapHalf)';
	  for (var c=0; c<count; c++){
		var p = seqByRow[rIndex][c];
		row.appendChild(renderPortCell(d, p));
	  }
	  portsContainer.appendChild(row);
	});

  var card = el('div',{class:'device', id:'dev-'+d.id}, head, meta, portsContainer);
  wrap.appendChild(card);
  return wrap;
}

/* ===================== CHANGE PORT COUNT ===================== */
function changePorts(d, newCount){
  newCount = Math.max(1, Math.min(9999, Number(newCount)||1));
  if (newCount === d.ports) return;
  if (newCount < d.ports){
    state.links = state.links.filter(function(L){
      var aOk = !(L.a.deviceId===d.id && L.a.port>newCount);
      var bOk = !(L.b.deviceId===d.id && L.b.port>newCount);
      return aOk && bOk;
    });
    Object.keys(state.portAliases).forEach(function(k){
      var parts = k.split(':'); if(parts[0]===d.id && Number(parts[1])>newCount) delete state.portAliases[k];
    });
    Object.keys(state.reservedPorts).forEach(function(k){
      var parts = k.split(':'); if(parts[0]===d.id && Number(parts[1])>newCount) delete state.reservedPorts[k];
    });
  }
  d.ports = newCount;
}

/* ===================== CONNECTIONS TABLE ===================== */
function renderConnections() {
  var tb = $('#connBody');
  tb.innerHTML = '';

  var q = ($('#searchBox').value || '').toLowerCase().trim();
  var rows = [];

  state.links.forEach(function (L, i) {
    var da = deviceById(L.a.deviceId),
        db = deviceById(L.b.deviceId);

    var aName  = (da && da.name) || 'Unknown';
    var bName  = (db && db.name) || 'Unknown';
    var aAlias = aliasFor(L.a.deviceId, L.a.port, L.a.sub);
    var bAlias = aliasFor(L.b.deviceId, L.b.port, L.b.sub);

    // 🔹 Use custom port names if present, otherwise fall back to "Port X[/Y]"
    var aPortLabel =
      (da && da.portNames && da.portNames[keyFor(L.a.deviceId, L.a.port, L.a.sub)]) ||
      ('Port ' + L.a.port + (L.a.sub != null ? '/' + (L.a.sub + 1) : ''));

    var bPortLabel =
      (db && db.portNames && db.portNames[keyFor(L.b.deviceId, L.b.port, L.b.sub)]) ||
      ('Port ' + L.b.port + (L.b.sub != null ? '/' + (L.b.sub + 1) : ''));

    var parts = [
      aName,
      'port' + L.a.port,
      'port ' + L.a.port,
      String(L.a.port),
      aPortLabel,         // include custom label in search
      bName,
      'port' + L.b.port,
      'port ' + L.b.port,
      String(L.b.port),
      bPortLabel,         // include custom label in search
      aAlias,
      bAlias
    ];

    var text = parts.join(' ').toLowerCase();
    if (q && text.indexOf(q) === -1) return;

    var aColor = da ? (da.color || '#ccc') : '#ccc';
    var bColor = db ? (db.color || '#ccc') : '#ccc';

    // Build VLAN text for this connection (from A side)
    var connVlanIds = (typeof getPortVlanIds === 'function') ? getPortVlanIds(L.a.deviceId, L.a.port, L.a.sub) : [];
    var vlanTexts = [];
    connVlanIds.forEach(function(vid){
      var vdef = (typeof getVlanById === 'function') ? getVlanById(vid) : null;
      if (vdef) vlanTexts.push(String(vdef.vid));
    });
    var vlanCellText = vlanTexts.join(', ');

    var tr = el(
      'tr',
      { class: 'conn-row', dataset: { linkId: L.id } },
      el('td', {}, String(i + 1)),
      el(
        'td',
        { class: 'deviceA' },
        el('span', { class: 'devName', style: '--devColor:' + aColor, title: aName }, aName)
      ),
      el('td', {}, aPortLabel),
      el('td', {}, aAlias),
      el('td', {}, '⇄'),
      el(
        'td',
        { class: 'deviceB' },
        el('span', { class: 'devName', style: '--devColor:' + bColor, title: bName }, bName)
      ),
      el('td', {}, bPortLabel),
      el('td', {}, bAlias),
      el('td', {style:'font-size:11px;color:var(--muted)'}, vlanCellText),
      el(
        'td',
        {},
        el('button', { class: 'btn-danger', onclick: function () { unlink(L.id); } }, 'Unlink')
      )
    );

    tr.addEventListener('click', function (e) {
      if (e.target.tagName.toLowerCase() === 'button') return;
      if (highlightedLinkId === L.id) {
        highlightLink(null);
        clearSelectionOutlines();
        return;
      }
      highlightLink(L.id);

      function sel(ep) {
        var s = '.port[data-device-id="' + ep.deviceId + '"][data-port="' + ep.port + '"]';
        if (ep.sub != null) s += '[data-sub="' + ep.sub + '"]';
        return s;
      }

      clearSelectionOutlines();
      var portA = document.querySelector(sel(L.a));
      var portB = document.querySelector(sel(L.b));
      if (portA) outlineForSelection(portA);
      if (portB) outlineForSelection(portB);
    });

    tr.addEventListener('mouseenter', function (e) {
      if (!tr.classList.contains('highlight')) return;

      // For hover, show the same labels we show in the table
      var html =
        '<div class="hc-title">Connection</div>' +
        '<div class="hc-row"><div class="hc-k">A</div><div>' +
          aName + ' • ' + aPortLabel + (aAlias ? ' • ' + aAlias : '') +
        '</div></div>' +
        '<div class="hc-row"><div class="hc-k">B</div><div>' +
          bName + ' • ' + bPortLabel + (bAlias ? ' • ' + bAlias : '') +
        '</div></div>';

      hcShowForTarget(tr, html, e);
    });

    tr.addEventListener('mousemove', function (e) {
      if (!tr.hasAttribute('data-link-id')) return; // cheap guard, but safe
      if (!tr.classList.contains('highlight')) return;
      hcShowForTarget(tr, __hoverEl && __hoverEl.innerHTML || '', e);
    });

    tr.addEventListener('mouseleave', hcHide);

    rows.push(tr);
  });

  if (state.reservedPorts) {
    Object.keys(state.reservedPorts).forEach(function (k) {
      var parts = k.split(':');
      var devId = parts[0];
      var port  = Number(parts[1]);
      var sub   = (parts[2] === '' ? null : Number(parts[2]));
      var d     = deviceById(devId);
      if (!d) return;

      var rLabel = state.reservedPorts[k];
      var alias  = aliasFor(devId, port, sub);

      // 🔹 Custom name for reserved ports too
      var reservedPortLabel =
        (d && d.portNames && d.portNames[keyFor(devId, port, sub)]) ||
        ('Port ' + port + (sub != null ? '/' + (sub + 1) : ''));

      var textParts = [
        (d && d.name) || 'Unknown',
        'port' + port,
        'port ' + port,
        String(port),
        reservedPortLabel,
        alias || '',
        rLabel || '',
        (sub != null ? (sub === 0 ? 'top' : 'bottom') : '')
      ];

      var text = textParts.join(' ').toLowerCase();
      if (q && text.indexOf(q) === -1) return;

      var tr = el(
        'tr',
        {
          class: 'conn-row reserved-row',
          dataset: {
            deviceId: devId,
            port: String(port),
            sub: (sub == null ? '' : String(sub))
          }
        },
        el('td', {}, '—'),
        el(
          'td',
          { class: 'deviceA' },
          el(
            'span',
            { class: 'devName', style: '--devColor:' + (d.color || '#ccc'), title: d.name },
            d.name
          )
        ),
        el('td', {}, reservedPortLabel),
        el('td', {}, alias || ''),
        el('td', {}, '⟂'),
        el(
          'td',
          { class: 'deviceB' },
          el(
            'span',
            { class: 'devName', style: '--devColor:#666', title: 'Reserved' },
            'Reserved'
          )
        ),
        el('td', {}, '—'),
        el('td', {}, rLabel || ''),
        el(
          'td',
          {},
          el('button', {
              class: 'btn-danger',
              onclick: function () {
                delete state.reservedPorts[k];
                saveStore();
                render();
              }
            },
            'Clear'
          )
        )
      );

      tr.addEventListener('click', function (e) {
        if (e.target.tagName.toLowerCase() === 'button') return;
        highlightedLinkId = null;
        applyRowHighlight();
        clearSelectionOutlines();

        var sel =
          '.port[data-device-id="' + devId + '"][data-port="' + port + '"]' +
          (sub != null ? '[data-sub="' + sub + '"]' : '');

        var n = document.querySelector(sel);
        if (n) outlineForSelection(n);
      });

      rows.push(tr);
    });
  }

  if (!rows.length) {
    tb.appendChild(
      el(
        'tr',
        {},
        el('td', { colspan: '9', class: 'muted' }, 'No connections found.')
      )
    );
  } else {
    rows.forEach(function (r) { tb.appendChild(r); });
  }

  applyRowHighlight();
}

function applyRowHighlight(){ $all('.conn-row').forEach(function(r){ if(highlightedLinkId && r.dataset.linkId===highlightedLinkId) r.classList.add('highlight'); else r.classList.remove('highlight'); }); }
function highlightLink(linkId, opts){
  highlightedLinkId = linkId; applyRowHighlight(); clearSelectionOutlines();
  if (!linkId) return;
  var L = state.links.find(function(x){ return x.id===linkId; }); if (!L) return;
  function sel(ep){ var s='.port[data-device-id="'+ep.deviceId+'"][data-port="'+ep.port+'"]'; if(ep.sub!=null) s+='[data-sub="'+ep.sub+'"]'; return s; }
  var portA = document.querySelector(sel(L.a));
  var portB = document.querySelector(sel(L.b));
  if (portA) outlineForSelection(portA);
  if (portB) outlineForSelection(portB);
  if (opts && opts.from){
    var origin = document.querySelector(sel(opts.from));
    if (origin){ outlineForSelection(origin); }
  }
}
function unlink(linkId){
  state.links = state.links.filter(function(L){ return L.id!==linkId; });
  if(highlightedLinkId===linkId) highlightedLinkId=null;
  saveStore(); render();
}

/* ------- Speed picker (context menu) ------- */
var __speedMenu = null;
var __menuOpen = false;

function closeSpeedMenu(){
  if(__speedMenu){
    __speedMenu.remove();
    __speedMenu = null;
  }
  __menuOpen = false;
  hcHide();
  clearSelectionOutlines();
}


function openSpeedMenu(e, deviceId, port, sub){
  closeSpeedMenu();
  e.preventDefault();

  __menuOpen = true;
  hcHide();

  clearSelectionOutlines();
  const portEl = document.querySelector(
    `.port[data-device-id="${deviceId}"][data-port="${port}"]${sub != null ? `[data-sub="${sub}"]` : ''}`
  );
  if (portEl) outlineForSelection(portEl);

  var curSpeed = speedFor(deviceId, port, sub) || '';
  var curVlan  = vlanFor(deviceId, port, sub);
  var curVlanStr = (curVlan === '' ? '' : String(curVlan));

  // ----- Build DEFAULT "Linked to" value (read-only behaviour) -----
  var peer = getPeer(deviceId, port, sub);

  var peerDev = null;
  var peerPortLabel = '';
  var defaultLinkedTo = '';

  if (peer) {
    peerDev = deviceById(peer.deviceId);
    if (peerDev) {
      peerPortLabel =
        (peerDev.portNames && peerDev.portNames[keyFor(peer.deviceId, peer.port, peer.sub)]) ||
        ('Port ' + peer.port + (peer.sub != null ? '/' + (peer.sub + 1) : ''));
      defaultLinkedTo = (peerDev.name || 'Unknown') + ' – ' + peerPortLabel;
    }
  } else if (isReservedPort(deviceId, port, sub)) {
    // For reserved ports, show the reserved label as the "default"
    defaultLinkedTo = reservedLabelFor(deviceId, port, sub) || 'Reserved';
  } else {
    defaultLinkedTo = '';
  }

  // If user has overridden it, show the override, otherwise default
  var curLinkedTo = linkedToOverrideFor(deviceId, port, sub);
  var linkedToValue = (curLinkedTo !== '' ? curLinkedTo : defaultLinkedTo);

  var div = document.createElement('div');
  div.className = 'speed-menu';

  div.innerHTML =
    '<div style="font-size:12px;margin-bottom:6px;font-weight:600">'+(typeof T!=='undefined'&&T.port_settings||'Port settings')+'</div>' +

    (store.settings.enablePortRename ? (
      '<div style="display:flex;align-items:center;gap:6px;font-size:12px;margin-bottom:6px">' +
        '<label for="__nameInput" style="margin:0;white-space:nowrap;min-width:70px">'+(typeof T!=='undefined'&&T.port_name||'Port name')+'</label>' +
        '<input id="__nameInput" type="text" placeholder="e.g. Channel 1" ' +
               'style="flex:1;padding:4px 6px;font-size:12px;border-radius:6px;' +
                      'border:1px solid var(--line);background:var(--bg);color:var(--ink)">' +
        '<button id="__nameReset" type="button" title="'+(typeof T!=="undefined"&&T.reset_default||"Reset to default")+'" ' +
               'style="padding:4px 6px;font-size:11px;border-radius:6px;border:1px solid var(--line);' +
                      'background:var(--bg);color:var(--ink);cursor:pointer;">↺</button>' +
      '</div>'
    ) : '') +

    '<div style="display:flex;align-items:center;gap:6px;font-size:12px;margin-bottom:6px">' +
      '<label for="__aliasInput" style="margin:0;white-space:nowrap;min-width:70px">'+(typeof T!=='undefined'&&T.port_alias||'Alias')+'</label>' +
      '<input id="__aliasInput" type="text" placeholder="Alias (optional)" ' +
             'style="flex:1;padding:4px 6px;font-size:12px;border-radius:6px;' +
                    'border:1px solid var(--line);background:var(--bg);color:var(--ink)">' +
    '</div>' +

    '<div style="display:flex;align-items:center;gap:6px;font-size:12px;margin-bottom:6px">' +
      '<label for="__speedSelect" style="margin:0;white-space:nowrap;min-width:70px">'+(typeof T!=='undefined'&&T.link_speed||'Link speed')+'</label>' +
      '<select id="__speedSelect" style="flex:1">' +
        '<option value="">'+(typeof T!=='undefined'&&T.speed_none||'(none)')+'</option>' +
        '<option>100 Mbit</option><option>1 Gbit</option><option>2.5 Gbit</option>' +
        '<option>5 Gbit</option><option>10 Gbit</option><option>25 Gbit</option>' +
        '<option>40 Gbit</option><option>100 Gbit</option>' +
      '</select>' +
    '</div>' +

    // --- VLAN assignments (multi-select) ---
    (function(){
      var vlans = getVlans();
      if (!vlans.length) return '';
      var curIds = getPortVlanIds(deviceId, port, sub);
      var html = '<div style="font-size:11px;color:var(--muted);margin-bottom:2px">VLANs</div>' +
        '<div id="__vlanChecks" style="max-height:100px;overflow-y:auto;border:1px solid var(--line);border-radius:6px;padding:4px 6px;margin-bottom:6px;background:var(--bg)">';
      vlans.forEach(function(v){
        var checked = curIds.indexOf(v.id) >= 0 ? ' checked' : '';
        html += '<label style="display:flex;align-items:center;gap:4px;font-size:11px;cursor:pointer;padding:1px 0">' +
          '<input type="checkbox" data-vlan-id="'+v.id+'"'+checked+' style="width:14px;height:14px">' +
          '<span class="vlan-dot" style="background:'+(v.color||'#888')+';width:8px;height:8px;border-radius:50%;display:inline-block;flex-shrink:0"></span>' +
          '<span>'+v.vid+' '+(v.name||'')+'</span></label>';
      });
      html += '</div>';
      return html;
    })() +

    // --- Port color ---
    '<div style="display:flex;align-items:center;gap:6px;font-size:12px;margin-bottom:6px">' +
      '<label style="margin:0;white-space:nowrap;min-width:70px">'+(typeof T!=='undefined'&&T.port_color||'Port color')+'</label>' +
      '<input id="__portColorInput" type="color" value="' + (portColorFor(deviceId,port,sub)||'#0f141d') + '" ' +
             'style="width:28px;height:24px;border:1px solid var(--line);border-radius:4px;padding:0;cursor:pointer;background:var(--bg)">' +
      '<button id="__portColorReset" type="button" title="'+(typeof T!=="undefined"&&T.reset_color||"Reset color")+'" ' +
             'style="padding:4px 6px;font-size:11px;border-radius:6px;border:1px solid var(--line);background:var(--bg);color:var(--ink);cursor:pointer;">↺</button>' +
    '</div>' +

    // --- Notes ---
    '<div style="display:flex;gap:6px;font-size:12px;margin-bottom:6px">' +
      '<label for="__noteInput" style="margin:0;white-space:nowrap;min-width:70px;padding-top:3px">Note</label>' +
      '<textarea id="__noteInput" maxlength="150" rows="4" placeholder="'+(typeof T!=='undefined'&&T.port_note_ph||'Notes...')+'" ' +
             'style="flex:1;padding:4px 6px;font-size:12px;border-radius:6px;border:1px solid var(--line);background:var(--bg);color:var(--ink);resize:vertical;font-family:inherit">' + (portNoteFor(deviceId,port,sub)||'').replace(/</g,'&lt;') + '</textarea>' +
    '</div>' +

    // --- LINKED TO (editable) + reset button ---
    '<div style="display:flex;align-items:center;gap:6px;font-size:12px;margin-bottom:8px">' +
      '<label for="__linkedToInput" style="margin:0;white-space:nowrap;min-width:70px">'+(typeof T!=='undefined'&&T.linked_to||'Linked to')+'</label>' +
      '<input id="__linkedToInput" type="text" placeholder="(auto)" ' +
             'style="flex:1;min-width:160px;padding:4px 6px;font-size:12px;border-radius:6px;border:1px solid var(--line);background:var(--bg);color:var(--ink)">' +
      '<button id="__linkedToReset" type="button" title="'+(typeof T!=="undefined"&&T.revert_auto||"Revert to automatic value")+'" ' +
             'style="padding:4px 6px;font-size:11px;border-radius:6px;border:1px solid var(--line);background:var(--bg);color:var(--ink);cursor:pointer;">↺</button>' +
    '</div>' +

    '<div style="display:flex;gap:6px;justify-content:flex-end">' +
      '<button id="__speedCancel" class="btn" type="button">'+(typeof T!=='undefined'&&T.cancel||'Cancel')+'</button>' +
      '<button id="__speedSave"   class="btn" type="button" style="font-weight:700">'+(typeof T!=='undefined'&&T.save||'Save')+'</button>' +
    '</div>';

  document.body.appendChild(div);
  __speedMenu = div;

  div.addEventListener('mousedown', function(ev){ ev.stopPropagation(); }, { capture:true });

  // Port rename (if enabled)
  var nin = div.querySelector('#__nameInput');
  if (nin) {
    const dev = deviceById(deviceId);
    const currentName =
      (dev && dev.portNames && dev.portNames[keyFor(deviceId, port, sub)]) ||
      ('Port ' + port + (sub != null ? '/' + (sub + 1) : ''));
    nin.value = currentName;
  }
  const resetBtn = div.querySelector('#__nameReset');
  if (resetBtn && nin) {
    resetBtn.addEventListener('click', () => {
      const def = 'Port ' + port + (sub != null ? '/' + (sub + 1) : '');
      nin.value = def;
    });
  }

  // Fill current values
  var ain = div.querySelector('#__aliasInput');
  var sel = div.querySelector('#__speedSelect');
  var lnk = div.querySelector('#__linkedToInput');

  ain.value = aliasFor(deviceId, port, sub) || '';
  sel.value = curSpeed || '';
  if (lnk) lnk.value = linkedToValue || '';

  // Linked-to reset (confirm → revert to automatic)
  var lnkReset = div.querySelector('#__linkedToReset');
  if (lnkReset && lnk) {
    lnkReset.addEventListener('click', function(){
      if (!confirm(typeof T!=='undefined'&&T.revert_auto_confirm||'Revert "Linked to" back to the automatic value?')) return;
      setLinkedToOverrideFor(deviceId, port, sub, '');
      lnk.value = defaultLinkedTo || '';
      // No render needed yet; saving happens on "Save" too, but this keeps state correct immediately.
    });
  }

  // Position menu
  var x = e.clientX, y = e.clientY;
  var vw = window.innerWidth, vh = window.innerHeight;
  var rw = 340, rh = 300;
  if (x + rw + 8 > vw) x = vw - rw - 8;
  if (y + rh + 8 > vh) y = Math.max(4, vh - rh - 8);
  div.style.left = x + 'px';
  div.style.top  = y + 'px';

  // Port color reset
  var pcResetBtn = div.querySelector('#__portColorReset');
  var pcColorIn = div.querySelector('#__portColorInput');
  if (pcResetBtn && pcColorIn) {
    pcResetBtn.addEventListener('click', function(){
      pcColorIn.value = '#0f141d';
      pcColorIn.dataset.wasReset = '1';
    });
    pcColorIn.addEventListener('input', function(){ pcColorIn.dataset.wasReset = '0'; });
  }

  function doSave(){
    // Save port name (if enabled)
    if (store.settings.enablePortRename && nin) {
      var newName = nin.value.trim() || ('Port ' + port + (sub != null ? '/' + (sub + 1) : ''));
      var dev = deviceById(deviceId);
      if (dev) {
        dev.portNames = dev.portNames || {};
        dev.portNames[keyFor(deviceId, port, sub)] = newName;
      }
    }

    // Alias
    var aliasRaw = ain.value.trim();
    var aliasKey = keyFor(deviceId, port, sub);
    if (!aliasRaw) delete state.portAliases[aliasKey];
    else state.portAliases[aliasKey] = aliasRaw;

    // Speed
    var speedVal = sel.value || '';
    setSpeedFor(deviceId, port, sub, speedVal);

    // VLAN assignments (multi-select) + sync to peer
    var vlanChecks = div.querySelectorAll('#__vlanChecks input[type=checkbox]');
    var selectedVlans = [];
    vlanChecks.forEach(function(cb){ if(cb.checked) selectedVlans.push(cb.dataset.vlanId); });
    setPortVlanIds(deviceId, port, sub, selectedVlans);
    // Sync VLANs to peer side
    var syncPeer = getPeer(deviceId, port, sub);
    if (syncPeer) {
      setPortVlanIds(syncPeer.deviceId, syncPeer.port, syncPeer.sub, selectedVlans);
    }

    // Port color + sync to peer
    var pcInput = div.querySelector('#__portColorInput');
    var pcReset = div.querySelector('#__portColorReset');
    if (pcInput) {
      var wasReset = pcInput.dataset.wasReset === '1';
      if (wasReset) {
        setPortColor(deviceId, port, sub, '');
        if (syncPeer) setPortColor(syncPeer.deviceId, syncPeer.port, syncPeer.sub, '');
      } else {
        setPortColor(deviceId, port, sub, pcInput.value);
        if (syncPeer) setPortColor(syncPeer.deviceId, syncPeer.port, syncPeer.sub, pcInput.value);
      }
    }

    // Notes
    var noteIn = div.querySelector('#__noteInput');
    if (noteIn) {
      setPortNote(deviceId, port, sub, noteIn.value);
    }

    // Linked-to override (store only if different from default)
    if (lnk) {
      var v = lnk.value.trim();
      if (!v || v === defaultLinkedTo) {
        setLinkedToOverrideFor(deviceId, port, sub, '');
      } else {
        setLinkedToOverrideFor(deviceId, port, sub, v);
      }
    }

    saveStore();
    render();
    closeSpeedMenu();
  }

  div.querySelector('#__speedSave').addEventListener('click', doSave);
  div.querySelector('#__speedCancel').addEventListener('click', closeSpeedMenu);

  // Enter/Escape convenience
  if (lnk) {
    lnk.addEventListener('keydown', function(ev){
      if (ev.key === 'Enter') doSave();
      if (ev.key === 'Escape') closeSpeedMenu();
    });
  }

  setTimeout(function(){
    function onDoc(e2){
      if (__speedMenu && !__speedMenu.contains(e2.target)) closeSpeedMenu();
    }
    function onEsc(e2){ if (e2.key==='Escape') closeSpeedMenu(); }
    document.addEventListener('mousedown', onDoc, { once:true, capture:true });
    document.addEventListener('keydown', onEsc, { once:true });
    window.addEventListener('wheel', function onWheel(e2){
      if (__speedMenu && __speedMenu.contains(e2.target)) return;
      closeSpeedMenu();
      window.removeEventListener('wheel', onWheel);
    }, { passive:true });
  }, 0);
}



/* ===================== PRINT ===================== */
function openPrintSheet(includeTable){
  var devicesHTML = document.getElementById('devRows').innerHTML;
  var tableEl = document.getElementById('connTable');
  var tableHTML = (includeTable && tableEl) ? tableEl.outerHTML : '';
  var when = new Date().toLocaleString();

  var css = ''
+ '@page{size:A4 portrait;margin:12mm}\n'
+ '@page:first{size:A4 landscape;margin:12mm}\n'
+ '*{box-sizing:border-box}\n'
+ '@media print{*{-webkit-print-color-adjust:exact;print-color-adjust:exact}}\n'
+ '.device,.port,.dual-cell,.swatch,header{ -webkit-print-color-adjust:exact; print-color-adjust:exact }\n'
+ '.page{break-inside:avoid;page-break-inside:avoid}\n'
+ '.page + .page{break-before:page;page-break-before:always}\n'
+ ':root{--ink:#111;--line:#ddd;--prtPortH:75px;--prtGap:0px}\n'
+ '.port{height:var(--prtPortH)!important;box-sizing:border-box;overflow:hidden;display:block}\n'
+ '.dual-cell{display:flex;flex-direction:column;gap:var(--prtGap);height:calc(var(--prtPortH)*2 + var(--prtGap))!important;box-sizing:border-box;overflow:hidden}\n'
+ '.dual-cell .port{height:var(--prtPortH)!important;border:0!important;border-radius:0!important}\n'
+ '.dual-cell .port:first-child{border-bottom:1px solid #333!important}\n'

  + '*{box-sizing:border-box}\n'
  + 'body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;color:var(--ink);background:#fff;margin:0}\n'
  + '.page{max-width:calc(297mm - 16mm);margin:10mm auto}\n'
  + 'header{display:flex;align-items:baseline;justify-content:space-between;margin-bottom:12px}\n'
  + 'h1{font-size:18px;margin:0}\n'
  + '.meta{font-size:12px;color:#555}\n'
  + '.dev-rows{display:flex;flex-direction:column;gap:12px}\n'
  + '.dev-row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}\n'
  + '.device-wrap{width:100%}\n'
  + '.device-wrap.full{grid-column:1 / -1}\n'
  + '.device{background:#fff;border:1px solid var(--line);border-radius:10px;padding:10px;overflow:hidden}\n'
  + '.device-head{display:flex;align-items:baseline;justify-content:space-between;gap:8px}\n'
  + '.device-title{font-size:14px;font-weight:700;display:flex;align-items:center;gap:8px}\n'
  + '.device-actions,.inline-controls{display:none!important}\n'
  + '.swatch{width:12px;height:12px;border-radius:3px;border:1px solid #0003;display:inline-block}\n'
  + '.ports-rows{display:flex;flex-direction:column;gap:8px;margin-top:10px;overflow:hidden}\n'
  + '.port-row{display:grid;justify-content:start;align-content:start}\n'
  + '.device-wrap.full .port-row{grid-template-columns:repeat(12,minmax(40px,1fr));gap:10px !important;}\n'
  + '.device-wrap:not(.full) .port-row{grid-template-columns:repeat(6,minmax(40px,1fr));gap:7px !important;}\n'
  /* --------------------------------------------------------------------------- */
  + '.port{border:1px solid #333;border-radius:8px;padding:6px 3px 4px;text-align:center;grid-column:auto!important;width:auto!important}\n'
  + '.port .num{font-size:12px;font-weight:600}\n'
  + '.port .alias{font-size:11px;margin-top:2px;min-height:1.1em}\n'
  + '.port .peer{font-size:12px;font-weight:700;margin-top:4px;min-height:1.2em}\n'
  + '.port:not(.connected):not(.reserved){background:#fff !important;border-color:#444 !important}\n'
  + '.port:not(.connected):not(.reserved) .num,'
  + '.port:not(.connected):not(.reserved) .alias,'
  + '.port:not(.connected):not(.reserved) .peer{color:#000 !important}\n'
  + '.port.connected{color:#000}\n'
  + '.port.reserved{background:#555 !important;color:#fff !important;border-color:#333 !important}\n'
  + '.dual-cell{border-color:#333 !important}\n'
  + '.port.reserved .num,.port.reserved .alias,.port.reserved .peer{color:#fff !important}\n'
  + 'table{width:100%;border-collapse:collapse;margin-top:16px}\n'
  + 'th,td{border:1px solid var(--line);padding:6px 8px;font-size:12px;text-align:left}\n'
  + 'th{background:#f6f7f9;font-weight:700}\n'
  + 'th:last-child, td:last-child{display:none}\n'
  + '@media print{.controls{display:none};}\n';


  var html = ''
  + '<!doctype html><html><head><meta charset="utf-8">'
  + '<title>Ethernet Cable Connection Manager – Print</title><style>' + css + '</style></head><body>'
  + '<div class="page">'
  + '<header><h1>Profile: ' + store.current + '</h1><div class="meta">' + 'Ethernet Cable Connection Manager: <a href="https://https://github.com/bijomaru78/eccm" target="_blank">https://github.com/bijomaru78/eccm</a> - Printed on '+when+'</div></header>' 
  + '<section id="printDevices" class="dev-rows">' + devicesHTML + '</section>'
  + (tableHTML ? '<div class="page"><h2 style="font-size:16px;margin:18px 0 8px">Connections</h2>' + tableHTML + '</div>' : '')
  + '<div class="controls" style="margin-top:12px"><button onclick="window.print()">Print</button></div>'
  + '</div>'
  + '<script>(function(){'
  + '  var GAP_FULL=9, GAP_HALF=7, MIN_TRACK=40;'
  + '  function sizeRows(){'
  + '    var rows=document.querySelectorAll(".port-row,[data-port-row]");'
  + '    for(var i=0;i<rows.length;i++){'
  + '      var row=rows[i];'
  + '      var wrap=row.closest(".device-wrap");'
  + '      row.style.display = "grid";'
  + '      row.style.justifyContent = "start";'
  + '      row.style.alignContent = "start";'
  + '      row.style.gridAutoFlow = "row";'
  + '      var isFull = wrap && wrap.classList.contains("full");'
  + '      var cols   = isFull ? 12 : 6;'
  + '      var gap    = isFull ? GAP_FULL : GAP_HALF;'
  + '      var inner = row.getBoundingClientRect().width;'
  + '      if(inner && inner > 0){'
  + '        var track = Math.floor((inner - gap * (cols - 1)) / cols);'
  + '        if (!(track > 0)) track = 60;'
  + '        if (track < MIN_TRACK) track = MIN_TRACK;'
  + '		 track += -3;'
  + '        row.style.gridTemplateColumns = "repeat(" + cols + ", " + track + "px)";'
  + '      }'
  + '      row.style.gap = gap + "px";'
  + '    }'
  + '  }'
  + '  requestAnimationFrame(function(){ requestAnimationFrame(sizeRows); });'
  + '  Array.prototype.forEach.call(document.querySelectorAll(".port"),function(p){'
  + '    if(!p.classList.contains("connected") && !p.classList.contains("reserved")){'
  + '      p.style.background=""; p.style.color="";'
  + '      p.querySelectorAll(".num,.alias,.peer").forEach(function(el){ el.style.color=""; });'
  + '    }'
  + '  });'
  + '  Array.prototype.forEach.call(document.querySelectorAll("[draggable]"),function(el){el.removeAttribute("draggable");});'
  + '})();<\/script>'
  + '</body></html>';

  var win = window.open('', '_blank');
  win.document.open();
  win.document.write(html);
  win.document.close();
  
  win.onload = function() {
  win.focus();
  setTimeout(function() {
    win.print();
    win.close();
  }, 30); 
};
}



/* ===================== ORCHESTRATION ===================== */
function render(){ renderDevices(); applyPeerScrolling(); renderConnections(); }

/* ===================== PALETTE ===================== */
(function(){
  const palette = [
  '#FFCDD2','#C8E6C9','#BBDEFB','#F5F5F5',
  '#F44336','#4CAF50','#2196F3','#E0E0E0',
  '#FF0000','#00FF00','#0000FF','#BDBDBD',
  '#FF6F00','#FFFF00','#BF00FF','#9E9E9E',
  '#FF9800','#FFEB3B','#9C27B0','#757575',
  '#FFB74D','#FFF176','#BA68C8','#616161'
]

   const modal   = document.getElementById('paletteModal');
  const grid    = document.getElementById('paletteGrid');
  const openBtn = document.getElementById('openPalette');
  const closeBtn= document.getElementById('paletteClose');
  const previewSidebar = document.getElementById('devColorPreview');
  let chosenColor = '#E74C3C';

  function open(){ modal.style.display='flex'; }
  function close(){ modal.style.display='none'; }
  function updatePreview(){ previewSidebar.style.background = chosenColor; }

  /* ---------- RGB / HSV conversion helpers ---------- */
  function rgbToHex(r,g,b){
    return '#' + [r,g,b].map(x=>x.toString(16).padStart(2,'0')).join('').toUpperCase();
  }
  function hexToRgb(hex){
    const n = parseInt(hex.replace('#',''),16);
    return {r:(n>>16)&255, g:(n>>8)&255, b:n&255};
  }
  function rgbToHsv(r, g, b) {
    r/=255; g/=255; b/=255;
    const max = Math.max(r,g,b), min = Math.min(r,g,b);
    const d = max-min;
    let h,s,v = max;
    s = max===0 ? 0 : d/max;
    if(max===min) h=0;
    else {
      switch(max){
        case r: h=(g-b)/d + (g<b?6:0); break;
        case g: h=(b-r)/d + 2; break;
        case b: h=(r-g)/d + 4; break;
      }
      h /= 6;
    }
    return {h:h*360, s:s*100, v:v*100};
  }
  function hsvToRgb(h, s, v){
    h/=360; s/=100; v/=100;
    const i = Math.floor(h*6);
    const f = h*6 - i;
    const p = v*(1-s);
    const q = v*(1-f*s);
    const t = v*(1-(1-f)*s);
    let r,g,b;
    switch(i%6){
      case 0: r=v; g=t; b=p; break;
      case 1: r=q; g=v; b=p; break;
      case 2: r=p; g=v; b=t; break;
      case 3: r=p; g=q; b=v; break;
      case 4: r=t; g=p; b=v; break;
      case 5: r=v; g=p; b=q; break;
    }
    return {r:Math.round(r*255), g:Math.round(g*255), b:Math.round(b*255)};
  }

  /* ---------- Build palette grid ---------- */
  grid.innerHTML = '';
  palette.forEach(hex=>{
    const chip = document.createElement('button');
    chip.className='chip';
    chip.style.background=hex;
    chip.title=hex;
    chip.addEventListener('click',()=>{
      chosenColor=hex;
      updateFromHex(hex);
      updatePreview();
    });
    grid.appendChild(chip);
  });

  /* ---------- Add custom sliders ---------- */
const customHTML = `
  <div id="customColorPicker"
       style="display:grid;
              grid-template-columns:repeat(5,1fr);
              grid-template-rows:auto auto auto;
              gap:12px;
              align-items:center;
              justify-items:center;
              margin-left:12px;
              margin-top:6px;">

    <!-- Headers -->
    <div style="font-size:12px;color:var(--muted)">Hue</div>
    <div style="font-size:12px;color:var(--muted)">Sat</div>
    <div style="font-size:12px;color:var(--muted)">R</div>
    <div style="font-size:12px;color:var(--muted)">G</div>
    <div style="font-size:12px;color:var(--muted)">B</div>

    <!-- Sliders -->
<div id="hueWrap"
     style="position:relative;height:120px;width:16px;display:flex;align-items:center;justify-content:center;">
  <!-- static hue gradient -->
  <div id="hueBar"
       style="position:absolute;inset:0;
              background:linear-gradient(to top, red, yellow, lime, cyan, blue, magenta, red);
              border-radius:6px;filter:brightness(0.9);">
  </div>
  <!-- moving indicator -->
  <div id="hueMarker"
       style="position:absolute;left:0;right:0;height:2px;background:white;
              border-radius:1px;box-shadow:0 0 4px rgba(0,0,0,0.8);pointer-events:none;">
  </div>
  <!-- functional slider -->
  <input type="range" id="hueSlider" min="0" max="360"
         style="position:relative;z-index:5;height:120px;width:16px;
                appearance:slider-vertical;writing-mode:bt-lr;
                opacity:0;">
</div>

    <input type="range" id="satSlider" min="0" max="100"
           style="height:120px;width:10px;appearance:slider-vertical;writing-mode:bt-lr;">
    <input type="range" id="rSlider" min="0" max="255"
           style="height:120px;width:10px;appearance:slider-vertical;writing-mode:bt-lr;">
    <input type="range" id="gSlider" min="0" max="255"
           style="height:120px;width:10px;appearance:slider-vertical;writing-mode:bt-lr;">
    <input type="range" id="bSlider" min="0" max="255"
           style="height:120px;width:10px;appearance:slider-vertical;writing-mode:bt-lr;">

<!-- Preview -->
<div id="colorPreviewBox"
     style="grid-column:1 / span 5;
            width:36px;height:36px;
            border-radius:8px;
            border:1px solid #333;
            box-shadow:0 0 8px rgba(0,0,0,0.25);
            margin-top:10px;"></div>

<!-- Hex field (editable) -->
<input type="text" id="hexOutput"
       placeholder="#RRGGBB"
       style="grid-column:1 / span 5;
              width:110px;
              text-align:center;
              font-size:13px;
              background:#0f141d;
              border:1px solid var(--line);
              border-radius:6px;
              color:var(--ink);
              padding:5px;
              margin-top:6px;">

  </div>`;


  grid.insertAdjacentHTML('afterend', customHTML);

  /* ---------- DOM refs ---------- */
  const hSlider=document.getElementById('hueSlider');
  const sSlider=document.getElementById('satSlider');
  const rSlider=document.getElementById('rSlider');
  const gSlider=document.getElementById('gSlider');
  const bSlider=document.getElementById('bSlider');
  const colorBox=document.getElementById('colorPreviewBox');
  const hexOut=document.getElementById('hexOutput');

  /* ---------- Core update logic ---------- */
  function updatePreviewBox(hex){
    colorBox.style.background=hex;
    hexOut.value=hex.toUpperCase();
    chosenColor=hex;
    updatePreview();
  }
  
  function updateHueMarker() {
  const hue = +hSlider.value || 0;
  const marker = document.getElementById('hueMarker');
  if (!marker) return;
  const bar = document.getElementById('hueWrap');
  const height = bar ? bar.clientHeight : 120;
  const pos = height - (hue / 360) * height; // invert so 0°=bottom, 360°=top
  marker.style.top = `${pos - 1}px`;
}


  function updateFromRGB(){
    const r=+rSlider.value, g=+gSlider.value, b=+bSlider.value;
    const hex=rgbToHex(r,g,b);
    const {h,s}=rgbToHsv(r,g,b);
    hSlider.value=Math.round(h);
    sSlider.value=Math.round(s);
    updatePreviewBox(hex);
	updateHueMarker();
  }

  function updateFromHSV(){
    const h=+hSlider.value, s=+sSlider.value;
    const {r,g,b}=hsvToRgb(h,s,100);
    rSlider.value=r; gSlider.value=g; bSlider.value=b;
    const hex=rgbToHex(r,g,b);
    updatePreviewBox(hex);
	updateHueMarker();
  }

  function updateFromHex(hex){
    const {r,g,b}=hexToRgb(hex);
    rSlider.value=r; gSlider.value=g; bSlider.value=b;
    const {h,s}=rgbToHsv(r,g,b);
    hSlider.value=Math.round(h);
    sSlider.value=Math.round(s);
    updatePreviewBox(hex);
	updateHueMarker();
  }

  /* ---------- Events ---------- */
  [rSlider,gSlider,bSlider].forEach(sl=>sl.addEventListener('input',updateFromRGB));
  [hSlider,sSlider].forEach(sl=>sl.addEventListener('input',updateFromHSV));
	hexOut.addEventListener('input', e => {
	  const v = e.target.value.trim();
	  // allow partial input like "#ff" without breaking live update
	  if (/^#?[0-9A-Fa-f]{0,6}$/.test(v)) {
		e.target.style.borderColor = 'var(--line)';
	  } else {
		e.target.style.borderColor = '#d33'; // red outline if invalid
		return;
	  }
	  if (/^#?[0-9A-Fa-f]{6}$/.test(v)) {
		const hex = v.startsWith('#') ? v.toUpperCase() : '#' + v.toUpperCase();
		updateFromHex(hex);
	  }
	});


  /* ---------- Modal + Add device ---------- */
  openBtn.addEventListener('click',open);
  closeBtn.addEventListener('click',close);

  document.getElementById('addBtn').addEventListener('click',function(){
    const name=document.getElementById('devName').value.trim();
    const ports=Math.max(1,Math.min(9999,Number(document.getElementById('devPorts').value)||1));
    if(!name){alert('Please enter a device name.');return;}
    state.devices.push({
      id:uid(),name,ports,color:chosenColor,
      forceFullRow:false,midWrapMode:'balanced',smallWrap:false,dualLink:false,
      numbering:'row'
    });
    document.getElementById('devName').value='';
    saveStore();render();
  });

  updateFromHex(chosenColor);
  updateHueMarker();
})();


/* ===================== LAYOUT MODAL ===================== */
function openLayoutModal(deviceId){
  var d = deviceById(deviceId); if (!d) return;
  var backdrop = $('#layoutModal');
  var nameEl = $('#layoutDeviceName');
  var selFull = $('#layoutFullRow');
  var selMid = $('#layoutMidWrap');
  var selSmall = $('#layoutSmallWrap');
  var selDual = $('#layoutDualLink');
  var rowMid = $('#optMidWrap');
  var rowSmall = $('#optSmallWrap');
  var selNumbering = $('#layoutNumbering');


  nameEl.textContent = d.name + ' ('+d.ports+' ports)';
  selFull.value = d.forceFullRow ? 'full' : 'auto';
  selMid.value = (d.midWrapMode === 'twelve') ? 'twelve' : 'balanced';
  selSmall.value = d.smallWrap ? 'split' : 'single';
  selDual.value = d.dualLink ? 'on' : 'off';
  selNumbering.value = (d.numbering === 'column' || d.numbering === 'column-bt') ? d.numbering : 'row';

  rowMid.style.display   = (d.ports>=13 && d.ports<=24) ? 'flex' : 'none';
  rowSmall.style.display = (d.ports>=7  && d.ports<=12) ? 'flex' : 'none';

  $('#layoutCancel').onclick = function(){ backdrop.style.display='none'; };
  $('#layoutSave').onclick = function(){
    d.forceFullRow = (selFull.value === 'full');
    if (d.ports>=13 && d.ports<=24) d.midWrapMode = (selMid.value === 'twelve') ? 'twelve' : 'balanced';
    if (d.ports>=7  && d.ports<=12) d.smallWrap   = (selSmall.value === 'split');
    d.dualLink = (selDual.value === 'on');
	d.numbering = (selNumbering.value === 'column' || selNumbering.value === 'column-bt') ? selNumbering.value : 'row';
    saveStore(); backdrop.style.display='none'; render();
  };
  /* --- allow Escape key & outside click to close Layout modal --- */
(function wireLayoutModalClose() {
  const modal = document.getElementById('layoutModal');
  if (!modal) return;

  // Close on Escape
  document.addEventListener('keydown', function (e) {
    if (modal.style.display === 'flex' && e.key === 'Escape') {
      modal.style.display = 'none';
    }
  });

  // Close when clicking backdrop (outside the inner .modal)
  modal.addEventListener('click', function (e) {
    if (e.target === modal) {
      modal.style.display = 'none';
    }
  });
})();

  backdrop.style.display = 'flex';
}

/* ===================== EDIT DEVICE MODAL ===================== */
let currentEditDevice = null;
let editChosenColor = '#888';

function openEditDeviceModal(deviceId) {
  const d = deviceById(deviceId);
  if (!d) return;

  currentEditDevice = d;
  const modal = document.getElementById('editDeviceModal');
  const nameInput = document.getElementById('editDevName');
  const portsInput = document.getElementById('editDevPorts');
  const preview = document.getElementById('editDevColorPreview');

  nameInput.value = d.name || '';
  portsInput.value = d.ports || 1;
  editChosenColor = d.color || '#888';
  preview.style.background = editChosenColor;
	const speedOptions = [
	  '', '100 Mbit', '1 Gbit', '2.5 Gbit', '5 Gbit',
	  '10 Gbit', '25 Gbit', '40 Gbit', '100 Gbit'
	];

	let maxSpeedRow = document.getElementById('editDevMaxSpeedRow');
	if (!maxSpeedRow) {
	  maxSpeedRow = document.createElement('div');
	  maxSpeedRow.className = 'row';
	  maxSpeedRow.id = 'editDevMaxSpeedRow';
	  maxSpeedRow.innerHTML =
		'<label style="min-width:100px">Max speed</label>' +
		'<select id="editDevMaxSpeed"></select>';
	  modal.querySelector('.modal').insertBefore(
		maxSpeedRow,
		modal.querySelector('.actions')
	  );
	}

	const speedSel = document.getElementById('editDevMaxSpeed');
	speedSel.innerHTML = speedOptions
	  .map(s => `<option value="${s}">${s || '(none)'}</option>`)
	  .join('');
	speedSel.value = d.maxSpeed || '';

  modal.style.display = 'flex';
}

function closeEditDeviceModal() {
  document.getElementById('editDeviceModal').style.display = 'none';
  currentEditDevice = null;
}

/* --- use main palette modal for Edit Device colour selection --- */
(function setupEditColorPicker() {
  const paletteModal = document.getElementById('paletteModal');
  const openBtn = document.getElementById('editOpenPalette');
  const preview = document.getElementById('editDevColorPreview');
  const paletteGrid = document.getElementById('paletteGrid');
  const closeBtn = document.getElementById('paletteClose');
  let tempTarget = null;

  // open shared palette modal but record the target (edit or add)
  openBtn.addEventListener('click', function () {
    tempTarget = 'edit';
    paletteModal.style.display = 'flex';
  });

  // intercept clicks in palette grid to update the correct target
  paletteGrid.querySelectorAll('.chip').forEach(chip => {
    chip.addEventListener('click', function () {
      const hex = chip.title || chip.style.background;
      if (tempTarget === 'edit') {
        editChosenColor = hex;
        preview.style.background = hex;
      } else {
        // fallback for add device
        chosenColor = hex;
        document.getElementById('devColorPreview').style.background = hex;
      }
      paletteModal.style.display = 'none';
    });
  });

  // close palette normally
  closeBtn.addEventListener('click', function () {
    paletteModal.style.display = 'none';
    tempTarget = null;
  });
})();


/* --- modal buttons --- */
document.getElementById('editDevCancel').addEventListener('click', closeEditDeviceModal);
document.getElementById('editDevSave').addEventListener('click', function() {
  if (!currentEditDevice) return;
  const name = document.getElementById('editDevName').value.trim();
  const ports = Math.max(1, Math.min(9999, Number(document.getElementById('editDevPorts').value) || 1));
  currentEditDevice.name = name || currentEditDevice.name;
  changePorts(currentEditDevice, ports);
  currentEditDevice.color = editChosenColor;
  const speedSel = document.getElementById('editDevMaxSpeed');
  currentEditDevice.maxSpeed = speedSel ? speedSel.value || null : null;
  saveStore(); render();
  closeEditDeviceModal();
});
/* --- allow Escape key & outside click to close --- */
(function wireEditModalClose() {
  const modal = document.getElementById('editDeviceModal');
  if (!modal) return;

  // Close on Escape
  document.addEventListener('keydown', function (e) {
    if (modal.style.display === 'flex' && e.key === 'Escape') {
      closeEditDeviceModal();
    }
  });

  // Close when clicking outside the modal box
  modal.addEventListener('click', function (e) {
    if (e.target === modal) {
      closeEditDeviceModal();
    }
  });
})();

