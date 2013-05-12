$(document).ready(function() {
    jQuery(function($) {
        $('#list_sliding_tabs').stop().animate({'right': '-260px'}, 1000);
        //$('#list_sliding_tabs > .sliding_tab_item')

        zIndexValue = 999;
        
        $('#list_sliding_tabs > .sliding_tab_item').each(
                function() {
                    zIndexValue = zIndexValue+1;
                    $(this).css({"z-index" : zIndexValue});
                    
                    if ($(this).html() !=  $('#list_sliding_tabs .sliding_tab_item:first').html()){
                        $(this).css({"margin-top" : -260});
                    }
                    
                    $(".sliding_tab_item_content",  $(this)).css({"z-index" : zIndexValue+2});
                });

        $('#list_sliding_tabs > .sliding_tab_item').hover(
                function() {
                    tempzindexValue = $(this).css('zIndex');
                    $(this).stop().animate({'right': '260px'}, 200);
                    $(this).css({"z-index" : zIndexValue + 1});
                },
                function() {
                    $(this).stop().animate({'right': '0px'}, 200);
                    $(this).css({"z-index" : tempzindexValue});
                }
        );
    });

    $('#slide_out_tab_contact').tabSlideOut({
        tabHandle: '.handle', //class of the element that will be your tab
        pathToTabImage: '/img/contact_tab.gif', //path to the image for the tab *required*
        imageHeight: '122px', //height of tab image *required*
        imageWidth: '40px', //width of tab image *required*    
        tabLocation: 'left', //side of screen where tab lives, top, right, bottom, or left
        speed: 300, //speed of animation
        action: 'click', //options: 'click' or 'hover', action to trigger animation
        topPos: '200px', //position from the top
        fixedPosition: false                               //options: true makes it stick(fixed position) on scroll
    });
});