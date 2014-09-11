(function ($) {
    $(document).ready(function(){
        resizeSidebar();
        $(window).on('resize', function(el){
            resizeSidebar();
        });

        function resizeSidebar(){
            $('#left-sidebar').height($(document).height()-$("#header").outerHeight());
            console.log(1);
        }

    });
})(jQuery);