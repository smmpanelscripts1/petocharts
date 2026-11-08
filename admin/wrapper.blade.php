@php
  $isAdminIndex = request()->routeIs('admin.index');
  $isServersIndex = request()->is('admin/servers') && !request()->is('admin/servers/*');
@endphp

@if($isAdminIndex)
  <style>
    .petocharts-admin-root { margin-bottom: 20px; }
    .petocharts-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 15px; margin-bottom: 15px; }
    @media (max-width: 991px) { .petocharts-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 520px) { .petocharts-stats { grid-template-columns: 1fr; } }
    .petocharts-stat { background: #1f2d3d; border: 1px solid #2c3b4c; border-top: 3px solid #3c8dbc; border-radius: 3px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.35); padding: 14px 16px 12px; }
    .petocharts-stat.is-servers { border-top-color: #f39c12; }
    .petocharts-stat.is-disk { border-top-color: #00a65a; }
    .petocharts-stat.is-ram { border-top-color: #00c0ef; }
    .petocharts-stat.is-cpu { border-top-color: #a78bfa; }
    .petocharts-stat__label { margin: 0; font-size: 12px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: #9ca3af; }
    .petocharts-stat__value { margin: 8px 0 0; font-size: 26px; font-weight: 600; line-height: 1.15; color: #f3f4f6; }
    .petocharts-stat__hint { margin: 6px 0 0; font-size: 12px; color: #9ca3af; }
  </style>
  <div id="petocharts-admin-root" class="petocharts-admin-root" style="display:none;">
    <div class="petocharts-stats">
      <div class="petocharts-stat is-servers">
        <p class="petocharts-stat__label">Servers</p>
        <p class="petocharts-stat__value" id="petocharts-stat-servers">0</p>
        <p class="petocharts-stat__hint">Total servers</p>
      </div>
      <div class="petocharts-stat is-disk">
        <p class="petocharts-stat__label">Disk</p>
        <p class="petocharts-stat__value" id="petocharts-stat-disk">0 / 0</p>
        <p class="petocharts-stat__hint">Allocated / capacity</p>
      </div>
      <div class="petocharts-stat is-ram">
        <p class="petocharts-stat__label">RAM</p>
        <p class="petocharts-stat__value" id="petocharts-stat-ram">0 / 0</p>
        <p class="petocharts-stat__hint">Allocated / capacity</p>
      </div>
      <div class="petocharts-stat is-cpu">
        <p class="petocharts-stat__label">CPU</p>
        <p class="petocharts-stat__value" id="petocharts-stat-cpu">0%</p>
        <p class="petocharts-stat__hint">Allocated limit</p>
      </div>
    </div>
    <div class="petocharts-admin-grid">
    <div class="petocharts-admin-card is-ram">
      <div class="petocharts-admin-card__header">
        <h3 class="petocharts-admin-card__title">RAM Usage</h3>
        <p class="petocharts-admin-card__value" id="petocharts-ram-label">0 MiB</p>
      </div>
      <div class="petocharts-admin-card__body">
        <canvas id="petocharts-ram"></canvas>
      </div>
    </div>

    <div class="petocharts-admin-card is-cpu">
      <div class="petocharts-admin-card__header">
        <h3 class="petocharts-admin-card__title">CPU Usage</h3>
        <p class="petocharts-admin-card__value" id="petocharts-cpu-label">0 %</p>
      </div>
      <div class="petocharts-admin-card__body">
        <canvas id="petocharts-cpu"></canvas>
      </div>
    </div>

    <div class="petocharts-admin-card is-disk">
      <div class="petocharts-admin-card__header">
        <h3 class="petocharts-admin-card__title">Disk Usage</h3>
        <p class="petocharts-admin-card__value" id="petocharts-disk-label">0 MiB</p>
      </div>
      <div class="petocharts-admin-card__body">
        <canvas id="petocharts-disk"></canvas>
      </div>
    </div>

    <div class="petocharts-admin-card is-servers">
      <div class="petocharts-admin-card__header">
        <h3 class="petocharts-admin-card__title">Servers</h3>
        <p class="petocharts-admin-card__value" id="petocharts-servers-label">0</p>
      </div>
      <div class="petocharts-admin-card__body">
        <canvas id="petocharts-servers"></canvas>
      </div>
    </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script>
    (function () {
      const root = document.getElementById('petocharts-admin-root');
      const content = document.querySelector('.content');
      if (!root || !content || typeof Chart === 'undefined') return;

      root.style.display = 'block';
      content.insertBefore(root, content.firstChild);

      const statServers = document.getElementById('petocharts-stat-servers');
      const statDisk = document.getElementById('petocharts-stat-disk');
      const statRam = document.getElementById('petocharts-stat-ram');
      const statCpu = document.getElementById('petocharts-stat-cpu');

      const amount = (mib) => {
        const value = Number(mib) || 0;
        if (Math.abs(value) >= 1024) {
          const gb = value / 1024;
          const digits = gb >= 10 ? 0 : 1;
          return { text: gb.toFixed(digits).replace(/\.0$/, ''), unit: 'GB' };
        }
        return { text: String(Math.round(value)), unit: 'MiB' };
      };

      const pair = (used, total, unlimited) => {
        const left = amount(used);
        if (unlimited) return left.text + ' ' + left.unit + ' / ∞';
        const right = amount(total);
        if (left.unit === right.unit) return left.text + ' / ' + right.text + ' ' + left.unit;
        return left.text + ' ' + left.unit + ' / ' + right.text + ' ' + right.unit;
      };

      const HISTORY = 20;
      const empty = () => Array(HISTORY).fill(0);

      const makeChart = (canvasId, color) => {
        const ctx = document.getElementById(canvasId).getContext('2d');
        return new Chart(ctx, {
          type: 'line',
          data: {
            labels: empty().map((_, i) => i),
            datasets: [{
              data: empty(),
              borderColor: color,
              backgroundColor: color + '33',
              fill: true,
              tension: 0.25,
              pointRadius: 0,
              borderWidth: 2,
            }],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            plugins: { legend: { display: false } },
            scales: {
              x: { display: false },
              y: {
                beginAtZero: true,
                ticks: { maxTicksLimit: 4, color: '#9ca3af' },
                grid: { color: 'rgba(148, 163, 184, 0.18)' },
              },
            },
          },
        });
      };

      const charts = {
        ram: makeChart('petocharts-ram', '#00c0ef'),
        cpu: makeChart('petocharts-cpu', '#605ca8'),
        disk: makeChart('petocharts-disk', '#00a65a'),
        servers: makeChart('petocharts-servers', '#f39c12'),
      };

      const labels = {
        ram: document.getElementById('petocharts-ram-label'),
        cpu: document.getElementById('petocharts-cpu-label'),
        disk: document.getElementById('petocharts-disk-label'),
        servers: document.getElementById('petocharts-servers-label'),
      };

      const push = (chart, value) => {
        const data = chart.data.datasets[0].data;
        data.push(Number(value.toFixed(2)));
        data.shift();
        chart.update();
      };

      const refresh = async () => {
        try {
          const res = await fetch('/extensions/petocharts/stats', {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
          });
          if (!res.ok) return;
          const stats = await res.json();

          labels.ram.textContent = `${Number(stats.ram).toFixed(0)} MiB`;
          labels.cpu.textContent = `${Number(stats.cpu).toFixed(1)} %`;
          labels.disk.textContent = `${Number(stats.disk).toFixed(0)} MiB`;
          labels.servers.textContent = `${Number(stats.servers)}`;

          statServers.textContent = String(Number(stats.servers) || 0);
          statDisk.textContent = pair(stats.disk, stats.disk_total, stats.disk_unlimited);
          statRam.textContent = pair(stats.ram, stats.ram_total, stats.ram_unlimited);
          statCpu.textContent = `${Number(stats.cpu).toFixed(0)}%`;

          push(charts.ram, Number(stats.ram) || 0);
          push(charts.cpu, Number(stats.cpu) || 0);
          push(charts.disk, Number(stats.disk) || 0);
          push(charts.servers, Number(stats.servers) || 0);
        } catch (e) {
          console.error('[petocharts]', e);
        }
      };

      refresh();
      setInterval(refresh, 5000);
    })();
  </script>
@endif

@if($isServersIndex)
  <style>
    .petocharts-col { white-space: nowrap; font-variant-numeric: tabular-nums; font-size: 12px; vertical-align: middle !important; }
    .petocharts-col .pc-muted { color: #9ca3af; font-weight: 400; }
    .petocharts-state { display: inline-block; min-width: 64px; padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; }
    .petocharts-state.is-running { background: #14532d; color: #86efac; }
    .petocharts-state.is-offline, .petocharts-state.is-unknown { background: #3f1d1d; color: #fca5a5; }
    .petocharts-state.is-starting, .petocharts-state.is-stopping { background: #422006; color: #fde68a; }
    .petocharts-state.is-installing, .petocharts-state.is-restoring_backup { background: #1e3a5f; color: #93c5fd; }
    .petocharts-bar { display: block; height: 4px; margin-top: 4px; background: #1f2937; border-radius: 2px; overflow: hidden; max-width: 110px; }
    .petocharts-bar > span { display: block; height: 100%; background: #3b82f6; }
    .petocharts-bar.is-warn > span { background: #f59e0b; }
    .petocharts-bar.is-hot > span { background: #ef4444; }
    .petocharts-check { width: 36px; text-align: center; vertical-align: middle !important; }
    .petocharts-power { white-space: nowrap; vertical-align: middle !important; }
    .petocharts-power .btn { margin: 0 2px; padding: 2px 7px; }
    .petocharts-power .btn[disabled] { opacity: .45; }
    .petocharts-bulk {
      position: sticky; bottom: 12px; z-index: 50; margin: 12px 0 0;
      display: none; align-items: center; gap: 10px; flex-wrap: wrap;
      padding: 10px 14px; border-radius: 6px;
      background: #111827; border: 1px solid #334155;
      box-shadow: 0 10px 30px rgba(0,0,0,.35);
    }
    .petocharts-bulk.is-open { display: flex; }
    .petocharts-bulk__count { font-weight: 700; color: #e2e8f0; margin-right: 4px; }
    .petocharts-bulk .btn { margin-right: 4px; }
    .petocharts-toast {
      position: fixed; right: 18px; bottom: 18px; z-index: 3000;
      max-width: 420px; padding: 12px 14px; border-radius: 6px;
      background: #0f172a; border: 1px solid #334155; color: #e2e8f0;
      box-shadow: 0 12px 40px rgba(0,0,0,.45); display: none; font-size: 13px; line-height: 1.45;
    }
    .petocharts-toast.is-open { display: block; }
    .petocharts-toast.is-ok { border-color: #166534; }
    .petocharts-toast.is-fail { border-color: #7f1d1d; }
  </style>
  <div id="petocharts-toast" class="petocharts-toast"></div>
  <script>
  (function () {
    var path = location.pathname.replace(/\/+$/, '');
    if (path !== '/admin/servers') return;

    var table = document.querySelector('.content table.table');
    if (!table || table.dataset.petochartsReady) return;
    table.dataset.petochartsReady = '1';

    var headRow = table.querySelector('tr');
    if (!headRow) return;

    function csrf() {
      var m = document.querySelector('meta[name="csrf-token"], meta[name="_token"]');
      return m ? m.getAttribute('content') : '';
    }

    // --- columns: checkbox | ...stats... | power ---
    var checkTh = document.createElement('th');
    checkTh.className = 'petocharts-check';
    checkTh.innerHTML = '<input type="checkbox" id="petocharts-check-all" title="Select all">';
    headRow.insertBefore(checkTh, headRow.firstChild);

    var statHeaders = ['State', 'CPU', 'RAM', 'Disk', 'Net ↓/↑', 'Uptime'];
    var powerTh = document.createElement('th');
    powerTh.textContent = 'Power';
    var wrenchTh = headRow.children[headRow.children.length - 1];
    statHeaders.forEach(function (label) {
      var th = document.createElement('th');
      th.className = 'petocharts-th';
      th.textContent = label;
      headRow.insertBefore(th, wrenchTh);
    });
    headRow.insertBefore(powerTh, wrenchTh);

    var idByShort = {};

    Array.prototype.forEach.call(table.querySelectorAll('tr[data-server]'), function (tr) {
      var short = tr.getAttribute('data-server');
      var nameLink = tr.querySelector('td a[href*="/admin/servers/view/"]');
      var sid = null;
      if (nameLink) {
        var m = nameLink.getAttribute('href').match(/\/admin\/servers\/view\/(\d+)/);
        if (m) sid = Number(m[1]);
      }
      if (sid) idByShort[short] = sid;
      tr.dataset.serverId = sid ? String(sid) : '';

      var checkTd = document.createElement('td');
      checkTd.className = 'petocharts-check';
      checkTd.innerHTML = '<input type="checkbox" class="petocharts-row-check" data-id="' + (sid || '') + '">';
      tr.insertBefore(checkTd, tr.firstChild);

      var wrenchTd = tr.children[tr.children.length - 1];
      for (var i = 0; i < statHeaders.length; i++) {
        var td = document.createElement('td');
        td.className = 'petocharts-col petocharts-c' + i;
        td.innerHTML = '<span class="pc-muted">…</span>';
        tr.insertBefore(td, wrenchTd);
      }
      var powerTd = document.createElement('td');
      powerTd.className = 'petocharts-power';
      powerTd.innerHTML =
        '<button type="button" class="btn btn-xs btn-success" data-power="start" title="Start">▶</button>' +
        '<button type="button" class="btn btn-xs btn-warning" data-power="restart" title="Restart">↻</button>' +
        '<button type="button" class="btn btn-xs btn-danger" data-power="stop" title="Stop">■</button>';
      tr.insertBefore(powerTd, wrenchTd);
    });

    // Bulk bar
    var box = table.closest('.box');
    var bulk = document.createElement('div');
    bulk.id = 'petocharts-bulk';
    bulk.className = 'petocharts-bulk';
    bulk.innerHTML =
      '<span class="petocharts-bulk__count" id="petocharts-bulk-count">0 selected</span>' +
      '<button type="button" class="btn btn-sm btn-success" data-bulk="start">Start</button>' +
      '<button type="button" class="btn btn-sm btn-warning" data-bulk="restart">Restart</button>' +
      '<button type="button" class="btn btn-sm btn-danger" data-bulk="stop">Stop</button>' +
      '<button type="button" class="btn btn-sm btn-default" data-bulk="kill">Kill</button>' +
      '<button type="button" class="btn btn-sm btn-default" id="petocharts-bulk-clear">Clear</button>';
    if (box && box.parentNode) box.parentNode.insertBefore(bulk, box.nextSibling);

    function selectedIds() {
      return Array.prototype.map.call(
        table.querySelectorAll('.petocharts-row-check:checked'),
        function (el) { return Number(el.getAttribute('data-id')); }
      ).filter(Boolean);
    }
    function updateBulk() {
      var ids = selectedIds();
      bulk.classList.toggle('is-open', ids.length > 0);
      document.getElementById('petocharts-bulk-count').textContent = ids.length + ' selected';
      var all = table.querySelectorAll('.petocharts-row-check');
      var checked = table.querySelectorAll('.petocharts-row-check:checked');
      var master = document.getElementById('petocharts-check-all');
      if (master) {
        master.checked = all.length > 0 && checked.length === all.length;
        master.indeterminate = checked.length > 0 && checked.length < all.length;
      }
    }

    document.getElementById('petocharts-check-all').addEventListener('change', function (e) {
      Array.prototype.forEach.call(table.querySelectorAll('.petocharts-row-check'), function (cb) {
        cb.checked = e.target.checked;
      });
      updateBulk();
    });
    table.addEventListener('change', function (e) {
      if (e.target && e.target.classList.contains('petocharts-row-check')) updateBulk();
    });
    document.getElementById('petocharts-bulk-clear').addEventListener('click', function () {
      Array.prototype.forEach.call(table.querySelectorAll('.petocharts-row-check'), function (cb) { cb.checked = false; });
      updateBulk();
    });

    var toastEl = document.getElementById('petocharts-toast');
    var toastTimer = null;
    function toast(msg, kind) {
      toastEl.textContent = msg;
      toastEl.className = 'petocharts-toast is-open' + (kind ? ' is-' + kind : '');
      clearTimeout(toastTimer);
      toastTimer = setTimeout(function () { toastEl.classList.remove('is-open'); }, 5000);
    }

    function setPowerBusy(tr, busy) {
      Array.prototype.forEach.call(tr.querySelectorAll('[data-power]'), function (btn) {
        btn.disabled = !!busy;
      });
    }

    function power(ids, action) {
      if (!ids.length) return Promise.resolve();
      toast(action.toUpperCase() + ' → ' + ids.length + ' server…', '');
      return fetch('/extensions/petocharts/power', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf(),
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ action: action, ids: ids })
      }).then(function (r) {
        return r.json().then(function (j) { return { ok: r.ok, body: j }; });
      }).then(function (res) {
        if (!res.ok) {
          toast('Hata: ' + (res.body.error || JSON.stringify(res.body)), 'fail');
          return;
        }
        var msg = res.body.ok_count + ' OK';
        if (res.body.failed_count) msg += ', ' + res.body.failed_count + ' FAIL';
        toast(action + ': ' + msg, res.body.failed_count ? 'fail' : 'ok');
        setTimeout(refresh, 1200);
        setTimeout(refresh, 3500);
      }).catch(function (err) {
        toast('İstek hatası: ' + err, 'fail');
      });
    }

    table.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-power]');
      if (!btn) return;
      var tr = btn.closest('tr[data-server]');
      if (!tr) return;
      var id = Number(tr.dataset.serverId);
      if (!id) { toast('Server id bulunamadı', 'fail'); return; }
      setPowerBusy(tr, true);
      power([id], btn.getAttribute('data-power')).finally(function () {
        setPowerBusy(tr, false);
      });
    });

    bulk.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-bulk]');
      if (!btn) return;
      var action = btn.getAttribute('data-bulk');
      var ids = selectedIds();
      if (!ids.length) return;
      if (action === 'kill' || action === 'stop') {
        if (!confirm(ids.length + ' sunucuya "' + action + '" uygulansın mı?')) return;
      }
      Array.prototype.forEach.call(bulk.querySelectorAll('[data-bulk]'), function (b) { b.disabled = true; });
      power(ids, action).finally(function () {
        Array.prototype.forEach.call(bulk.querySelectorAll('[data-bulk]'), function (b) { b.disabled = false; });
      });
    });

    function fmtBytes(n) {
      n = Number(n) || 0;
      if (n < 1024) return n + ' B';
      var u = ['KiB', 'MiB', 'GiB', 'TiB'];
      var i = -1;
      do { n /= 1024; i++; } while (n >= 1024 && i < u.length - 1);
      return n.toFixed(n >= 10 || i === 0 ? 0 : 1) + ' ' + u[i];
    }
    function fmtUptime(ms) {
      ms = Number(ms) || 0;
      if (ms <= 0) return '—';
      var s = Math.floor(ms / 1000);
      var h = Math.floor(s / 3600);
      var m = Math.floor((s % 3600) / 60);
      var sec = s % 60;
      if (h > 0) return h + 'h ' + m + 'm';
      if (m > 0) return m + 'm ' + sec + 's';
      return sec + 's';
    }
    function pct(used, limit) {
      if (!limit) return 0;
      return Math.max(0, Math.min(100, (used / limit) * 100));
    }
    function bar(p) {
      var cls = p >= 90 ? ' is-hot' : (p >= 70 ? ' is-warn' : '');
      return '<span class="petocharts-bar' + cls + '"><span style="width:' + p.toFixed(0) + '%"></span></span>';
    }
    function stateClass(state) {
      var s = String(state || 'offline').toLowerCase();
      return 'is-' + s.replace(/[^a-z0-9_]/g, '');
    }

    function render(map) {
      Array.prototype.forEach.call(table.querySelectorAll('tr[data-server]'), function (tr) {
        var id = tr.getAttribute('data-server');
        var s = map[id];
        var cells = tr.querySelectorAll('.petocharts-col');
        if (!cells.length) return;
        if (!s) {
          cells[0].innerHTML = '<span class="petocharts-state is-unknown">n/a</span>';
          return;
        }
        if (s.id && !tr.dataset.serverId) {
          tr.dataset.serverId = String(s.id);
          var cb = tr.querySelector('.petocharts-row-check');
          if (cb) cb.setAttribute('data-id', String(s.id));
        }
        var memPct = pct(s.memory_bytes, s.memory_limit_bytes || (s.limits.memory_mib * 1024 * 1024));
        var diskLimit = (s.limits.disk_mib || 0) * 1024 * 1024;
        var diskPct = pct(s.disk_bytes, diskLimit);
        var cpuLimit = s.limits.cpu || 100;
        var cpuPct = pct(s.cpu_absolute, cpuLimit);

        cells[0].innerHTML = '<span class="petocharts-state ' + stateClass(s.state) + '">' + (s.state || 'offline') + '</span>';
        cells[1].innerHTML = s.cpu_absolute.toFixed(1) + '% <span class="pc-muted">/ ' + cpuLimit + '%</span>' + bar(cpuPct);
        cells[2].innerHTML = fmtBytes(s.memory_bytes) + ' <span class="pc-muted">/ ' + fmtBytes(s.memory_limit_bytes) + '</span>' + bar(memPct);
        cells[3].innerHTML = fmtBytes(s.disk_bytes) + ' <span class="pc-muted">/ ' + fmtBytes(diskLimit) + '</span>' + bar(diskPct);
        cells[4].innerHTML = fmtBytes(s.network_rx_bytes) + ' <span class="pc-muted">/</span> ' + fmtBytes(s.network_tx_bytes);
        cells[5].textContent = fmtUptime(s.uptime_ms);
      });
    }

    function refresh() {
      fetch('/extensions/petocharts/servers', {
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json' }
      }).then(function (r) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
      }).then(function (data) {
        var map = {};
        (data.servers || []).forEach(function (s) { map[s.uuidShort] = s; });
        render(map);
      }).catch(function (err) {
        console.error('[petocharts servers]', err);
      });
    }

    refresh();
    setInterval(refresh, 5000);
  })();
  </script>
@endif
