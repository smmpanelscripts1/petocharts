<div class="row">
  <div class="col-xs-12">
    <div class="box box-primary">
      <div class="box-header with-border">
        <h3 class="box-title">Kaynak kullanım raporu</h3>
        <div class="box-tools pull-right">
          <div class="btn-group" id="petocharts-range">
            <button type="button" class="btn btn-sm btn-default" data-range="24h">Son 24 saat</button>
            <button type="button" class="btn btn-sm btn-primary" data-range="7d">Son 7 gün</button>
          </div>
          <a id="petocharts-csv" class="btn btn-sm btn-default" href="/extensions/petocharts/history.csv?range=7d">CSV indir</a>
        </div>
      </div>
      <div class="box-body">
        <p class="text-muted" style="margin-top:0">
          Overview açıkken Wings anlık kullanımı dakikada bir kaydedilir. Kayıtlar 7 gün tutulur.
          Tepeler, ortalamalar ve günlük özet, kota artışı veya sunucu taşıması için referanstır.
        </p>
        <p id="petocharts-report-empty" class="text-muted" style="display:none">
          Henüz yeterli örnek yok. Admin → Overview sayfasını bir süre açık tut; ilk dakikadan sonra grafik dolmaya başlar.
        </p>
        <div class="row" id="petocharts-kpis">
          <div class="col-sm-4">
            <div class="petocharts-kpi is-ram">
              <h4>RAM</h4>
              <p>Ort. <strong id="pc-ram-avg">—</strong></p>
              <p>Tepe <strong id="pc-ram-max">—</strong> <span class="text-muted" id="pc-ram-peak"></span></p>
              <p>Dip <strong id="pc-ram-min">—</strong></p>
            </div>
          </div>
          <div class="col-sm-4">
            <div class="petocharts-kpi is-cpu">
              <h4>CPU</h4>
              <p>Ort. <strong id="pc-cpu-avg">—</strong></p>
              <p>Tepe <strong id="pc-cpu-max">—</strong> <span class="text-muted" id="pc-cpu-peak"></span></p>
              <p>Dip <strong id="pc-cpu-min">—</strong></p>
            </div>
          </div>
          <div class="col-sm-4">
            <div class="petocharts-kpi is-disk">
              <h4>Disk</h4>
              <p>Ort. <strong id="pc-disk-avg">—</strong></p>
              <p>Tepe <strong id="pc-disk-max">—</strong> <span class="text-muted" id="pc-disk-peak"></span></p>
              <p>Dip <strong id="pc-disk-min">—</strong></p>
            </div>
          </div>
        </div>
        <div class="petocharts-report-grid">
          <div class="petocharts-admin-card is-ram">
            <div class="petocharts-admin-card__header"><h3 class="petocharts-admin-card__title">RAM (anlık vs atanan)</h3></div>
            <div class="petocharts-admin-card__body is-report"><canvas id="pc-chart-ram"></canvas></div>
          </div>
          <div class="petocharts-admin-card is-cpu">
            <div class="petocharts-admin-card__header"><h3 class="petocharts-admin-card__title">CPU %</h3></div>
            <div class="petocharts-admin-card__body is-report"><canvas id="pc-chart-cpu"></canvas></div>
          </div>
          <div class="petocharts-admin-card is-disk">
            <div class="petocharts-admin-card__header"><h3 class="petocharts-admin-card__title">Disk (anlık vs atanan)</h3></div>
            <div class="petocharts-admin-card__body is-report"><canvas id="pc-chart-disk"></canvas></div>
          </div>
        </div>
        <h4 class="petocharts-daily-title">Günlük özet</h4>
        <div class="table-responsive petocharts-table-wrap">
          <table class="petocharts-table" id="pc-daily">
            <thead>
              <tr>
                <th>Gün</th>
                <th>Örnek</th>
                <th>RAM ort / tepe</th>
                <th>CPU ort / tepe</th>
                <th>Disk ort / tepe</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
  const amount = (mib) => {
    const value = Number(mib) || 0;
    if (Math.abs(value) >= 1024) {
      const gb = value / 1024;
      const digits = gb >= 10 ? 0 : 1;
      return gb.toFixed(digits).replace(/\.0$/, '') + ' GB';
    }
    return Math.round(value) + ' MiB';
  };
  const fmtCpu = (v) => Number(v || 0).toFixed(1).replace(/\.0$/, '') + '%';
  const fmtWhen = (ts) => {
    if (!ts) return '';
    const d = new Date(ts * 1000);
    return d.toLocaleString();
  };

  const makeChart = (id, colors) => {
    const ctx = document.getElementById(id).getContext('2d');
    return new Chart(ctx, {
      type: 'line',
      data: { labels: [], datasets: colors.map((c, i) => ({
        data: [],
        borderColor: c,
        backgroundColor: c + (i === 0 ? '33' : '00'),
        fill: i === 0,
        tension: 0.25,
        pointRadius: 0,
        borderWidth: i === 0 ? 2 : 1.5,
        borderDash: i === 0 ? [] : [5, 4],
      })) },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { display: false } },
        scales: {
          x: { ticks: { maxTicksLimit: 8, color: '#9ca3af', maxRotation: 0 }, grid: { display: false } },
          y: { beginAtZero: true, ticks: { maxTicksLimit: 5, color: '#9ca3af' }, grid: { color: 'rgba(148,163,184,.18)' } },
        },
      },
    });
  };

  const charts = {
    ram: makeChart('pc-chart-ram', ['#00c0ef', '#7dd3fc']),
    cpu: makeChart('pc-chart-cpu', ['#a78bfa']),
    disk: makeChart('pc-chart-disk', ['#00a65a', '#86efac']),
  };

  const fill = (chart, labels, series) => {
    chart.data.labels = labels;
    series.forEach((data, i) => { chart.data.datasets[i].data = data; });
    chart.update();
  };

  const setKpi = (key, summary, fmt) => {
    document.getElementById('pc-' + key + '-avg').textContent = fmt(summary.avg);
    document.getElementById('pc-' + key + '-max').textContent = fmt(summary.max);
    document.getElementById('pc-' + key + '-min').textContent = fmt(summary.min);
    document.getElementById('pc-' + key + '-peak').textContent = summary.peak_at ? ('@ ' + fmtWhen(summary.peak_at)) : '';
  };

  let range = '7d';
  const load = async () => {
    const res = await fetch('/extensions/petocharts/history?range=' + range, {
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
    });
    if (!res.ok) return;
    const data = await res.json();
    const empty = !data.count;
    document.getElementById('petocharts-report-empty').style.display = empty ? 'block' : 'none';
    document.getElementById('petocharts-csv').href = '/extensions/petocharts/history.csv?range=' + range;

    const series = data.series || [];
    const labels = series.map((row) => {
      const d = new Date(row.t * 1000);
      return range === '24h'
        ? d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
        : d.toLocaleString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    });
    fill(charts.ram, labels, [series.map((r) => r.ram), series.map((r) => r.ram_a)]);
    fill(charts.cpu, labels, [series.map((r) => r.cpu)]);
    fill(charts.disk, labels, [series.map((r) => r.disk), series.map((r) => r.disk_a)]);

    setKpi('ram', data.summary.ram || {}, amount);
    setKpi('cpu', data.summary.cpu || {}, fmtCpu);
    setKpi('disk', data.summary.disk || {}, amount);

    const tbody = document.querySelector('#pc-daily tbody');
    tbody.innerHTML = (data.daily || []).map((d) => (
      '<tr><td>' + d.day + '</td><td>' + d.samples + '</td><td>' +
      amount(d.ram_avg) + ' / ' + amount(d.ram_max) + '</td><td>' +
      fmtCpu(d.cpu_avg) + ' / ' + fmtCpu(d.cpu_max) + '</td><td>' +
      amount(d.disk_avg) + ' / ' + amount(d.disk_max) + '</td></tr>'
    )).join('') || '<tr><td colspan="5">Kayıt yok</td></tr>';
  };

  document.getElementById('petocharts-range').addEventListener('click', (e) => {
    const btn = e.target.closest('[data-range]');
    if (!btn) return;
    range = btn.getAttribute('data-range');
    document.querySelectorAll('#petocharts-range [data-range]').forEach((el) => {
      el.classList.toggle('btn-primary', el === btn);
      el.classList.toggle('btn-default', el !== btn);
    });
    load();
  });

  load();
})();
</script>
