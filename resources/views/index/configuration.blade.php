@extends('layouts.app')

@section('title', 'Index')

@section('content')
    <main id="enisa-main" class="bg-white">
        <div class="container-fluid">

            <div class="row ">
                <div class="col-10 offset-1 pe-0">
                    <div class="enisa-breadcrump p-1 d-flex">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb text-end">
                                <li class="breadcrumb-item"><a href="/">{{ __('Home') }}</a></li>
                                <li class="breadcrumb-item active"><a href="#">{{ __('Index & Survey Configuration') }}</a>
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-10 offset-1">
                    <h1>{{ __('Index & Survey Configuration') }}</h1>
                </div>
            </div>

            @include('components.alert', ['type' => 'pageAlert'])

            <div class="row mt-3">
                <div class="col-10 offset-1 ps-0 pe-0">
                    <div class="table-section col-12 mt-2">
                        <div class="row">
                            <div class="col-12 col-lg-6 col-xl-6">
                                <div class="d-flex gap-2">
                                    <div>
                                        <h2 class="mt-2">{{ __('Year') }} -</h2>
                                    </div>
                                    <div class="mt-1">
                                        @include('components.year-dropdown', ['years' => $years])
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-6 col-xl-6 mt-2 {{ (!$canPreviewSurvey) ? 'd-none' : '' }}">
                                <div class="d-flex justify-content-end ps-0 pe-0">
                                    <div class="col-5 mt-1">
                                        <a href="javascript:;" class="d-flex justify-content-end preview-survey">{{ __('Preview Survey') }}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-5">
                            <div class="col-lg-12 col-lg-6 col-xl-6 mt-2">
                                <button type="button" class="btn btn-enisa import-properties" {{ ($publishedSurvey) ? 'disabled' : '' }}>{{ __('Import Properties') }}</button>
                                <button type="button" class="btn btn-enisa clone-index" {{ ($publishedSurvey) ? 'disabled' : '' }}>{{ __('Clone Index') }}</button>
                            </div>
                            <div class="col-lg-12 col-lg-6 col-xl-6 mt-2">
                                <div class="d-flex gap-1 justify-content-end ps-0 pe-0">
                                    <button type="button" class="btn btn-enisa-invert edit-survey" {{ (!$canEditSurvey) ? 'disabled' : '' }}>{{ __('Edit Survey') }}</button>
                                    <span wire:ignore class="download-section" item-id="download-index-properties">
                                        <button type="button" class="btn btn-enisa-invert download" item-id="all"
                                            {{ (!$canDownloadConfiguration) ? 'disabled' : '' }}>
                                            <i id="button-spinner" class="fa fa-spinner fa-spin d-none"></i>
                                            <span class="in-progress d-none">{{ __('Downloading Configuration') }}</span>
                                            <span class="start">{{ __('Download Configuration') }}</span>
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
                    <div class="accordion" id="areaAccordionPanelsStayOpen">
                        <div class="accordion-item index-accordion-item">
                            <div class="accordion-header" id="accordion-heading-areas">
                               <div class="d-flex">
                                    <h2 class="mb-2 areas-accordion-plus">Areas <span class="clickable-icon btn-unstyle item-create {{ ($publishedIndex) ? 'clickable-icon-deactivated' : '' }}" data-bs-toggle="tooltip" title="Create area" type="button" item-type="area"></span></h2>
                                    <button class="accordion-button index-accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#accordion-collapse-areas" aria-expanded="false" aria-controls="accordion-collapse-0-1">
                                </button>
                               </div>
                            </div>
                            <div id="accordion-collapse-areas" class="accordion-collapse collapse" aria-labelledby="accordion-heading-areas" data-order="0" style="">
                                <div class="accordion-body indicator-body pt-2">
                                    <table id="area-table" class="display enisa-table-group">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Name') }}</th>
                                                <th>{{ __('Description') }}</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-5">
                <div class="col-10 offset-1 table-section">
                <div class="accordion" id="subareasAccordionPanelsStayOpen">
                    <div class="accordion-item index-accordion-item">
                        <div class="accordion-header" id="accordion-heading-subareas">
                            <div class="d-flex">
                                <h2 class="mb-2 areas-accordion-plus">Subareas <span class="clickable-icon btn-unstyle item-create {{ ($publishedIndex) ? 'clickable-icon-deactivated' : '' }}" data-bs-toggle="tooltip" title="Create subarea" type="button" item-type="subarea"></span></h2>
                                <button class="accordion-button index-accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#accordion-collapse-subareas" aria-expanded="false" aria-controls="accordion-collapse-0-2"></button>
                            </div>
                            </div>
                        <div id="accordion-collapse-subareas" class="accordion-collapse collapse" aria-labelledby="accordion-heading-subareas" data-order="0" style="">
                            <div class="accordion-body indicator-body pt-2">
                                <table id="subarea-table" class="display enisa-table-group">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Name') }}</th>
                                            <th>{{ __('Description') }}</th>
                                            <th>{{ __('Area') }}</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>

            <div class="row mt-5">
                <div class="col-10 offset-1 table-section">
                    <div class="accordion" id="indicatorsAccordionPanelsStayOpen">
                        <div class="accordion-item index-accordion-item">
                            <div class="accordion-header" id="accordion-heading-indicators">
                                <div class="d-flex">
                                    <h2 class="mb-2 areas-accordion-plus">Indicators <span class="clickable-icon btn-unstyle item-create {{ ($publishedIndex) ? 'clickable-icon-deactivated' : '' }}" data-bs-toggle="tooltip" title="Create indicator" type="button" item-type="indicator"></span></h2>
                                    <button class="accordion-button index-accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#accordion-collapse-indicators" aria-expanded="true" aria-controls="accordion-collapse-0-3"></button>
                                </div>
                            </div>
                            <div id="accordion-collapse-indicators" class="accordion-collapse collapse show" aria-labelledby="accordion-heading-indicators" data-order="0" style="">
                                <div class="accordion-body indicator-body pt-2">
                                    <table id="indicator-table" class="display enisa-table-group">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th>{{ __('Name') }}</th>
                                                <th>{{ __('Description') }}</th>
                                                <th>{{ __('Type') }}</th>
                                                <th>{{ __('Weight') }}</th>
                                                <th>{{ __('Subarea') }}</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="printSurveyModal" class="printSurveyModalPrint" tabindex="-1" aria-labelledby="printSurveyModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header p-3 justify-content-between">
                            <button type="button" class="btn btn-enisa" data-bs-dismiss="modal">{{ __('Close') }}</button>
                            <button type="button" class="btn btn-enisa" onclick="saveReportPDF();" href="javascript:;">{{ __('Export | PDF') }}</button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-12 mt-2">
                                    <div class="row survey-print" id="surveyContainer"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="pageModal" tabindex="-1" aria-labelledby="pageModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header justify-content-between p-3">
                            <h5 class="modal-title" id="pageModalLabel"></h5>
                        </div>
                        <div class="modal-body p-3"></div>
                        <div class="modal-footer justify-content-between">
                            <button type="button" class="btn btn-enisa" data-bs-dismiss="modal">{{ __('Close') }}</button>
                            <button type="button" class="btn btn-enisa process-data" id='process-data'></button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script src="{{ mix('mix/js/main.js') }}" defer></script>
    <script src="{{ mix('mix/js/alert.js') }}" defer></script>
    <script src="{{ mix('mix/js/modal.js') }}" defer></script>
    <script src="{{ mix('mix/js/tinymce-custom.js') }}" defer></script>
    <script src="{{ mix('js/tinymce/tinymce.min.js') }}" referrerpolicy="origin"></script>

    <script>
        var pageModal = new bootstrap.Modal(document.getElementById('pageModal'));
        var printSurveyModal = new bootstrap.Modal(document.getElementById('printSurveyModal'));
        let area_table;
        let subarea_table;
        let indicator_table;
        let section = '/index';
        let publishedIndex = '{{ $publishedIndex }}';
        let publishedSurvey = '{{ $publishedSurvey }}';
        let areas_loaded = false;
        let subareas_loaded = false;
        let indicators_loaded = false;
        var max_identifier = 0;
        
        $(document).ready(function() {
            skipFadeOut = true;
            skipClearErrors = true;

            if (localStorage.getItem('pending-export-file') == 1) {
                updateDownloadButton('downloadInProgress');
            }

            area_table = $('#area-table').DataTable({
                "ajax": section + '/area/list',
                "initComplete": function() {
                    areas_loaded = true;

                    var loadEvent = new Event('loadEvent');
                    window.dispatchEvent(loadEvent);
                },
                "drawCallback": function() {
                    toggleDataTablePagination(this, '#area-table_paginate');

                    let areas = this.api().rows().data();
                    
                    if (areas.length) {
                        $('.download').prop('disabled', false);
                    }
                },
                "columns": [
                    {
                        "data": "name"
                    },
                    {
                        "data": "description"
                    },
                    {
                        "data": "actions",
                        render: function(data, type, row) {
                            return `<div class="d-flex justify-content-center">
                                        <button class="icon-edit btn-unstyle item-edit ${(publishedIndex ? 'icon-edit-deactivated' : '')}" data-bs-toggle="tooltip"
                                            title="Edit area" type="button" item-id="${row.id}" item-type="area">
                                        </button>
                                        <button class="icon-bin btn-unstyle item-delete ${(publishedIndex || row.default_subarea.length ? 'icon-bin-deactivated' : '')}" data-bs-toggle="tooltip"
                                            title="Delete area" item-id="${row.id}" item-name="${row.name}" item-type="area">
                                        </button>
                                    </div>`;
                        },
                        "className": "actions",
                        "orderable": false
                    }
                ]
            });

            subarea_table = $('#subarea-table').DataTable({
                "ajax": section + '/subarea/list',
                "initComplete": function() {
                    subareas_loaded = true;

                    var loadEvent = new Event('loadEvent');
                    window.dispatchEvent(loadEvent);
                },
                "drawCallback": function() {
                    toggleDataTablePagination(this, '#subarea-table_paginate');
                },
                "order": [
                    [2, 'asc'], // Sort by area
                    [0, 'asc']  // Sort by name
                ],
                "columns": [
                    {
                        "data": "name"
                    },
                    {
                        "data": "description"
                    },
                    {
                        "data": "default_area_name",
                        render: function(data, type, row) {
                            return (row.default_area) ? row.default_area.name : '';
                        }
                    },
                    {
                        "data": "actions",
                        render: function(data, type, row) {
                            return `<div class="d-flex justify-content-center">
                                        <button class="icon-edit btn-unstyle item-edit ${(publishedIndex ? 'icon-edit-deactivated' : '')}" data-bs-toggle="tooltip"
                                            title="Edit subarea" type="button" item-id="${row.id}" item-type="subarea">
                                        </button>
                                        <button class="icon-bin btn-unstyle item-delete ${(publishedIndex || row.default_indicator.length ? 'icon-bin-deactivated' : '')}" data-bs-toggle="tooltip"
                                            title="Delete subarea" item-id="${row.id}" item-name="${row.name}" item-type="subarea">
                                        </button>
                                    </div>`;
                        },
                        "className": "actions",
                        "orderable": false
                    }
                ]
            });

            indicator_table = $('#indicator-table').DataTable({
                "ajax": {
                    "url": section + '/indicator/list',
                    "data": function (data) {
                        data.category = null;
                    }
                },
                "initComplete": function() {
                    indicators_loaded = true;

                    var loadEvent = new Event('loadEvent');
                    window.dispatchEvent(loadEvent);
                },
                "drawCallback": function() {
                    toggleDataTablePagination(this, '#indicator-table_paginate');

                    let indicators = this.api().rows().data();
                    let survey_indicators = [];

                    $.each(indicators, function (key, val) {
                        if (val.category == 'survey') {
                            survey_indicators.push(val.id);
                        }
                    });

                    if (!publishedSurvey &&
                        survey_indicators.length)
                    {
                        $('.edit-survey').prop('disabled', false).attr('onclick', `location.href='/index/indicator/survey/${survey_indicators[0]}'`);
                    }
                    else {
                        $('.edit-survey').prop('disabled', true).removeAttr('onclick');
                    }
                },
                "order": [
                    [0, 'asc']  // Sort by order
                ],
                "columns": [
                    {
                        "data": "order",
                        "visible": false,
                        "searchable": false
                    },
                    {
                        "data": "name"
                    },
                    {
                        "data": "description"
                    },
                    {
                        "data": "category",
                        render: function(data, type, row) {
                            return (row.category == 'manual') ? 'Other' : '<span style="text-transform: capitalize;">' + row.category + '</span>';
                        }
                    },
                    {
                        "data": "default_weight"
                    },
                    {
                        "data": "default_subarea_name",
                        render: function(data, type, row) {
                            return (row.default_subarea) ? row.default_subarea.name : '';
                        }
                    },
                    {
                        "data": "actions",
                        render: function(data, type, row) {
                            return `<div class="d-flex justify-content-center">
                                        <button class="icon-edit btn-unstyle item-edit ${(publishedIndex ? 'icon-edit-deactivated' : '')}" data-bs-toggle="tooltip"
                                            title="Edit indicator" type="button" item-id="${row.id}" item-type="indicator">
                                        </button>
                                        <button class="icon-overview btn-unstyle ${(publishedSurvey || row.category != 'survey' ? 'icon-overview-deactivated' : '')}" data-bs-toggle="tooltip"
                                            title="Edit survey" onclick="location.href=\'/index/indicator/survey/${row.id}\';">
                                        </button>
                                        <button class="icon-bin btn-unstyle item-delete ${(publishedSurvey ? 'icon-bin-deactivated' : '')}" data-bs-toggle="tooltip"
                                            title="Delete indicator" item-id="${row.id}" item-name="${row.name}" item-type="indicator">
                                        </button>
                                    </div`;
                        },
                        "orderable": false
                    }
                ]
            });
        });

        $(document).on('click', '.preview-survey', function() {
            $('.loader').fadeIn();

            skipFadeOut = true;

            $.ajax({
                'url': '/questionnaire/preview',
                'data': {
                    'with_answers': false
                },
                success: function() {
                    printSurveyModal.show();
                }
            }).done(function (data) {
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
        });

        $(document).on('click', '.import-properties', function() {
            $('.loader').fadeIn();
            
            $.ajax({
                'url': section + '/configuration/import/show',
                success: function(data) {
                    $('.loader').fadeOut();

                    let obj = {
                        'action': 'import',
                        'route': section + '/configuration/import/store',
                        'title': 'Import Properties',
                        'html': data,
                        'btn': 'Import'
                    };
                    setModal(obj);

                    pageModal.show();
                },
                error: function(req) {
                    $('.loader').fadeOut();

                    setAlert({
                        'status': 'error',
                        'msg': req.responseJSON.error
                    });
                }
            });
        });

        $(document).on('click', '.clone-index', function() {
            $('.loader').fadeIn();
            
            $.ajax({
                'url': section + '/configuration/clone/show',
                success: function(data) {
                    $('.loader').fadeOut();
 
                    let obj = {
                        'action': 'clone',
                        'route': section + '/configuration/clone/store',
                        'title': 'Clone Index',
                        'html': data,
                        'btn': 'Clone'
                    };
                    setModal(obj);

                    pageModal.show();
                },
                error: function(req) {
                    $('.loader').fadeOut();

                    setAlert({
                        'status': 'error',
                        'msg': req.responseJSON.error
                    });
                }
            });
        });

        $(document).on('click', '.download', function() {
            if (localStorage.getItem('pending-export-file') == 1)
            {
                setAlert({
                    'status': 'warning',
                    'msg': 'Another download is in progress. Please wait...'
                });

                return;
            }

            $('.loader').fadeIn();
            skipClearErrors = false;
            
            let element = $(this).parent().attr('item-id');

            $.ajax({
                'url': '/export/properties/create/' + $('#index-year-select').val(),
                'type': 'post',
                success: function() {
                    localStorage.setItem('pending-export-file', 1);
                    localStorage.setItem('pending-export-task', 'ExportIndexProperties');
                    localStorage.setItem('pending-element-id', element);

                    updateDownloadButton('downloadInProgress');

                    pollExportFile();

                    $('.loader').fadeOut();
                }
            });
        });

        $(document).on('click', '.item-create', function() {
            $('.loader').fadeIn();

            let type = $(this).attr('item-type');

            $.ajax({
                'url': section + '/' + type + '/create',
                success: function(data) {
                    $('.loader').fadeOut();

                    let obj = {
                        'large': (type == 'indicator'),
                        'action': 'create',
                        'type': type,
                        'route': section + '/' + type + '/store',
                        'title': 'New ' + getLabel(type),
                        'html': data
                    };
                    setModal(obj);

                    if (type == 'indicator') {
                        initTinyMCE();
                    }

                    pageModal.show();
                }
            });
        });

        $(document).on('click', '.item-edit', function() {
            $('.loader').fadeIn();

            let id = $(this).attr('item-id');
            let type = $(this).attr('item-type');

            $.ajax({
                'url': section + '/' + type + '/show/' + id,
                success: function(data) {
                    $('.loader').fadeOut();

                    let obj = {
                        'id': id,
                        'large': (type == 'indicator'),
                        'action': 'edit',
                        'type': type,
                        'route': section + '/' + type + '/update/' + id,
                        'title': 'Edit ' + getLabel(type),
                        'html': data
                    };
                    setModal(obj);

                    if (type == 'indicator') {
                        initTinyMCE();
                    }

                    pageModal.show();
                }
            });
        });

        $(document).on('click', '.item-delete', function() {
            let id = $(this).attr('item-id');
            let type = $(this).attr('item-type');
            let label = getLabel(type);
            let name = $(this).attr('item-name');
            let data = `<input hidden id="item-id"/>
                        <input hidden id="item-type"/>
                        <input hidden id="item-action"/>
                        <input hidden id="item-route"/>
                        <div class="warning-message">
                            ${label} '${name}' will be deleted. Are you sure?
                        </div>`;

            let obj = {
                'modal': 'warning',
                'id': id,
                'action': 'delete',
                'type': type,
                'route': section + '/' + type + '/delete/' + id,
                'title': 'Delete ' + getLabel(type),
                'html': data,
                'btn': 'Delete'
            };
            setModal(obj);

            pageModal.show();
        });

        $(document).on('click', '#process-data', function() {
            $('.loader').fadeIn();
            skipClearErrors = false;
            
            let route = $('.modal #item-route').val();
            let action = $('.modal #item-action').val();
            let data = getFormData();
            if (max_identifier) {
                data.append('max_identifier', max_identifier);
            }

            $.ajax({
                'url': route,
                'type': 'post',
                'data': data,
                'contentType': false,
                'processData': false,
                success: function() {
                    if (action == 'import' ||
                        action == 'clone')
                    {
                        location.reload();
                    }
                    else
                    {
                        pageModal.hide();

                        area_table.ajax.reload(checkDataTablesReload, false);
                        subarea_table.ajax.reload(checkDataTablesReload, false);
                        indicator_table.ajax.reload(checkDataTablesReload, false);
                    }
                },
                error: function(req) {
                    $('.loader').fadeOut();

                    if (req.status == 400) {
                        showInputErrors(req.responseJSON);
                    }
                    else if (req.status == 405 ||
                             req.status == 500)
                    {
                        pageModal.hide();

                        setAlert({
                            'status': 'error',
                            'msg': req.responseJSON.error
                        });
                    }
                }
            });
        });

        function getLabel(type)
        {
            switch (type) {
                case 'area':
                    return 'Area';
                case 'subarea':
                    return 'Subarea';
                case 'indicator':
                    return 'Indicator';
                default:
                    return '';
            }
        }

        function checkDataTablesReload()
        {
            if (areas_loaded &&
                subareas_loaded &&
                indicators_loaded)
            {
                $('.loader').fadeOut();
            }
        }

        function saveReportPDF()
        {
            let printIframe = document.getElementById('surveyIframe').contentWindow;

             printIframe.focus();
             printIframe.print();

            return false;
        }

        window.addEventListener('loadEvent', function() {
            checkDataTablesReload();
        });

        window.addEventListener('yearChange', function() {
            $('.loader').fadeIn();

            location.reload();
        });

        window.addEventListener('pagedRendered', function() {
            $('.loader').fadeOut();

            skipFadeOut = false;
        });
    </script>
@endsection
