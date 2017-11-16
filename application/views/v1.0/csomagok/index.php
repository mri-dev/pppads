<h1>Szolgáltatás csomagok<br><span>Válasszon az alábbi csomagjaink közül, amelyik Önnek a legmegfelelőbb.</span></h1>

<div class="packages-list" ng-controller="Packages" ng-init="init()">
  <div class="list-wrapper" ng-show="loaded">
    <div class="package header-col">
      <div class="p-wrapper">
        <div class="offed"></div>
        <div class="name"></div>
        <div class="price" ng-hide="package.isdemo">
          <div class="label">
            Szolgáltatás csomag nettó havi díja
            <md-tooltip md-direction="top">
              A csomag nettó havi díja. Havi rendszerességgel fizetendő díj.
            </md-tooltip>
          </div>
        </div>
        <div class="uniquevisit">
          <div class="label">
            Havi egyedi látogatók száma
            <md-tooltip md-direction="top">
              Ennyi egyedi oldallátogatást enged a csomag.
            </md-tooltip>
          </div>
        </div>
        <div class="view">
          <div class="label">
            Megjelenések száma
            <md-tooltip md-direction="top">
              A csomag havi díj tartalmaz egy megjelenési keretet, melyet felhasználhat.
            </md-tooltip>
          </div>
        </div>
        <div class="viewprice">
          <div class="label">
            Extra megjelenések nettó költsége
            <md-tooltip md-direction="top">
              A csomagban foglalt megjelenésen felüli megjelenés költsége, mely a rendelkezésre álló egyenlegből kerül levonásra.
            </md-tooltip>
          </div>
        </div>
        <div class="domain">
          <div class="label">
            Domain-ek száma
            <md-tooltip md-direction="top">
              Ennyi domainen használhatja a szoftvert.
            </md-tooltip>
          </div>
        </div>
        <div class="">
          <div class="label">
            Hirdetések száma
          </div>
        </div>
        <div class="">
          <div class="label">
            Kampányok száma
          </div>
        </div>
        <div class="subsc">
          <div class="label">
            Feliratkozók gyűjtése
          </div>
        </div>
        <div class="subscgroup">
          <div class="label">
            Feliratkozók csoportosítása
            <md-tooltip md-direction="top">
              A feliratkozókat egyedileg létrehozott csoportokba lehet gyűjteni és csoportosítani. A kampány során ki lehet választani, hogy mely csoportba gyűjtse a feliratkozókat.
            </md-tooltip>
          </div>
        </div>
        <div class="subsc">
          <div class="label">
            Statisztika
          </div>
        </div>
        <div class="offed"></div>
        <div class="order offed"></div>
      </div>
    </div>
    <div class="package" ng-class="((package.isdemo)?'demopackage':'')+((current_package && current_package.ID==package.ID)?' current-package':'')" ng-repeat="package in packages">
      <div class="p-wrapper">
        <div class="promotext" style="background-color:{{package.promotextcolor}};" ng-class="(!package.promotext ? 'offed' : '')">
          <div ng-show="package.promotext">{{package.promotext}}</div>
        </div>
        <div class="name">
          {{package.name}}
          <div class="subtitle" ng-show="package.isdemo">
            Korlátlan funkció 14 napig.
          </div>
        </div>
        <div class="price">
          {{package.price}} <span class="it">Ft</span>
        </div>
        <div class="uniquevisit">
          korlátlan
        </div>
        <div class="view">
          {{(package.freeviews == -1) ? 'korlátlan' : package.freeviews}}
          <span class="it" ng-show="package.freeviews!=-1">db</span>
        </div>
        <div class="viewprice">
          {{package.viewprice}} <span class="it">Ft</span>
        </div>
        <div class="domain">
          {{(package.domains == -1) ? 'korlátlan' : package.domains}}
          <span class="it" ng-show="package.domains!=-1">db</span>
        </div>
        <div class="">
          korlátlan
        </div>
        <div class="">
          korlátlan
        </div>
        <div class="subsc">
          <i class="fa fa-check" ng-show="package.subs"></i>
          <i class="fa fa-times" ng-show="!package.subs"></i>
        </div>
        <div class="subscgroup">
          <i class="fa fa-check" ng-show="package.subsgroups"></i>
          <i class="fa fa-times" ng-show="!package.subsgroups"></i>
        </div>
        <div class="subsc">
          <i class="fa fa-check"></i>
        </div>
        <div class="allfunc">Összes funkció</div>
        <div class="order" style="border-bottom-color:{{package.colorcode}};">
          <button ng-click="changePackage(package.ID, $event)" ng-show="current_package && current_package.ID!=package.ID">Aktiválás</button>
          <div ng-show="current_package && current_package.ID==package.ID" class="already-active">
            <i class="fa fa-check-circle-o"></i> Jelenlegi csomagja.
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
