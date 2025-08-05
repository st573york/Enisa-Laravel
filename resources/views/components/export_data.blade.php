@extends('layouts.app')

@section('title', 'Index')

@section('content')

    <main id="enisa-main" class="bg-white">
        <input hidden class="loaded-index" value="{{ $loaded_index_data->id }}" />
        <div class="container-fluid">

            <div class="row ">
                <div class="col-10 offset-1 ps-0">
                    <div class="enisa-breadcrump p-1 d-flex">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb text-end">
                                <li class="breadcrumb-item"><a href="/">Home</a></li>
                                <li class="breadcrumb-item active"><a href="#">Index - Reports & Data</a></li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-5 offset-1 ps-0">
                    <h1>{{ __('Reports & Data') }}</h1>
                </div>
            </div>

            @include('components.alert', ['type' => 'pageAlert'])

            @php
                $is_admin = Auth::user()->isAdmin();
            @endphp

            <div class="row mt-3">
                <div class="col-10 offset-1 ps-0 pe-0">
                    <div class="table-section col-12 mt-2">
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex gap-2">
                                    <div>
                                        <h2 class="mt-2">{{ __('Year') }} -</h2>
                                    </div>
                                    <div class="mt-1">
                                        @include('components.year-dropdown', ['years' => $years])
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if (!empty($indices) && !is_null($baseline_index) && ($loaded_index_data->eu_published || $loaded_index_data->ms_published))
                <div class="row mt-5">
                    <div class="col-10 offset-1 table-section">
                        <h2 class="">Reports & Data</h2>
                        <div class="d-flex justify-content-end {{ (!Auth::user()->isAdmin() && !Auth::user()->isEnisa()) ? 'd-none' : '' }}">
                            <div class="d-flex gap-2">
                                <label for="country" style="margin-top: 6.5px;">Country:</label>
                                <select id="country" class="form-select mb-2" aria-label="Select Country">
                                    @foreach ($indices as $index)
                                        <option value="{{ $index['country_id'] }}" item-id="{{ $index['id'] }}">{{ $index['value'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <table id="export-data-table" class="display enisa-table-group">
                            <thead>
                                <tr>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            @endif

        </div>

        <div class="modal fade" id="modal-reports">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header justify-content-between">
                        <button type="button" class="btn btn-enisa" href="javascript:;" onclick="resetSaveReportButton();" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-enisa" id="export-pdf" href="javascript:;" onclick="saveReportPDF();" class="d-flex justify-content-between" disabled> Loading... </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12 mt-2">
                                <div class="row" id="frameContainer"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <script src="{{ mix('mix/js/main.js') }}" defer></script>
    <script src="{{ mix('mix/js/alert.js') }}" defer></script>

    <script>
        var pageModal = new bootstrap.Modal(document.getElementById('modal-reports'));
        let table;
        let id = "{{ $loaded_index_data->id }}";
        let year = "{{ $loaded_index_data->year }}";
        let indices = "{{ (!empty($indices)) ? true : false }}";
        let baseline_index = "{{ (!is_null($baseline_index)) ? true : false }}";
        let eu_published = "{{ boolval($loaded_index_data->eu_published) }}";
        let ms_published = "{{ boolval($loaded_index_data->ms_published) }}";
        let export_data = <?php echo json_encode($export_data); ?>;
        let is_admin = "{{ $is_admin }}";
        let report_type = '';

        $(document).ready(function () {
            if (indices &&
                baseline_index)
            {
                if (!eu_published &&
                    !ms_published)
                {
                    if (is_admin)
                    {
                        setAlert({
                            'status': 'warning',
                            'msg': 'EU/MS Reports/Visualisations for ' + year + ' are unpublished.\
                                Browse to <a href="/index/show/' + id + '">Index</a> to publish them.'
                        });

                        return;
                    }
                }
                else
                {
                    skipFadeOut = true;

                    initDataTable();

                    return;
                }
            }

            setAlert({
                'status': 'warning',
                'msg': 'No reports available' + (year == 2022 ? ' for 2022.' : '. Data collection for ' + year + ' is currently in progress.')
            });
        });

        $(document).on('change', '#country', function() {
            table.clear().rows.add(export_data).draw();
        });

        $('#modal-reports').on('hide.bs.modal', function (e) {
            const button = document.getElementById('export-pdf');

            button.disabled = true;
            button.textContent = 'Loading...';
        })

        $(document).on('click', '.download, .download-section .item-download', function() {
            if (localStorage.getItem('pending-export-file') == 1)
            {
                setAlert({
                    'status': 'warning',
                    'msg': 'Another download is in progress. Please wait...'
                });

                return;
            }

            $('.loader').fadeIn();

            let element = $(this).parent().attr('item-id');

            if (element.indexOf('report-data') >= 0)
            {
                $.ajax({
                    'url': '/export/reportdata/create/' + (element.indexOf('ms') >= 0 ? $(this).attr('item-id') : ''),
                    'type': 'post',
                    'data': {
                        'year': year
                    },
                    success: function() {
                        localStorage.setItem('pending-export-file', 1);
                        localStorage.setItem('pending-export-task', 'ExportReportData');
                        localStorage.setItem('pending-element-id', element);

                        updateDownloadButton('downloadInProgress');

                        pollExportFile();
                    }
                });
            }
            else if (element.indexOf('ms-raw-data') >= 0)
            {
                $.ajax({
                    'url': '/export/msrawdata/create/' + $(this).attr('item-id'),
                    'type': 'post',
                    'data': {
                        'year': year
                    },
                    success: function() {
                        localStorage.setItem('pending-export-file', 1);
                        localStorage.setItem('pending-export-task', 'ExportMSRawData');
                        localStorage.setItem('pending-element-id', element);

                        updateDownloadButton('downloadInProgress');

                        pollExportFile();
                    }
                });
            }
        });

        window.addEventListener('yearChange', function() {
            $('.loader').fadeIn();

            location.reload();
        });

        function initDataTable()
        {
            table = $('#export-data-table').DataTable({
                "data": export_data,
                "drawCallback": function() {
                    toggleDataTablePagination(this, '#export-data-table_paginate');

                    $('.loader').fadeOut();

                    skipFadeOut = false;
                },
                "columns": [
                    {
                        "data": "title",
                        render: function(data, type, row) {
                            return row.title + ' - ' + (row.country ? getCountryName() + ' ' : '') + year;
                        }
                    },
                    {
                        "data": "actions",
                        render: function(data, type, row) {
                            let actions = '';

                            if (row.type == 'ms_report')
                            {
                                if (year == '2023') {
                                    actions = `<button class="icon-pdf-download btn-unstyle" data-bs-toggle="tooltip"
                                                    title="Download pdf report" type="button" onclick="javascript:getReport('ms');">
                                               </button>`;
                                }
                                else {
                                    actions = `<button class="icon-show btn-unstyle" data-bs-toggle="tooltip"
                                                    title="View report" type="button" onclick="javascript:viewReport();">
                                               </button>
                                               <span class="download-section button-spinner-wrapper" item-id="download-ms-report-data-${getCountryId()}">
                                                    <div class="d-none" style="margin-top: 4px; padding: 0 10px 0 10px;">
                                                        <i id="button-spinner" class="fa fa-spinner fa-spin"></i>
                                                    </div>
                                                    <button class="icon-xls-download btn-unstyle item-download"
                                                        title="Download xls report" data-bs-toggle="tooltip" item-id="${getCountryId()}">
                                                    </button>
                                               </span>`;
                                }
                            }
                            else if (row.type == 'eu_report')
                            {
                                if (year == '2023') {
                                    actions = `<button class="icon-pdf-download btn-unstyle" data-bs-toggle="tooltip"
                                                    title="Download pdf report" type="button" onclick="javascript:getReport('eu');">
                                               </button>`;
                                }
                                else {
                                    actions = `<button class="icon-show btn-unstyle" data-bs-toggle="tooltip"
                                                    title="View report" type="button" onclick="javascript:viewEuReport();">
                                               </button>
                                               <span class="download-section button-spinner-wrapper" item-id="download-eu-report-data-${getCountryId()}">
                                                    <div class="d-none" style="margin-top: 4px; padding: 0 10px 0 10px;">
                                                        <i id="button-spinner" class="fa fa-spinner fa-spin"></i>
                                                    </div>
                                                    <button class="icon-xls-download btn-unstyle item-download"
                                                        title="Download xls report" data-bs-toggle="tooltip" item-id="${getCountryId()}">
                                                    </button>
                                               </span>`;
                                }
                            }
                            else if (row.type == 'ms_raw_data')
                            {
                                actions = `<button class="icon-xls-download btn-unstyle" data-bs-toggle="tooltip"
                                                title="Download xls data" type="button" onclick="javascript:getMsRawData();">
                                           </button>`;
                                
                                // actions = `<span class="download-section datatable" item-id="download-ms-raw-data-${getCountryId()}">
                                //                 <div class="d-none" style="margin-top: 1px; padding: 0 10px 0 10px;">
                                //                     <i id="button-loader" class="fa fa-spinner fa-spin"></i>
                                //                 </div>
                                //                 <button class="icon-xls-download btn-unstyle item-download"
                                //                     title="Download xls data" data-bs-toggle="tooltip" item-id="${getCountryId()}">
                                //                 </button>
                                //            </span>`;
                            }
                            else if (row.type == 'ms_results')
                            {
                                actions = `<button class="icon-xls-download btn-unstyle item-download" data-bs-toggle="tooltip"
                                                title="Download xls data" type="button" onclick="javascript:getMsResults();">
                                           </button>`;
                            }

                            return `<div class="d-flex justify-content-start">${actions}</div>`;
                        },
                        "width": "20%"
                    }
                ],
                "paging": false,
                "info": false,
                "searching": false,
                "ordering": false
            });
        }

        function resetSaveReportButton()
        {
            const button = document.getElementById('export-pdf');
            
            button.disabled = true;
            button.textContent = 'Loading...';
        }

        function viewReport()
        {
            $('.loader').fadeIn();

            skipFadeOut = true;

            report_type = 'ms';

            $.ajax({
                'url': '/index/report/json/' + getIndexId(),
                success: function() {
                    pageModal.show();
                },
                error: function(req) {
                    if (req.status == 403) {
                        setAlert({
                            'status': 'error',
                            'msg': req.responseJSON.error
                        });
                    }
                }
            }).done(function (html) {
                $('#frameContainer').empty();

                setTimeout(() => {
                    let iframe = document.createElement('iframe');
                    iframe.setAttribute('id', 'reportIframe');
                    iframe.style.height = '70vh';
                    
                    document.getElementById('frameContainer').appendChild(iframe);

                    iframe.contentWindow.document.open();
                    iframe.contentWindow.document.write(html);
                    iframe.contentWindow.document.close();

                    $('.loader').fadeOut();

                    skipFadeOut = false;
                }, 1000);
            });
        }

        function getReport(type)
        {
            if (type == 'ms') {
                location.href = '/index/report/download_ms/' + getIndexId();
            }
            else if (type == 'eu') {
                location.href = '/index/report/download_eu';
            }
        }

        function getMsRawData()
        {
            location.href = '/index/data/download_ms_raw_data/' + getIndexId();
        }

        function getMsResults()
        {
            location.href = '/index/data/download_ms_results';
        }

        function viewEuReport()
        {
            $('.loader').fadeIn();

            skipFadeOut = true;

            report_type = 'eu';

            $.ajax({
                'url': '/index/report/json_eu',
                success: function() {
                    pageModal.show();
                }
            }).done(function (html) {
                $('#frameContainer').empty();

                setTimeout(() => {
                    let iframe = document.createElement('iframe');
                    iframe.setAttribute('id', 'reportIframe');
                    iframe.style.height = '70vh';
                    
                    document.getElementById('frameContainer').appendChild(iframe);

                    iframe.contentWindow.document.open();
                    iframe.contentWindow.document.write(html);
                    iframe.contentWindow.document.close();

                    $('.loader').fadeOut();

                    skipFadeOut = false;
                }, 1000);
            });
        }

        function saveReportPDF()
        {

            // if (report_type == 'ms') {
            //     location.href = '/index/report/download_ms/' + getIndexId();
            // }
            // else if (report_type == 'eu') {
            //     location.href = '/index/report/download_eu';
            // }

            let reportIframe = document.getElementById('reportIframe').contentWindow;

            reportIframe.focus();
            reportIframe.print();

            return false;
        }

        function getCountryId()
        {
            return $('#country').find(':selected').val();
        }

        function getCountryName()
        {
            return $('#country').find(':selected').text();
        }

        function getIndexId()
        {
            return $('#country').find(':selected').attr('item-id');
        }
    </script>
@endsection
