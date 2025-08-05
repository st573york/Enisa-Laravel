@extends('layouts.app')

@section('title', 'Data Collection - Import Data')

@section('content')

<main id="enisa-main" class="bg-white">
    <div class="container-fluid ">
        <div class="row ps-0">
            <div class="col-10 offset-1 ps-0">
                <div class="enisa-breadcrump d-flex">
                    <nav aria-label="breadcrumb">
                        <ol class="index-nav breadcrumb text-end">
                            <li class="breadcrumb-item"><a href="/">{{ __('Home') }}</a></li>
                            <li class="breadcrumb-item"><a href="/index/datacollection">{{ __('Data Collection') }} - {!! $loaded_index_data->name !!}</a>
                            <li class="breadcrumb-item active"><a href="#">{{ __('Import Data') }}</a>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-10 col-xl-6 col-md-5 offset-1 ps-0">
                <h1>{{ __('Import Data') }}</h1>
            </div>
        </div>

        @include('components.alert', ['type' => 'pageAlert'])

        @livewire('import-data-collection', ['index' => $loaded_index_data])
            
        <div class="row mt-5 {{ ($task && in_array($task->status->id, [1, 3])) ? 'd-none' : '' }}">
            <div class="col-10 offset-1 ps-0 pe-0">
                <div class="table-section col-12 mt-2">
                    <div class="d-flex gap-4 mb-4 {{ (!$task) ? 'd-none' : '' }}">
                        <div>
                            <label for="indicator">Indicator:</label>
                            <select id="indicator" class="form-select" aria-label="Select Indicator">
                                <option value="All" selected>{{ __('All Indicators') }}</option>
                                @foreach ($indicators as $indicator)
                                    <option value="{{ $indicator }}">{{ $indicator }}</option>
                                @endforeach
                              </select>
                        </div>
                        <div style="min-width: 30%;">
                            <label for="country">Country:</label>
                            <select id="country" class="form-select" aria-label="Select Country">
                                <option value="All" selected>{{ __('All Countries') }}</option>
                                @foreach ($countries as $country)
                                    <option value="{{ $country }}">{{ $country }}</option>
                                @endforeach
                              </select>
                        </div>
                    </div>
                    <table id="import-data-collection-table" class="display enisa-table-group">
                        <thead>
                            <tr>
                                <th>{{ __('Indicator') }}</th>
                                <th>{{ __('Country') }}</th>
                                <th>{{ __('Value') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
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
                    <button type="button" class="btn btn-enisa" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="button" class="btn btn-enisa process-data" id='process-data'></button>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="{{ mix('mix/js/main.js') }}" defer></script>
<script src="{{ mix('mix/js/alert.js') }}" defer></script>
<script src="{{ mix('mix/js/modal.js') }}" defer></script>

<script>
    var pageModal = new bootstrap.Modal(document.getElementById('pageModal'));
    let table;
    let section = '/index/datacollection/importdata';
    let table_data = <?php echo json_encode($table_data); ?>;
    let task_status_id = <?php echo ($task) ? $task->status->id : 0 ?>;
    let task_exception = <?php echo ($task && isset($task->payload['last_exception'])) ? json_encode($task->payload['last_exception']) : json_encode('') ?>;
    let loaded_index_id = "{{ $loaded_index_data->id }}";

    $(document).ready(function() {
        skipFadeOut = true;
        skipClearErrors = true;

        if (task_status_id == 3 &&
            task_exception.length &&
            localStorage.getItem('pending-import-data-collection'))
        {
            setAlert({
                'status': 'error',
                'msg': task_exception
            });

            localStorage.removeItem('pending-import-data-collection');
        }

        table = $('#import-data-collection-table').DataTable({
            "processing": true,
            "data": table_data,
            "drawCallback": function() {
                toggleDataTablePagination(this, '#import-data-collection-table_paginate');

                if (localStorage.getItem('pending-export-file') == 1) {
                    updateDownloadButton('downloadInProgress');
                }

                $('.loader').fadeOut();
            },
            "columns": [
                {
                    "data": "indicator_name"
                },
                {
                    "data": "country_name"
                },
                {
                    "data": "indicator_value"
                }
            ]
        });
    });

    $(document).on('click', '.import-data', function() {
        $('.loader').fadeIn();
            
        $.ajax({
            'url': section + '/show/' + loaded_index_id,
            success: function(data) {
                $('.loader').fadeOut();

                let obj = {
                    'action': 'import',
                    'route': section + '/store/' + loaded_index_id,
                    'title': 'Import Data',
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

    $(document).on('click', '.discard-data', function() {
        let data = `<input hidden id="item-action"/>
                    <input hidden id="item-route"/>
                    <div class="warning-message">
                        All uploaded data from other sources will be deleted.
                    </div>`;

        let obj = {
            'modal': 'warning',
            'action': 'discard',
            'route': section + '/discard/' + loaded_index_id,
            'title': 'Discard Data',
            'html': data,
            'btn': 'Discard'
        };
        setModal(obj);

        pageModal.show();
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

        let obj = {
            'element': $(this).parent().attr('item-id'),
            'countries': $(this).attr('item-id'),
            'sources': 'manual',
            'requestLocation': 'index'
        };
            
        exportData(obj);
    });

    $(document).on('click', '#process-data', function() {
        $('.loader').fadeIn();
        skipClearErrors = false;

        let route = $('.modal #item-route').val();
        let action = $('.modal #item-action').val();
        let data = getFormData();
            
        $.ajax({
            'url': route,
            'type': 'post',
            'data': data,
            'contentType': false,
            'processData': false,
            success: function() {
                pageModal.hide();
            },
            error: function(req) {
                $('.loader').fadeOut();

                if (req.status == 400) {
                    showInputErrors(req.responseJSON);
                }
                else if (req.status == 405)
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

    $(document).on('change', '#indicator, #country', function() {
        table.ajax.url(section + '/list/' + loaded_index_id +
            '?indicator=' + $('#indicator').val() + '&country=' + $('#country').val()).load();
    });

    window.addEventListener('yearChange', function() {
        $('.loader').fadeIn();

        location.reload();
    });

    Livewire.on('importDataCollectionNoImport', () => {
        if (task_status_id == 2 ||
            task_status_id == 3)
        {
            task_status_id = 0;

            location.reload();

            scrollToTop();
        }
    });

    Livewire.on('importDataCollectionInProgress', () => {
        if (task_status_id != 1)
        {
            task_status_id = 1;

            localStorage.setItem('pending-import-data-collection', true);

            location.reload();

            scrollToTop();
        }
    });

    Livewire.on('importDataCollectionCompleted', () => {
        if (task_status_id == 1)
        {
            task_status_id = 2;

            localStorage.removeItem('pending-import-data-collection');
                
            $('.loader').fadeIn();

            location.reload();

            scrollToTop();
        }
    });

    Livewire.on('importDataCollectionFailed', () => {
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
