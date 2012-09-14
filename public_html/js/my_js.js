// lavalamp main menu plugin
jQuery(function($) {
	$(function() {$("#header #box_main_menu #main_menu").lavaLamp({
		fx: "backout",
		speed: 600
		});
	});
});

// back to top button plugin
jQuery(function($) {
	$(function() {	
		// hide #back-top first
		$("#back-top").hide();
	
		// fade in #back-top
		$(function () {
			$(window).scroll(function () {
				if ($(this).scrollTop() > 100) {
					$('#back-top').fadeIn();
				} else {
					$('#back-top').fadeOut();
				}
			});

			// scroll body to 0px on click
			$('#back-top a').click(function () {
				$('body,html').animate({
					scrollTop: 0
				}, 800);
				return false;
			});
		});
	});	
});	
	
	
// countdown clock script
jQuery(function($) {
    $('#countdown_dashboard').countDown({
        targetDate: {
            'day':1,
            'month':2,
            'year':2013,
            'hour':19,
            'min':0,
            'sec':0,
            // time set as UTC
            'utc':true
        }
    });
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
	
	// run function for clear "X" button
	$(function() {$(".x_field").addXbtn({

			}
		);
	});
});

// skitter slider plugin
jQuery(function($) {
	$(function() {
		var options = {};
		
		options['label'] = true;
		options['numbers'] = true;
		options['preview'] = true;
		options['velocity'] = 2500;
		options['dots'] = true;
		options['focus'] = true;
		options['focus_position'] = 'leftTop';
		options['controls'] = true;
		options['controls_position'] = 'rightTop';
		options['hideTools'] = true;
		options['animation'] = 'random';
		//options['easing_default'] = 'random';
		options['interval'] = 5000;
		options['xml'] = '../xml/skitter_slider.xml';
		
		if (document.location.search) {
			var array = document.location.search.split('=');
			var param = array[0].replace('?', '');
			var value = array[1];
			
			if (param == 'animation') {
				options.animation = value;
			}
			else if (param == 'type_navigation') {
				if (value == 'dots_preview') {
					$('.border_box').css({'marginBottom': '40px'});
					options['dots'] = true;
					options['preview'] = true;
				}
				else {
					options[value] = true;
					if (value == 'dots') $('.border_box').css({'marginBottom': '40px'});
				}
			}
		}

		$('.box_skitter_large').css({width: 731, height: 300}).skitter(options);
	});
});