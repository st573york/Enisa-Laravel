@extends('layouts.app')

@section('title', 'Survey Dashboard - Indicator Values')

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
                            <li class="breadcrumb-item"><a href="/questionnaire/admin/dashboard/{{ $questionnaire->id }}">{{ __('Survey Dashboard') }} - {!! $questionnaire->title !!}</a></li>
                            <li class="breadcrumb-item active"><a href="#">{{ __('Indicator Values') }} - {!! $questionnaire->title !!}</a>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-10 offset-1 ps-0">
                <h1>{{ __('Indicator Values') }} - {!! $questionnaire->title !!}</h1>
            </div>
        </div>

        @include('components.alert', ['type' => 'pageAlert'])

        @livewire('questionnaire-indicator-values', ['questionnaire' => $questionnaire])

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
                    <table id="questionnaire-admin-dashboard-indicator-values-table" class="display enisa-table-group">
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
    let section = '/questionnaire/admin/dashboard/indicatorvalues';
    let table_data = <?php echo json_encode($table_data); ?>;
    let task_status_id = <?php echo ($task) ? $task->status->id : 0 ?>;
    let task_exception = <?php echo ($task && isset($task->payload['last_exception'])) ? json_encode($task->payload['last_exception']) : json_encode('') ?>;

    $(document).ready(function() {
        skipFadeOut = true;
        skipClearErrors = true;

        if (task_status_id == 3 &&
            task_exception.length &&
            localStorage.getItem('pending-indicator-values-calculation'))
        {
            setAlert({
                'status': 'error',
                'msg': task_exception
            });

            localStorage.removeItem('pending-indicator-values-calculation');
        }

        table = $('#questionnaire-admin-dashboard-indicator-values-table').DataTable({
            "processing": true,
            "data": table_data,
            "drawCallback": function() {
                toggleDataTablePagination(this, '#questionnaire-admin-dashboard-indicator-values-table_paginate');

                $('.loader').fadeOut();
            },
            "order": [
                    [1, 'asc']  // Sort by country
                ],
            "columns": [
                {
                    "data": "indicator_name"
                },
                {
                    "data": "country_name"
                },
                {
                    "data": "indicator_value",
                    render: function(data) {
                        if (data < 1) {
                            return Number((data * 100).toFixed(2));
                        }
                        else {
                            return Number(data.toFixed(2));
                        }
                    }
                }
            ]
        });
    });

    $(document).on('change', '.loaded-questionnaire', function() {
        $('.loader').fadeIn();

        window.location = section + '/' + $('.loaded-questionnaire').val();
    });

    $(document).on('change', '#indicator, #country', function() {
        table.ajax.url(section + '/list/' + $('.loaded-questionnaire').val() +
            '?indicator=' + $('#indicator').val() + '&country=' + $('#country').val()).load();
    });

    $(document).on('click', '.calculate-indicator-values', function() {
        let data = `<input hidden id="item-action"/>
                    <input hidden id="item-route"/>
                    <div class="warning-message">
                        <ul>
                            <li>Surveys that have all their indicators approved will ONLY be calculated.</li>
                            <li>Any country that is already approved will NOT be affected.</li>
                        </ul>
                    </div>`;

        let obj = {
            'modal': 'warning',
            'action': 'calculate',
            'route': section + '/calculate/' + $('.loaded-questionnaire').val(),
            'title': 'Calculate Values',
            'html': data,
            'btn': 'Calculate'
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
                pageModal.hide();
                
                $('.loader').fadeOut();

                setAlert({
                    'status': 'error',
                    'msg': req.responseJSON.error
                });
            }
        });
    });

    Livewire.on('indicatorValuesCalculationInProgress', () => {
        if (task_status_id != 1)
        {
            task_status_id = 1;

            localStorage.setItem('pending-indicator-values-calculation', true);

            location.reload();

            scrollToTop();
        }
    });

    Livewire.on('indicatorValuesCalculationCompleted', () => {
        if (task_status_id == 1)
        {
            task_status_id = 2;

            localStorage.removeItem('pending-indicator-values-calculation');
                
            $('.loader').fadeIn();

            location.reload();

            scrollToTop();
        }
    });

    Livewire.on('indicatorValuesCalculationFailed', () => {
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
