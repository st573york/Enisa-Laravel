window.skipClearErrors = false;

$(document).on('ajaxSend', function () {
    if (!skipClearErrors)
    {
        clearInputErrors();
        clearPageErrors();
    }
});

window.getFormData = function() {
    // Get values from all form inputs, exclude radio buttons - processed below
    let form = $('form').not(':input[type=radio]')[0];

    // FormData object
    let fd = new FormData(form);
    
    // Get values from datatables
    if ($.fn.DataTable.isDataTable('.enisa-table-group') && 
        $('.enisa-table-group').find('.item-select').length)
    {
        let table = $('.enisa-table-group').DataTable();
        let rows = table.$('.item-select', {'page': 'all'});        

        let selected = [];
        let all = [];
        
        rows.each(function() {
            let id = this.id.replace('item-', '');
            if (this.checked) {
                selected.push(id);
            }
            all.push(id);
        });

        fd.append('datatable-selected', selected);
        fd.append('datatable-all', all);
    }

    // Get values from all checkboxes - convert 'on' value to integer
    $('form input[type=checkbox]').each(function() { 
        fd.append(this.name, (this.checked ? 1 : 0));    
    });

    // Get value from selected radio
    $('form input[type=radio]:checked').each(function() {
        fd.append(this.name, this.id);    
    });

    // Get values from all tinymce editors
    $('.tinymce').each(function() {
        let editor = tinymce.get(this.id);
        let text_content = $.trim(editor.getContent({format: 'text'}));
        let raw_content = editor.getContent({format: 'raw'}).replace(/&nbsp;/g, '');
        let content = (text_content) ? encodeURIComponent(raw_content) : text_content;

        fd.append(this.name, content);
    });

    // Get values from uploaded files
    $('form input[type=file]').each(function() {
        if (this.files.length) 
        {
            let CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute("content");           

            fd.append('file', this.files[0]);
            fd.append('_token', CSRF_TOKEN);
        }
    });

    return fd;
}

window.clearInputErrors = function() {
    $('form, .wizard-fieldset.show').find(':input').removeClass('is-invalid');
    $('form, .wizard-fieldset.show').find('.invalid-feedback').empty();
}

window.clearPageErrors = function() {
    $('.close-alert').trigger('click');
}

window.showInputErrors = function(data) {
    for (var key in data) 
    {
        let input = $('form, .wizard-fieldset.show').find(':input[name="' + key + '"]');
        input.addClass('is-invalid');
        if (input.nextAll('.invalid-feedback:first').length) {
            input.nextAll('.invalid-feedback:first').html(data[ key ][0]).show();
        }
        else if (input.parents().nextAll('.invalid-feedback:first').length) {
            input.parents().nextAll('.invalid-feedback:first').html(data[ key ][0]).show();
        }
    }
}

window.toggleDataTablePagination = function(that, selector) {
    let api = that.api();
    let pages = api.page.info().pages;
    
    $(selector).toggle((pages > 1));
}

window.toggleRoles = function(user_group) {
    let country = $('option:selected', 'select[name="country"]').val();
    let role = $('option:selected', 'select[name="role"]').val();

    switch (country)
    {
        case user_group:
            if (role != 'admin' &&
                role != 'viewer')
            {
                $('select[name="role"]').val($('select[name="role"] option:first').val());
            }

            break;
        default:
            if (role == 'admin') {
                $('select[name="role"]').val($('select[name="role"] option:first').val());
            }

            break;
    }
}

window.toggleCountries = function(user_group) {
    let country = $('option:selected', 'select[name="country"]').val();
    let role = $('option:selected', 'select[name="role"]').val();

    switch (role)
    {
        case 'admin':
            if (country != user_group) {
                $('select[name="country"]').val($('select[name="country"] option:first').val());
            }

            break;
        case 'Primary PoC':
        case 'PoC':
        case 'operator':
            if (country == user_group) {
                $('select[name="country"]').val($('select[name="country"] option:first').val());
            }

            break;
        default:
            break;
    }
}