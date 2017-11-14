<md-dialog aria-label="confirm_package_order">
  <md-toolbar class="package-dialog-style">
    <div class="md-toolbar-tools">
      <h2><strong>{{csomag.name}}</strong> csomag aktiválása</h2>
      <span flex></span>
      <md-button class="md-icon-button" ng-click="closeDialog()">
        <md-icon md-svg-src="<?=IMG?>icons/ic_close_white_24px.svg" aria-label="Close dialog"></md-icon>
      </md-button>
    </div>
  </md-toolbar>
  <md-dialog-content>
    <div class="md-dialog-content">
      A szolgáltatási csomag váltása során 1 hónapos időszakra fizet elő.<br><br>
      <h4>Csomag paraméterei:</h4>
      <div class="dialog-table-info">
        <div layout="row">
          <div flex><strong>Domainek száma:</strong></div>
          <div flex>{{(csomag.domains == -1) ? 'korlátlan' : csomag.domains}}
          <span class="it" ng-show="csomag.domains!=-1">db</span></div>
        </div>
        <div layout="row">
          <div flex><strong>Csomagban foglalt megjelenések:</strong></div>
          <div flex>{{(csomag.freeviews == -1) ? 'korlátlan' : csomag.freeviews}}
          <span class="it" ng-show="csomag.freeviews!=-1">db</span></div>
        </div>
        <div layout="row">
          <div flex><strong>Extra megjelenések költsége:</strong></div>
          <div flex>{{csomag.viewprice}}<span class="it"> Ft / megjelenés</span></div>
        </div>
      </div>
      <div class="">
        A szolgáltatás díja a(z) <strong>{{csomag.name}}</strong> csomag esetén, havonta <strong>{{csomag.price}} Ft + ÁFA</strong>.
      </div>
      <div class="not-enought-price" ng-show="(csomag.raw_price > me.data.cash_net)?true:false">
        Nincs elég egyenlege a csomag aktiválásához!
      </div>
    </div>
  </md-dialog-content>
  <md-dialog-actions layout="row">
      <md-button ng-click="closeDialog()" class="md-button">
        Mégse
      </md-button>
      <span flex></span>
      <md-button ng-hide="(csomag.raw_price > me.data.cash_net)?true:false" ng-click="answer('useful')" class="md-button md-primary package-dialog-style" md-autofocus>
        Aktiválom a csomagot!
      </md-button>
      <md-button ng-show="(csomag.raw_price > me.data.cash_net)?true:false" ng-click="topup()" class="md-button topup-button" md-autofocus>
        Feltöltöm az egyenlegem <md-icon md-svg-src="<?=IMG?>icons/ic_navigate_next_white_24px.svg"></md-icon>
      </md-button>
    </md-dialog-actions>
</md-dialog>
