$(document).ready(function() {
    jQuery(function($) {
        zIndexValue = 999;
        itemPositionValue = 70;

        $('#list_sliding_tabs > .sliding_tab_item').each(
                function() {
                    zIndexValue = zIndexValue + 1;
                    $(this).css({"z-index": zIndexValue});

                    if ($(this).html() != $('#list_sliding_tabs .sliding_tab_item:first').html()) {
                        $(this).css({"top": itemPositionValue});
                        itemPositionValue = itemPositionValue + 70;
                    }

                    $(".sliding_tab_item_content", $(this)).css({"z-index": zIndexValue + 2});
                });

        $('#list_sliding_tabs > .sliding_tab_item').hover(
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

});