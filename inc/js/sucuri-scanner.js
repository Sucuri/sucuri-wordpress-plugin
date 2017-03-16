
function sucuriscan_alert_close (id) {
    var a = document.getElementById('sucuriscan-alert-' + id);
    a.parentNode.removeChild(a);
}

jQuery(document).ready(function ($) {
    $('.sucuriscan-modal-btn').on('click', function (event) {
        event.preventDefault();

        var modalid = $(this).data('modalid');

        $('div.' + modalid).removeClass('sucuriscan-hidden');
    });

    $('.sucuriscan-overlay, .sucuriscan-modal-close').on('click', function(event) {
        event.preventDefault();

        $('.sucuriscan-overlay').addClass('sucuriscan-hidden');
        $('.sucuriscan-modal').addClass('sucuriscan-hidden');
    });

    if ($('.sucuriscan-tabs').length) {
        var d = 'sucuriscan-hidden';
        var b = 'sucuriscan-tab-active';
        var a = location.href.split('#')[1];

        $('.sucuriscan-tabs > ul a').on('click', function (event) {
            event.preventDefault();

            var tabbtn = $(this);
            var tabname = tabbtn.data('tabname');
            var f = $('.sucuriscan-tab-containers > #sucuriscan-' + tabname);

            if (f.length) {
                var g = location.href.replace(location.hash, '');
                var i = g + '#' + tabname;

                window.history.pushState({}, document.title, i);

                $('.sucuriscan-tabs > ul a').removeClass(b);
                $('.sucuriscan-tab-containers > div').addClass(d);

                tabbtn.addClass(b);
                f.removeClass(d);
            }
        });

        $('.sucuriscan-tab-containers > div').addClass(d);

        if (a !== undefined) {
            $('.sucuriscan-tabs > ul li a').each(function(e, f) {
                if ($(f).data('tabname') === a) {
                    $(f).trigger('click');
                }
            });
        } else {
            $('.sucuriscan-tabs > ul li:first-child a').trigger('click');
        }
    }

    $('body').on('click', '.sucuriscan-reveal', function (event) {
        event.preventDefault();

        var target = $(this).attr('data-target');
        $('.sucuriscan-' + target).removeClass('sucuriscan-hidden');
    });

    $('body').on('click', '.sucuriscan-corefiles .manage-column :checkbox', function () {
        $('.sucuriscan-corefiles tbody :checkbox').each(function(key, element) {
            var checked = $(element).is(':checked');
            $(element).attr('checked', !checked);
        });
    });
});
