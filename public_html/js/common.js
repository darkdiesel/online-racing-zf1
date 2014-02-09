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

});