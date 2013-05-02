$(document).ready(function(){
    /*jQuery(function($){
        // lavalamp main menu plugin
        $(function() {
            $("#header #box_main_menu #main_menu").lavaLamp({
                fx: "backout",
                speed: 600
            });
        });
    });*/
    
    
    
    // Clear button for .x_field field
    jQuery(function($) {
        $.fn.addXbtn = (function (e) {
            $(this).each(function() {
                $(this).wrap('<span class="wrap_x_field"></span>');
                $(this).parent().append('<span class="icon_clear">X</span>');
            });
		
            // add css for clear "X" button
            $(this).parent().each(function() {
                $(this).css({
                    "position":"relative",
                    "display":"inline-block",
                    "width":$('.x_field',this).outerWidth()+"px"
                });
                $('.icon_clear',this).css({
                    "border-radius":"30px", 
                    "display":"none", 
                    "font-family":"Verdana", 
                    "cursor":"pointer",
                    "font-weight":"bold", 
                    "position":"absolute", 
                    "right":"5px", 
                    "text-align":"center"
                });
			
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
                    $('.icon_clear',this).css({
                        "line-height":"18px", 
                        "font-size":"11px", 
                        "width":"18px",
                        "height":"18px"
                    });
                } else {
                    $('.icon_clear',this).css({
                        "line-height":$('.x_field',this).outerHeight()/2+"px", 
                        "font-size":$('.x_field',this).outerHeight()/3.2+"px", 
                        "width":$('.x_field',this).outerHeight()/2+"px",
                        "height":$('.x_field',this).outerHeight()/2+"px"
                    });
                }
			
                $('.icon_clear',this).css({
                    "top":(($('.x_field',this).outerHeight()-$('.icon_clear',this).outerHeight())/2)+"px"
                });
                $('.x_field',this).css({
                    "padding-right":($('.icon_clear',this).outerWidth())+8+"px",
                    "width":($('.x_field',this).width()-(($('.icon_clear',this).outerWidth())+2))+"px"
                });
                $(this).css({
                    "width":$('.x_field',this).outerWidth()+"px"
                });
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
    });

    jQuery(function($){
        // run function for clear "X" button
        $(function() {
            $(".x_field").addXbtn({
                });
        });
    });
    
    $(function(){
        $('.tooltip_field').tooltip();
    });
    
    $(".congigure_block_item_links").hover(function(o) {
        $(this).parent(".block_item").children(".block_item_content").css({
            "border" : "1px dashed red"
        });
    }, function(o) {
        $(this).parent(".block_item").children(".block_item_content").css({
            "border" : "none"
        });
    });
    
    $(".block_item").hover(function(o) {
        $(this).children(".congigure_block_item_links").show();
    }, function(o) {
        $(this).children(".congigure_block_item_links").hide();
    });

});