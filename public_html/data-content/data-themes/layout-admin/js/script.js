(function ($) {
    $(window).load(function(){
        $('.preloader').fadeOut(1000); // set duration in brackets
    });

    $(document).ready(function () {
        resizeSidebar();
        $(window).on('resize', function (el) {
            resizeSidebar();
        });

        function resizeSidebar() {
            $('#left-sidebar').height('auto');
            if ($('body').width() >= '768'){
                $('#left-sidebar').height($(document).height() - $("#header").outerHeight());
            }
        }

        $('#digital-clock').clock({ type: 'digital'});
    });
})(jQuery);