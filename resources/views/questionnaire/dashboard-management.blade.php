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
                                @php
                                    $questionnaire_title = $questionnaire->questionnaire->title;
                                @endphp
                                <li class="breadcrumb-item"><a href="/">Home</a></li>
                                @if (Auth::user()->isAdmin())
                                    <li class="breadcrumb-item"><a href="/questionnaire/admin/management">{{ __('Surveys') }}</a></li>
                                    <li class="breadcrumb-item"><a dusk="survey_admin_dashboard" href="/questionnaire/admin/dashboard/{{ $questionnaire->questionnaire_id }}">{{ __('Survey Dashboard') }} - {!! $questionnaire_title !!}</a></li>
                                @elseif (Auth::user()->isPoC())
                                    <li class="breadcrumb-item"><a dusk="survey_management" href="/questionnaire/management">{{ __('Surveys') }}</a></li>
                                @endif
                                <li class="breadcrumb-item active"><a href="#">{{ __('Survey Dashboard') }} - {!! $questionnaire_title !!}</a></li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-10 offset-1 ps-0">
                    <h1 dusk="dashboard_survey_title">{{ __('Survey Dashboard') }} - {!! (Auth::user()->isAdmin()) ? $questionnaire->country->name : $questionnaire_title !!}</h1>
                </div>
            </div>

            @include('components.alert', ['type' => 'pageAlert'])

            @php
                $is_admin = (Auth::user()->isAdmin());
                $is_poc = (Auth::user()->isPoC());
                $is_assigned = (!empty($indicators_assigned));
            @endphp
            
            <div class="row mt-5">
                <div class="col-10 offset-1 table-section">
                    <div class="row">
                        <div class="d-flex justify-content-between">
                            <div class="col-12 col-lg-5 col-xl-5">
                                <div class="d-flex gap-2">
                                    <div>
                                        <h2 class="mb-2">{{ __('Dashboard') }}</h2>
                                    </div>
                                </div>
                            </div>
                            @if (!is_null($indicators_percentage))
                                <div class="col-12 col-lg-2 col-xl-2">
                                    <div class="d-flex gap-1 justify-content-end overall">
                                        <span style="line-height: 2">{{ __('Progress') }}:</span>
                                        <h2 dusk="dashboard_indicators_progress">{{ $indicators_percentage }}%</h2>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                    <table dusk="dashboard_table" id="questionnaire-dashboard-management-table" class="display enisa-table-group">
                        <thead>
                            <tr>
                                <th><input class="form-check-input" type="checkbox" id="item-select-all"></th>
                                <th></th>
                                <th>{{ __('Indicator') }}</th>
                                <th>{{ __('Assignee') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Deadline') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-10 offset-1 d-flex gap-2 justify-content-end ps-0 pe-0">
                    @php
                        $review_click = 'onclick="' . ($is_assigned ? 'viewSurvey({questionnaire_country_id: ' . $questionnaire->id . '})"' : 'location.href=\'/questionnaire/management\'"');
                    @endphp
                    @if ($is_admin)
                        <button dusk="dashboard_finalise_survey" type="button" class="btn btn-enisa finalise-survey" {{ ($indicators_approved && is_null($questionnaire->approved_by)) ? '' : 'disabled' }}>{{ __('Finalise Survey') }}</button>
                        <button dusk="dashboard_approve_selected_indicators" type="button" class="btn btn-enisa multiple-approve" disabled>{{ __('Approve Selected Indicators') }}</button>
                    @elseif ($is_poc)
                        <button dusk="dashboard_edit_selected_indicators" type="button" class="btn btn-enisa multiple-edit" disabled>{{ __('Edit Selected Indicators') }}</button>
                    @endif
                    <button dusk="dashboard_submit_requested_changes" type="button" class="btn btn-enisa submit-requested-changes" {{ $pending_requested_changes->count() ? '' : 'disabled' }}>{{ __('Submit Requested Changes') }}</button>
                    <button dusk="dashboard_review_survey" type="button" class="btn btn-enisa" {!! $review_click !!}>{{ __('Review Survey') }}</button>
                </div>
            </div>

            <div dusk="dashboard_modal" class="modal fade" id="pageModal" tabindex="-1" aria-labelledby="pageModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header p-3">
                            <h5 dusk="dashboard_modal_title" class="modal-title" id="pageModalLabel"></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-3"></div>
                        <div class="modal-footer justify-content-between">
                            <button dusk="dashboard_modal_close" type="button" class="btn btn-enisa" data-bs-dismiss="modal">{{ __('Close') }}</button>
                            <button dusk="dashboard_modal_save" type="button" class="btn btn-enisa process-data" id='process-data'></button>
                        </div>
                    </div>
                </div>
            </div>
    </main>

    <script src="{{ mix('mix/js/main.js') }}" defer></script>
    <script src="{{ mix('mix/js/modal.js') }}" defer></script>
    <script src="{{ mix('mix/js/alert.js') }}" defer></script>
    <script src="{{ mix('mix/js/datepicker-custom.js') }}" defer></script>

    <script>
        var pageModal = new bootstrap.Modal(document.getElementById('pageModal'));
        let table;
        let section = '/questionnaire';
        let questionnaire_country_id = "{{ $questionnaire->id }}";
        let questionnaire_deadline = "{{ $questionnaire->questionnaire->deadline }}";
        let is_admin = "{{ $is_admin }}";
        let is_poc = "{{ $is_poc }}";
        let is_assigned = "{{ $is_assigned }}";
        let is_finalised = "{{ $questionnaire->approved_by }}";
        let user_group = <?php echo json_encode(config('constants.USER_GROUP')); ?>;
        let requested_changes = false;

        $(document).ready(function() {
            table = $('#questionnaire-dashboard-management-table').DataTable({
                "ajax": section + '/dashboard/management/list/' + questionnaire_country_id,
                "drawCallback": function() {
                    toggleDataTablePagination(this, '#questionnaire-dashboard-management-table_paginate');

                    $('#item-select-all')
                        .trigger('update_select_all')
                        .trigger('update_buttons');
                },
                "order": [
                    [1, 'asc']
                ],
                "columns": [
                    {
                        "data": "id",
                        render: function(data, type, row) {
                            let can_select = ((is_poc && row.state < 7) || (is_admin && row.questionnaire_country.submitted_by && row.state == 7)) ? true : false;

                            return "<input dusk='datatable_indicator_select_" + row.number + "' class='item-select form-check-input' type='checkbox' id='item-" + data + "'" + (can_select ? '' : ' disabled') + "\>";
                        },
                        "orderable": false
                    },
                    {
                        "data": "number",
                        "visible": false,
                        "searchable": false
                    },
                    {
                        "data": "name",
                        render: function(data, type, row) {
                            return "<a dusk='datatable_indicator_name_" + row.number + "' href='javascript:;' onclick='showIndicatorInfo(" + JSON.stringify(row) + ");'>" + row.number + '. ' + row.name + "</a>";
                        }
                    },
                    {
                        "data": "assignee",
                        render: function(data, type, row) {
                            return '<span dusk="datatable_indicator_assignee_' + row.number + '">' + row.assignee.name + '</span>';
                        }
                    },
                    {
                        "data": "status",
                        render: function(data, type, row) {
                            let obj = getRowData(row);

                            return `<div class="d-flex justify-content-center">
                                        <button dusk="datatable_indicator_status_${row.number}" class="btn-${obj.status_style} btn-label btn-with-tooltip" data-bs-toggle="tooltip" title="${obj.status_info}" type="button">${obj.status_label}<span style="display: none;">${obj.status_style}</span></button>
                                    </div>`;
                        }
                    },
                    {
                        "data": "deadline",
                        render: function(data, type, row) {
                            return '<span dusk="datatable_indicator_deadline_' + row.number + '">' + row.deadline + '</span>';
                        }
                    },
                    {
                        "data": "actions",
                        render: function(data, type, row) {
                            let review_click = 'onclick=' + (is_assigned ? "'viewSurvey(" + JSON.stringify(row) + ");'" : "location.href='/questionnaire/management'");

                            let edit_button = '';
                            let approve_button = '';

                            if (is_poc)
                            {
                                let can_edit = (row.state > 6) ? false : true;

                                let edit_click = (can_edit) ? " onclick='editIndicator(" + JSON.stringify(row) + ");'" : '';

                                edit_button =
                                    `<button dusk="datatable_indicator_edit_${row.number}" class="icon-edit btn-unstyle ${(can_edit ? '' : 'icon-edit-deactivated')} "data-bs-toggle="tooltip"
                                        title="Edit indicator" type="button" ${edit_click} ${(!can_edit ? 'disabled' : '')}>
                                    </button>`;
                            }
                            else if (is_admin)
                            {
                                let can_approve = (row.questionnaire_country.submitted_by && row.state == 7) ? true : false;

                                approve_button =
                                    `<button dusk="datatable_indicator_approve_${row.number}" class="icon-verify btn-unstyle item-approve ${(can_approve ? '' : 'icon-verify-deactivated')} "data-bs-toggle="tooltip"
                                        title="Approve indicator" type="button" item-id="${row.id}" ${(!can_approve ? 'disabled' : '')}>
                                    </button>`;
                            }
                            
                            return `<div class="d-flex justify-content-center">
                                        ${edit_button}
                                        ${approve_button}
                                        <button dusk="datatable_indicator_review_${row.number}" class="icon-show btn-unstyle" data-bs-toggle="tooltip"
                                            title="Review indicator" type="button" ${review_click}>
                                        </button>
                                    </div>`;
                        },
                        "orderable": false
                    }
                ]
            });
        });
        
        $(document).on('change update_buttons', '#item-select-all, .item-select', function() {
            $('.multiple-edit, .multiple-approve').prop('disabled', (!getDataTableCheckboxesCheckedAllPages()));
        });

        $(document).on('show', '#formDate', function() {
            if (datepickerLimit)
            {
                $(this).datepicker('setStartDate', new Date());
                if (!requested_changes) {
                    $(this).datepicker('setEndDate', questionnaire_deadline);
                }
                datepickerLimit = false;
            }
        });

        $(document).on('click', '.item-approve', function() {
            $('.loader').fadeIn();
            let id = $(this).attr('item-id');
            
            $.ajax({
                'url': section + '/indicator/single/update/' + id,
                'type': 'post',
                'data': {
                    'action': 'final_approve',
                    'questionnaire_country_id': questionnaire_country_id
                },
                success: function(response) {
                    table.ajax.reload(null, false);

                    if (response.approved) {
                        $('.finalise-survey').prop('disabled', false);
                    }
                },
                error: function(req) {
                    table.ajax.reload(null, false);

                    setAlert({
                        'status': 'error',
                        'msg': req.responseJSON.error
                    });
                }
            }).done(function () {
                $('#item-select-all').prop('checked', false);
            });
        });

        $(document).on('click', '.finalise-survey', function() {
            let data = `<input hidden id="item-action"/>
                        <input hidden id="item-route"/>
                        <div class="warning-message">
                            By clicking Finalise, the Survey of this Member State will accept no further changes.<br>
                            The PPoC of the Member State will be notified by email that the Survey has been finalised and accepted.
                        </div>`;

            let obj = {
                'large': true,
                'modal': 'warning',
                'action': 'finalise',
                'route': section + '/finalise',
                'title': 'Finalise Survey',
                'html': data,
                'btn': 'Finalise'
            };
            setModal(obj);

            pageModal.show();
        });
        
        $(document).on('click', '.multiple-approve', function() {
            $('.loader').fadeIn();
            let data = getFormData();
            data.append('action', 'final_approve');
            data.append('questionnaire_country_id', questionnaire_country_id);
    
            $.ajax({
                'url': section + '/indicator/multiple/update',
                'type': 'post',
                'data': data,
                'contentType': false,
                'processData': false,
                success: function(response) {
                    table.ajax.reload(null, false);

                    if (response.approved) {
                        $('.finalise-survey').prop('disabled', false);
                    }
                },
                error: function(req) {
                    table.ajax.reload(null, false);

                    setAlert({
                        'status': 'error',
                        'msg': req.responseJSON.error
                    });
                }
            }).done(function () {
                $('#item-select-all').prop('checked', false);
            });
        });

        $(document).on('click', '.multiple-edit', function() {
            $('.loader').fadeIn();
            let data = getFormData();
            let rows = table.$('.item-select', {'page': 'all'});
            
            $.ajax({
                'url': section + '/indicator/multiple/edit',
                'data': {
                    'indicators': data.get('datatable-selected'),
                    'questionnaire_country_id': questionnaire_country_id
                },
                success: function(data) {
                    let obj = {
                        'action': 'edit',
                        'route': section + '/indicator/multiple/update',
                        'title': 'Edit Indicators',
                        'html': data
                    };
                    setModal(obj);

                    initDatePicker();

                    pageModal.show();
                }
            })
        });

        $(document).on('click', '.submit-requested-changes', function() {
            $('.loader').fadeIn();
    
            $.ajax({
                'url': section + '/submit_requested_changes',
                'type': 'post',
                'data': {
                    'questionnaire_country_id': questionnaire_country_id
                },
                success: function() {
                    table.ajax.reload(null, false);

                    $('.submit-requested-changes').prop('disabled', true);
                },
                error: function(req) {
                    setAlert({
                        'status': 'error',
                        'msg': req.responseJSON.error
                    });
                }
            });
        });

        function viewSurvey(obj)
        {
            let questionnaire_country_id = obj.questionnaire_country_id;
            let requested_indicator = '';
            if (obj.questionnaire_country)
            {
                questionnaire_country_id = obj.questionnaire_country.id;
                requested_indicator = ((is_poc && obj.questionnaire_country.submitted_by) || obj.questionnaire_country.approved_by)
                    ? requested_indicator : '<input type="hidden" name="requested_indicator" value="' + obj.id + '"/>';
            }
            let form = `<form action="/questionnaire/view/${questionnaire_country_id}" method="POST">
                            @csrf
                            <input type="hidden" name="action" value="view"/>
                            ${requested_indicator}
                        </form>`;

            $(form).appendTo($(document.body)).submit();
        }

        $(document).on('click', '#process-data', function() {
            $('.loader').fadeIn();
            let action = $('.modal #item-action').val();
            
            if (action == 'edit' ||
                action == 'finalise')
            {
                let route = $('.modal #item-route').val();
                let data = getFormData();
                data.append('action', action);
                data.append('questionnaire_country_id', questionnaire_country_id);
                data.append('requested_changes', requested_changes);

                $.ajax({
                    'url': route,
                    'type': 'post',
                    'data': data,
                    'contentType': false,
                    'processData': false,
                    success: function(data) {
                        pageModal.hide();

                        if (action == 'finalise')
                        {
                            $('.finalise-survey').prop('disabled', true);

                            is_finalised = true;
                        }

                        table.ajax.reload(null, false);
                    },
                    error: function(req) {
                        if (req.status == 400) {
                            showInputErrors(req.responseJSON);
                        }

                        if (req.responseJSON.error)
                        {
                            pageModal.hide();

                            if (action == 'edit') {
                                table.ajax.reload(null, false);
                            }

                            setAlert({
                                'status': 'error',
                                'msg': req.responseJSON.error
                            });
                        }
                    }
                }).done(function () {
                    $('#item-select-all').prop('checked', false);
                });
            }
            else if (action == 'review')
            {
                let data = JSON.parse($('.modal #item-data').val());

                viewSurvey(data);
            }
        });

        function showIndicatorInfo(row)
        {
            $('.loader').fadeIn();

            $.ajax({
                'url': section + '/indicator/get/' + row.id,
                'data': {
                    'questionnaire_country_id': questionnaire_country_id,
                    'indicator_number': row.number
                },
                success: function(data) {
                    let obj = {
                        'large': true,
                        'data': JSON.stringify(row),
                        'action': 'review',
                        'title': row.number + '. ' + row.name,
                        'html': data,
                        'btn': 'Review Indicator'
                    };
                    setModal(obj);

                    pageModal.show();
                }
            });
        }

        function editIndicator(row)
        {
            $('.loader').fadeIn();
            
            requested_changes = row.requested_changes;
            
            $.ajax({
                'url': section + '/indicator/single/edit/' + row.id,
                'data': {'questionnaire_country_id': questionnaire_country_id},
                success: function(data) {
                    let obj = {
                        'action': 'edit',
                        'route': section + '/indicator/single/update/' + row.id,
                        'title': 'Edit Indicator ' + row.number,
                        'html': data
                    };
                    setModal(obj);

                    initDatePicker();

                    pageModal.show();
                }
            });
        }
                
        function getRowData(row)
        {
            let obj = {};
            
            switch (row.dashboard_state)
            {
                case 2:
                case 6:
                    obj.status_label = 'Assigned';
                    obj.status_style = 'positive-invert-with-tooltip' + (row.requested_changes ? ' icon-requested-changes-positive-invert' : '');
                    obj.status_info = (row.requested_changes)
                        ? 'Changes have been requested for this indicator. The MS has not yet started revising the indicator.'
                        : 'Indicator is assigned but has not yet been edited/revised.';

                    break;
                case 3:
                    obj.status_label = 'In progress';
                    obj.status_style = 'positive-invert-with-tooltip' + (row.requested_changes ? ' icon-requested-changes-positive-invert' : '');
                    obj.status_info = (row.requested_changes)
                        ? 'Changes have been requested for this indicator. Indicator is currently under revision by the MS.'
                        : 'The assignee is currently working on this indicator.';

                    break;
                case 4:
                    obj.status_label = 'Completed';
                    obj.status_style = 'approved-with-tooltip' + (row.requested_changes ? ' icon-requested-changes-approved' : '');
                    obj.status_info = (row.requested_changes)
                        ? 'The assigned indicators have been completed, after requested changes, and submitted to the PPoC for approval.'
                        : 'The assignee has submitted their answers, pending approval by the PPoC.';

                    break;
                case 5:
                    obj.status_label = 'Under review';
                    obj.status_style = 'positive-invert-with-tooltip';
                    obj.status_info = user_group + ' has requested changes for the indicator, but the request has not yet been sent to the MS.';

                    break;
                case 7:
                    obj.status_label = 'Approved';
                    obj.status_style = 'positive-with-tooltip' + (row.requested_changes ? ' icon-requested-changes-approved' : '');
                    obj.status_info = (row.requested_changes)
                        ? 'Following request by ' + user_group + ', the indicator has been revised and approved by the MS (PPoC). The indicator is under review by ' + user_group + '.'
                        : 'Indicator has been approved by the MS (PPoC).';

                    break;
                case 8:
                    obj.status_label = 'Approved';
                    obj.status_style = 'positive-with-tooltip' + (row.requested_changes ? ' icon-requested-changes-approved-and-approved' : ' icon-approved');

                    let requested_changes_text = (is_poc || is_finalised)
                        ? 'The indicator can no longer be edited.'
                        : 'The indicator can be unapproved.';
                    let approved_text = (is_poc || is_finalised)
                        ? 'The indicator has been approved by ' + user_group + ' and can no longer be edited.'
                        : 'The indicator has been approved by ' + user_group + ' but it can be unapproved.'

                    obj.status_info = (row.requested_changes)
                        ? 'Changes made to the indicator by the MS have been approved by ' + user_group + '. ' + requested_changes_text
                        : approved_text;

                    break;
                default:
                    obj.status_label = 'Unassigned';
                    obj.status_style = 'positive-invert-with-tooltip';
                    obj.status_info = '';

                    break;
            }
            
            return obj;
        }
    </script>
@endsection
