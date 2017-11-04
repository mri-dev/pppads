<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html xmlns="http://www.w3.org/1999/html4"
      xmlns:og="http://ogp.me/ns#"
      xmlns:fb="http://www.facebook.com/2008/fbml" lang="hu-HU" ng-app="casada">
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
				$('.ct').css({
					'paddingLeft' : '220px'
				});
			}else{
				slideMenu.css({
					'left' : '-'+closeNum+'px'
				});
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
					$('.ct').animate({
						'paddingLeft' : '75px'
					},200);
					saveState('closed');
				}else{
					isSlideOut = true;
					slideMenu.animate({
						'left' : '0px'
					},200);
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
<body class="<? if(!$this->user): ?>blured-bg<? endif; ?>">
<div id="top" class="container-fluid">
	<div class="row">
		<? if(!$this->user): ?>
		<div class="col-md-12 center"><img height="34" src="<?=IMG?>logo_white.svg" alt="<?=TITLE?>"></div>
		<? else: ?>
    	<div class="col-md-7 left">
    		<img height="34" class="top-logo" src="<?=IMG?>logo_white.svg" alt="<?=TITLE?>">
    		<div class="link">
    			<strong><i class="fa fa-globe"></i> Nincs weboldal regisztrálva!</strong> <a href="/websites">Új weboldal regisztrálása</a>
    		</div>
    	</div>

        <div class="col-md-5" align="right">
        	<div class="shower">
          	<i class="fa fa-user"></i>
          	<?=$this->user['data']['nev']?>
              <i class="fa fa-caret-down"></i>
              <div class="dmenu">
              	<ul>
              		<li><a href="/home/exit">Kijelentkezés</a></li>
              	</ul>
              </div>
          </div>
        	<div class="shower no-bg">
        		<a href="<?=FILE_BROWSER_IMAGE?>" data-fancybox-type="iframe" class="iframe-btn">Galéria <i class="fa fa-picture-o"></i></a>
          </div>
          <div class="shower no-bg">
            <a href="/mystatus">Fiók státusz: <span class="user-status <?=$this->user['data']['user_group']?>"><?php echo $this->u->getUserGroupe(); ?></span></a>
          </div>
          <div class="shower no-bg cash">
            <a href="/egyenleg">Egyenleg: <strong><?=number_format($this->u->getEgyenleg(), 0,""," ")."<span class='smd'><sup>.".end(explode(".",number_format($this->u->getEgyenleg(), 2, ".","")))?></sup></span> FT</strong></a>
          </div>
        </div>
        <? endif; ?>
    </div>
</div>
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
                <div class="left links"><a href="<?=HOMEDOMAIN?>"><i class="fa fa-angle-left"></i> www.<?=str_replace(array('https://','www.'), '', $this->settings['page_url'])?></a></div>
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
            	<li class="<?=($this->gets[0] == 'home')?'on':''?>"><a href="/" title="Dashboard"><span class="ni">1</span><i class="fa fa-life-saver"></i> Dashboard</a></li>
              <li class="<?=($this->gets[0] == 'websites')?'on':''?>"><a href="/websites" title="Weboldalaim"><span class="ni">1</span><i class="fa fa-globe"></i> Weboldalaim</a></li>
              <li class="<?=($this->gets[0] == 'install')?'on':''?>"><a href="/install" title="Telepítés"><span class="ni">1</span><i class="fa fa-code"></i> Telepítés</a></li>
              <li class="<?=($this->gets[0] == 'egyenleg')?'on':''?>"><a href="/egyenleg" title="Egyenleg"><span class="ni">1</span><i class="fa fa-credit-card"></i> Egyenleg</a></li>
      				<li class="<?=($this->gets[0] == 'popup')?'on':''?>"><a href="/popup" title="Popup"><span class="ni">8</span><i class="fa fa-bullhorn"></i> Popup</a></li>
      				<li class="<?=($this->gets[0] == 'beallitasok')?'on':''?>"><a href="/beallitasok" title="Beállítások"><span class="ni">8</span><i class="fa fa-gear"></i> Beállítások</a></li>
        	</ul>
        </div>
    </div>
    <? endif; ?>
    <div class="ct">
    	<div class="innerContent">
