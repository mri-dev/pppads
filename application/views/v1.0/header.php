<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html xmlns="http://www.w3.org/1999/html4"
      xmlns:og="http://ogp.me/ns#"
      xmlns:fb="http://www.facebook.com/2008/fbml" lang="hu-HU" ng-app="pilot">
<head>
	<title><?=$this->title?></title>
    <?=$this->addMeta('robots','index,folow')?>
    <?=$this->SEOSERVICE?>
   	<? $this->render('meta'); ?>
    <script type="text/javascript">
    	$(function(){
			var slideMenu 	= $('#content .slideMenu');
			var closeNum 	= slideMenu.width() - 58;
			var isSlideOut 	= getMenuState();
			var prePressed = false;
			$(document).keyup(function(e){
				var key = e.keyCode;
				if(key === 17){
					prePressed = false;
				}
			});
			$(document).keydown(function(e){
				var key = e.keyCode;
				var keyUrl = new Array();
					keyUrl[49] = '/'; keyUrl[97] = '/';
					keyUrl[50] = '/termekek'; keyUrl[98] = '/termekek';
					keyUrl[51] = '/reklamfal'; keyUrl[99] = '/reklamfal';
					keyUrl[52] = '/menu'; keyUrl[100] = '/menu';
					keyUrl[53] = '/oldalak'; keyUrl[101] = '/oldalak';
					keyUrl[54] = '/kategoriak'; keyUrl[102] = '/kategoriak';
					keyUrl[55] = '/markak'; keyUrl[103] = '/markak';
				if(key === 17){
					prePressed = true;
				}
				if(typeof keyUrl[key] !== 'undefined'){
					if(prePressed){
						//document.location.href=keyUrl[key];
					}
				}
			});

			if(isSlideOut){
				slideMenu.css({
					'left' : '0px'
				});
        slideMenu.removeClass('closed');
				$('.ct').css({
					'paddingLeft' : '220px'
				});
			}else{
				slideMenu.css({
					'left' : '-'+closeNum+'px'
				});
        slideMenu.addClass('closed');
				$('.ct').css({
					'paddingLeft' : '75px'
				});
			}

			$('.slideMenuToggle').click(function(){
				if(isSlideOut){
					isSlideOut = false;
					slideMenu.animate({
						'left' : '-'+closeNum+'px'

					},200);
          slideMenu.addClass('closed');
					$('.ct').animate({
						'paddingLeft' : '75px'
					},200);
					saveState('closed');
				}else{

					isSlideOut = true;
					slideMenu.animate({
						'left' : '0px'
					},200);
          slideMenu.removeClass('closed');
					$('.ct').animate({
						'paddingLeft' : '220px'
					},200);
					saveState('opened');
				}
			});
		})

		function saveState(state){
			if(typeof(Storage) !== "undefined") {
				if(state == 'opened'){
					localStorage.setItem("slideMenuOpened", "1");
				}else if(state == 'closed'){
					localStorage.setItem("slideMenuOpened", "0");
				}
			}
		}

		function getMenuState(){
			var state =  localStorage.getItem("slideMenuOpened");

			if(typeof(state) === null){
				return false;
			}else{
				if(state == "1") return true; else return false;
			}
		}
    </script>
