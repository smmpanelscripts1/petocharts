@php
  $isAdminIndex = request()->routeIs('admin.index');
@endphp

@if($isAdminIndex)
  <div id="petocharts-admin-root" class="petocharts-admin-grid" style="display:none;">
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

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script>
    (function () {
      const root = document.getElementById('petocharts-admin-root');
      const content = document.querySelector('.content');
      if (!root || !content || typeof Chart === 'undefined') return;

      root.style.display = 'grid';
      content.insertBefore(root, content.firstChild);

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
