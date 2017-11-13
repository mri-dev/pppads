<h1>Szolgáltatás csomagok<br><span>Válasszon az alábbi csomagjaink közül, amelyik Önnek a legmegfelelőbb.</span></h1>

<div class="packages-list" ng-controller="Packages" ng-init="init()">
  <div class="list-wrapper" ng-show="loaded">
    <div class="package" ng-class="(package.isdemo)?'demopackage':''" ng-repeat="package in packages">
      <div class="p-wrapper">
        <div class="name">
          {{package.name}}
        </div>
        <div class="domain">
          <div class="label">
            Domainek száma
            <md-tooltip md-direction="top">
              Ennyi domainen használhatja a szoftvert.
            </md-tooltip>
          </div>
          {{(package.domains == -1) ? 'korlátlan' : package.domains}}
          <span class="it" ng-show="package.domains!=-1">db</span>
        </div>
        <div class="view">
          <div class="label">
            Csomagban foglalt megjelenések
            <md-tooltip md-direction="top">
              A csomag havi díj tartalmaz egy megjelenési keretet, melyet felhasználhat.
            </md-tooltip>
          </div>
          {{(package.freeviews == -1) ? 'korlátlan' : package.freeviews}}
          <span class="it" ng-show="package.freeviews!=-1">db</span>
        </div>
        <div class="viewprice">
          <div class="label">
            Extra megjelenések költsége
            <md-tooltip md-direction="top">
              A csomagban foglalt megjelenésen felüli megjelenés költsége, mely a rendelkezésre álló egyenlegből kerül levonásra.
            </md-tooltip>
          </div>
          {{package.viewprice}}<br>
          <span class="it">Ft / megjelenés</span>
        </div>
        <div class="price" ng-hide="package.isdemo">
          <div class="label">
            Csomag havi díja
            <md-tooltip md-direction="top">
              A csomag nettó havi díja. Havi rendszerességgel fizetendő díj.
            </md-tooltip>
          </div>
          {{package.price}} <span class="it">Ft</span>
        </div>
        <div class="order">
          <button ng-show="current_package && current_package.ID!=package.ID">Aktiválás</button>
          <div ng-show="current_package && current_package.ID==package.ID" class="already-active">
            <i class="fa fa-check-circle-o"></i> Aktív csomag.
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="loading-screen" ng-hide="loaded">
    <i class="fa fa-spin fa-spinner"></i>
    <h3>Csomagok betöltése folyamatban.</h3>
    Kis türelmét kérjük. Az aktuális csomag betöltése folyamatban.
  </div>
</div>
<div class="price-vat-info" ng-show="loaded">
  Az oldalon feltüntetett árak nettó árak, nem tartalmazzák az ÁFA-t!
</div>
