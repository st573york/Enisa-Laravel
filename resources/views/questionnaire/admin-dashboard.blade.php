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
                                <li class="breadcrumb-item"><a href="/questionnaire/admin/management">{{ __('Surveys') }}</a></li>
                                <li class="breadcrumb-item active"><a href="#">{{ __('Survey Dashboard') }} - {!! $questionnaire->title !!}</a>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-10 offset-1 ps-0">
                    <h1 dusk="dashboard_survey_title">{{ __('Survey Dashboard') }} - {!! $questionnaire->title !!}</h1>
                </div>
            </div>

            @include('components.alert', ['type' => 'pageAlert'])

            <div class="row mt-3">
                <div class="col-10 offset-1 ps-0 pe-0">
                    <div dusk="dashboard_survey_actions" class="table-section col-12 mt-2">
                        <div class="row">
                            <div class="col-12 col-lg-6 col-xl-6">
                                <div class="d-flex gap-2">
                                    <div>
                                        <h2 class="mt-2">{{ __('Survey') }} -</h2>
                                    </div>
                                    <div class="mt-1">
                                        <select dusk="dashboard_survey_loaded" class="form-select loaded-questionnaire" name="loaded_questionnaire" id="questionnaire-year-select">
                                            <option value="" data-year="" disabled>{{ __('Choose...') }}</option>
                                            @foreach ($published_questionnaires as $questionnaire_data)
                                                <option value={{ $questionnaire_data->id }} data-year={{ $questionnaire_data->year }}
                                                    {{ $questionnaire->id == $questionnaire_data->id ? 'selected' : '' }}>
                                                    {!! $questionnaire_data->title !!}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-6 col-xl-6">
                                <div class="d-flex gap-2 justify-content-end ps-0 pe-0">
                                    <button dusk="dashboard_indicator_values" type="button" class="btn btn-enisa-invert"
                                        onclick="location.href='/questionnaire/admin/dashboard/indicatorvalues/{{ $questionnaire->id }}'">{{ __('Indicator Values') }}
                                    </button>
                                    <span class="download-section" item-id="download-survey-data-collection">
                                        <button dusk="dashboard_download_data" type="button" class="btn btn-enisa-invert download" item-id="all">
                                            <i id="button-spinner" class="fa fa-spinner fa-spin d-none"></i>
                                            <span class="in-progress d-none">{{ __('Downloading Data') }}</span>
                                            <span class="start">{{ __('Download Data') }}</span>
                                        </button>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-5">
                <div class="col-10 offset-1 table-section">
                    <h2 class="mb-2">Dashboard</h2>
                    <table dusk="dashboard_table" id="questionnaire-admin-dashboard-table" class="display enisa-table-group">
                        <thead>
                            <tr>
                                <th>{{ __('Country') }}</th>
                                <th>{{ __('Progress') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('PPoC') }}</th>
                                <th>{{ __('Survey Submission') }}</th>
                                <th>{{ __('Requests Submission') }}</th>
                                <th>{{ __('Requests Deadline') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <div class="modal fade" id="pageModal" tabindex="-1" aria-labelledby="pageModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header p-3">
                            <h5 class="modal-title" id="pageModalLabel"></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-3"></div>
                        <div class="modal-footer justify-content-between">
                            <button type="button" class="btn btn-enisa"
                                data-bs-dismiss="modal">{{ __('Close') }}</button>
                            <button type="button" class="btn btn-enisa process-data" id='process-data'></button>
                        </div>
                    </div>
                </div>
            </div>
    </main>

    <script src="{{ mix('mix/js/main.js') }}" defer></script>
    <script src="{{ mix('mix/js/modal.js') }}" defer></script>
    <script src="{{ mix('mix/js/alert.js') }}" defer></script>

    <script>
        var pageModal = new bootstrap.Modal(document.getElementById('pageModal'));
        let table;
        let section = '/questionnaire';
        let questionnaire_id = '{{ $questionnaire->id }}';

        $(document).ready(function() {
            table = $('#questionnaire-admin-dashboard-table').DataTable({
                "ajax": section + '/admin/dashboard/list/' + questionnaire_id,
                "drawCallback": function() {
                    convertToLocalTimestamp();

                    toggleDataTablePagination(this, '#questionnaire-admin-dashboard-table_paginate');

                    if (localStorage.getItem('pending-export-file') == 1) {
                        updateDownloadButton('downloadInProgress');
                    }
                },
                "columns": [
                    {
                        "data": "country_name",
                        render: function(data, type, row) {
                            return `<span dusk="datatable_country_name_${row.id}">${data}</span>`;
                        }
                    },
                    {
                        "data": "in_progress",
                        render: function(data, type, row) {
                            if (row.percentage_in_progress != null &&
                                row.percentage_approved != null)
                            {
                                return `<div style="width: max-content;">
                                            <div class="d-flex">
                                                <div dusk="datatable_in_progress_${row.id}" class="d-flex justify-content-end progress-percentage pe-1">${(row.submitted_by != null) ? '-' : row.percentage_in_progress + '%'}</div>
                                                <div class="progress-percentage-label">In progress</div>
                                            </div>
                                            <div class="d-flex">
                                                <div dusk="datatable_approved_${row.id}" class="d-flex justify-content-end progress-percentage pe-1">${row.percentage_approved}%</div>
                                                <div class="progress-percentage-label">Approved</div>
                                            </div>
                                        </div>`;
                            }
                            else {
                                return `<div class="d-flex justify-content-center">-</div>`;
                            }
                        }
                    },
                    {
                        "data": "status",
                        render: function(data, type, row) {
                            let status = (row.style && row.status) ? `<button dusk="datatable_status_${row.id}" class="btn-${row.style} btn-label btn-with-tooltip" data-bs-toggle="tooltip" title="${row.info}" type="button">${row.status}</button>` : '-';

                            return `<div class="d-flex justify-content-center">${status}</div>`;
                        }
                    },
                    {
                        "data": "primary_poc",
                        render: function(data, type, row) {
                            let primary_poc = (row.primary_poc) ? `<span dusk="datatable_primary_poc_${row.id}">${row.primary_poc}</span>` : '-';

                            return `<div class="d-flex justify-content-center">${primary_poc}</div>`;
                        }
                    },
                    {
                        "data": "submitted_at",
                        render: function(data, type, row) {
                            if (type === 'sort') {
                                return data;
                            }

                            let dusk = 'dusk="datatable_last_survey_submitted_' + row.id + '"';
                                                        
                            if (row.submitted_at) {
                                return `<div ${dusk} class="local-timestamp" style="width: 81px;">${row.submitted_at}</div>`;
                            }
                            else {
                                return `<div ${dusk} class="d-flex justify-content-center">-</div>`;
                            }
                        }
                    },
                    {
                        "data": "requested_changes_submitted_at",
                        render: function(data, type, row) {
                            if (type === 'sort') {
                                return data;
                            }

                            let dusk = 'dusk="datatable_last_requested_changes_submitted_' + row.id + '"';
                            
                            if (row.requested_changes_submitted_at) {
                                return `<div ${dusk} class="local-timestamp" style="width: 81px;">${row.requested_changes_submitted_at}</div>`;
                            }
                            else {
                                return `<div ${dusk} class="d-flex justify-content-center">-</div>`;
                            }
                        }
                    },
                    {
                        "data": "requested_changes_deadline",
                        render: function(data, type, row) {
                            if (type === 'sort') {
                                return data;
                            }

                            let dusk = 'dusk="datatable_last_requested_changes_deadline_' + row.id + '"';
                                                        
                            if (row.requested_changes_deadline)
                            {
                                let last_requested_changes_deadline = new Date(row.requested_changes_deadline).toLocaleDateString('en-GB').split('/').join('-');

                                return `<div ${dusk} style="width: 81px;">${last_requested_changes_deadline}</div>`;
                            }
                            else {
                                return `<div ${dusk} class="d-flex justify-content-center">-</div>`;
                            }
                        }
                    },
                    {
                        "data": "actions",
                        render: function(data, type, row) {
                            if (row.questionnaire_country_id)
                            {
                                let obj = $.extend(true, {}, row);
                                delete obj.json_data;

                                return `<div class="d-flex justify-content-center">
                                            <button dusk="datatable_review_survey_${row.id}" class="icon-show btn-unstyle" data-bs-toggle="tooltip"
                                                title="Review survey" type="button" onclick='viewSurvey(${JSON.stringify(obj)});'>
                                            </button>
                                            <button dusk="datatable_indicators_dashboard_${row.id}" class="icon-overview btn-unstyle" data-bs-toggle="tooltip" title="View indicators dashboard" type="button"
                                                onclick="location.href=\'/questionnaire/dashboard/management/${row.questionnaire_country_id}\';">
                                            </button>
                                            <button dusk="datatable_survey_summary_data_${row.id}" class="icon-summary btn-unstyle" data-bs-toggle="tooltip" title="View survey summary data" type="button"
                                                onclick="location.href=\'/questionnaire/dashboard/summarydata/${row.questionnaire_country_id}\';">
                                            </button>
                                            <span class="download-section button-spinner-wrapper" item-id="download-survey-data-collection-${row.country_id}">
                                                <div class="d-none" style="margin-top: 4px; padding: 0 10px 0 10px;">
                                                    <i id="button-spinner" class="fa fa-spinner fa-spin"></i>
                                                </div>
                                                <button dusk="datatable_download_survey_data_${row.id}" class="icon-xls-download btn-unstyle item-download"
                                                    title="Download survey data" data-bs-toggle="tooltip" item-id="${row.country_id}">
                                                </button>
                                            </span>
                                        </div>`;
                            }

                            return '';
                        },
                        "orderable": false
                    }
                ]
            });
        });

        $(document).on('click', '.download, .item-download', function() {
            if (localStorage.getItem('pending-export-file') == 1)
            {
                setAlert({
                    'status': 'warning',
                    'msg': 'Another download is in progress. Please wait...'
                });

                return;
            }

            let obj = {
                'element': $(this).parent().attr('item-id'),
                'countries': $(this).attr('item-id'),
                'sources': 'survey',
                'requestLocation': 'survey'
            };
            
            exportData(obj);
        });

        $(document).on('click', '#process-data', function() {
            $('.loader').fadeIn();
            let route = $('.modal #item-route').val();
            let data = getFormData();

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
                    showInputErrors(req.responseJSON);
                }
            });
        });

        window.addEventListener('yearChange', function() {
            $('.loader').fadeIn();

            window.location = section + '/admin/dashboard/' + $('.loaded-questionnaire').val();
        });

        function viewSurvey(obj)
        {
            let form = `<form action="/questionnaire/view/${obj.questionnaire_country_id}" method="POST">
                            @csrf
                            <input type="hidden" name="action" value="view"/>
                        </form>`;

            $(form).appendTo($(document.body)).submit();
        }
    </script>
@endsection
