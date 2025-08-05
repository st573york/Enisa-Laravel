@extends('layouts.app')

@section('title', __('Data Collection - External Sources'))

@section('content')

<main id="enisa-main" class="bg-white">
    <div class="container-fluid">

        <div class="row ">
            <div class="col-10 offset-1 ps-0">
                <div class="enisa-breadcrump p-1 d-flex">
                    <nav aria-label="breadcrumb">
                        <ol class="index-nav breadcrumb text-end">
                            <li class="breadcrumb-item"><a href="/">{{ __('Home') }}</a></li>
                            <li class="breadcrumb-item"><a href="/index/datacollection">{{ __('Data Collection') }} - {!! $loaded_index_data->name !!}</a>
                            <li class="breadcrumb-item active"><a href="#">{{ __('External Sources') }}</a>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-10 col-xl-6 col-md-5 offset-1 ps-0">
                <h1>{{ __('External Sources') }}</h1>
            </div>
        </div>

        @include('components.alert', ['type' => 'pageAlert'])

        @livewire('external-data-collection', ['index' => $loaded_index_data])

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
                    <table id="external-data-collection-table" class="display enisa-table-group">
                        <thead>
                            <tr>
                                @if ($task && $task->status->id == 2)
                                    <th><input class="form-check-input" type="checkbox" id="item-select-all"></th>
                                @endif
                                <th>{{ __('Indicator') }}</th>
                                <th>{{ __('Country') }}</th>
                                <th>{{ __('Value') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="row mt-3 {{ (!$task || ($task && $task->status->id != 2)) ? 'd-none' : '' }}">
            <div class="col-10 offset-1 d-flex gap-2 justify-content-end ps-0 pe-0">
                <button type="button" class="btn btn-enisa approve-all">{{ __('Approve All') }}</button>
                <button type="button" class="btn btn-enisa approve-selected" disabled>{{ __('Approve Selected') }}</button>
            </div>
        </div>

    </div>
    <div class="modal fade" id="pageModal" tabindex="-1" aria-labelledby="pageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header p-3">
                    <h5 class="modal-title" id="pageModalLabel"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cancel"></button>
                </div>
                <div class="modal-body p-3"></div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-enisa" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
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
        let section = '/index/datacollection/external';
        let task_status_id = <?php echo ($task) ? $task->status->id : 0 ?>;
        let task_exception = <?php echo ($task && isset($task->payload['last_exception'])) ? json_encode($task->payload['last_exception']) : json_encode('') ?>;
        let loaded_index_id = "{{ $loaded_index_data->id }}";
        let table_data = <?php echo json_encode($table_data); ?>;
        
        $(document).ready(function() {
            skipFadeOut = true;
            skipClearErrors = true;

            if (task_status_id == 3 &&
                task_exception.length &&
                localStorage.getItem('pending-external-data-collection'))
            {
                setAlert({
                    'status': 'error',
                    'msg': 'Please contact your administrator!'
                });

                localStorage.removeItem('pending-external-data-collection');
            }

            initDataTable();
        });

        $(document).on('change', '#indicator, #country', function() {
            table.ajax.url(section + '/list/' + loaded_index_id +
                '?indicator=' + $('#indicator').val() + '&country=' + $('#country').val()).load();
        });

        $(document).on('change update_buttons', '#item-select-all, .item-select', function() {
            $('.approve-selected').prop('disabled', (!getDataTableCheckboxesCheckedAllPages()));
        });

        $(document).on('click', '.collect-data', function() {
            let data = `<input hidden id="item-action"/>
                        <input hidden id="item-route"/>
                        <div class="warning-message">
                            New data collection from predefined external sources will start. Existing data will NOT be overwritten at this point.
                        </div>`;

            let obj = {
                'modal': 'warning',
                'action': 'collect',
                'route': section + '/collect/' + loaded_index_id,
                'title': 'Collect Data',
                'html': data,
                'btn': 'Collect'
            };
            setModal(obj);

            pageModal.show();
        });

        $(document).on('click', '.discard-data', function() {
            let data = `<input hidden id="item-action"/>
                        <input hidden id="item-route"/>
                        <div class="warning-message">
                            New data collected from predefined external sources will be deleted. Existing data will NOT be affected.
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
                'sources': 'eurostat',
                'requestLocation': 'index'
            };
            
            exportData(obj);
        });

        $(document).on('click', '.approve-all', function() {
            approve('approve-all');
        });

        $(document).on('click', '.approve-selected', function() {
            if (getDataTableCheckboxesAllPages() == getDataTableCheckboxesCheckedAllPages()) {
                approve('approve-selected');
            }
            else
            {
                let data = `<input hidden id="item-action"/>
                            <input hidden id="item-route"/>
                            <div class="warning-message">
                                Only the selected data will be imported in the index and you will no longer be able to approve any further data for this data collection.
                            </div>`;

                let obj = {
                    'modal': 'warning',
                    'action': 'approve-selected',
                    'route': section + '/approve/' + loaded_index_id,
                    'title': 'Approve Selected Data',
                    'html': data,
                    'btn': 'Approve'
                };
                setModal(obj);

                pageModal.show();
            }
        });

        $(document).on('click', '#process-data', function() {
            $('.loader').fadeIn();
            skipClearErrors = false;

            let route = $('.modal #item-route').val();
            let action = $('.modal #item-action').val();
            let data = getFormData();
            data.append('action', action);
                        
            $.ajax({
                'url': route,
                'type': 'post',
                'data': data,
                'contentType': false,
                'processData': false,
                success: function(data) {
                    pageModal.hide();
                },
                error: function(req) {
                    pageModal.hide();

                    $('.loader').fadeOut();

                    setAlert({
                        'status': 'error',
                        'msg': req.responseJSON.error
                    });
                }
            });
        });

        function initDataTable()
        {
            table = $('#external-data-collection-table').DataTable({
                "processing": true,
                "data": table_data,
                "drawCallback": function(settings) {
                    toggleDataTablePagination(this, '#external-data-collection-table_paginate');
                    
                    $('#item-select-all')
                        .trigger('update_select_all')
                        .trigger('update_buttons');

                    if (localStorage.getItem('pending-export-file') == 1) {
                        updateDownloadButton('downloadInProgress');
                    }

                    $('.loader').fadeOut();
                },
                "order": getDataTableOrder(),
                "columns": getDataTableColumns()
            });
        }

        function getDataTableOrder()
        {
            let order =[];
            
            if (task_status_id == 2) {
                order.push([1, 'asc']);
            }
            else {
                order.push([0, 'asc']);
            }

            return order;
        }

        function getDataTableColumns()
        {
            let columns =[];

            if (task_status_id == 2)
            {
                columns.push(
                    {
                        "data": "indicator_id",
                        render: function(data, type, row) {
                            let can_select = (row.country_status == 3) ? false : true;

                            return "<input class='item-select form-check-input' type='checkbox' id='item-" + data + "'" + (can_select ? '' : ' disabled') + "\>";
                        },
                        "orderable": false
                    }
                );
            }

            columns.push(
                {
                    "data": "indicator_name"
                },
                {
                    "data": "country_name"
                },
                {
                    "data": "indicator_value"
                }
            );

            return columns;
        }

        function approve(action)
        {
            $('.loader').fadeIn();
            skipClearErrors = false;

            let data = getFormData();
            data.append('action', action);

            $.ajax({
                'url': section + '/approve/' + loaded_index_id,
                'type': 'post',
                'data': data,
                'contentType': false,
                'processData': false,
                error: function(req) {
                    pageModal.hide();
                    
                    $('.loader').fadeOut();

                    setAlert({
                        'status': 'error',
                        'msg': req.responseJSON.error
                    });
                }
            });
        }

        window.addEventListener('yearChange', function() {
            $('.loader').fadeIn();

            location.reload();
        });

        Livewire.on('externalDataCollectionNoCollection', () => {
            if (task_status_id == 2 ||
                task_status_id == 3)
            {
                task_status_id = 0;

                location.reload();

                scrollToTop();
            }
        });

        Livewire.on('externalDataCollectionInProgress', () => {
            if (task_status_id != 1)
            {
                task_status_id = 1;

                localStorage.setItem('pending-external-data-collection', true);

                location.reload();

                scrollToTop();
            }
        });

        Livewire.on('externalDataCollectionCompleted', () => {
            if (task_status_id == 1)
            {
                task_status_id = 2;

                localStorage.removeItem('pending-external-data-collection');
                
                $('.loader').fadeIn();

                location.reload();

                scrollToTop();
            }
        });

        Livewire.on('externalDataCollectionFailed', () => {
            if (task_status_id == 1)
            {
                task_status_id = 3;

                $('.loader').fadeIn();

                location.reload();

                scrollToTop();
            }
        });

        Livewire.on('externalDataCollectionApproved', () => {
            if (task_status_id == 2 ||
                task_status_id == 3)
            {
                task_status_id = 4;

                location.reload();

                scrollToTop();
            }
        });
</script>
@endsection
