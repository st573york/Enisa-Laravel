@php
    use App\HelperFunctions\GeneralHelper;
@endphp

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
                                @if (Auth::user()->isAdmin())
                                    <li class="breadcrumb-item"><a href="/questionnaire/admin/management">{{ __('Surveys') }}</a></li>
                                    <li class="breadcrumb-item"><a href="/questionnaire/admin/dashboard/{{ $questionnaire->questionnaire_id }}">{{ __('Survey Dashboard') }} - {!! $questionnaire->questionnaire->title !!}</a></li>
                                @elseif (Auth::user()->isPoC())
                                    <li class="breadcrumb-item"><a href="/questionnaire/management">{{ __('Surveys') }}</a></li>
                                @endif
                                <li class="breadcrumb-item active"><a href="#">{{ __('Survey Summary Data') }} - {{ $questionnaire->country->name }}</a>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-10 offset-1 ps-0">
                    <h1>{{ __('Survey Summary Data') }} - {{ (Auth::user()->isAdmin()) ? $questionnaire->country->name : $questionnaire->questionnaire->title }}</h1>
                </div>
            </div>

            @include('components.alert', ['type' => 'pageAlert'])

            @php
                $is_poc = Auth::user()->isPoC();
            @endphp

            <div class="row mt-5">
                <div class="col-10 offset-1 ps-0 pe-0">
                    <div class="table-section col-12 mt-2">
                        <form class="row">
                            @if (Auth::user()->isAdmin())
                                <div class="col-md-6">
                                    <label for="formSurvey" class="col-form-label">{{ __('Survey') }}</label>
                                    <input class="form-control" type="text" name="survey" id="formSurvey" value="{{ $questionnaire->questionnaire->title }}" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label for="formCountry" class="col-form-label">{{ __('Country') }}</label>
                                    <input class="form-control" type="text" name="survey" id="formCountry" value="{{ $questionnaire->country->name }}" disabled>
                                </div>
                            @endif
                            <div class="col-md-6">
                                <label for="formPrimaryPoC" class="col-form-label">{{ __('Primary PoC') }}</label>
                                <input class="form-control" type="text" name="survey" id="formPrimaryPoC" value="{{ $questionnaire->primary_poc }}" disabled>
                            </div>
                            <div class="col-md-6">
                                <label for="formSurveySubmission" class="col-form-label">{{ __('Survey Submission') }}</label>
                                <input class="form-control" type="text" name="survey" id="formSurveySubmission" value="{{ (!is_null($questionnaire->submitted_at)) ? GeneralHelper::dateFormat($questionnaire->submitted_at, 'd-m-Y') : '-' }}" disabled>
                            </div>
                            <div class="col-md-6">
                                <label for="formStatus" class="col-form-label">{{ __('Survey Status') }}</label>
                                @if (!is_null($questionnaire->status) &&
                                     !is_null($questionnaire->style))
                                    <button class="form-control btn-{{ $questionnaire->style }} btn-label btn-with-tooltip" data-bs-toggle="tooltip" title="{{ $questionnaire->info }}" type="button">{{ $questionnaire->status }}</button>
                                @else
                                    <div>-</div>
                                @endif
                            </div>
                            <div class="col-md-3">
                                <label for="formProgress" class="col-form-label">{{ __('Indicators In progress') }}</label>
                                @if (is_null($questionnaire->submitted_by))
                                    <div class="progress">
                                        <div class="progress-bar bg-{{ $questionnaire->style }}" role="progressbar" style="width: {{ $questionnaire->percentage_in_progress }}%"
                                            aria-valuenow="50" aria-valuemin="0" aria-valuemax="100">{{ $questionnaire->percentage_in_progress }}%</div>
                                    </div>
                                @else
                                    <div>-</div>
                                @endif
                            </div>
                            <div class="col-md-3">
                                <label for="formProgress" class="col-form-label">{{ __('Indicators Approved') }}</label>
                                <div class="progress">
                                    <div class="progress-bar bg-{{ $questionnaire->style }}" role="progressbar" style="width: {{ $questionnaire->percentage_approved }}%"
                                        aria-valuenow="50" aria-valuemin="0" aria-valuemax="100">{{ $questionnaire->percentage_approved }}%</div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="row mt-5">
                <div class="col-10 offset-1 table-section">
                    <h2 class="mb-2">Requested changes</h2>
                    <table id="questionnaire-requested-changes-table" class="display enisa-table-group">
                        <thead>
                            <tr>
                                <th>{{ __('Number') }}</th>
                                <th>{{ __('Requested at') }}</th>
                                <th>{{ __('Requested by') }}</th>
                                <th>{{ __('Answered at') }}</th>
                                <th>{{ __('Answered by') }}</th>
                                <th>{{ __('Changes') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <div class="row mt-5">
                <div class="col-10 offset-1 table-section">
                    <h2 class="mb-2">Data not available</h2>
                    <table id="questionnaire-data-not-available-table" class="display enisa-table-group">
                        <thead>
                            <tr>
                                <th>{{ __('Number') }}</th>
                                <th>{{ __('Indicator name') }}</th>
                                <th>{{ __('Question no') }}</th>
                                <th>{{ __('Question name') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <div class="row mt-5">
                <div class="col-10 offset-1 table-section">
                    <h2 class="mb-2">References</h2>
                    <table id="questionnaire-references-table" class="display enisa-table-group">
                        <thead>
                            <tr>
                                <th>{{ __('Number') }}</th>
                                <th>{{ __('Indicator name') }}</th>
                                <th>{{ __('Question no') }}</th>
                                <th>{{ __('Year') }}</th>
                                <th>{{ __('Source') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <div class="row mt-5">
                <div class="col-10 offset-1 table-section">
                    <h2 class="mb-2">Comments</h2>
                    <table id="questionnaire-comments-table" class="display enisa-table-group">
                        <thead>
                            <tr>
                                <th>{{ __('Number') }}</th>
                                <th>{{ __('Indicator name') }}</th>
                                <th>{{ __('Comment') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

    </main>

    <script src="{{ mix('mix/js/main.js') }}" defer></script>

    <script>
        let section = '/questionnaire/dashboard/summarydata';
        let questionnaire_country_id = '{{ $questionnaire->id }}';
        let requested_changes = <?php echo json_encode($requested_changes); ?>;
        let data_not_available = <?php echo json_encode($data_not_available); ?>;
        let references = <?php echo json_encode($references); ?>;
        let comments = <?php echo json_encode($comments); ?>;
        let is_poc = "{{ $is_poc }}";
        let user_group = <?php echo json_encode(config('constants.USER_GROUP')); ?>;
        
        $(document).ready(function() {
            $('#questionnaire-requested-changes-table').DataTable({
                "data": requested_changes,
                "drawCallback": function() {
                    toggleDataTablePagination(this, '#questionnaire-requested-changes-table_paginate');
                },
                "order": [
                    [1, 'asc']
                ],
                "columns": [
                    {
                        "data": "order"
                    },
                    {
                        "data": "requested_at",
                        render: function(data, type, row) {
                            if (type === 'sort') {
                                return data;
                            }
                                                        
                            if (row.requested_at)
                            {
                                let requested_at = new Date(row.requested_at).toLocaleDateString('en-GB').split('/').join('-');

                                return `<div style="width: 81px;">${requested_at}</div>`;
                            }
                            else {
                                return '<div class="d-flex justify-content-center">-</div>';
                            }
                        }
                    },
                    {
                        "data": "requested_by_name",
                        render: function(data, type, row) {
                            if (is_poc &&
                                row.requested_by_role == 1)
                            {
                                return user_group;
                            }

                            return data;
                        }
                    },
                    {
                        "data": "answered_at",
                        render: function(data, type, row) {
                            if (type === 'sort') {
                                return data;
                            }
                                                        
                            if (row.answered_at)
                            {
                                let answered_at = new Date(row.answered_at).toLocaleDateString('en-GB').split('/').join('-');

                                return `<div style="width: 81px;">${answered_at}</div>`;
                            }
                            else {
                                return '<div class="d-flex justify-content-center">-</div>';
                            }
                        }
                    },
                    {
                        "data": "requested_to_name"
                    },
                    {
                        "data": "changes",
                        render: function(data) {
                            return $(data).text();
                        }
                    }
                ]
            });

            $('#questionnaire-data-not-available-table').DataTable({
                "data": data_not_available,
                "drawCallback": function() {
                    toggleDataTablePagination(this, '#questionnaire-data-not-available-table_paginate');
                },
                "order": [
                    [0, 'asc']
                ],
                "columns": [
                    {
                        "data": "order"
                    },
                    {
                        "data": "title"
                    },
                    {
                        "data": "number"
                    },
                    {
                        "data": "question"
                    }
                ]
            });

            $('#questionnaire-references-table').DataTable({
                "data": references,
                "drawCallback": function() {
                    toggleDataTablePagination(this, '#questionnaire-references-table_paginate');
                },
                "order": [
                    [0, 'asc']
                ],
                "columns": [
                    {
                        "data": "order"
                    },
                    {
                        "data": "title"
                    },
                    {
                        "data": "number"
                    },
                    {
                        "data": "reference_year"
                    },
                    {
                        "data": "reference_source"
                    }
                ]
            });

            $('#questionnaire-comments-table').DataTable({
                "data": comments,
                "drawCallback": function() {
                    toggleDataTablePagination(this, '#questionnaire-comments-table_paginate');
                },
                "order": [
                    [0, 'asc']
                ],
                "columns": [
                    {
                        "data": "order"
                    },
                    {
                        "data": "title"
                    },
                    {
                        "data": "comments"
                    }
                ]
            });
        });
    </script>
@endsection
