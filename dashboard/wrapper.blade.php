<style>
  .pc-dash-check,
  .pc-dash-power {
    position: fixed;
    z-index: 40;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: auto;
  }
  .pc-dash-check input,
  .pc-dash-all input {
    width: 16px;
    height: 16px;
    accent-color: #3c8dbc;
    cursor: pointer;
    margin: 0;
  }
  .pc-dash-all {
    position: fixed;
    z-index: 41;
    display: none;
    align-items: center;
    gap: 8px;
    color: #9ca3af;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .04em;
    pointer-events: auto;
  }
  .pc-dash-power { gap: 3px; }
  .pc-dash-power button,
  .pc-dash-bulk button {
    border: 0;
    border-radius: 3px;
    padding: 3px 8px;
    font-size: 11px;
    font-weight: 600;
    line-height: 1.3;
    color: #fff;
    cursor: pointer;
  }
  .pc-dash-power button[disabled] { opacity: .45; cursor: default; }
  .pc-dash-power .is-start, .pc-dash-bulk .is-start { background: #00a65a; }
  .pc-dash-power .is-restart, .pc-dash-bulk .is-restart { background: #f39c12; }
  .pc-dash-power .is-stop, .pc-dash-bulk .is-stop { background: #dd4b39; }
  .pc-dash-bulk .is-kill { background: #444; }
  .pc-dash-bulk .is-clear { background: #334155; }
  .pc-dash-bulk {
    position: sticky;
    bottom: 12px;
    z-index: 50;
    display: none;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin: 12px auto 0;
    max-width: 1200px;
    padding: 10px 14px;
    border-radius: 6px;
    background: #111827;
    border: 1px solid #334155;
    box-shadow: 0 10px 30px rgba(0,0,0,.35);
    color: #e2e8f0;
    font-size: 13px;
  }
  .pc-dash-bulk.is-open { display: flex; }
  .pc-dash-bulk__count { font-weight: 700; margin-right: 4px; }
  .pc-dash-toast {
    position: fixed;
    right: 18px;
    bottom: 18px;
    z-index: 3000;
    max-width: 420px;
    padding: 12px 14px;
    border-radius: 6px;
    background: #0f172a;
    border: 1px solid #334155;
    color: #e2e8f0;
    display: none;
    font-size: 13px;
  }
  .pc-dash-toast.is-open { display: block; }
  .pc-dash-toast.is-ok { border-color: #166534; }
  .pc-dash-toast.is-fail { border-color: #7f1d1d; }
  .pc-dash-modal {
    position: fixed;
    inset: 0;
    z-index: 4000;
    display: none;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.62);
  }
  .pc-dash-modal.is-open { display: flex; }
  .pc-dash-modal__box {
    width: min(440px, calc(100vw - 32px));
    background: #1f2937;
    border: 1px solid #334155;
    border-radius: 10px;
    box-shadow: 0 24px 60px rgba(0,0,0,.5);
    padding: 20px 22px 16px;
    color: #e5e7eb;
  }
  .pc-dash-modal__title { margin: 0 0 8px; font-size: 16px; font-weight: 700; color: #f3f4f6; }
  .pc-dash-modal__body { margin: 0 0 18px; font-size: 13px; line-height: 1.5; color: #cbd5e1; }
  .pc-dash-modal__actions { display: flex; justify-content: flex-end; gap: 8px; }
  .pc-dash-modal__actions button {
    border: 0;
    border-radius: 6px;
    padding: 8px 14px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
  }
  .pc-dash-modal__cancel { background: #334155; color: #e2e8f0; }
  .pc-dash-modal__ok { background: #dd4b39; color: #fff; }
  .pc-user-stats {
    position: fixed;
    z-index: 35;
    display: none;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    pointer-events: none;
  }
  @@media (max-width: 991px) { .pc-user-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
  @@media (max-width: 560px) { .pc-user-stats { grid-template-columns: 1fr; } }
  .pc-user-stat {
    background: #1f2937;
    border: 1px solid #334155;
    border-top: 3px solid #3c8dbc;
    border-radius: 6px;
    padding: 12px 14px 10px;
  }
  .pc-user-stat.is-servers { border-top-color: #f39c12; }
  .pc-user-stat.is-disk { border-top-color: #00a65a; }
  .pc-user-stat.is-ram { border-top-color: #00c0ef; }
  .pc-user-stat.is-cpu { border-top-color: #a78bfa; }
  .pc-user-stat__label { margin: 0; font-size: 11px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: #9ca3af; }
  .pc-user-stat__rows { margin: 8px 0 0; display: grid; gap: 6px; }
  .pc-user-stat__row { display: flex; justify-content: space-between; align-items: baseline; gap: 10px; }
  .pc-user-stat__row dt { margin: 0; font-size: 11px; color: #9ca3af; font-weight: 500; }
  .pc-user-stat__row dd { margin: 0; font-size: 14px; font-weight: 650; color: #e5e7eb; font-variant-numeric: tabular-nums; }
  .pc-user-stat__row.is-live dd { font-size: 18px; color: #fff; }
</style>
<div id="pc-dash-toast" class="pc-dash-toast"></div>
<div id="pc-dash-modal" class="pc-dash-modal" aria-modal="true" role="dialog">
  <div class="pc-dash-modal__box">
    <h3 class="pc-dash-modal__title" id="pc-dash-modal-title">Onay</h3>
    <p class="pc-dash-modal__body" id="pc-dash-modal-body"></p>
    <div class="pc-dash-modal__actions">
      <button type="button" class="pc-dash-modal__cancel" id="pc-dash-modal-cancel">Vazgeç</button>
      <button type="button" class="pc-dash-modal__ok" id="pc-dash-modal-ok">Onayla</button>
    </div>
  </div>
</div>
<div id="pc-user-stats" class="pc-user-stats">
  <div class="pc-user-stat is-servers">
    <p class="pc-user-stat__label">Servers</p>
    <dl class="pc-user-stat__rows">
      <div class="pc-user-stat__row is-live"><dt>Toplam sunucu</dt><dd id="pc-us-total">0</dd></div>
      <div class="pc-user-stat__row"><dt>Aktif sunucu</dt><dd id="pc-us-on">0</dd></div>
      <div class="pc-user-stat__row"><dt>Kapalı sunucu</dt><dd id="pc-us-off">0</dd></div>
    </dl>
  </div>
  <div class="pc-user-stat is-disk">
    <p class="pc-user-stat__label">Disk</p>
    <dl class="pc-user-stat__rows">
      <div class="pc-user-stat__row is-live"><dt>Anlık kullanım</dt><dd id="pc-ud-live">0</dd></div>
      <div class="pc-user-stat__row"><dt>Sunuculara atanan</dt><dd id="pc-ud-alloc">0</dd></div>
    </dl>
  </div>
  <div class="pc-user-stat is-ram">
    <p class="pc-user-stat__label">RAM</p>
    <dl class="pc-user-stat__rows">
      <div class="pc-user-stat__row is-live"><dt>Anlık kullanım</dt><dd id="pc-ur-live">0</dd></div>
      <div class="pc-user-stat__row"><dt>Sunuculara atanan</dt><dd id="pc-ur-alloc">0</dd></div>
    </dl>
  </div>
  <div class="pc-user-stat is-cpu">
    <p class="pc-user-stat__label">CPU</p>
    <dl class="pc-user-stat__rows">
      <div class="pc-user-stat__row is-live"><dt>Anlık kullanım</dt><dd id="pc-uc-live">0%</dd></div>
      <div class="pc-user-stat__row"><dt>Sunuculara atanan</dt><dd id="pc-uc-alloc">0%</dd></div>
    </dl>
  </div>
</div>
<script>
(function () {
  var CHECK_W = 40;
  var POWER_W = 168;

  function onDash() {
    var p = location.pathname.replace(/\/+$/, '') || '/';
    return p === '/' || p === '';
  }

  function csrf() {
    var meta = document.querySelector('meta[name="csrf-token"], meta[name="_token"]');
    if (meta && meta.getAttribute('content')) return meta.getAttribute('content');
    var m = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);
    return m ? decodeURIComponent(m[1]) : '';
  }

  var toastEl = document.getElementById('pc-dash-toast');
  var toastTimer = null;
  function toast(msg, kind) {
    if (!toastEl) return;
    toastEl.textContent = msg;
    toastEl.className = 'pc-dash-toast is-open' + (kind ? ' is-' + kind : '');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { toastEl.classList.remove('is-open'); }, 4500);
  }

  var modal = document.getElementById('pc-dash-modal');
  var modalTitle = document.getElementById('pc-dash-modal-title');
  var modalBody = document.getElementById('pc-dash-modal-body');
  var modalOk = document.getElementById('pc-dash-modal-ok');
  var modalCancel = document.getElementById('pc-dash-modal-cancel');
  var modalWait = null;
  function closeModal(ok) {
    if (!modal) return;
    modal.classList.remove('is-open');
    if (modalWait) {
      var done = modalWait;
      modalWait = null;
      done(!!ok);
    }
  }
  function askModal(title, body) {
    return new Promise(function (resolve) {
      if (!modal) { resolve(false); return; }
      modalWait = resolve;
      modalTitle.textContent = title;
      modalBody.textContent = body;
      modal.classList.add('is-open');
      modalOk.focus();
    });
  }
  if (modalOk) modalOk.addEventListener('click', function () { closeModal(true); });
  if (modalCancel) modalCancel.addEventListener('click', function () { closeModal(false); });
  if (modal) modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(false); });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal && modal.classList.contains('is-open')) closeModal(false);
  });

  var bulk = document.getElementById('pc-dash-bulk');
  if (!bulk) {
    bulk = document.createElement('div');
    bulk.id = 'pc-dash-bulk';
    bulk.className = 'pc-dash-bulk';
    bulk.innerHTML =
      '<span class="pc-dash-bulk__count" id="pc-dash-count">0 selected</span>' +
      '<button type="button" class="is-start" data-bulk="start">Start</button>' +
      '<button type="button" class="is-restart" data-bulk="restart">Restart</button>' +
      '<button type="button" class="is-stop" data-bulk="stop">Stop</button>' +
      '<button type="button" class="is-kill" data-bulk="kill">Kill</button>' +
      '<button type="button" class="is-clear" data-bulk="clear">Clear</button>';
    document.body.appendChild(bulk);
  }

  var allWrap = document.getElementById('pc-dash-all-wrap');
  if (!allWrap) {
    allWrap = document.createElement('label');
    allWrap.id = 'pc-dash-all-wrap';
    allWrap.className = 'pc-dash-all';
    allWrap.innerHTML = '<input type="checkbox" id="pc-dash-all"> Tümünü seç';
    document.body.appendChild(allWrap);
  }

  function cards() {
    return Array.prototype.filter.call(document.querySelectorAll('a[href^="/server/"]'), function (a) {
      return /^\/server\/[A-Za-z0-9-]+$/.test(a.getAttribute('href') || '');
    });
  }

  function identifier(a) {
    return (a.getAttribute('href') || '').replace('/server/', '').replace(/\/+$/, '');
  }

  function selected() {
    return Array.prototype.map.call(
      document.querySelectorAll('.pc-dash-check input:checked'),
      function (el) { return el.getAttribute('data-id'); }
    ).filter(Boolean);
  }

  function updateBulk() {
    var ids = selected();
    bulk.classList.toggle('is-open', onDash() && ids.length > 0);
    var count = document.getElementById('pc-dash-count');
    if (count) count.textContent = ids.length + ' selected';
    var boxes = document.querySelectorAll('.pc-dash-check input');
    var master = document.getElementById('pc-dash-all');
    if (master) {
      master.checked = boxes.length > 0 && ids.length === boxes.length;
      master.indeterminate = ids.length > 0 && ids.length < boxes.length;
    }
  }

  function el(id, cls, html) {
    var node = document.getElementById(id);
    if (node) return node;
    node = document.createElement('div');
    node.id = id;
    node.className = cls;
    node.innerHTML = html;
    document.body.appendChild(node);
    return node;
  }

  function boxFor(id) {
    return el(
      'pc-check-' + id,
      'pc-dash-check',
      '<input type="checkbox" data-id="' + id + '" title="Select">'
    );
  }

  function powerFor(id) {
    return el(
      'pc-power-' + id,
      'pc-dash-power',
      '<button type="button" class="is-start" data-power="start" data-id="' + id + '">Start</button>' +
      '<button type="button" class="is-restart" data-power="restart" data-id="' + id + '">Restart</button>' +
      '<button type="button" class="is-stop" data-power="stop" data-id="' + id + '">Stop</button>'
    );
  }

  function pin(node, left, top, width, height) {
    node.style.display = 'flex';
    node.style.left = Math.round(left) + 'px';
    node.style.top = Math.round(top) + 'px';
    node.style.width = Math.round(width) + 'px';
    node.style.height = Math.round(height) + 'px';
  }

  function place() {
    var show = onDash();
    var seen = {};
    if (show) {
      cards().forEach(function (a) {
        var id = identifier(a);
        if (!id) return;
        seen[id] = true;
        a.setAttribute('data-pc-dash', '1');
        a.style.paddingLeft = CHECK_W + 'px';
        a.style.paddingRight = (POWER_W + 16) + 'px';
        a.style.marginLeft = '';
        a.style.marginRight = '';
        var r = a.getBoundingClientRect();
        if (r.width < 40 || r.height < 20) {
          boxFor(id).style.display = 'none';
          powerFor(id).style.display = 'none';
          return;
        }
        pin(boxFor(id), r.left, r.top, CHECK_W, r.height);
        pin(powerFor(id), r.right - POWER_W - 14, r.top, POWER_W, r.height);
      });
    }
    Array.prototype.forEach.call(document.querySelectorAll('.pc-dash-check, .pc-dash-power'), function (node) {
      var id = node.id.replace('pc-check-', '').replace('pc-power-', '');
      if (!show || !seen[id]) node.style.display = 'none';
    });
    if (!show) {
      Array.prototype.forEach.call(document.querySelectorAll('a[data-pc-dash]'), function (a) {
        a.style.paddingLeft = '';
        a.style.paddingRight = '';
        a.style.marginLeft = '';
        a.style.marginRight = '';
        a.style.marginTop = '';
        a.removeAttribute('data-pc-dash');
      });
    }
    var list = cards();
    var statsBox = document.getElementById('pc-user-stats');
    if (show && list.length) {
      if (statsBox) {
        statsBox.style.display = 'grid';
        var probe = list[0].getBoundingClientRect();
        statsBox.style.width = Math.round(probe.width) + 'px';
        statsBox.style.left = Math.round(probe.left) + 'px';
        var statsH = statsBox.offsetHeight || 130;
        Array.prototype.forEach.call(list, function (a, i) {
          a.style.marginTop = i === 0 ? (statsH + 48) + 'px' : '';
        });
        var r0 = list[0].getBoundingClientRect();
        statsBox.style.left = Math.round(r0.left) + 'px';
        statsBox.style.width = Math.round(r0.width) + 'px';
        statsBox.style.top = Math.round(r0.top - statsH - 40) + 'px';
      }
      if (allWrap) {
        var first = list[0].getBoundingClientRect();
        allWrap.style.display = 'flex';
        allWrap.style.left = Math.round(first.left) + 'px';
        allWrap.style.top = Math.round(Math.max(8, first.top - 30)) + 'px';
      }
    } else {
      if (allWrap) allWrap.style.display = 'none';
      if (statsBox) statsBox.style.display = 'none';
    }
    updateBulk();
  }

  function powerOne(id, signal) {
    return fetch('/api/client/servers/' + encodeURIComponent(id) + '/power', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf(),
        'X-XSRF-TOKEN': csrf(),
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({ signal: signal })
    }).then(function (r) {
      if (r.status === 204 || r.ok) return { id: id, ok: true };
      return r.json().then(function (j) {
        return { id: id, ok: false, error: (j.errors && j.errors[0] && j.errors[0].detail) || ('HTTP ' + r.status) };
      }).catch(function () {
        return { id: id, ok: false, error: 'HTTP ' + r.status };
      });
    });
  }

  function powerMany(ids, signal) {
    if (!ids.length) return;
    toast(signal.toUpperCase() + ' → ' + ids.length + ' server…');
    Promise.all(ids.map(function (id) { return powerOne(id, signal); })).then(function (results) {
      var ok = results.filter(function (r) { return r.ok; }).length;
      var fail = results.length - ok;
      toast(signal + ': ' + ok + ' OK' + (fail ? ', ' + fail + ' FAIL' : ''), fail ? 'fail' : 'ok');
    }).catch(function (err) {
      toast('İstek hatası: ' + err, 'fail');
    });
  }

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-power]');
    if (!btn || !btn.closest('.pc-dash-power')) return;
    e.preventDefault();
    e.stopPropagation();
    var id = btn.getAttribute('data-id');
    var signal = btn.getAttribute('data-power');
    if (id && signal) powerMany([id], signal);
  });

  document.addEventListener('change', function (e) {
    if (e.target && e.target.closest('.pc-dash-check')) updateBulk();
  });

  bulk.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-bulk]');
    if (!btn) return;
    var action = btn.getAttribute('data-bulk');
    if (action === 'clear') {
      Array.prototype.forEach.call(document.querySelectorAll('.pc-dash-check input'), function (cb) { cb.checked = false; });
      updateBulk();
      return;
    }
    var ids = selected();
    if (!ids.length) return;
    var run = function () { powerMany(ids, action); };
    if (action === 'stop' || action === 'kill') {
      askModal(
        action === 'kill' ? 'Sunucuları öldür' : 'Sunucuları durdur',
        ids.length + ' sunucuya ' + action + ' uygulanacak. Devam edilsin mi?'
      ).then(function (ok) { if (ok) run(); });
      return;
    }
    run();
  });

  var master = document.getElementById('pc-dash-all');
  if (master) {
    master.addEventListener('change', function (e) {
      Array.prototype.forEach.call(document.querySelectorAll('.pc-dash-check input'), function (cb) {
        cb.checked = e.target.checked;
      });
      updateBulk();
    });
  }

  function setTxt(id, text) {
    var n = document.getElementById(id);
    if (n) n.textContent = text;
  }
  function amount(mib) {
    var value = Number(mib) || 0;
    if (Math.abs(value) >= 1024) {
      var gb = value / 1024;
      var digits = gb >= 10 ? 0 : 1;
      return gb.toFixed(digits).replace(/\.0$/, '') + ' GB';
    }
    return Math.round(value) + ' MiB';
  }
  function fmtCpu(value, unlimited) {
    if (unlimited) return '∞';
    return Number(value || 0).toFixed(1).replace(/\.0$/, '') + '%';
  }
  function loadUserStats() {
    if (!onDash()) return;
    fetch('/extensions/petocharts/mystats', {
      credentials: 'same-origin',
      headers: { Accept: 'application/json' }
    }).then(function (r) { return r.ok ? r.json() : null; }).then(function (stats) {
      if (!stats) return;
      var total = Number(stats.servers) || 0;
      var on = Number(stats.servers_running) || 0;
      var off = Number(stats.servers_offline != null ? stats.servers_offline : (total - on));
      setTxt('pc-us-total', String(total));
      setTxt('pc-us-on', String(on));
      setTxt('pc-us-off', String(off));
      setTxt('pc-ud-live', amount(stats.disk));
      setTxt('pc-ud-alloc', amount(stats.disk_allocated));
      setTxt('pc-ur-live', amount(stats.ram));
      setTxt('pc-ur-alloc', amount(stats.ram_allocated));
      setTxt('pc-uc-live', fmtCpu(stats.cpu));
      setTxt('pc-uc-alloc', fmtCpu(stats.cpu_allocated, stats.cpu_unlimited));
      place();
    }).catch(function () {});
  }

  window.addEventListener('scroll', place, true);
  window.addEventListener('resize', place);
  setInterval(place, 800);
  setTimeout(place, 400);
  loadUserStats();
  setInterval(loadUserStats, 5000);
})();
</script>
