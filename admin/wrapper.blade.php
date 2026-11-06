@php
  $isAdminIndex = request()->routeIs('admin.index');
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
                ticks: {
                  maxTicksLimit: 4,
                  color: '#9ca3af',
                },
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
