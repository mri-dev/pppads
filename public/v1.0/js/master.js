$(function(){
	$('.con i.hbtn').click(function(){
		var key = $(this).attr('key');
		
		$('.'+key).slideToggle(200);
	});	

	getNotifications();
	startReceiveNotification( 10000 );
	
	tinymce.init({
	    selector: "textarea:not(.no-editor)",
	    editor_deselector : 'no-editor',
	    theme: "modern",
	    language: "hu_HU",
	    content_css : "/public/v1.0/styles/DinFonts.css",
	    allow_styles: 'family-font',
	    font_formats : 
	   			"Din Composit=Din Comp, sans-serif;"+
	   			"Din Condensed=Din Cond, sans-serif;"+
	    		"Andale Mono=andale mono,times;"+
                "Arial=arial,helvetica,sans-serif;"+
                "Arial Black=arial black,avant garde;"+
                "Book Antiqua=book antiqua,palatino;"+
                "Comic Sans MS=comic sans ms,sans-serif;"+
                "Courier New=courier new,courier;"+
                "Georgia=georgia,palatino;"+
                "Helvetica=helvetica;"+
                "Impact=impact,chicago;"+
                "Symbol=symbol;"+
                "Tahoma=tahoma,arial,helvetica,sans-serif;"+
                "Terminal=terminal,monaco;"+
                "Times New Roman=times new roman,times;"+
                "Trebuchet MS=trebuchet ms,geneva;"+
                "Verdana=verdana,geneva;"+
                "Webdings=webdings;"+
                "Wingdings=wingdings,zapf dingbats",
	    plugins: [
	         "advlist autolink link image lists charmap print preview hr anchor pagebreak autoresize",
	         "searchreplace wordcount visualblocks visualchars insertdatetime media nonbreaking",
	         "table contextmenu directionality emoticons paste textcolor responsivefilemanager fullscreen code"
	   ],
	   toolbar1: "undo redo | bold italic underline | fontselect fontsizeselect forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | styleselect",
	   toolbar2: "| responsivefilemanager | link unlink anchor | image media |  print preview code ",
	   image_advtab: true ,
	   theme_advanced_resizing : true,	   
	   external_filemanager_path:"/filemanager/",
	   filemanager_title:"Responsive Filemanager" ,
	   external_plugins: { "filemanager" : "/filemanager/plugin.min.js"}
	 });

	$('.zoom').fancybox({
		openEffect	: 'none',
		closeEffect	: 'none'
	});

	$('.iframe-btn').fancybox({
		maxWidth	: 800,
		maxHeight	: 600,
		fitToView	: false,
		width		: '70%',
		height		: '70%',
		autoSize	: false,
		closeClick	: false,
		openEffect	: 'none',
		closeEffect	: 'none',
		closeBtn 	: false,
		padding		: 0
    });
})

var t = null;
function startReceiveNotification( timer ){
	t = setInterval( getNotifications, timer );
}

function loadTemplate ( key, arg, callback) {
	$.post('/ajax/post', {
		type : 'template',
		key : key,
		arg : $.param(arg)
	}, function(d){
		callback(d);
	},"html");
}

// Admin live értesítő
function getNotifications(){ 
	$.post("/ajax/get", {
		type : 'getNotification'
	}, function(d){
		var a = $.parseJSON(d);

		// Üzenetek
		var msg_nf 		= $('.slideMenu .menu li a[title=Üzenetek]');
		var msg_nf_e 	= msg_nf.find('.ni');

		if( a.data.new_msg == 0 ){
			msg_nf_e
				.text( 0 )
				.attr( 'title', '' );
			msg_nf_e.css({
				visibility : 'hidden'
			});
		}else{
			msg_nf_e
				.text( a.data.new_msg )
				.attr( 'title', a.data.new_msg+ ' db új üzenet' );
			msg_nf_e.css({
				visibility : 'visible'
			});
		}

		// Megrendelés
		var order_nf 		= $('.slideMenu .menu li a[title=Megrendelések]');
		var order_nf_e 		= order_nf.find('.ni');

		if( a.data.new_order == 0 ){
			order_nf_e
				.text( 0 )
				.attr( 'title', '' );
			order_nf_e.css({
				visibility : 'hidden'
			});
		}else{
			order_nf_e
				.text( a.data.new_order )
				.attr( 'title', a.data.new_order+ ' db új megrendelés' );
			order_nf_e.css({
				visibility : 'visible'
			});
		}

		// Casada Shop
		var order_nf 		= $('.slideMenu .menu li a[title=Üzletek]');
		var order_nf_e 		= order_nf.find('.ni');

		if( a.data.inactive_casadapont == 0 ){
			order_nf_e
				.text( 0 )
				.attr( 'title', '' );
			order_nf_e.css({
				visibility : 'hidden'
			});
		}else{
			order_nf_e
				.text( a.data.inactive_casadapont )
				.attr( 'title', a.data.inactive_casadapont+ ' db inaktív / feldolgozatlan igény' );
			order_nf_e.css({
				visibility : 'visible'
			});
		}

		// Arena Water Card
		var awc_nf 		= $(".slideMenu .menu li a[title='Arena Water Card']");
		var awc_nf_e 	= awc_nf.find('.ni');

		if( a.data.new_awc == 0 ){
			awc_nf_e
				.text( 0 )
				.attr( 'title', '' );
			awc_nf_e.css({
				visibility : 'hidden'
			});
		}else{
			console.log(awc_nf_e);
			awc_nf_e
				.text( a.data.new_awc )
				.attr( 'title', a.data.new_awc+ ' db aktiválásra váró kártya' );
			awc_nf_e.css({
				visibility : 'visible'
			});
		}
		
	}, "html");
}