</head>
<body class="<?=$this->bodyclass?> <? if(!$this->user): ?>blured-bg<? endif; ?>">
<? if($this->user): ?>
<div id="top" class="container-fluid">
  <div class="logo">
    <img class="top-logo" src="<?=IMG?>logo_white.svg" alt="<?=TITLE?>">
  </div>
  <div class="topnavbar">
    <div class="mainbar">
      	<div class="left">
      		<div class="link">
            <div class="domain-list" ng-controller="Domains" ng-init="init()">
              <div class="selected-domain">
                <div class="current">
                  <label>Kiválasztott weboldalam:</label>
                  <strong>{{getDomain().domain}}</strong>
                </div>
              </div>
              <div class="droplist">
                <div class="item" ng-class="(domain.ID==current_domain)?'curr':''" ng-click="selectDomain(domain.ID)" data-id="{{domain.ID}}" ng-repeat="domain in domains">
                  <i ng-show="domain.active" class="fa fa-check-circle-o"></i><i ng-hide="domain.active" class="fa fa-times-circle-o"></i> {{domain.domain}}
                </div>
              </div>
              <div ng-show="domainsnum == 0">
                <strong><i class="fa fa-globe"></i> Nincs weboldal regisztrálva!</strong> <a href="/websites">Új weboldal regisztrálása</a>
              </div>
            </div>
      		</div>
      	</div>
        <div class="right">
        	<div class="shower userblock">
          	<i class="fa fa-user-circle-o"></i>
          	<?=$this->user['data']['nev']?>
              <i class="fa fa-angle-down"></i>
              <div class="dmenu">
              	<ul>
              		<li><a href="/home/exit">Kijelentkezés</a></li>
              	</ul>
              </div>
          </div>
        	<div class="shower no-bg" style="display: none;">
        		<a href="<?=FILE_BROWSER_IMAGE?>" data-fancybox-type="iframe" class="iframe-btn">Galéria <i class="fa fa-picture-o"></i></a>
          </div>
          <div class="shower no-bg">
            <a href="/csomagok">Az Ön jelenlegi csomagja: <span class="user-status <?=($this->u->MyPackage()->isDemo()?'demo':'user')?>"><?php echo $this->u->MyPackage()->getName(); ?></span></a>
          </div>
          <div class="shower sep">|</div>
          <?php if ($leftviews = $this->u->viewsLeft() != -1): ?>
          <div class="shower no-bg cash">
            <a href="/egyenleg"><i class="fa fa-eye"></i> <strong><?=number_format($this->u->viewsLeft(), 0,""," ")?></strong></a>
          </div>
          <?php endif; ?>
          <div class="shower no-bg cash">
            <a href="/egyenleg">Egyenleg: <strong><?=number_format($this->u->getEgyenleg(), 0,""," ")."<span class='smd'><sup>.".end(explode(".",number_format($this->u->getEgyenleg(), 2, ".","")))?></sup></span> FT</strong></a>
          </div>
        </div>
      </div>
      <div class="subbar">
        <div class="">
          <i class="fa fa-clock-o"></i> Utolsó befizetés dátuma: <strong>nov. 13.</strong>, összege: <strong>60 000,00</strong> Ft.
        </div>
        <div class="">
          <i class="fa fa-calendar"></i> István, az Ön által választott csomag még <strong class="red-text">15</strong> napig érvényes!
        </div>
        <div class="">
          <a class="btn-red" href="/egyenleg">Egyenlegfeltöltés</a>
        </div>
        <div class="">
          <a href="/helpdesk"><i class="fa fa-mortar-board"></i> Segítség</a>
        </div>
      </div>
  </div>
</div>
<? endif; ?>
<!-- Login module -->
<? if(!$this->user): ?>
<div id="login" class="container-fluid">
	<div class="row">
	    <div class="bg col-md-4 col-md-offset-4">
	    	<h3>Bejelentkezés</h3>
            <? if($this->err){ echo $this->bmsg; } ?>
            <form action="" method="post">
	            <div class="input-group">
      	              <span class="input-group-addon"><i class="fa fa-user"></i></span>
      				  <input type="text" class="form-control" name="email">
      				</div>
                      <br>
                      <div class="input-group">
      	              <span class="input-group-addon"><i class="fa fa-lock"></i></span>
      				  <input type="password" class="form-control" name="pw">
      				</div>
              <br>
              <div align="right"><button name="login">Bejelentkezés <i class="fa fa-arrow-circle-right"></i></button></div>
            </form>

	    </div>
    </div>
</div>
<? endif; ?>
<!--/Login module -->
<div id="content">
<div class="container-fluid">
	<? if($this->user): ?>
    <div class="slideMenu">
    	<div class="slideMenuToggle" title="Kinyit/Becsuk"><i class="fa fa-arrows-h"></i></div>
        <div class="clr"></div>
   		<div class="menu">
        	<ul>
            	<li class="<?=($this->gets[0] == 'home')?'on':''?>"><a href="/" title="Dashboard"><span class="ni">1</span><i class="fa fa-life-saver"></i><span class="txt">Dashboard</span></a></li>
              <li class="<?=($this->gets[0] == 'websites')?'on':''?>"><a href="/websites" title="Weboldalaim"><span class="ni">1</span><i class="fa fa-globe"></i> Weboldalaim</a></li>
              <li class="<?=($this->gets[0] == 'install')?'on':''?>"><a href="/install" title="Telepítés"><span class="ni">1</span><i class="fa fa-code"></i> Telepítés</a></li>
              <li class="<?=($this->gets[0] == 'egyenleg')?'on':''?>"><a href="/egyenleg" title="Egyenleg"><span class="ni">1</span><i class="fa fa-credit-card"></i> Egyenleg</a></li>
      				<li class="<?=($this->gets[0] == 'popup')?'on':''?>"><a href="/popup" title="Popup"><span class="ni">8</span><i class="fa fa-bullhorn"></i> Popup</a></li>
              <li class="<?=($this->gets[0] == 'csomagok')?'on':''?>"><a href="/csomagok" title="Csomagok"><span class="ni">8</span><i class="fa fa-cubes"></i> Csomagok</a></li>
      				<li class="<?=($this->gets[0] == 'beallitasok')?'on':''?>"><a href="/beallitasok" title="Beállítások"><span class="ni">8</span><i class="fa fa-gear"></i> Beállítások</a></li>
        	</ul>
        </div>
    </div>
    <? endif; ?>
    <div class="ct">
    	<div class="innerContent">
