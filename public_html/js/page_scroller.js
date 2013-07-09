jQuery(document).ready(function($) {

    $('#page-scroller #topcontrol').click(function(e) {
	$('body,html').animate({
	    scrollTop: 0
	},  1500, "easeOutBounce");
	return false;
    });
    
    $('#page-scroller #downcontrol').click(function(e) {
	$('body,html').animate({
	    scrollTop: $(document).height()
	},  1000);
	return false;
    });
});