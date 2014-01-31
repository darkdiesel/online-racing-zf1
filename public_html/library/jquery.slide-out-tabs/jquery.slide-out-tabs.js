(function($) {
	$.fn.slideOutTabs = function(options) {
		var settings = $.extend({
		}, options);

		var zIndexValue = 999;
		var itemPositionValue = 70;

		return this.each(function() {
			$(this).children('.slide-out-tab').each(function() {
				zIndexValue = zIndexValue + 1;
				$(this).css({"z-index": zIndexValue});

				if ($(this).html() != $('.slide-out-tabs .slide-out-tab:first').html()) {
					$(this).css({"top": itemPositionValue});
					itemPositionValue = itemPositionValue + 70;
				}

				$(".slide-out-tab-body", $(this)).css({"z-index": zIndexValue + 2});
			}).hover(
					function() {
						tempzindexValue = $(this).css('zIndex');
						$(this).stop().animate({'right': '0px'}, 200);
						$(this).css({"z-index": zIndexValue + 1});
					},
					function() {
						$(this).stop().animate({'right': '-260px'}, 200);
						$(this).css({"z-index": tempzindexValue});
					}
			);
		});
	};
})(jQuery);