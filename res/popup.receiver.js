var wppopup = angular.module('WebProPopup', ['ngCookies']);

wppopup.controller("popupReceiver", ['$scope', '$sce', '$cookies', '$http', '$location', '$window', '$timeout', function($scope, $sce, $cookies, $http, $location, $window, $timeout)
{
	var ctrl 	= this;
	var _url 	= $location.absUrl();
	var _path 	= $location.path();
	var _host 	= $location.host();
	var loadedsco = false;
	// Defaults
	var _config = {
		'contentWidth' : 970,
		'headerHeight' : 75,
		'responsiveBreakpoint' : 960,
		'domain' : false,
		'receiverdomain' : 'https://www.popup.web-pro.hu',
		'imageRoot' : 'https://www.popup.web-pro.hu/'
	};

	var param 	= function(obj) {
	    var query = '', name, value, fullSubName, subName, subValue, innerObj, i;

	    for(name in obj) {
	      value = obj[name];

	      if(value instanceof Array) {
	        for(i=0; i<value.length; ++i) {
	          subValue = value[i];
	          fullSubName = name + '[' + i + ']';
	          innerObj = {};
	          innerObj[fullSubName] = subValue;
	          query += param(innerObj) + '&';
	        }
	      }
	      else if(value instanceof Object) {
	        for(subName in value) {
	          subValue = value[subName];
	          fullSubName = name + '[' + subName + ']';
	          innerObj = {};
	          innerObj[fullSubName] = subValue;
	          query += param(innerObj) + '&';
	        }
	      }
	      else if(value !== undefined && value !== null)
	        query += encodeURIComponent(name) + '=' + encodeURIComponent(value) + '&';
	    }

	    return query.length ? query.substr(0, query.length - 1) : query;
	};

	$http.defaults.headers.post["Content-Type"] = 'application/x-www-form-urlencoded;charset=utf-8';
	$http.defaults.transformRequest = [function(data) {
	    return angular.isObject(data) && String(data) !== '[object File]' ? param(data) : data;
	}];

	$scope.showed = false;
	$scope.test = 'minta';

	/**
	* Böngésző szélesség
	*/
	$scope.windowWidth = function(){
		return parseInt($window.innerWidth);
	}

	/**
	* Böngésző magasság
	*/
	$scope.windowHeight= function(){
		return parseInt($window.innerHeight);
	}

	$scope.init = function ( settings )
	{

		angular.extend( _config, settings );

		ctrl.checkCookie(_config.domain);

		// Dokumentum magasság (px)
		var _documentHeight = jQuery(document).height();

		ctrl.loadScreen(_url, function(sco, template)
		{
			if (sco.show)
			{
				// Timed event
				if (sco.data.creative.type == 'timed')
				{
					$timeout(function()
					{
						ctrl.loadedsco = sco;
						ctrl.loadTemplate(template);
						$scope.showed = true;
						ctrl.logShow( sco.data.creative.id, sco.data.screen_loaded );

					}, sco.data.creative.settings.timed_delay_sec * 1000);
				}

				// Scroll event on scroll
				if (sco.data.creative.type == 'scroll')
				{
					var opencount = 0;

					jQuery(window).scroll(function()
					{
						// Távolság a felső résztől (px)
						var _top = jQuery('body').scrollTop();
						var realheight = jQuery(document).height() - $scope.windowHeight();
						var scrollpercent = _top / (realheight / 100);

						if (scrollpercent > sco.data.creative.settings.scroll_percent_point)
						{
							//console.log(view);
							if ( !$scope.showed && opencount == 0 )
							{
								opencount++;
								ctrl.loadedsco = sco;
								ctrl.loadTemplate(template);
								$scope.showed = true;
								ctrl.logShow( sco.data.creative.id, sco.data.screen_loaded );
							};
						}

					});
				};

				// Mousemove event
				if (sco.data.creative.type == 'exit')
				{
					var delay_pause = (typeof sco.data.creative.settings.exit_pause_delay_sec === 'undefined') ? 0 : sco.data.creative.settings.exit_pause_delay_sec;

					$timeout(function()
					{
						var opencount = 0;
						jQuery(document).mousemove(function(e)
						{
							var w = e.clientX;
							var h = e.clientY;

							if (h < _config.headerHeight )
							{
								if ( !$scope.showed && opencount == 0  )
								{
									opencount++;
									ctrl.loadedsco = sco;
									ctrl.loadTemplate(template);
									$scope.showed = true;
									ctrl.logShow( sco.data.creative.id, sco.data.screen_loaded );
								};
							}
						});

					}, delay_pause * 1000);

				};

			};

		});

	}

	$scope.redirect = function()
	{
		ctrl.logInteraction(true, function()
		{
			ctrl.loadedsco 	= false;
			$scope.showed 	= false;
		});
	}

	$scope.close = function()
	{
		ctrl.logInteraction(false, function()
		{
			ctrl.loadedsco 	= false;
			$scope.showed 	= false;
		});
	}

	// TODO
	this.logInteraction = function(positive, callback)
	{
		$http.post(_config.receiverdomain+'/ajax/post/',
		{
			type 		: 'logPopupClick',
			creative 	: ctrl.loadedsco.data.creative.id,
			screen 		: ctrl.loadedsco.data.screen.id,
			closed 		: (positive) ? 0 : 1,
			sessionid	: ctrl.getSessionID()

		}).success(function(d,s,h,c){
			callback();
		});
	}

	this.loadScreen = function( url, callback )
	{
		$http.post(_config.receiverdomain+'/ajax/post/',
		{
			type 		: 'getPopupScreenVariables',
			url 		: url,
			sessionid	: ctrl.getSessionID()

		}).success(function(d,s,h,c){
			//
			var template = {};

			console.log(d);

			if (d.show) {
				template = {
					'settings' 	: angular.fromJson(d.data.screen.variables.settings),
					'screen' 	: angular.fromJson(d.data.screen.variables.screen),
					'content' 	: angular.fromJson(d.data.screen.variables.content),
					'interacion': angular.fromJson(d.data.screen.variables.interacion),
					'links' 	: angular.fromJson(d.data.screen.variables.links),
				}
			};

			callback(d, template);

		});
	}

	this.logShow = function( c, s) {

		$http.post(_config.receiverdomain+'/ajax/post/',
		{
			type 		: 'logPopupScreenshow',
			creative 	: c,
			screen 		: s,
			sessionid	: ctrl.getSessionID()

		}).success(function(d,s,h,c){
			console.log(d);
		});
	}

	this.getSessionID = function() {
		return $cookies.get('popupHostSessionID');
	}

	this.checkCookie = function( domain ) {
		var user = $cookies.get('popupHostSessionID');

		// Create
		if (typeof user === 'undefined')
		{
			var key = Math.floor((Math.random()*999999999)+111111111);
			var expires = new Date();
			expires.setDate(expires.getDate() + 30);
			$cookies.put('popupHostSessionID', key, { 'path' : '/', 'domain' : domain, 'expires' : expires });
			user = key;
		}
	}

	this.loadTemplate = function( savedTemplate )
	{

		if ( savedTemplate.settings.type == '%' && $scope.windowWidth() < _config.contentWidth )
		{
			savedTemplate.settings.width = 95;
			savedTemplate.content.title.size  = savedTemplate.content.title.size - (savedTemplate.content.title.size / 100 * 20 );
			savedTemplate.interacion.main.text_size = savedTemplate.interacion.main.text_size - (savedTemplate.interacion.main.text_size / 100 * 30);
		}

		if ( savedTemplate.settings.type == 'px' && $scope.windowWidth() < savedTemplate.settings.width  )
		{
			savedTemplate.settings.width = $scope.windowWidth() - 10;
			savedTemplate.content.title.size  = savedTemplate.content.title.size - (savedTemplate.content.title.size / 100 * 20 );
			savedTemplate.interacion.main.text_size = savedTemplate.interacion.main.text_size - (savedTemplate.interacion.main.text_size / 100 * 30);
		}

		// Settings
		$scope.settings = {};
		$scope.settings.width 	= 50;
		$scope.settings.type 	= '%';
		$scope.settings.width_types = ['px', '%'];
		$scope.settings.background_color = 'rgba(255, 121, 154, 0.79)';
		angular.extend($scope.settings, savedTemplate.settings);

		// Screen
		$scope.screen = {};
		$scope.screen.padding 			= 10;
		$scope.screen.background_color 	= 'rgba(212, 28, 79, 1)';
		$scope.screen.background_image 	= '';
		$scope.screen.background_pos 	= {
			'left top' : 'Balra fentre',
			'left center' : 'Balra középre',
			'left bottom' : 'Balra alulra',
			'right top' : 'Jobbra fentre',
			'right center' : 'Jobbra középre',
			'right bottom' : 'Jobbra alulra',
			'center top' : 'Középre fentre',
			'center center' : 'Középre',
			'center bottom' : 'Középre alulra'
		};
		$scope.screen.background_pos_sel= 'center center';
		$scope.screen.background_reps   = { 'no-repeat' : 'Nincs ismétlődés', 'repeat' : 'Ismétlődik', 'repeat-x' : 'Horizontális tengelyen ismétlődik', 'repeat-y' : 'Vertikális tengelyen ismétlődik'};
		$scope.screen.background_repeat = 'no-repeat';
		$scope.screen.background_sizes 	= { '' : 'Eredeti méret', 'contain' : 'Tartalomhoz igazít', 'cover' : 'Kitöltés', '100%' : '100% szélesség'};
		$scope.screen.background_size 	= '';
		$scope.screen.border_color 		= 'rgba(255, 255, 255, 0.2)';
		$scope.screen.border_size 		= 5;
		$scope.screen.border_type 		= "solid";
		$scope.screen.border_types 		= ['dotted','dashed','solid','double','groove','ridge','inset','outset'];
		$scope.screen.shadow_radius		= 50;
		$scope.screen.shadow_color		= '#000';
		$scope.screen.shadow			= { 'x' : 0, 'y' : 15 };
		$scope.screen.shadow_width		= -5;
		$scope.screen.cssstyles			= '';

		// Szöveg
		$scope.screen.text_color 		= "#fff";
		$scope.screen.text_size 		= 1;
		$scope.screen.text_align		= 'center';

		savedTemplate.screen.background_pos = $scope.screen.background_pos;
		savedTemplate.screen.background_reps = $scope.screen.background_reps;
		savedTemplate.screen.background_sizes = $scope.screen.background_sizes;
		savedTemplate.screen.border_types = $scope.screen.border_types;

		angular.extend($scope.screen, savedTemplate.screen);

		// Content
		$scope.content 					= {};
		$scope.content.title 			= {};
		$scope.content.title.text 		= 'Főcím';
		$scope.content.title.color 		= '';
		$scope.content.title.size 		= 2.4;
		$scope.content.title.align 		= '';

		$scope.content.subtitle 			= {};
		$scope.content.subtitle.text 		= 'Alcím';
		$scope.content.subtitle.color 		= '';
		$scope.content.subtitle.size 		= 1.4;
		$scope.content.subtitle.align 		= '';

		$scope.content.fill 			= {};
		$scope.content.fill.text 		= 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam vel metus id arcu fermentum rutrum. Aenean neque ante, dignissim non massa non, cursus malesuada nulla. Ut sodales volutpat leo vel lobortis. Nulla sagittis tempor dolor at laoreet. Donec at pharetra mauris. Cras at tortor at sapien condimentum facilisis. Vivamus quis erat non nisl dapibus fermentum in sit amet mi. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Vivamus non dapibus ligula. Donec ac nunc interdum, ultricies ligula vitae, cursus lacus. Cras imperdiet ultrices turpis a pulvinar. Phasellus id tortor vitae ante ultrices elementum eget at elit. Duis cursus arcu et magna porttitor, eget maximus mauris dignissim.';
		$scope.content.fill.color 		= '';
		$scope.content.fill.size 		= 1;
		$scope.content.fill.align 		= '';


		savedTemplate.content.fill.text = savedTemplate.content.fill.text.replace( '../', _config.imageRoot);

		$scope.textHTML 	= function(){
	 		return $sce.trustAsHtml($scope.content.fill.text);
		}

		angular.extend($scope.content, savedTemplate.content);

		// Interakció
		$scope.interacion 					= {};
		$scope.interacion.main 				= {};
		$scope.interacion.main.text 		= 'Tovább';
		$scope.interacion.main.text_color 	= 'rgba(255,255,255,1)';
		$scope.interacion.main.text_size 	= 1.8;
		$scope.interacion.main.text_custom 	= '';
		$scope.interacion.main.text_align 	= 'center';
		$scope.interacion.main.background  	= 'rgba(0,0,0,1)';
		$scope.interacion.main.width 		= 60;
		$scope.interacion.main.width_type   = '%';
		$scope.interacion.main.width_types  = ['%', 'px'];
		$scope.interacion.main.padding  	= 10;
		$scope.interacion.main.margin  		= 10;
		$scope.interacion.main.border_color = '#fff';
		$scope.interacion.main.border_width = 2;
		$scope.interacion.main.border_style = 'solid';
		$scope.interacion.main.border_radius = 10;

		// Kilépő
		$scope.interacion.exit 				= {};
		$scope.interacion.exit.text 		= 'Nem érdekel';
		$scope.interacion.exit.text_color 	= 'rgba(255,255,255,0.8)';
		$scope.interacion.exit.text_style 	= 'italic';
		$scope.interacion.exit.text_styles 	= { 'bold' : 'Félkövér', 'italic' : 'Dölt', 'normal' : 'Normál' };
		$scope.interacion.exit.text_size 	= 0.8;
		$scope.interacion.exit.text_custom 	= '';

		angular.extend($scope.interacion, savedTemplate.interacion);

		// Linkek
		$scope.links 			= {};
		$scope.links.to_url 	= '#';
		$scope.links.exit_url 	= 'javascript:popupClose();';
		$scope.links.open_type 	= '_blank';
		$scope.links.open_types = {'_blank': 'Új ablakban', '_self':'Helyben'};

		angular.extend($scope.links, savedTemplate.links);
	}
}]);
