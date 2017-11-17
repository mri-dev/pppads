<div class="row">
  <div class="col-md-12">
    <h1>Egyenlegfeltöltés</h1>
  </div>
</div>

<div class="egyenleg-controller" ng-controller="Egyenleg" ng-init="init()">
  <div class="content-holder ">
    <div class="order-session">
      <div layout="row" layout-md="column">
        <div flex="50" class="topop-boxes">
          <div class="topup-box by-cash">
            <div class="header">
              Egyenleg feltöltés &mdash; Összeg alapján
            </div>
            <div class="ct-wrapper">
              <md-slider-container>
                <div flex>1.000</div>
                <md-slider flex min="1000" step="1000" max="100000" ng-model="selector.price" ng-change="selectPrice()" aria-label="red" id="cash-slider" class="md-warn">
                </md-slider>
                <div flex>100.000</div>
              </md-slider-container>
            </div>
          </div>
          <div class="topup-box-divider"></div>
          <div class="topup-box by-view">
            <div class="header">
              Egyenleg feltöltés &mdash; Megjelenések alapján
            </div>
            <div class="ct-wrapper">
              <md-slider-container>
                <md-slider flex min="0" step="1" max="100000" ng-model="selector.views" ng-change="selectView()" aria-label="red" id="view-slider" class="md-primary">
                </md-slider>
              </md-slider-container>
            </div>
          </div>
        </div>
        <div flex>
          <div layout="row">
            <div flex="33" class="package-box">
              <div class="header">
                Jelenlegi csomagja alapján
              </div>
              <div class="ct-wrapper">
                <div class="pack-acticity">
                  A csomagja még <br>
                  <strong>15</strong><br>
                  napig érvényes.
                </div>
                <div class="divider"></div>
                <div class="free-cash-of">
                  Megjelenés nettó költsége
                  <div class="cash-price">
                    {{viewprice}} Ft
                  </div>
                </div>
                <div class="divider"></div>
                <div class="ord-views">
                  Az egyenlegért vásárolható megjelenések száma
                  <div class="view">
                    {{selector.views}} db
                  </div>
                </div>
              </div>
              <div class="footer">

              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="order-cash" ng-show="selector.price!=0">
        <h2>Kiválasztott egyenleg megvásárlása:</h2>
        <div class="cash">
          <span class="selected"><span class="net">{{selector.price}} Ft <span class="small">+ ÁFA</span></span> = <strong>{{selector.pricepaid}} Ft</strong></span>
        </div>
        <div class="accept">
          <md-content flex layout-padding>
            <h3>Általános Üzletszabályzat</h3>
            <p>Lorem ipsum dolor sit amet, ne quod novum mei. Sea omnium invenire mediocrem at, in lobortis conclusionemque nam. Ne deleniti appetere reprimique pro, inani labitur disputationi te sed. At vix sale omnesque, id pro labitur reformidans accommodare, cum labores honestatis eu. Nec quem lucilius in, eam praesent reformidans no. Sed laudem aliquam ne.</p>

            <p>
        Facete delenit argumentum cum at. Pro rebum nostrum contentiones ad. Mel exerci tritani maiorum at, mea te audire phaedrum, mel et nibh aliquam. Malis causae equidem vel eu. Noster melius vis ea, duis alterum oporteat ea sea. Per cu vide munere fierent.
            </p>

            <p>
        Ad sea dolor accusata consequuntur. Sit facete convenire reprehendunt et. Usu cu nonumy dissentiet, mei choro omnes fuisset ad. Te qui docendi accusam efficiantur, doming noster prodesset eam ei. In vel posse movet, ut convenire referrentur eum, ceteros singulis intellegam eu sit.
            </p>

            <p>
        Sit saepe quaestio reprimique id, duo no congue nominati, cum id nobis facilisi. No est laoreet dissentias, idque consectetuer eam id. Clita possim assueverit cu his, solum virtute recteque et cum. Vel cu luptatum signiferumque, mel eu brute nostro senserit. Blandit euripidis consequat ex mei, atqui torquatos id cum, meliore luptatum ut usu. Cu zril perpetua gubergren pri. Accusamus rationibus instructior ei pro, eu nullam principes qui, reque justo omnes et quo.
            </p>
          </md-content>
        </div>
        <div class="termaccept">
          <md-input-container class="md-block">
            <md-checkbox ng-model="accepted_terms" required>
              Elfogadom az Általános Üzletszabályzatot.
            </md-checkbox>
          </md-input-container>
        </div>
        <div class="order-button" ng-show="!orderprogress">
          <md-button ng-show="accepted_terms" ng-click="orderCash()" class="md-raised">Egyenleg megrendelése</md-button>
        </div>
      </div>
    </div>
    <div class="transaction-list page-container">
      <h2>Tranzakciók</h2>
      <div class="subtitle">
        Ebben a táblázatban megtalálja az összes egyenlegfeltöltés tranzakciót.
      </div>
      <div class="list">
        <div class="table-stucture" ng-class="(transactions.length == 0 && transload)?'loading':''">
          <div class="load-overlay" ng-show="transload">
            <div class="cont">
              Adatok frissítése folyamatban... <i class="fa fa-spin fa-spinner"></i>
            </div>
          </div>
          <div layout="row" layout-align="center center" class="tbl-row tbl-header">
            <div flex class="left">Sorszám / Azonosító kulcs</div>
            <div flex="10" class="center">Feltöltés összege</div>
            <div flex="15" class="center">Állapot</div>
            <div flex="10" class="center">Tranzakció ideje</div>
            <div flex="10" class="center">Jóváírás ideje</div>
            <div flex="20">Hivatkozások</div>
          </div>
          <div layout="row" class="tbl-row" ng-class="(transload)?'blured':''" ng-repeat="t in transactions">
            <div flex>
              <div class="azon">
                {{t.customid}}
              </div>
              <div class="hash">
                {{t.hashkey}}
              </div>
            </div>
            <div flex="10" class="cash center"><strong>{{t.br_price}} Ft</strong><br> ({{t.net_price}} Ft + ÁFA)</div>
            <div flex="15" class="stat center">
              <span class="stat-inprogress" ng-show="t.status==0">Folyamatban</span>
              <span class="stat-done" ng-show="t.status==1">Teljesítve</span>
              <span class="stat-deleted" ng-show="t.status==-1">Törölve</span>
            </div>
            <div flex="10" class="center">{{t.datetime}}</div>
            <div flex="10" class="center">{{t.acceptdate}}</div>
            <div flex="20"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
