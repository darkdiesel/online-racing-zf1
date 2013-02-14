(function($) {
$.fn.lavaLamp = function(o) {

    o = $.extend({ fx: "linear", speed: 300, click: function(){} }, o || {});

    return this.each(function() {
		//Checked the parrent if it "li"
		if (!$(this).parent().is("li")){
			var me = $(this), noop = function(){},
				$back = $('<li class="back"><div class="left"></div></li>').appendTo(me),
				$li = $("li", this), curr = $("li.active", this)[0] || $("li.active-trail", this)[0] || $($li[0]).addClass("active")[0];
		}
			
		$li = $("li", this);
        $li.not(".back").hover(function() {
            if (!$(this).parent().parent().hasClass('expanded')){			
				move(this);
			}
        }, noop);
				
        $(this).hover(noop, function() {
			if ((!$(this).parent().parent().hasClass('expanded')) && !(curr === undefined)) {
				move(curr);
			}
        });

        $li.click(function(e) {
		if ((!$(this).parent().parent().hasClass('expanded'))&&($(e.target).is("a"))){
			setCurr(this);
            return o.click.apply(this, [e, this]);
		}
        });

        
		if (!$(this).parent().is("li")) { setCurr(curr);}

        function setCurr(el) {
			if (!$(this).parent().parent().hasClass('expanded')){
				$back.css({ "left": (el.offsetLeft-10)+"px", "width": (el.offsetWidth+8)+"px" });
				curr = el;
			}
        };
		
        function move(el) {
			$back.each(function() {
				$(this).dequeue(); 
				$(this).animate({left:'+='+(el.offsetLeft-10 - $(this).position().left), width:'+='+(el.offsetWidth+8-$(this).width())},o.speed);			
				}
			)
        }
				
    });
};
})(jQuery);
