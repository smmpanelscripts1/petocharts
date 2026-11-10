<div class="row">
  <div class="col-xs-12">
    <div class="box box-primary">
      <div class="box-header with-border">
        <h3 class="box-title">Peto Dashboard Charts</h3>
      </div>
      <div class="box-body">
        <p>
          Bu eklenti <code>/admin</code> ana sayfasına dört özet kart ve dört line chart ekler.
          Kartlar sunucu sayısı, disk doluluğu, RAM ve CPU değerlerini gösterir.
        </p>
        <p>
          Ayrıca <a href="{{ route('admin.servers') }}">Admin → Servers</a> tablosuna
          canlı <strong>State / CPU / RAM / Disk / Net / Uptime</strong> sütunları,
          satır içi <strong>Start / Restart / Stop</strong> ve checkbox ile
          <strong>toplu power</strong> (yüzlerce sunucu) ekler.
        </p>
        <p class="text-muted" style="margin-bottom:0">
          Disk, RAM ve CPU kartları Wings’ten gelen anlık kullanımı node kapasitesiyle karşılaştırır.
        </p>
      </div>
    </div>
  </div>
</div>
