//init google analitycs
jQuery(function($){
    var _gaq = _gaq || [];
    _gaq.push(['_setAccount', 'UA-32674101-2']);
    _gaq.push(['_trackPageview']);

    (function() {
        var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
        ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
        var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
    })();
});

// Clear button for .x_field field
jQuery(function($) {
	$.fn.addXbtn = (function (e) {
		$(this).each(function() {
			$(this).wrap('<span class="wrap_x_field"></span>');
			$(this).parent().append('<span class="icon_clear">X</span>');
		});
		
		// add css for clear "X" button
		$(this).parent().each(function() {
			$(this).css({"position":"relative","display":"inline-block"});
			$('.icon_clear',this).css({"border-radius":"30px", "display":"none", "font-family":"Verdana", "cursor":"pointer",
			"font-weight":"bold", "position":"absolute", "right":"5px", "text-align":"center"});
			
			if (e.bgcolor == undefined){
				$('.icon_clear',this).css("background","#EDEDED");
			} else {
				$('.icon_clear',this).css("background",e.bgcolorhov);
			}

			if (e.bordercolor == undefined){
				$('.icon_clear',this).css("border","1px solid #D2D2D2");
			} else {
				$('.icon_clear',this).css("border","1px solid "+e.bordercolor);
			}
			if (e.color == undefined){
				$('.icon_clear',this).css("color","#BEBEBE");
			} else {
				$('.icon_clear',this).css("border",e.color);
			}		
			
			if ($('.x_field',this).outerHeight() >= 35 ) {
				$('.icon_clear',this).css({"line-height":"18px", "font-size":"11px", "width":"18px","height":"18px"});
			} else {
				$('.icon_clear',this).css({"line-height":$('.x_field',this).outerHeight()/2+"px", "font-size":$('.x_field',this).outerHeight()/3.2+"px", "width":$('.x_field',this).outerHeight()/2+"px",
					"height":$('.x_field',this).outerHeight()/2+"px"});
			}
			
			$('.icon_clear',this).css({"top":(($('.x_field',this).outerHeight()-$('.icon_clear',this).outerHeight())/2)+"px"});
			$('.x_field',this).css({"padding-right":($('.icon_clear',this).outerHeight())+8+"px"});
		});
		
		$(this).blur(function(o) {
			$('.icon_clear').fadeOut(300);
		});
		
		$(this).parent().click(function(o) {
			if (($(".x_field",this).val() != '') && ($(o.target).is('.x_field'))){
				$('.icon_clear', this).fadeIn(400);
			}
			if ($(o.target).is("span")){
				$('.x_field',this).val('');
				$('.icon_clear', this).delay(100).fadeOut(400);
			}
		});
		
		$(this).parent().keyup(function() {
			if ($(".x_field",this).val().length > 0) {
				$('.icon_clear',this).fadeIn(300);
			}
			else {
				$('.icon_clear').fadeOut(300);
			}
		});
	});
});