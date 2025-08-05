window.datepickerLimit = true;

window.initDatePicker = function() {
    $('.datepicker').datepicker({
        format: 'dd-mm-yyyy',
        todayHighlight: true,
        autoclose: true
    })
    .on('hide', function(e) {
        datepickerLimit = true;
        e.stopPropagation();
    });
}
