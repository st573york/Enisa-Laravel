@extends('layouts.app')

@section('title', 'Index')

@section('content')
    <main id="enisa-main" class="bg-white">
        <div class="container-fluid ">
            <div class="row ps-0">
                <div class="col-10 offset-1 ps-0">
                    <div class="enisa-breadcrump d-flex">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb text-end">
                                <li class="breadcrumb-item"><a href="/">Home</a></li>
                                <li class="breadcrumb-item active"><a href="#">Surveys</a></li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-10 offset-1 ps-0">
                    <h1 dusk="surveys_title">{{ __('List of Surveys') }}</h1>
                </div>
            </div>

            @include('components.alert', ['type' => 'pageAlert'])

            @php
                $is_poc = Auth::user()->isPoC();
                $is_primary_poc = Auth::user()->isPrimaryPoC();
                $is_operator = Auth::user()->isOperator();
            @endphp

            @if ($is_poc || empty($questionnaires) || !empty($questionnaires_assigned))
                <div class="row mt-5">
                    <div class="col-10 offset-1 table-section">
                        <h2 class="mb-2">Surveys</h2>
                        <table dusk="surveys_table" id="questionnaire-table" class="display enisa-table-group">
                            <thead>
                                <tr>
                                    <th>{{ __('Survey') }}</th>
                                    <th>{{ __('Year') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    @if ($is_poc)
                                        <th>{{ __('Submitted By') }}</th>
                                    @endif
                                    <th>{{ __('Deadline') }}</th>
                                    <th></th>
                                    <th></th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Fill in offline modal --}}
            <div dusk="survey_fill_in_offline_modal" class="modal fade" id="pageModal" tabindex="-1"
                aria-labelledby="pageModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <input type="hidden" name="questionnaire-id" id="questionnaire-id" value=""/>
                        <input type="hidden" name="questionnaire-country-id" id="questionnaire-country-id" value=""/>
                        <div class="modal-header p-3">
                            <h5 class="modal-title" id="pageModalLabel">Fill In Survey Offline</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-3">
                            <div class="alert alert-warning mb-2 d-none" role="alert"></div>
                            <p>You can download the following template in order to fill your survey offline, and
                                afterwards you import your filled in data.</p>
                            <div class="d-flex gap-2 justify-content-end ">
                                <span class="download-section" item-id="download-survey-template">
                                    <button dusk="survey_download_template" type="button" class="btn btn-enisa download">
                                        <i id="button-spinner" class="fa fa-spinner fa-spin d-none"></i>
                                        <span class="in-progress d-none">Downloading Template</span>
                                        <span class="start">Download Template</span>
                                    </button>
                                </span>
                            </div>
                        </div>
                        <div class="modal-footer justify-content-end">
                            <form id="upload-form" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="file" name="file" id="formFile" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"/>
                                <button dusk="survey_import_template" type="button" class="btn btn-enisa-invert"
                                    onclick="$('#formFile').click();">{{ __('Import Completed Survey') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div dusk="survey_import_template_conflict_modal" class="modal fade" id="explicitModal" tabindex="-1"
                aria-labelledby="explicitModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header p-3">
                            <h5 class="modal-title" id="explicitModalLabel">Indicator Conflict</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-3">
                            <p dusk="survey_import_alert_message" id="explicit-upload-body"></p>
                        </div>
                        <div class="modal-footer justify-content-between">
                            <button dusk="survey_import_cancel" type="button" class="btn btn-enisa" data-bs-dismiss="modal">Cancel</button>
                            <button dusk="survey_import_continue" type="button" class="btn btn-enisa-invert w-100" type="submit" id="submit-explicit">Continue</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="printSurveyModal" class="printSurveyModalPrint" tabindex="-1" aria-labelledby="printSurveyModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header p-3">
                            <h5 class="modal-title" id="printSurveyModalLabel">Preview Survey With Answers</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-12 mt-2">
                                    <div class="row survey-print" id="surveyContainer"></div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer justify-content-between">
                            <button type="button" class="btn btn-enisa" data-bs-dismiss="modal">{{ __('Close') }}</button>
                            <button type="button" class="btn btn-enisa" onclick="saveReportPDF();" href="javascript:;">{{ __('Export | PDF') }}</button>
                        </div>
                    </div>
                </div>
            </div>
    </main>

    <script src="{{ mix('mix/js/main.js') }}" defer></script>
    <script src="{{ mix('mix/js/alert.js') }}" defer></script>
    <script src="{{ mix('mix/js/questionnaire-list.js') }}" defer></script>

    <script>
        var pageModal = new bootstrap.Modal(document.getElementById('pageModal'));
        var explicitModal = new bootstrap.Modal(document.getElementById('explicitModal'));
        var printSurveyModal = new bootstrap.Modal(document.getElementById('printSurveyModal'));
        let section = '/questionnaire';
        let is_poc = "{{ $is_poc }}";
        let is_primary_poc = "{{ $is_primary_poc }}";
        let is_operator = "{{ $is_operator }}";
        let questionnaires = <?php echo json_encode($questionnaires); ?>;
        let questionnaires_assigned = <?php echo json_encode($questionnaires_assigned); ?>;
        let user_group = <?php echo json_encode(config('constants.USER_GROUP')); ?>;

        $(document).ready(function() {
            if (!is_poc &&
                questionnaires.length &&
                !questionnaires_assigned.length) {
                setAlert({
                    'status': 'warning',
                    'msg': 'You haven\'t been assigned any indicators.'
                });

                return;
            }

            table = $('#questionnaire-table').DataTable({
                "data": questionnaires_assigned,
                "drawCallback": function() {
                    toggleDataTablePagination(this, '#questionnaire-table_paginate');
                },
                "columns": getDataTableColumns()
            });
        });

        $(document).on('click', '.download', function() {
            if (localStorage.getItem('pending-export-file') == 1)
            {
                $('#pageModal .alert').html('Another download is in progress. Please wait...').removeClass('d-none');

                return;
            }

            $('.loader').fadeIn();
            let questionnaire_id = $('#questionnaire-id').val();
            let questionnaire_country_id = $('#questionnaire-country-id').val();
            let element = $(this).parent().attr('item-id');

            $.ajax({
                'url': '/export/surveyexcel/create/' + questionnaire_id,
                'type': 'post',
                'data': {
                    'type': 'survey_template',
                    'questionnaire_country_id': questionnaire_country_id
                },
                success: function () {
                    localStorage.setItem('pending-export-file', 1);
                    localStorage.setItem('pending-export-task', 'ExportSurveyExcel');
                    localStorage.setItem('pending-element-id', element);

                    updateDownloadButton('downloadInProgress');

                    pollExportFile();
                }
            });
        });

        $(document).on('click', '.item-download', function() {
            if (localStorage.getItem('pending-export-file') == 1)
            {
                $('#pageModal .alert').html('Another download is in progress. Please wait...').removeClass('d-none');

                return;
            }

            $('.loader').fadeIn();
            let obj = JSON.parse($(this).attr('item-obj'));
            let element = $(this).parent().attr('item-id');

            $.ajax({
                'url': '/export/surveyexcel/create/' + obj.questionnaire_id,
                'type': 'post',
                'data': {
                    'type': 'survey_with_answers',
                    'questionnaire_country_id': obj.questionnaire_country_id
                },
                success: function () {
                    localStorage.setItem('pending-export-file', 1);
                    localStorage.setItem('pending-export-task', 'ExportSurveyExcel');
                    localStorage.setItem('pending-element-id', element);

                    updateDownloadButton('downloadInProgress');

                    pollExportFile();
                }
            });
        });

        function getDataTableColumns()
        {
            let columns = [];

            columns.push(
            {
                "data": "questionnaire_title",
                render: function(data, type, row) {
                    return '<span dusk="survey_title_' + row.questionnaire_country_id + '">' + data + '</span>';
                }
            },
            {
                "data": "questionnaire_year"
            },
            {
                "data": "status",
                render: function(data, type, row) {
                    let obj = getRowData(row);

                    return `<div class="d-flex justify-content-center">
                                <button dusk="survey_status_${row.questionnaire_country_id}" class="btn-${obj.status_style} btn-label btn-with-tooltip" data-bs-toggle="tooltip" title="${obj.status_info}" type="button">${obj.status_label}</button>
                           </div>`;
                }
            });

            if (is_poc) {
                columns.push({
                    "data": "submitted_by",
                    render: function(data, type, row) {
                        return '<span dusk="survey_submitted_by_' + row.questionnaire_country_id + '">' + (data ? data : '') + '</span>';
                    }
                });
            }

            columns.push(
            {
                "data": "questionnaire_deadline"
            },
            {
                "data": "fill_in_actions",
                render: function(data, type, row) {
                    let obj = $.extend(true, {}, row);
                    delete obj.questionnaire_json_data;
                    
                    let can_fill_in = (!row.indicators_assigned_exact || ((row.indicators_submitted || row.completed) && !is_primary_poc) || row.submitted_by) ? false : true;
                    
                    return `<div class="d-flex justify-content-center">
                                <button dusk="survey_fill_in_online_${row.questionnaire_country_id}" type="button" class="btn-unstyle p-0 ${(!can_fill_in ? 'view-btn-deactivated' : '')}" ${(!can_fill_in ? 'disabled' : '')}>
                                    <a dusk="survey_fill_in_online_link_${row.questionnaire_country_id}" onclick='viewSurvey(${JSON.stringify(obj)});' href="javascript:;" class="pointer-event-none view-btn ${(!can_fill_in ? 'view-btn-deactivated' : '')}">
                                        <span>Fill in online</span>
                                    </a>
                                </button>
                                &nbsp;&nbsp;
                                <button dusk="survey_fill_in_offline_${row.questionnaire_country_id}" type="button" class="btn-unstyle p-0 ${(!can_fill_in ? 'view-btn-deactivated' : '')}" ${(!can_fill_in ? 'disabled' : '')}>
                                    <a dusk="survey_fill_in_offline_link_${row.questionnaire_country_id}" onclick='fillInOffline(${JSON.stringify(obj)});' href="javascript:;" class="pointer-event-none view-btn ${(!can_fill_in ? 'view-btn-deactivated' : '')}">
                                        <span>Fill in offline</span>
                                    </a>
                                </button>
                            </div>`;
                },
                "orderable": false
            },
            {
                "data": "questionnaire_actions",
                render: function(data, type, row) {
                    let obj = $.extend(true, {}, row);
                    delete obj.questionnaire_json_data;

                    let can_view = ((row.indicators_submitted && is_operator) || row.submitted_by) ? true : false;

                    let view_dashboard_button = '';
                    let view_summary_data_button = '';
                    let download_pdf = '';
                    let download_excel = '';
                    
                    if (is_poc)
                    {
                        view_dashboard_button =
                            `<button dusk="survey_view_dashboard_${row.questionnaire_country_id}" class="icon-overview btn-unstyle" data-bs-toggle="tooltip"
                                title="View survey dashboard" type="button" onclick="location.href='/questionnaire/dashboard/management/${row.questionnaire_country_id}';">
                            </button>`;
                        view_summary_data_button =
                            `<button class="icon-summary btn-unstyle" data-bs-toggle="tooltip" title="View survey summary data" type="button"
                                onclick="location.href=\'/questionnaire/dashboard/summarydata/${row.questionnaire_country_id}\';">
                            </button>`;
                        download_pdf =
                            `<button dusk="survey_download_pdf_${row.questionnaire_country_id}" class="icon-pdf-download btn-unstyle" data-bs-toggle="tooltip"
                                title="Preview survey with answers" type="button" onclick="javascript:previewSurveyWithAnswers(${row.questionnaire_country_id});">
                            </button>`;
                        download_excel =
                            `<span class="download-section button-spinner-wrapper" item-id="download-survey-template-with-answers-${row.questionnaire_country_id}">
                                <div class="d-none" style="margin-top: 4px; padding: 0 10px 0 10px;">
                                    <i id="button-spinner" class="fa fa-spinner fa-spin"></i>
                                </div>
                                <button class="icon-xls-download btn-unstyle item-download"
                                    title="Download survey with answers (excel)" data-bs-toggle="tooltip" item-id="${row.questionnaire_country_id}" item-obj='${JSON.stringify(obj)}'>
                                </button>
                            </span>`;
                    }

                    return `<div class="d-flex justify-content-center">
                                <button dusk="survey_view_${row.questionnaire_country_id}" class="icon-show btn-unstyle ${(!can_view ? 'icon-show-deactivated' : '')}" data-bs-toggle="tooltip"
                                    title="View survey" type="button" ${(!can_view ? 'disabled' : '')} onclick='viewSurvey(${JSON.stringify(obj)});'>
                                </button>
                                ${view_dashboard_button}
                                ${view_summary_data_button}
                                ${download_pdf}
                                ${download_excel}
                            </div>`;
                },
                "orderable": false
            });

            return columns;
        }

        function viewSurvey(obj)
        {
            let form = `<form action="/questionnaire/view/${obj.questionnaire_country_id}" method="POST">
                            @csrf
                            <input type="hidden" name="action" value="view"/>
                        </form>`;

            $(form).appendTo($(document.body)).submit();
        }

        function previewSurveyWithAnswers(id)
        {
            $('.loader').fadeIn();

            skipFadeOut = true;

            $.ajax({
                'url': '/questionnaire/preview',
                'data': {
                    'with_answers': true,
                    'questionnaire_country_id': id
                },
                success: function() {
                    printSurveyModal.show();
                }
            }).done(function(data) {
                /*** setTimeout needed to give time for pajed.js to render page ***/
                setTimeout(() => {
                    $('#surveyContainer').empty();
                        
                    let iframe = document.createElement('iframe');

                    iframe.setAttribute('id', 'surveyIframe');
                    iframe.style.height = '70vh';

                    document.getElementById('surveyContainer').appendChild(iframe);

                    iframe.onload = function () {
                        let css_files = [
                            "{{ mix('css/app.css') }}",
                            "{{ asset('css/bootstrap.min.css') }}",
                            "{{ asset('css/survey-preview.css') }}"
                        ];

                        css_files.forEach((href) => {
                            let css_link = document.createElement('link');
                            
                            css_link.rel = 'stylesheet';
                            css_link.href = href;
                            css_link.type = 'text/css';
                            
                            iframe.contentDocument.head.appendChild(css_link);
                        });
                    };

                    iframe.contentWindow.document.open();
                    iframe.contentWindow.document.write(data);
                    iframe.contentWindow.document.close();
                }, 1000);
            });
        }

        function saveReportPDF()
        {
            let printIframe = document.getElementById('surveyIframe').contentWindow;

             printIframe.focus();
             printIframe.print();

            return false;
        }

        function getRowData(row)
        {
            let obj = {
                status_label: 'Pending',
                status_style: 'positive-invert-with-tooltip',
                status_info: 'Indicators have been assigned by the PPoC and are pending completion.'
            };

            if (((!row.indicators_assigned_exact || row.indicators_submitted || row.completed) && !is_primary_poc) ||
                row.submitted_by)
            {
                obj.status_label = (is_primary_poc) ? 'Submitted' : 'Completed';
                obj.status_style = 'positive-with-tooltip';
                obj.status_info = (is_primary_poc)
                    ? 'Survey has been submitted by the MS and is under review by ' + user_group + '. Clarifications or changes may be requested.'
                    : 'The assignee has submitted their answers, pending approval by the PPoC.';
            }

            return obj;
        }

        window.addEventListener('pagedRendered', function() {
            $('.loader').fadeOut();

            skipFadeOut = false;
        });
    </script>
@endsection
