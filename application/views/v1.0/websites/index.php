<div class="row">
  <div class="col-md-10">
    <h1>Weboldalaim <span><strong><?php echo count($this->mydomains); ?> db</strong> domain regisztrálva.</span>
    <div class="subtitle">Itt regisztrálhatja azokat a weboldalait, ahol használni szeretné a popup hirdetőt.</div>
    </h1>
  </div>
  <div class="col-md-2 right">
    <a href="/websites/?mode=create" class="btn btn-primary">Új weboldal</a>
  </div>
</div>

<div class="domains">
  <?php foreach ( $this->mydomains as $domain ): ?>
  <div class="domain">
    <div class="con">
      <div class="row">
        <div class="col-md-11">
          <div class="title">
            <?php echo $domain['domain']; ?>
          </div>
        </div>
        <div class="col-md-1 center">
          <div class="status <?=($domain['active']==1)?'active':'inactive'?>"><?=($domain['active']==1)?'Aktív':'Inaktív'?></div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
