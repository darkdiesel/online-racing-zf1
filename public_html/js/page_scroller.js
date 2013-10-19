jQuery(document).ready(function($) {
    $("#page-scroller").on("click", "#topcontrol", function(e){
	$('html,body').animate({
	    scrollTop:0
	}, 800);
	e.preventDefault();
    }).on("click", "#downcontrol", function(e){
	$('html,body').animate({
	    scrollTop: $(document).outerHeight()
	}, 800);
        
	e.preventDefault();
    });
});