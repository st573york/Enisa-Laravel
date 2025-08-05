@extends('layouts.app')

@section('title', 'Index')

@section('content')

    <main id="enisa-main" class="bg-white">
        <div class="container-fluid">

            <input type="hidden" name="questionnaire-id" id="questionnaire-id" value="" />

            <div class="row ">
                <div class="col-10 offset-1 ps-0">
                    <div class="enisa-breadcrump p-1 d-flex">
                        <nav aria-label="breadcrumb">
                            <ol class="index-nav breadcrumb text-end">
                                <li class="breadcrumb-item"><a href="/">{{ __('Home') }}</a></li>
                                <li class="breadcrumb-item active"><a href="#">{{ __('Surveys') }}</a>
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-10 offset-1">
                    <h1 dusk="surveys_title">{{ __('Manage Surveys') }}</h1>
                </div>
            </div>

            @include('components.alert', ['type' => 'pageAlert'])

            <div class="row mt-5">
                <div class="col-10 offset-1 table-section">
                    <h2 class="mb-2">Surveys <button dusk="create_survey" class="clickable-icon btn-unstyle item-create"
                            data-bs-toggle="tooltip" title="Create survey" type="button"></button></h2>
                    <table dusk="surveys_table" id="questionnaire-table" class="display enisa-table-group">
                        <thead>
                            <tr>
                                <th>{{ __('Title') }}</th>
                                <th>{{ __('Year') }}</th>
                                <th>{{ __('Deadline') }}</th>
                                <th>{{ __('Created by') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <div dusk="survey_manage_modal" class="modal fade" id="pageModal" tabindex="-1" aria-labelledby="pageModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header p-3">
                            <h5 dusk="survey_manage_modal_title" class="modal-title" id="pageModalLabel"></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-3"></div>
                        <div class="modal-footer justify-content-between">
                            <button dusk="survey_manage_modal_close" type="button" class="btn btn-enisa" data-bs-dismiss="modal">{{ __('Close') }}</button>
                            <button dusk="survey_manage_modal_process" type="button" class="btn btn-enisa process-data" id='process-data'></button>
                        </div>
                    </div>
                </div>
            </div>
    </main>

    <script src="{{ mix('mix/js/main.js') }}" defer></script>
    <script src="{{ mix('mix/js/alert.js') }}" defer></script>
    <script src="{{ mix('mix/js/modal.js') }}" defer></script>
    <script src="{{ mix('mix/js/questionnaire-list.js') }}" defer></script>
    <script src="{{ mix('mix/js/datepicker-custom.js') }}" defer></script>
    <script src="{{ mix('mix/js/tinymce-custom.js') }}" defer></script>
    <script src="{{ mix('js/tinymce/tinymce.min.js') }}" referrerpolicy="origin"></script>

    <script>
        var pageModal = new bootstrap.Modal(document.getElementById('pageModal'));
        let table;
        let modal_table;
        let section = '/questionnaire';

        $(document).ready(function() {
            table = $('#questionnaire-table').DataTable({
                "ajax": section + '/admin/management/list',
                "drawCallback": function() {
                    toggleDataTablePagination(this, '#questionnaire-table_paginate');

                    if (localStorage.getItem('pending-export-file') == 1) {
                        updateDownloadButton('downloadInProgress');
                    }
                },
                "columns": [
                    {
                        "data": "title",
                        render: function(data, type, row) {
                            return `<span dusk="datatable_survey_title_${row.id}">${data}</span>`;
                        }
                    },
                    {
                        "data": "year",
                        render: function(data, type, row) {
                            return `<span dusk="datatable_survey_year_${row.id}">${data}</span>`;
                        }
                    },
                    {
                        "data": "deadline",
                        render: function(data, type, row) {
                            if (type === 'sort') {
                                return `<div dusk="datatable_survey_deadline_${row.id}">${data}</div>`;
                            }
                            
                            let deadline = new Date(row.deadline).toLocaleDateString('en-GB').split('/').join('-');

                            return `<div dusk="datatable_survey_deadline_${row.id}">${deadline}</div>`;
                        }
                    },
                    {
                        "data": "created_by",
                        render: function(data, type, row) {
                            return `<span dusk="datatable_survey_created_by_${row.id}">${data}</span>`;
                        }
                    },
                    {
                        "data": "status",
                        render: function(data, type, row) {
                            let obj = getRowData(row.published);

                            return `<div class="d-flex justify-content-center">
                                        <button dusk="datatable_survey_status_${row.id}" class="btn-${obj.status_style} btn-label pointer-events-none" type="button">${obj.status_label}</button>
                                    </div>`;
                        }
                    },
                    {
                        "data": "name",
                        render: function(data, type, row) {
                            let is_questionnaire_published = (row.published) ? true : false;
                            let is_questionnaire_submitted = (row.not_submitted) ? false : true;

                            let questionnaire_dashboard_click = (is_questionnaire_published) ?
                                ' onclick="location.href=\'/questionnaire/admin/dashboard/' + row.id + '\';"' : '';

                            return `<div class="d-flex justify-content-center">
                                        <button dusk="datatable_survey_edit_${row.id}" class="icon-edit btn-unstyle item-edit" data-bs-toggle="tooltip"
                                            title="Edit survey" type="button" item-id="${row.id}">
                                        </button>
                                        <button dusk="datatable_survey_publish_${row.id}" class="icon-publish btn-unstyle item-publish" data-bs-toggle="tooltip"
                                            title="Publish survey" type="button" item-id="${row.id}">
                                        </button>
                                        <button dusk="datatable_survey_send_notifications_${row.id}" class="icon-reminder btn-unstyle item-remind ${(is_questionnaire_submitted) ? 'icon-reminder-deactivated' : ''}"
                                            data-bs-toggle="tooltip" title="Send notifications" type="button" item-id="${row.id}" ${(is_questionnaire_submitted ? 'disabled' : '')}>
                                        </button>
                                        <button dusk="datatable_survey_view_dashboard_${row.id}" class="icon-overview btn-unstyle ${(!is_questionnaire_published) ? 'icon-overview-deactivated' : ''}"
                                            data-bs-toggle="tooltip" title="View survey dashboard" type="button" ${questionnaire_dashboard_click} ${(!is_questionnaire_published ? 'disabled' : '')}>
                                        </button>
                                        <span class="download-section button-spinner-wrapper" item-id="download-survey-template-${row.id}">
                                            <div class="d-none" style="margin-top: 4px; padding: 0 10px 0 10px;">
                                                <i id="button-spinner" class="fa fa-spinner fa-spin"></i>
                                            </div>
                                            <button dusk="datatable_survey_download_template_${row.id}" class="icon-xls-download btn-unstyle item-download"
                                                data-bs-toggle="tooltip" title="Download survey template" type="button" item-id="${row.id}">
                                            </button>
                                        </span>
                                        <button dusk="datatable_survey_delete_${row.id}" class="icon-bin btn-unstyle item-delete ${(is_questionnaire_published) ? 'icon-bin-deactivated' : ''}"
                                            data-bs-toggle="tooltip" title="Delete survey" type="button" item-id="${row.id}" item-name="${row.title}" ${(is_questionnaire_published ? 'disabled' : '')}>
                                        </button>
                                    </div>`;
                        },
                        "orderable": false
                    }
                ]
            });
        });

        $(document).on('click', '.item-create', function() {
            $('.loader').fadeIn();

            $.ajax({
                'url': section + '/create',
                success: function(data) {
                    let obj = {
                        'large': true,
                        'action': 'create',
                        'route': section + '/store',
                        'title': 'New Survey',
                        'html': data
                    };
                    setModal(obj);

                    initDatePicker();
                    initTinyMCE();

                    pageModal.show();
                }
            });
        });

        $(document).on('click', '.item-edit', function() {
            $('.loader').fadeIn();
            let id = $(this).attr('item-id');

            $.ajax({
                'url': section + '/show/' + id,
                success: function(data) {
                    let obj = {
                        'id': id,
                        'large': true,
                        'action': 'edit',
                        'route': section + '/update/' + id,
                        'title': 'Edit Survey',
                        'html': data
                    };
                    setModal(obj);

                    initDatePicker();
                    initTinyMCE();

                    pageModal.show();
                }
            });
        });

        $(document).on('click', '.item-publish', function() {
            $('.loader').fadeIn();
            let id = $(this).attr('item-id');

            $.ajax({
                'url': section + '/publish/show/' + id,
                success: function(data) {
                    let obj = {
                        'id': id,
                        'large': true,
                        'action': 'publish',
                        'route': section + '/publish/create/' + id,
                        'title': 'Publish Survey',
                        'html': data,
                        'btn': 'Publish'
                    };
                    setModal(obj);

                    initModalDataTable(id);

                    pageModal.show();
                }
            });
        });

        $(document).on('click', '.item-remind', function() {
            $('.loader').fadeIn();
            let id = $(this).attr('item-id');

            $.ajax({
                'url': section + '/sendreminder/' + id,
                'type': 'post',
                success: function(data) {
                    table.ajax.reload(null, false);
                }
            });
        });

        $(document).on('click', '.item-download', function() {
            if (localStorage.getItem('pending-export-file') == 1)
            {
                setAlert({
                    'status': 'warning',
                    'msg': 'Another download is in progress. Please wait...'
                });

                return;
            }

            $('.loader').fadeIn();
            let id = $(this).attr('item-id');
            let element = $(this).parent().attr('item-id');

            $.ajax({
                'url': '/export/surveyexcel/create/' + id,
                'type': 'post',
                'data': {
                    'type': 'survey_template'
                },
                success: function() {
                    localStorage.setItem('pending-export-file', 1);
                    localStorage.setItem('pending-export-task', 'ExportSurveyExcel');
                    localStorage.setItem('pending-element-id', element);

                    updateDownloadButton('downloadInProgress');

                    pollExportFile();
                }
            });
        });

        $(document).on('click', '.item-delete', function() {
            let id = $(this).attr('item-id');
            let name = $(this).attr('item-name');
            let data = `<input hidden id="item-id"/>
                        <input hidden id="item-action"/>
                        <input hidden id="item-route"/>
                        <div dusk="survey_delete_modal_text" class="warning-message">
                            Survey '${name}' will be deleted. Are you sure?
                        </div>`;

            let obj = {
                'modal': 'warning',
                'id': id,
                'action': 'delete',
                'route': section + '/delete/' + id,
                'title': 'Delete Survey',
                'html': data,
                'btn': 'Delete'
            };
            setModal(obj);

            pageModal.show();
        });

        $(document).on('click', '#process-data', function() {
            $('.loader').fadeIn();
            let route = $('.modal #item-route').val();
            let data = getFormData();
            if ($('.modal #item-id').length &&
                $('.modal #item-id').val()) {
                data.append('id', $('.modal #item-id').val());
            }

            $.ajax({
                'url': route,
                'type': 'post',
                'data': data,
                'contentType': false,
                'processData': false,
                success: function(data) {
                    pageModal.hide();
                    table.ajax.reload(null, false);
                },
                error: function(req) {
                    if (req.status == 400)
                    {
                        if (req.responseJSON.type == 'pageModalForm') {
                            showInputErrors(req.responseJSON.errors);
                        }
                    }

                    if (req.responseJSON.type &&
                        req.responseJSON.error)
                    {
                        if (req.responseJSON.type == 'pageAlert') {
                            pageModal.hide();
                        }

                        setAlert({
                            'type': req.responseJSON.type,
                            'status': 'error',
                            'msg': req.responseJSON.error
                        });
                    }
                }
            });
        });

        $(document).on('change', '.notify_users', function() {
            $('#notify-users-table_wrapper').parent('div').toggle(this.id == 'radio-specific');
            $('.close-alert').trigger('click');
        });

        $(document).on('show', '#formDate', function() {
            if (datepickerLimit) {
                $(this).datepicker('setStartDate', new Date());
                datepickerLimit = false;
            }
        });

        function getRowData(is_published) {
            let obj = {};

            switch (is_published) {
                case 0:
                    obj.status_label = 'Draft';
                    obj.status_style = 'positive-invert';
                    break;
                case 1:
                    obj.status_label = 'Published';
                    obj.status_style = 'positive';
                    break;
            }

            return obj;
        }

        function initModalDataTable(id) {
            modal_table = $('#notify-users-table').DataTable({
                "ajax": section + '/users/' + id,
                "drawCallback": function() {
                    toggleDataTablePagination(this, '#notify-users-table_paginate');
                },
                "order": [
                    [4, 'asc']
                ],
                "createdRow": function(row, data, dataIndex) {
                    if (data.notified) {
                        $(row).addClass('row-disabled');
                    }
                },
                "columns": [{
                        "data": "id",
                        render: function(data, type, row) {
                            return `<input dusk="datatable_user_select_${row.id}" class="item-select form-check-input" type="checkbox" id="item-${row.id}" ${(row.notified ? 'disabled checked' : '')}/>`;
                        },
                        "orderable": false
                    },
                    {
                        "data": "name",
                        render: function(data, type, row) {
                            return `<div dusk="datatable_user_name_${row.id}">${data}</div>`;
                        }
                    },
                    {
                        "data": "email",
                        render: function(data, type, row) {
                            return `<div dusk="datatable_user_email_${row.id}">${data}</div>`;
                        }
                    },
                    {
                        "data": "role",
                        render: function(data, type, row) {
                            return `<div dusk="datatable_user_role_${row.id}">${data}</div>`;
                        }
                    },
                    {
                        "data": "country",
                        render: function(data, type, row) {
                            return `<div dusk="datatable_user_country_${row.id}">${data}</div>`;
                        }
                    }
                ]
            });

            $('#notify-users-table_wrapper').parent('div').hide();
        }
    </script>
@endsection
