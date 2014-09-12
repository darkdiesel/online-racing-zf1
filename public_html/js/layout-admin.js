(function ($) {
    $(document).ready(function () {
        resizeSidebar();
        $(window).on('resize', function (el) {
            resizeSidebar();
        });

        function resizeSidebar() {
            $('#left-sidebar').height($(document).height() - $("#header").outerHeight());
        }

        $('#digital-clock').clock({ type: 'digital'});
    });
})(jQuery);