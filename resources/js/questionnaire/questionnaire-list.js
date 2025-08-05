window.fillInOffline = function(obj) {  
    $('.loader').fadeIn();

    skipFadeOut = false;

    $('#questionnaire-id').val(obj.questionnaire_id);
    $('#questionnaire-country-id').val(obj.questionnaire_country_id);

    $.ajax({
        'url': '/questionnaire/offline/validate/' + obj.questionnaire_country_id,
        success: function () {
            pageModal.show();

            $('#pageModal .alert').addClass('d-none');

            if (localStorage.getItem('pending-export-file') == 1) {
                updateDownloadButton('downloadInProgress');
            }
        },
        error: function (req) {
            setAlert({
                'status': 'warning',
                'msg': req.responseJSON.warning
            });
        }
    });
}

$(document).on('change', '#formFile', function (e) {
    e.preventDefault();

    $('.loader').fadeIn();

    skipFadeOut = true;

    let questionnaire_country_id = $('#questionnaire-country-id').val();
    let data = getFormData();

    $.ajax({
        'url': '/questionnaire/upload/' + questionnaire_country_id,
        'type': 'POST',
        'data': data,
        'contentType': false,
        'processData': false,
        success: function () {
            viewSurvey({
                'questionnaire_country_id': questionnaire_country_id
            });
        },
        error: function (req) {
            $('.loader').fadeOut();

            if (req.status == 409)
            {
                explicitModal.show();
                
                let list = '<ul dusk="survey_import_alert_indicators">';
                $.each(req.responseJSON.list, function (key, val) {
                    list += `<li>${val}</li>`;
                })
                list += '</ul>';
                let body =
                    `<div class="alert alert-warning" role="alert">
                        <h5>${req.responseJSON.message}</h5>
                        <hr>
                        ${list}
                    </div>`;

                $('#explicit-upload-body').html(body);
                $('#explicit-filename').val(req.responseJSON.filename);
            } 
            else
            {
                let type = 'error';
                let message = '';

                if (req.status == 400)
                {
                    let messages = [];
                    $.each(req.responseJSON, function (key, val) {
                        messages.push(val);
                    });
                    message = messages.join(' ');
                }
                else if (req.status == 403)
                {
                    type = Object.keys(req.responseJSON)[0];
                    message = req.responseJSON[type];
                }
                else if (req.status == 413) {
                    message = 'The file is too large. Size should be less than or equal to 2MB';
                }

                setAlert({
                    'status': type,
                    'msg': message
                });
            }

            pageModal.hide();
        }
    });
});

$(document).on('click', '#submit-explicit', function () {
    $('.loader').fadeIn();
    
    skipFadeOut = true;

    let questionnaire_country_id = $('#questionnaire-country-id').val();
    let data = getFormData();
    data.append('explicit_flag', true);

    $.ajax({
        'url': '/questionnaire/upload/' + questionnaire_country_id,
        'type': 'POST',
        'data': data,
        'contentType': false,
        'processData': false,
        success: function () {
            viewSurvey({
                'questionnaire_country_id': questionnaire_country_id
            });
        },
        error: function (req) {
            $('.loader').fadeOut();

            let type = 'error';
            let message = '';

            if (req.status == 400)
            {
                let messages = [];
                $.each(req.responseJSON, function (key, val) {
                    messages.push(val);
                });
                message = messages.join(' ');
            }
            else if (req.status == 403)
            {
                type = Object.keys(req.responseJSON)[0];
                message = req.responseJSON[type];
            }
            else if (req.status == 409)
            {    
                type = 'warning';
                message = req.responseJSON.message;

                let list = '<ul style="padding-top: 10px; padding-left: 3rem;">';
                $.each(req.responseJSON.list, function (key, val) {
                    list += `<li>${val}</li>`;
                })
                list += '</ul>';

                message += `<br>${list}`;
            } 
            else if (req.status == 413) {
                message = 'The file is too large. Size should be less than or equal to 2MB';
            }

            setAlert({
                'status': type,
                'msg': message
            });

            explicitModal.hide();
        }
    });
});

