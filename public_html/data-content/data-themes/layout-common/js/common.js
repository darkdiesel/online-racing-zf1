$(document).ready(function () {
    $(".block-config-btn").hover(function (o) {
        $(this).closest(".block").css({
            "outline": "2px dashed red"
        });
    }, function (o) {
        $(this).closest(".block").css({
            "outline": "none"
        });
    });
    /* Tooltip fields supports by bootstrap js library */
    $('.tooltip-field').tooltip();

    /* Add responsive class for images */
    $('body').find('img').each(function (el) {
        if (!$(this).hasClass('img-responsive')) {
            $(this).addClass('img-responsive');
        }
    });

    $('.img-magnify').each(function (e) {
        //$(this).attr('data-toggle', 'magnify');
        $(this).magnify();
    });

});