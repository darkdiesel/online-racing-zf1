jQuery(function($) {
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
	
	$(function() {$("#header #box_main_menu #main_menu").lavaLamp({
		fx: "backout",
		speed: 600,
			click: function(event, menuItem) {
				return false;
			}
		});
	});
	
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

$(document).on('keyup', 'input[class=x_field]', function() {
    if ($(this).val().length > 0) {
        $('.icon_clear').fadeIn(300);
    }
    else {
        $('.icon_clear').fadeOut(300);
    }
});

$(document).on('click','.icon_clear',function() {
    $('input[name=x_field]').val('');
    $(this).delay(700).fadeOut(300);
});
