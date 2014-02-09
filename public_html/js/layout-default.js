jQuery(document).ready(function(e) {
	if (jQuery.fn.slideOutTabs) {
		$('.slide-out-tabs').slideOutTabs();
	}

	if (jQuery.fn.pageScroller) {
		$('.page-scroller').pageScroller();
	}

	if (jQuery.fn.snowfall) {
		jQuery('#page').snowfall({round: true, minSize: 3, maxSize: 8}); // add rounded
	}

	$(function() {
		$(window).scroll(function() {
			if ($(this).scrollTop() > 50) {
				$('#sub-navbar').addClass("navbar-fixed-top");
				$('#sub-navbar').removeClass("navbar-static-top");
			} else {
				$('#sub-navbar').addClass("navbar-static-top");
				$('#sub-navbar').removeClass("navbar-fixed-top");
			}
		});
	});
});

