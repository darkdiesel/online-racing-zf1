jQuery(document).ready(function($) {

    $('#page-scroller #topcontrol').click(function(e) {
	$('body,html').animate({
	    scrollTop: 0
	}, 800);
	return false;
    });

    $('#page-scroller #downcontrol').click(function(e) {
	$('body,html').animate({
	    scrollTop: $(document).outerHeight()
	}, 800);
	return false;
    });
});