$(document).on('click', '.close-alert', function () {
    $('.alert-wrapper').addClass('d-none');
});

window.setAlert = function (obj) {
    let style;
    let aria_label;
    let link_href;
    let type = (obj.type) ? obj.type : 'pageAlert';

    switch (obj.status) {
        case 'success':
            style = 'success';
            aria_label = 'Success';
            link_href = 'check-circle-fill';

            $('.alert').removeClass('alert-warning alert-danger');

            break;
        case 'warning':
            style = 'warning';
            aria_label = 'Warning';
            link_href = 'exclamation-triangle-fill';

            $('.alert').removeClass('alert-success alert-danger');

            break;
        case 'error':
            style = 'danger';
            aria_label = 'Danger';
            link_href = 'exclamation-triangle-fill';

            $('.alert').removeClass('alert-success alert-warning');

            break;
    }

    let alert = $('.alert.' + type);

    alert.addClass('alert-' + style).parents('.d-none').removeClass('d-none');
    alert.find('span').html(obj.msg);
    alert.find('.svg-alert').attr('aria-label', aria_label + ':').removeClass('d-none').children('use').attr('xlink:href', '#' + link_href);

    scrollToTop();
}