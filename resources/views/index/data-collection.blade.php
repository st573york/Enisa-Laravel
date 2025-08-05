@extends('layouts.app')

@section('title', __('Data Collection'))

@section('content')

    <main id="enisa-main" class="bg-white">
        <div class="container-fluid">

            <div class="row ">
                <div class="col-10 offset-1 ps-0">
                    <div class="enisa-breadcrump p-1 d-flex">
                        <nav aria-label="breadcrumb">
                            <ol class="index-nav breadcrumb text-end">
                                @php
                                    $index_name = $loaded_index_data->name;
                                @endphp
                                <li class="breadcrumb-item"><a href="/">{{ __('Home') }}</a></li>
                                <li class="breadcrumb-item active"><a href="#">{{ __('Data Collection') }} - {!! $index_name !!}</a>
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-10 col-xl-6 col-md-5 offset-1 ps-0">
                    <h1>{{ __('Data Collection') }} - {{ $index_name }}</h1>
                </div>
            </div>

            @include('components.alert', ['type' => 'pageAlert'])

            @livewire('data-collection', ['index' => $loaded_index_data])

            @php
                $is_latest_index = $latest_index_data->id == $loaded_index_data->id ? true : false;
            @endphp

            <div class="row mt-5 {{ ($task && $task->status->id == 1) ? 'd-none' : '' }}">
                <div class="col-10 offset-1 ps-0 pe-0">
                    <div class="table-section col-12 mt-2">
                        <table id="index-data-collection-table" class="display enisa-table-group">
                            <thead>
                                <tr>
                                    <th>{{ __('Country') }}</th>
                                    <th>{{ __('Imported Indicators') }}</th>
                                    <th>{{ __('Survey Indicators') }}</th>
                                    <th>{{ __('External Sources Indicators') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script src="{{ mix('mix/js/main.js') }}" defer></script>
    <script src="{{ mix('mix/js/alert.js') }}" defer></script>

    <script>
        let table;
        let section = '/index/datacollection';
        let is_latest_index = "{{ $is_latest_index }}";
        let task_status_id = <?php echo ($task) ? $task->status->id : 0 ?>;
        let task_exception = <?php echo ($task && isset($task->payload['last_exception'])) ? json_encode($task->payload['last_exception']) : json_encode('') ?>;
        let loaded_index_id = "{{ $loaded_index_data->id }}";
        let loaded_index_year = "{{ $loaded_index_data->year }}";
        let ms_published = "{{ boolval($loaded_index_data->ms_published) }}";

        $(document).ready(function() {
            skipFadeOut = true;
            skipClearErrors = true;

            if (task_status_id == 3 &&
                task_exception.length &&
                localStorage.getItem('pending-index-calculation'))
            {
                setAlert({
                    'status': 'error',
                    'msg': 'Please contact your administrator!'
                });

                localStorage.removeItem('pending-index-calculation');
            }

            table = $('#index-data-collection-table').DataTable({
                "processing": true,
                "ajax": section + '/list/' + loaded_index_id,
                "drawCallback": function() {
                    toggleDataTablePagination(this, '#index-data-collection-table_paginate');
                    
                    let data = this.api().rows().data();

                    if (data.length)
                    {
                        let countries_percentage = 0;
                        let country_percentage = 0;

                        for (var i = 0; i < data.length; i++)
                        {
                            country_percentage =
                                (data[i].imported_indicators_approved + data[i].questionnaire_indicators_final_approved + data[i].eurostat_indicators_approved) /
                                    (data[i].imported_indicators + data[i].questionnaire_indicators + data[i].eurostat_indicators);
                            countries_percentage += country_percentage * 100;
                        }

                        $('.overall').removeClass('d-none').find('h2').html(Math.floor(countries_percentage / data.length || 0) + '%');
                    }

                    if (localStorage.getItem('pending-export-file') == 1) {
                        updateDownloadButton('downloadInProgress');
                    }

                    $('.loader').fadeOut();
                },
                "columns": [{
                        "data": "country_name"
                    },
                    {
                        "data": "other_sources",
                        render: function(data, type, row) {
                            let progress = '-';

                            if (row.imported_indicators != undefined &&
                                row.imported_indicators_approved != undefined)
                            {
                                let imported_indicators_percentage_approved = Math.floor((row.imported_indicators_approved / row.imported_indicators || 0) * 100);

                                progress = '<div class="progress-ratio">' + row.imported_indicators_approved + '/' + row.imported_indicators + '</div>' +
                                           '<div class="progress">' +
                                                '<div class="progress-bar bg-positive" role="progressbar" style="width: ' +
                                                    imported_indicators_percentage_approved + '%"' +
                                                    'aria-valuenow="50" aria-valuemin="0" aria-valuemax="100">' +
                                                    imported_indicators_percentage_approved + '%' +
                                                '</div>' +
                                           '</div>';
                            }

                            return '<div class="d-flex justify-content-center">' + progress + '</div>';
                        }
                    },
                    {
                        "data": "survey",
                        render: function(data, type, row) {
                            let progress = '-';

                            if (row.questionnaire_indicators != undefined &&
                                row.questionnaire_indicators_final_approved != undefined)
                            {
                                progress = '<div class="progress-ratio">' + row.questionnaire_indicators_final_approved + '/' + row.questionnaire_indicators + '</div>' +
                                           '<div class="progress">' +
                                                '<div class="progress-bar bg-positive" role="progressbar" style="width: ' +
                                                    row.questionnaire_indicators_percentage_final_approved + '%"' +
                                                    'aria-valuenow="50" aria-valuemin="0" aria-valuemax="100">' +
                                                    row.questionnaire_indicators_percentage_final_approved + '%' +
                                                '</div>' +
                                           '</div>';
                            }

                            return '<div class="d-flex justify-content-center">' + progress + '</div>';
                        }
                    },
                    {
                        "data": "external_sources",
                        render: function(data, type, row) {
                            let progress = '-';

                            if (row.eurostat_indicators != undefined &&
                                row.eurostat_indicators_approved != undefined)
                            {
                                let eurostat_indicators_percentage_approved = Math.floor((row.eurostat_indicators_approved / row.eurostat_indicators || 0) * 100);

                                progress = '<div class="progress-ratio">' + row.eurostat_indicators_approved + '/' + row.eurostat_indicators + '</div>' +
                                           '<div class="progress">' +
                                                '<div class="progress-bar bg-positive" role="progressbar" style="width: ' +
                                                    eurostat_indicators_percentage_approved + '%"' +
                                                    'aria-valuenow="50" aria-valuemin="0" aria-valuemax="100">' +
                                                    eurostat_indicators_percentage_approved + '%' +
                                                '</div>' +
                                           '</div>';
                            }

                            return '<div class="d-flex justify-content-center">' + progress + '</div>';
                        }
                    },
                    {
                        "data": "status",
                        render: function(data, type, row) {
                            let status;
                            let obj = getRowData(row.status_id);

                            if (obj.status_style) {
                                status = '<button class="btn-' + obj.status_style + ' btn-label pointer-events-none">' + obj.status_label + '</button>';
                            }
                            else {
                                status = obj.status_label;
                            }

                            return '<div class="d-flex justify-content-center">' + status + '</div>';
                        }
                    },
                    {
                        "data": "actions",
                        render: function(data, type, row) {
                            let can_access_index = (row.status_id && ms_published) ? true : false;
                            let can_approve_index = (row.status_id == 2 && is_latest_index) ? true : false;

                            let access_index_click = (can_access_index) ? ' onclick="javascript:accessIndexByCountry(' + row.country_id + ');"' : '';
                            let approve_index_click = (can_approve_index) ? ' onclick="javascript:approveIndexByCountry(' + row.country_id + ');"' : '';

                            return `<div class="d-flex justify-content-center">
                                        <button class="icon-redirect btn-unstyle ${(!can_access_index ? 'icon-redirect-deactivated' : '')}
                                            "data-bs-toggle="tooltip" title="View visualisations" ${access_index_click}>
                                        </button>
                                        <span class="download-section button-spinner-wrapper" item-id="download-data-collection-${row.country_id}">
                                            <div class="d-none" style="margin-top: 1px; padding: 0 10px 0 10px;">
                                                <i id="button-spinner" class="fa fa-spinner fa-spin"></i>
                                            </div>
                                            <button class="icon-xls-download btn-unstyle item-download ${task_status_id != 2 ? 'icon-xls-download-deactivated cannot-download' : ''}"
                                                title="Download index data" data-bs-toggle="tooltip" item-id="${row.country_id}">
                                            </button>
                                        </span>
                                        <button class="icon-verify btn-unstyle ${(!can_approve_index ? 'icon-verify-deactivated' : '')}
                                            "data-bs-toggle="tooltip" title="Approve index" ${approve_index_click}>
                                        </button>
                                    </div>`;
                        },
                        "orderable": false
                    }
                ]
            });
        });

        $(document).on('click', '.calculate-index', function() {
            $('.loader').fadeIn();
            skipClearErrors = false;

            $.ajax({
                'url': section + '/calculate/' + loaded_index_id,
                'type': 'post',
                error: function(req) {
                    $('.loader').fadeOut();

                    setAlert({
                        'status': 'error',
                        'msg': req.responseJSON.error
                    });
                }
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
                'sources': 'all',
                'requestLocation': 'index'
            };
            
            exportData(obj);
        });

        function accessIndexByCountry(country)
        {
            localStorage.setItem('index-country', country);

            window.location.href = '/index/access';
        }

        function approveIndexByCountry(country)
        {
            $('.loader').fadeIn();
            skipClearErrors = false;

            $.ajax({
                'url': section + '/approve/' + loaded_index_id + '/' + country,
                'type': 'post',
                success: function(data) {
                    table.ajax.reload(null, false);
                },
                error: function(req) {
                    $('.loader').fadeOut();

                    setAlert({
                        'status': 'error',
                        'msg': req.responseJSON.error
                    });
                }
            });
        }

        function getRowData(status)
        {
            let obj = {};
            obj.progress_style = 'info';

            switch (status) {
                case 1:
                    obj.status_label = 'Open';
                    obj.status_style = 'positive-invert';
                    break;
                case 2:
                    obj.status_label = 'Pending';
                    obj.status_style = 'positive-invert';
                    break;
                case 3:
                    obj.status_label = 'Approved';
                    obj.status_style = obj.progress_style = 'approved';
                    break;
                case 4:
                    obj.status_label = 'Published';
                    obj.status_style = 'positive';
                    break;
                default:
                    obj.status_label = '-';
                    obj.status_style = '';
                    break;
            }

            return obj;
        }

        window.addEventListener('yearChange', function() {
            $('.loader').fadeIn();

            location.reload();
        });

        Livewire.on('indexCalculationInProgress', () => {
            if (task_status_id != 1)
            {
                task_status_id = 1;

                localStorage.setItem('pending-index-calculation', true);

                location.reload();

                scrollToTop();
            }
        });

        Livewire.on('indexCalculationCompleted', () => {
            if (task_status_id == 1)
            {
                task_status_id = 2;

                localStorage.removeItem('pending-index-calculation');

                $('.loader').fadeIn();

                location.reload();

                scrollToTop();
            }
        });

        Livewire.on('indexCalculationFailed', () => {
            if (task_status_id == 1)
            {
                task_status_id = 3;

                $('.loader').fadeIn();

                location.reload();

                scrollToTop();
            }
        });
    </script>
@endsection
