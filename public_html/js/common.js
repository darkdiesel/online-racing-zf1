$(document).ready(function() {
	$(".block-config-btn").hover(function(o) {
		$(this).closest(".block").css({
			"outline": "2px dashed red"
		});
	}, function(o) {
		$(this).closest(".block").css({
			"outline": "none"
		});
	});
	/* Tooltip fields supports by bootstrap js library */
	$('.tooltip-field').tooltip();
	
	/* Add responsive class for images */
	$('#default-post-id').find('img').each(function(el){
		if (!$(this).hasClass('img-responsive')){
			$(this).addClass('img-responsive');
		}
	});

});