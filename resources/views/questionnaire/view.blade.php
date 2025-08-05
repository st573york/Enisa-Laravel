@php
    use Ecas\Session\SessionStorage;
    use App\HelperFunctions\GeneralHelper;
    use App\HelperFunctions\QuestionnaireCountryHelper;
    use App\Models\Indicator;
    use App\Models\IndicatorQuestionChoice;
    use App\Models\IndicatorRequestedChange;
    use App\Models\SurveyIndicator;
    use App\Models\SurveyIndicatorAnswer;
    use App\Models\SurveyIndicatorOption;
    use App\Models\User;
@endphp

@extends('layouts.app')

@section('title', 'Survey')

@section('content')
    <main id="enisa-main" class="bg-white">
        <input hidden id="questionnaire_country_id" value="{{ $questionnaire->id }}" />
        <div class="container-fluid">
            <div class="row ps-0">
                <div class="col-10 offset-1 ps-0">
                    <div class="enisa-breadcrump d-flex">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb text-end">
                                <li class="breadcrumb-item"><a href="/">{{ __('Home') }}</a></li>
                                @php
                                    $questionnaire_title = $questionnaire->questionnaire->title;
                                    $previous = parse_url(url()->previous(), PHP_URL_PATH);
                                    if (!is_null($previous) && !preg_match('/\/questionnaire\/view/', $previous)) {
                                        SessionStorage::session()->setPreviousUrl($previous);
                                    }
                                    $previous = SessionStorage::session()->getPreviousUrl();
                                    $breadcrumb_list = [];
                                    $return_to_label = '';
                                    
                                    if ($previous == '/questionnaire/admin/dashboard/' . $questionnaire->questionnaire_id)
                                    {
                                        array_push($breadcrumb_list, [
                                            'dusk' => '',
                                            'label' => __('Surveys'),
                                            'link' => '/questionnaire/admin/management'
                                        ]);

                                        array_push($breadcrumb_list, [
                                            'dusk' => '',
                                            'label' => __('Survey Dashboard') . ' - ' . $questionnaire_title,
                                            'link' => $previous
                                        ]);

                                        $return_to_label = __('Dashboard');
                                    }
                                    elseif (Auth::user()->isAdmin() && $previous == '/questionnaire/dashboard/management/' . $questionnaire->id)
                                    {
                                        array_push($breadcrumb_list, [
                                            'dusk' => '',
                                            'label' => __('Surveys'),
                                            'link' => '/questionnaire/admin/management'
                                        ]);
                                        
                                        array_push($breadcrumb_list, [
                                            'dusk' => '',
                                            'label' => __('Survey Dashboard') . ' - ' . $questionnaire_title,
                                            'link' => '/questionnaire/admin/dashboard/' . $questionnaire->questionnaire_id
                                        ]);
                                        
                                        array_push($breadcrumb_list, [
                                            'dusk' => '',
                                            'label' => __('Survey Dashboard') . ' - ' . $questionnaire->country->name,
                                            'link' => $previous
                                        ]);

                                        $return_to_label = __('Dashboard');
                                    }
                                    elseif (Auth::user()->isPoC() && $previous == '/questionnaire/dashboard/management/' . $questionnaire->id)
                                    {
                                        array_push($breadcrumb_list, [
                                            'dusk' => '',
                                            'label' => __('Surveys'),
                                            'link' => '/questionnaire/management'
                                        ]);

                                        array_push($breadcrumb_list, [
                                            'dusk' => 'survey_dashboard_management',
                                            'label' => __('Survey Dashboard') . ' - ' . $questionnaire_title,
                                            'link' => $previous
                                        ]);
                                        
                                        $return_to_label = __('Dashboard');
                                    }
                                    elseif ($previous == '/questionnaire/management')
                                    {
                                        array_push($breadcrumb_list, [
                                            'dusk' => '',
                                            'label' => __('Surveys'),
                                            'link' => $previous
                                        ]);

                                        $return_to_label = __('Surveys');
                                    }
                                    elseif ($previous == '/index/survey/configuration/management')
                                    {
                                        array_push($breadcrumb_list, [
                                            'dusk' => '',
                                            'label' => __('Index & Survey Configuration'),
                                            'link' => $previous
                                        ]);

                                        $return_to_label = __('Index & Survey Configuration');
                                    }
                                @endphp
                                @foreach ($breadcrumb_list as $breadcrumb)
                                    <li class="breadcrumb-item"><a dusk="{{ $breadcrumb['dusk'] }}" href={{ $breadcrumb['link'] }}>{{ $breadcrumb['label'] }}</a></li>
                                @endforeach
                                <li class="breadcrumb-item active"><a href="javascript:;">{!! $questionnaire_title !!}</a></li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            @php
                $primary_poc = User::withTrashed()->find($questionnaire->default_assignee);
                $is_admin = Auth::user()->isAdmin();
                $is_primary_poc = Auth::user()->isPrimaryPoC();
                $is_primary_poc_active = ($primary_poc->trashed()) ? false : true;
                $is_poc = Auth::user()->isPoC();
                $is_operator = Auth::user()->isOperator();
                $is_assigned = (!empty($indicators_assigned)) ? true : false;
                $is_assigned_exact = (!empty($indicators_assigned_exact)) ? true : false;
                $survey_indicators = ($is_assigned) ? SurveyIndicator::getSurveyIndicators($questionnaire, $indicators_assigned['id']) : [];
                $is_authorised = ($is_poc || $is_admin) ? true : false;
                $is_assignee_indicators_submitted = (boolval($indicators_submitted));
                $is_questionnaire_started = (boolval($questionnaire_started));
                $is_questionnaire_completed = (boolval($questionnaire->completed));
                $is_questionnaire_submitted = (!is_null($questionnaire->submitted_by)) ? true : false;
                $is_questionnaire_approved = (!is_null($questionnaire->approved_by)) ? true : false;
                $is_last_questionnaire_submitted = (!is_null($last_questionnaire_country) && !is_null($last_questionnaire_country->submitted_by)) ? true : false;
                $pending_requested_changes = IndicatorRequestedChange::getQuestionnaireCountryRequestedChanges($questionnaire, [1]);
                $view_all = (($is_assignee_indicators_submitted && $is_operator) ||
                             ($is_questionnaire_submitted && $is_poc) ||
                             $is_questionnaire_approved ||
                             $action == 'export') ? true : false;
            @endphp

            <div class="row">
                <div class="col-10 d-flex offset-1 ps-0 pe-0">
                    <div class="me-auto">
                        <h1 class="questionnaire-title">{!! $questionnaire_title !!}</h1>
                    </div>
                    @if ($is_assigned && !$view_all)
                        <div class="row" style="margin-top: 0.8rem;">
                            <div class="d-flex">
                                <div style="margin-top: -5px;">
                                    <button class="icon-dropdown-menu btn-unstyle" type="button"></button>
                                </div>
                                <div class="dropdown">
                                    <a dusk="survey_navigation" id="surveyNavigationDropdown" href="#" class="dropdown-toggle" role="button" data-bs-toggle="dropdown" aria-expanded="false">{{ __('Survey navigation') }}</a>
                                    <ul dusk="survey_navigation_dropdown_menu" class="dropdown-menu dropdown-menu-end" role="menu" aria-labelledby="dLabel">
                                        <div class="dropdown-menu-wrapper pe-4">
                                            <div class="row">
                                                <div class="col-12 mt-2 ms-3">
                                                    <h4>{{ __('Survey Outline') }}</h4>
                                                </div>
                                                <div class="col-12 tree-column mt-2 mb-2 ms-3">
                                                    <ul>
                                                        <li>
                                                            <div class="step-choice step-info current" id="step-page-0" data-id="-1" data-type="info">
                                                                <span>{{ __('Participant Information and Background and scope') }}</span>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <span class="ms-span">{{ __('Content of the Surveys') }}</span>
                                                            <ul>
                                                                @foreach ($survey_indicators as $survey_indicator)
                                                                    @php
                                                                        $indicator = $survey_indicator->indicator;
                                                                        $indicator_assigned_or_not = ($survey_indicator->assignee == Auth::user()->id) ? 'assigned' : 'not_assigned';
                                                                    @endphp
                                                                    <li>
                                                                        <div dusk="survey_step_choice_indicator_{{ $indicator->id }}" class="step-choice indicators step-form-indicator {{ $indicator_assigned_or_not }}"
                                                                            id="step-page-{{ $loop->iteration + 1 }}"
                                                                            data-id="{{ $indicator->id }}" data-type="form-indicator">
                                                                            <span>{{ $loop->iteration }}. {!! $indicator->name !!}</span>
                                                                        </div>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            @include('components.alert', ['type' => 'pageAlert'])

            <div class="row alert-section d-none">
                <div class="col-10 offset-1 ps-0 pe-0 mt-2 d-flex justify-content-end">
                    <button type="button" class="btn btn-enisa" onclick="viewSurvey({questionnaire_country_id: {{ $questionnaire->id }}});">{{ __('Start') }}</button>
                </div>
            </div>
           
            {{-- Indicator State Content --}}
            @foreach ($survey_indicators as $survey_indicator)
                @if ($survey_indicator->state_id > 3)
                    @php
                        $indicator = $survey_indicator->indicator;
                        $submitted_requested_changes_indicator = IndicatorRequestedChange::getIndicatorRequestedChanges($questionnaire, $indicator, [2]);
                        $latest_requested_changes_indicator = IndicatorRequestedChange::getLatestIndicatorRequestedChanges($questionnaire, $indicator);
                        $submitted_requested_changes_indicator_history = (!is_null($latest_requested_changes_indicator) && (in_array($survey_indicator->state_id, [4, 6]) || ($survey_indicator->state_id == 7 && $is_questionnaire_submitted)))
                            ? $submitted_requested_changes_indicator->except($latest_requested_changes_indicator->id) : $submitted_requested_changes_indicator;
                        $latest_requested_changes_indicator_state = (!is_null($latest_requested_changes_indicator)) ? $latest_requested_changes_indicator->state : 0;
                        $latest_requested_changes_indicator_deadline = (!is_null($latest_requested_changes_indicator)) ? GeneralHelper::dateFormat($latest_requested_changes_indicator->deadline, 'd-m-Y') : $questionnaire->questionnaire->deadline;
                        $latest_requested_changes_indicator_author_name = (!is_null($latest_requested_changes_indicator)) ? $latest_requested_changes_indicator->user_requested_by->name : null;
                        $latest_requested_changes_indicator_author_role = (!is_null($latest_requested_changes_indicator)) ? $latest_requested_changes_indicator->user_requested_by->permissions()->first()->role->id : null;
                        $is_requested_changes_indicator_author_logged_in = (Auth::user()->name == $latest_requested_changes_indicator_author_name);
                        if (!is_null($latest_requested_changes_indicator) && !$is_admin && $latest_requested_changes_indicator_author_role == 1) {
                            $latest_requested_changes_indicator_author_name = config('constants.USER_GROUP');
                        }

                        $approved_author = (!is_null($survey_indicator->approved_by)) ? User::withTrashed()->find($survey_indicator->approved_by) : null;
                        $approved_author_name = ($approved_author) ? $approved_author->name : '';
                        $approved_author_role = ($approved_author) ? $approved_author->permissions()->first()->role->id : '';
                        $is_approved_author_logged_in = (Auth::user()->name == $approved_author_name);
                            
                        if ($approved_author && !$is_admin && $approved_author_role == 1) {
                            $approved_author_name = config('constants.USER_GROUP');
                        }
                    @endphp
                    <div class="row requested-changes-history d-none {{ ($submitted_requested_changes_indicator_history->count()) ? 'history' : '' }}"
                        data-id="{{ $indicator->id }}">
                        <div class="col-10 d-flex justify-content-end offset-1 ps-0 pe-2">
                            <div class="dropdown">
                                <a dusk="survey_requested_changes_history_{{ $indicator->id }}" href="javascript:;" class="dropdown-toggle" role="button" data-bs-toggle="dropdown" aria-expanded="false">{{ __('Requested changes history') }}</a>
                                <ul dusk="survey_requested_changes_history_dropdown_menu_{{ $indicator->id }}" class="dropdown-menu dropdown-menu-end" role="menu" aria-labelledby="dLabel">
                                    <div class="dropdown-menu-wrapper pe-4">
                                        <div class="row">
                                            <div class="col-12 tree-column mt-2 mb-2 ms-3">
                                                @foreach ($submitted_requested_changes_indicator_history as $key => $submitted_requested_change_indicator)
                                                    @php
                                                        $submitted_requested_change_indicator_author_name = $submitted_requested_change_indicator->user_requested_by->name;
                                                        $submitted_requested_change_indicator_author_role = $submitted_requested_change_indicator->user_requested_by->permissions()->first()->role->id;
                                                        $is_submitted_requested_change_indicator_author_logged_in = (Auth::user()->name == $submitted_requested_change_indicator_author_name) ? true : false;
                                                    @endphp
                                                    <div>
                                                        <div class="d-flex gap-2">
                                                            <span class="requested_change_indicator_by">{{ ($submitted_requested_change_indicator_author_role == 1 && !$is_admin)
                                                                ? config('constants.USER_GROUP') : $submitted_requested_change_indicator_author_name . ($is_submitted_requested_change_indicator_author_logged_in ? ' (you)' : '') }}</span>
                                                            <span>
                                                                <span class="requested_change_indicator_at">{{ GeneralHelper::dateFormat($submitted_requested_change_indicator->requested_at, 'd-m-Y') }} -</span>
                                                                <span class="requested_change_indicator_at">{{ date('H:i', strtotime($submitted_requested_change_indicator->requested_at)) }}</span>
                                                            </span>
                                                        </div>
                                                        <div>{{ strip_tags($submitted_requested_change_indicator->changes) }}</div>
                                                        @if ($loop->last)
                                                            @continue
                                                        @endif
                                                        <div class="dropdown-divider"></div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div dusk="survey_indicator_state_{{ $indicator->id }}" class="row indicator-state d-none"
                        id="page-{{ $loop->index }}"
                        data-id="{{ $indicator->id }}"
                        data-requested-changes-state="{{ $latest_requested_changes_indicator_state }}">
                        <div class="col-10 offset-1 ps-0 pe-0">
                            {{-- Requested Approval --}}
                            @if ($is_authorised)
                                <div class="request-approval-wrap d-none">
                                    <section class="request-approval-section ps-4 pe-4">
                                        <div class="row h-100 align-items-center">
                                            <div class="col-md-6">
                                                <div>
                                                    <span>{{ __('This indicator needs validation.') }}<span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="d-flex justify-content-end gap-2 btns-approval">
                                                    <div dusk="survey_indicator_accept_{{ $indicator->id }}" class="btn btn-approval btn-approve">
                                                        <span>{{ __('Accept') }}</span>
                                                    </div>
                                                    <div dusk="survey_indicator_request_changes_{{ $indicator->id }}" class="btn btn-approval btn-request-changes">
                                                        <span>{{ __('Request changes') }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </section>
                                </div>
                            @endif
                            {{-- Request / Requested Changes --}}
                            <div class="request-requested-changes-wrap d-none {{ (in_array($survey_indicator->state_id, [4, 6, 7])) ? 'mt-2' : '' }}">
                                <section class="request-requested-changes-section ps-4 pe-4">
                                    <div class="row h-100 align-items-center">
                                        <div class="col-md-8">
                                            <div>
                                                <span dusk="survey_indicator_request_changes_title_{{ $indicator->id }}" class="request-changes-title d-none">{{ __('Request changes.') }}</span>
                                                <span dusk="survey_indicator_requested_changes_title_{{ $indicator->id }}" class="requested-changes-title svg-requested d-none">{{ __('Changes have been requested.') }}
                                                    <span class="requested-changes-title-info ps-1">
                                                        {{ __('By:') }}
                                                        <span dusk="survey_indicator_requested_changes_author_{{ $indicator->id }}" class="requested-changes-title-author ps-1 pe-1" id="requested-changes-title-author-{{ $indicator->id }}">{{ $latest_requested_changes_indicator_author_name . ($is_requested_changes_indicator_author_logged_in ? ' (you)' : '') }}</span>
                                                        -
                                                        {{ __('Deadline:') }}
                                                        <span dusk="survey_indicator_requested_changes_deadline_{{ $indicator->id }}" class="requested-changes-title-deadline ps-1">{{ $latest_requested_changes_indicator_deadline }}</span>
                                                    </span>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-4 request-changes-deadline d-none">
                                            <div class="d-flex justify-content-end gap-2">
                                                <div>
                                                    <div class="input-group date">
                                                        <label for="request-requested-changes-deadline-{{ $indicator->id }}" class="col-form-label request-changes-title-info pe-2">{{ __('Deadline:') }}</label>
                                                        <input dusk="survey_indicator_request_changes_deadline_{{ $indicator->id }}" type="text" class="form-control datepicker request-requested-changes-deadline" id="request-requested-changes-deadline-{{ $indicator->id }}" placeholder="Deadline" value="{{ $latest_requested_changes_indicator_deadline }}" />
                                                        <span class="input-group-append"></span>
                                                        <span class="icon-calendar"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @if ($is_authorised)
                                            <div class="col-md-4 requested-changes-discard-edit d-none" data-role="{{ $latest_requested_changes_indicator_author_role }}">
                                                <div class="d-flex justify-content-end gap-2">
                                                    <div class="d-flex justify-content-end gap-2">
                                                        <a dusk="survey_indicator_requested_changes_discard_{{ $indicator->id }}" style="color:#fff;" href="javascript:;" class="btn-discard-requested-changes">{{ __('Discard') }}</a>
                                                        <a dusk="survey_indicator_requested_changes_edit_{{ $indicator->id }}" style="color:#fff;" href="javascript:;" class="btn-edit-requested-changes">{{ __('Edit') }}</a>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </section>
                                <div class="d-flex flex-column gap-2">
                                    <div class="ps-2 pe-2 mt-2 mb-2">
                                        <textarea class="form-control tinymce" id="request-requested-changes-{{ $indicator->id }}" disabled>{{ (!is_null($latest_requested_changes_indicator)) ? $latest_requested_changes_indicator->changes : '' }}</textarea>
                                    </div>
                                    @if ($is_authorised)
                                        <div class="d-flex justify-content-end align-items-center request-changes-actions gap-2 pe-0 mt-2 mb-3 me-3 d-none">
                                            <a href="javascript:;" class="fw-bold d-flex align-items-center me-2 btn-cancel-request-changes">{{ __('Cancel') }}</a>
                                            <a dusk="survey_indicator_request_changes_save_{{ $indicator->id }}" href="javascript:;" class="btn btn-send-request-changes">{{ __('Save') }}</a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            {{-- Approved --}}
                            <div class="approved-wrap d-none">
                                <section class="approved-section ps-4 pe-4">
                                    <div class="row h-100 align-items-center">
                                        <div class="col-md-6">
                                            <div>
                                                <span dusk="survey_indicator_approved_title_{{ $indicator->id }}" class="svg-approved">{{ __('Indicator has been accepted.') }}
                                                    <span class="approved-title-info ps-1">
                                                        {{ __('By:') }}
                                                        <span dusk="survey_indicator_approved_author_{{ $indicator->id }}" class="approved-title-author ps-1" id="approved-title-author-{{ $indicator->id }}">{{ $approved_author_name . ($is_approved_author_logged_in ? ' (you)' : '') }}</span>
                                                    </span>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-6 unapprove d-none">
                                            <div class="d-flex justify-content-end">
                                                <a href="javascript:;" class="btn-unapprove" style="color:#fff;">{{ __('Unapprove') }}</a>
                                            </div>
                                        </div>
                                    </div>
                                </section>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach

            <div class="row mt-3">
                <div class="col-10 offset-1 ps-0 pe-0">
                    <section class="wizard-section">
                        <div>
                            <div class="col-12 ps-0 pe-0">
                                <div class="form-wizard">
                                    <div id="question-form" method="get" role="form">
                                        @php
                                            $user = ($is_questionnaire_submitted && $is_admin) ? User::withTrashed()->find($questionnaire->submitted_by) : Auth::user();
                                            $country = ($user->permissions()->withTrashed()->first()) ? $user->permissions()->withTrashed()->first()->country->name : null;
                                        @endphp
                                        <fieldset dusk="survey_participant_info" class="wizard-fieldset show outline"
                                            id="page-0"
                                            data-id="-1"
                                            data-type="info">
                                            <div class="row">
                                                <div class="col-12 indicator-head">
                                                    <h5><span>{{ __('Participant Information') }}</span></h5>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-12">
                                                    <p class="form-indicator-text-content"></p>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-12 d-flex flex-column">
                                                    <div class="mb-3 row">
                                                        <div class="col-12  col-lg-3 d-flex align-items-end pe-0">
                                                            <label for="inputName" class="form-label">
                                                                {{ __('Name') }}
                                                            </label>
                                                        </div>
                                                        <div class="col-12  col-lg-5 d-flex align-items-end pe-0">
                                                            <input dusk="survey_participant_name" type="text" class="form-control"
                                                                id="inputName"
                                                                aria-describedby="emailHelp"
                                                                value='{{ $user->name }} {{ $user->trashed() ? config('constants.USER_INACTIVE') : '' }}' disabled>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <div class="col-12  col-lg-3 d-flex align-items-end pe-0">
                                                            <label for="inputEmail" class="form-label">
                                                                {{ __('Email Address') }}
                                                            </label>
                                                        </div>
                                                        <div class="col-12  col-lg-5 d-flex align-items-end pe-0">
                                                            <input dusk="survey_participant_email" type="email" class="form-control"
                                                                id="inputEmail"
                                                                aria-describedby="emailHelp"
                                                                value={{ $user->email }} disabled>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <div class="col-12 col-lg-3 d-flex align-items-end pe-0">
                                                            <label for="country-select" class="form-label">
                                                                {{ __('Country') }}
                                                            </label>
                                                        </div>
                                                        <div class="col-12 col-lg-5 d-flex align-items-end pe-0">
                                                            <select dusk="survey_participant_country" id="country-select" class="form-select"
                                                                aria-label="Country Select" disabled>
                                                                <option selected value="{{ $country }}">{{ $country }}</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-12 d-flex flex-column">
                                                    <div class="mb-3 row">
                                                        <div>
                                                            <span style="color: var(--brand-color-1);">*</span>
                                                            <span style="font-weight: 300;">{!! nl2br(e("Important notice: Please note that for auditing and traceability purposes, the above name and email address will appear in logs used internally\nin the EU-CSI tool, for all actions realized during the completion of the survey (i.e., name, email, question answered, date of the provided answer).")) !!}</span></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </fieldset>
                                        <fieldset dusk="survey_background_and_scope" class="wizard-fieldset pt-0 show outline merged"
                                            id="page-1"
                                            data-id="-2"
                                            data-type="info">
                                            @if (!empty($questionnaire->questionnaire->description))
                                                <div class="row">
                                                    <div class="col-12 indicator-head">
                                                        <h5><span>{{ __('Background and scope') }}</span></h5>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-12">
                                                        <p>
                                                            {!! $questionnaire->questionnaire->description !!}
                                                        </p>
                                                    </div>
                                                </div>
                                            @endif
                                            @if ($is_assigned && !$view_all)
                                                <div class="row btm mt-5">
                                                    <div class="col-12 d-flex justify-content-end mt-4 step-nav-buttons">
                                                        <button dusk="survey_start_or_resume" type="button" class="form-wizard-next-btn btn btn-enisa validate step-change">{{ ($is_questionnaire_started) ? __('Resume') : __('Start') }}</button>
                                                    </div>
                                                </div>
                                            @endif
                                        </fieldset>
                                        @foreach ($survey_indicators as $survey_indicator)
                                            @php
                                                $indicator = $survey_indicator->indicator;
                                                $accordions = $indicator->accordions()->orderBy('order')->get();
                                                $assignee = User::withTrashed()->find($survey_indicator->assignee);
                                                $assigned = ($assignee->id == Auth::user()->id) ? true : false;
                                                $assignee_info = '';
                                                if ($assignee->trashed()) {
                                                    $assignee_info = config('constants.USER_INACTIVE');
                                                }
                                                else if ($assigned) {
                                                    $assignee_info = '(you)';
                                                }
                                                $indicator_assigned_or_not = ($assigned) ? 'assigned' : 'not_assigned';
                                                $indicator_state = $survey_indicator->state_id;
                                                $indicator_answers_loaded = $survey_indicator->answers_loaded;
                                                $indicator_area = $indicator->default_subarea->default_area->name;
                                                $indicator_subarea = $indicator->default_subarea->name;
                                                $indicator_number = $indicator->order;
                                                $indicator_last_saved = $survey_indicator->last_saved;
                                                $indicator_rating = $survey_indicator->rating;
                                                $indicator_comments = $survey_indicator->comments;
                                                if ($indicator_answers_loaded)
                                                {
                                                    $last_survey_indicator = QuestionnaireCountryHelper::getLastPublishedSurveyIndicatorData($questionnaire, $indicator);
                                                    $last_indicator = $last_survey_indicator->indicator;

                                                    $indicator_rating = $last_survey_indicator->rating;
                                                    $indicator_comments = $last_survey_indicator->comments;

                                                    $last_data = [];
                                                    $last_data['assignee'] = User::withTrashed()->find($last_survey_indicator->assignee);
                                                    $last_data['assigned'] = ($last_survey_indicator->assignee == Auth::user()->id) ? true : false;
                                                    $last_data['assignee_info'] = '';
                                                    if ($last_data['assignee']->trashed()) {
                                                        $last_data['assignee_info'] = config('constants.USER_INACTIVE');
                                                    }
                                                    else if ($last_data['assigned']) {
                                                        $last_data['assignee_info']= '(you)';
                                                    }
                                                    $last_data['saved'] = GeneralHelper::dateFormat($last_survey_indicator->last_saved, 'd-m-Y');
                                                }
                                            @endphp
                                            <fieldset dusk="survey_indicator_{{ $indicator->id }}" class="wizard-fieldset {{ $indicator_assigned_or_not }} {{ ($view_all) ? 'mt-2 show' : '' }} outline"
                                                id="page-{{ $loop->iteration + 1 }}"
                                                data-id="{{ $indicator->id }}"
                                                data-type="form-indicator"
                                                data-state="{{ $indicator_state }}"
                                                data-assignee="{{ $assignee->id }}">
                                                <div class="row mb-2">
                                                    <div class="col-8">
                                                        <h4>
                                                            <span class="form-indicator-assignee-title">{{ __('Validated by:') }} <span dusk="survey_indicator_assigned_to_{{ $indicator->id }}" class="form-indicator-assignee {{ $indicator_assigned_or_not }}">{{ $assignee->name }} {{ $assignee_info }}</span></span>
                                                        </h4>
                                                    </div>
                                                    <div class="col-4 d-flex justify-content-end mt-2 {{ (is_null($indicator_last_saved) || $view_all) ? 'd-none' : '' }}">
                                                        <h6>
                                                            <span class="form-indicator-last-saved-title">{{ __('Last saved:') }} <span dusk="survey_indicator_last_saved_{{ $indicator->id }}" class="form-indicator-last-saved local-timestamp">{{ $indicator_last_saved }}</span></span>
                                                        </h6>
                                                    </div>
                                                    @php
                                                        $progress = round(($indicator_number / $loop->count) * 100);
                                                    @endphp
                                                    <div>
                                                        <span class="form-indicator-progress-percentage">{{ __('Indicator') }}
                                                            {{ $indicator_number }} {{ __('of') }}
                                                            {{ $loop->count }} {{ ($is_assigned && !$view_all) ? '- ' . $progress . '%' : '' }}
                                                        </span>
                                                    </div>
                                                    @if ($is_assigned && !$view_all)
                                                        <div>
                                                            <div class="progress form-indicator-progress-bar mt-1 mb-2">
                                                                <div class="progress-bar bg-approved" role="progressbar" style="width: {{ $progress }}%" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="indicator-head">
                                                            <div class="col-8">
                                                                <h5>
                                                                    <div>
                                                                        <span class="form-indicator-info">{!! $indicator_area !!} /</span>
                                                                        <span class="form-indicator-info">{!! $indicator_subarea !!}</span>
                                                                    </div>
                                                                    <div dusk="survey_indicator_number_and_title_{{ $indicator->id }}">
                                                                        <span class="form-indicator-id">{{ $indicator_number }}</span>.
                                                                        <span class="form-indicator-name">{!! $indicator->name !!}</span>
                                                                    </div>
                                                                </h5>
                                                            </div>
                                                            @if ($assigned && !$view_all && $is_last_questionnaire_submitted && !in_array($indicator_state, [4, 7, 8]))
                                                                <div class="col-4 d-flex justify-content-end indicator-action">
                                                                    @php
                                                                        $action_load_reset = ($indicator_answers_loaded) ? 'reset' : 'load';
                                                                    @endphp
                                                                    <button dusk="survey_indicator_load_reset_answers_{{ $indicator->id }}" type="button" class="btn btn-indicator-head" onclick="loadResetQuestionnaireIndicatorData('{{ $action_load_reset }}');">{{ ($action_load_reset == 'load' ? 'Pre-fill' : 'Reset')  . ' ' . $last_questionnaire_country->questionnaire->year . ' Answers' }}</button>
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <div class="indicator-body pt-2">
                                                            @php
                                                                $algorithm = ($indicator->algorithm != strip_tags($indicator->algorithm)) ? $indicator->algorithm : '<p class="form-indicator-text-content">' . $indicator->algorithm . '</p>';
                                                                $count = 0;
                                                            @endphp
                                                            <div class="col-10 offset-1">
                                                                {!! $algorithm !!}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="accordion" id="accordionPanelsStayOpen">
                                                            @foreach ($accordions as $accordion)
                                                                @php
                                                                    $title = '<span class="form-questions-title">' . $accordion->title . '</span>';
                                                                    $questions = $accordion->questions()->orderBy('order')->get();
                                                                @endphp
                                                                @if ($view_all)
                                                                    <div class="indicator-head">
                                                                        <h5>
                                                                            {!! $title !!}
                                                                        </h5>
                                                                    </div>
                                                                @else
                                                                    <div class="accordion-item">
                                                                        <div class="accordion-header" id="accordion-heading-{{ $indicator->id }}-{{ $loop->iteration }}">
                                                                            <button dusk="survey_accordion_button_{{ $indicator->id }}.{{ $loop->iteration }}" id="accordion-button-{{ $indicator->id }}-{{ $loop->iteration }}" class="accordion-button {{ ($loop->iteration == 1) ? '' : 'collapsed' }}"
                                                                                type="button" data-bs-toggle="collapse"
                                                                                data-bs-target="#accordion-collapse-{{ $indicator->id }}-{{ $loop->iteration }}"
                                                                                aria-expanded="{{ ($loop->iteration == 1) ? 'true' : 'false' }}"
                                                                                aria-controls="accordion-collapse-{{ $indicator->id }}-{{ $loop->iteration }}">
                                                                                {!! $title !!}
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                                <div dusk="survey_accordion_collapse_{{ $indicator->id }}.{{ $loop->iteration }}" id="accordion-collapse-{{ $indicator->id }}-{{ $loop->iteration }}"
                                                                    class="accordion-collapse collapse {{ ($loop->iteration == 1 || $view_all) ? 'show' : '' }}"
                                                                    aria-labelledby="accordion-heading-{{ $indicator->id }}-{{ $loop->iteration }}"
                                                                    data-order="{{ $accordion->order }}">
                                                                    @foreach ($questions as $question)
                                                                        @php
                                                                            $count++;
                                                                            $question_number = $indicator_number . '.' . $count;
                                                                            $dusk_number = $indicator->id . '.' . $count;
                                                                            $info = ($question->info) ? '<span class="info-icon-black" data-bs-toggle="tooltip" data-bs-placement="right" title="' . $question->info . '"></span>' : '';

                                                                            if ($indicator_answers_loaded &&
                                                                                $question->compatible)
                                                                            {
                                                                                $last_accordion = $last_indicator->accordions()->where('indicator_id', $last_indicator->id)->where('order', $accordion->order)->first();
                                                                                $last_question = $last_accordion->questions()->where('accordion_id', $last_accordion->id)->where('order', $question->order)->first();

                                                                                $survey_indicator_answer = SurveyIndicatorAnswer::getSurveyIndicatorAnswer($last_survey_indicator, $last_question);
                                                                                $survey_indicator_options = SurveyIndicatorOption::getSurveyIndicatorOptions($last_survey_indicator, $last_question);
                                                                            }
                                                                            else
                                                                            {
                                                                                $survey_indicator_answer = SurveyIndicatorAnswer::getSurveyIndicatorAnswer($survey_indicator, $question);
                                                                                $survey_indicator_options = SurveyIndicatorOption::getSurveyIndicatorOptions($survey_indicator, $question);
                                                                            }
                                                                        @endphp
                                                                        <div dusk="survey_indicator_question_{{ $dusk_number }}" id="survey_indicator_question_{{ $question->id }}" class="accordion-body indicator-body question-body pt-2">
                                                                            <div class="form-question-text">
                                                                                <p class="fw-bold">
                                                                                    <span class="form-required {{ ($question->answers_required) ? '' : 'd-none' }}" style="color: var(--brand-color-1);">*</span>
                                                                                    <span dusk="survey_indicator_question_number_{{ $dusk_number }}">{{ $question_number }}</span>. {!! $question->title !!}{!! $info !!}
                                                                                </p>
                                                                            </div>
                                                                            @if (!$view_all)
                                                                                @if ($assigned && $indicator_answers_loaded)
                                                                                    <div class="form-indicator-question-load-reset">
                                                                                        <p>
                                                                                            @if ($question->compatible)
                                                                                                <span dusk="survey_indicator_answers_loaded" class="answers-loaded">Answered by: {{ $last_data['assignee']->name }} {{ $last_data['assignee_info'] }} - on {{ $last_data['saved'] }}</span>
                                                                                            @else
                                                                                                <span dusk="survey_indicator_answers_not_loaded" class="answers-not-loaded">Unable to load previous answers. The possible answers have changed since last year.</span>
                                                                                            @endif
                                                                                        </p>
                                                                                    </div>
                                                                                @endif
                                                                                <div class="form-indicator-question-answer d-none">
                                                                                    <p>
                                                                                        <span class="answers-provided">Answered by: {{ $assignee->name }} {{ $assignee_info }} - on <span class="answers-provided-timestamp local-timestamp">{{ (!is_null($survey_indicator_answer)) ? $survey_indicator_answer->last_saved : '' }}</span></span>
                                                                                    </p>
                                                                                </div>
                                                                            @endif
                                                                            <div id="form-indicator-{{ $indicator->id }}"
                                                                                data-number="form-indicator-{{ $indicator_number }}"
                                                                                data-type="form-indicator"
                                                                                class="col-12 pe-0 mb-2 form-indicators">
                                                                                <div dusk="survey_indicator_choice_{{ $dusk_number }}" class="input-wrapper d-flex gap-4 mb-3 input-choice required"
                                                                                    data-order="{{ $loop->index }}">
                                                                                    @php
                                                                                        $choices = ($question->type_id == 3) ? IndicatorQuestionChoice::whereIn('id', [2, 3])->get() : IndicatorQuestionChoice::whereIn('id', [1, 3])->get();
                                                                                        $options = $question->options()->get();
                                                                                        
                                                                                        $choice_id = (!is_null($survey_indicator_answer)) ? $survey_indicator_answer->choice_id : '';
                                                                                        $free_text = (!is_null($survey_indicator_answer)) ? $survey_indicator_answer->free_text : '';
                                                                                        $reference_year = (!is_null($survey_indicator_answer)) ? $survey_indicator_answer->reference_year : '';
                                                                                        $reference_source = (!is_null($survey_indicator_answer)) ? $survey_indicator_answer->reference_source : '';
                                                                                    @endphp
                                                                                    @foreach ($choices as $choice)
                                                                                        <div class="form-check"
                                                                                            data-toggle="buttons">
                                                                                            <input type="radio"
                                                                                                class="form-input form-check-input {{ $indicator_assigned_or_not }}"
                                                                                                name="form-indicator-{{ $indicator->id }}-choice-{{ $accordion->order }}-{{ $question->order }}"
                                                                                                id="form-indicator-{{ $indicator->id }}-choice-{{ $accordion->order }}-{{ $question->order }}-{{ $loop->iteration }}"
                                                                                                value="{{ $choice->id }}"
                                                                                                {{ ($choice->id == $choice_id || $loop->first) ? 'checked' : '' }}>
                                                                                            <label class="form-check-label"
                                                                                                for="form-indicator-{{ $indicator->id }}-choice-{{ $accordion->order }}-{{ $question->order }}-{{ $loop->iteration }}">
                                                                                                {!! $choice->text !!}
                                                                                            </label>
                                                                                        </div>
                                                                                        @if ($assigned && !$view_all && $loop->last)
                                                                                            <div class="clear-answers-container">
                                                                                                <a href="#" class="clear-answers">{{ __('Clear All Answers') }}</a>
                                                                                            </div>
                                                                                        @endif
                                                                                    @endforeach
                                                                                </div>
                                                                                <div dusk="survey_indicator_invalid_choice_{{ $dusk_number }}" class="invalid-feedback"></div>
                                                                                <div dusk="survey_indicator_answers_{{ $dusk_number }}" class="input-wrapper actual-answers {{ $question->type()->first()->type }} {{ ($question->answers_required) ? 'required' : '' }}"
                                                                                    data-order="{{ $loop->index }}">
                                                                                    @if ($question->type_id == 2)
                                                                                        @foreach ($options as $option)
                                                                                            <div class="form-check">
                                                                                                <input type="checkbox"
                                                                                                    class="form-input form-check-input {{ $indicator_assigned_or_not }} {{ $option->master ? 'master' : '' }}"
                                                                                                    name="form-indicator-{{ $indicator->id }}-answers-{{ $accordion->order }}-{{ $question->order }}"
                                                                                                    id="form-indicator-{{ $indicator->id }}-answers-{{ $accordion->order }}-{{ $question->order }}-{{ $loop->iteration }}"
                                                                                                    value="{{ $option->value }}"
                                                                                                    {{ (in_array($option->value, $survey_indicator_options)) ? 'checked' : '' }}>
                                                                                                <label class="form-check-label"
                                                                                                    for="form-indicator-{{ $indicator->id }}-answers-{{ $accordion->order }}-{{ $question->order }}-{{ $loop->iteration }}">
                                                                                                    {!! $option->text !!}
                                                                                                </label>
                                                                                            </div>
                                                                                        @endforeach
                                                                                    @elseif ($question->type_id == 1)
                                                                                        @foreach ($options as $option)
                                                                                            <div class="form-check"
                                                                                                data-toggle="buttons">
                                                                                                <input type="radio"
                                                                                                    class="form-input form-check-input {{ $indicator_assigned_or_not }}"
                                                                                                    name="form-indicator-{{ $indicator->id }}-answers-{{ $accordion->order }}-{{ $question->order }}"
                                                                                                    id="form-indicator-{{ $indicator->id }}-answers-{{ $accordion->order }}-{{ $question->order }}-{{ $loop->iteration }}"
                                                                                                    value="{{ $option->value }}"
                                                                                                    {{ (in_array($option->value, $survey_indicator_options)) ? 'checked' : '' }}>
                                                                                                <label class="form-check-label"
                                                                                                    for="form-indicator-{{ $indicator->id }}-answers-{{ $accordion->order }}-{{ $question->order }}-{{ $loop->iteration }}">
                                                                                                    {!! $option->text !!}
                                                                                                </label>
                                                                                            </div>
                                                                                        @endforeach
                                                                                    @elseif ($question->type_id == 3)
                                                                                        <div>
                                                                                            <input type="text"
                                                                                                class="form-input form-control {{ $indicator_assigned_or_not }}"
                                                                                                name="form-indicator-{{ $indicator->id }}-answers-{{ $accordion->order }}-{{ $question->order }}"
                                                                                                id="form-indicator-{{ $indicator->id }}-answers-{{ $accordion->order }}-{{ $question->order }}-{{ $loop->iteration }}"
                                                                                                value="{!! $free_text !!}" />
                                                                                        </div>
                                                                                    @endif
                                                                                </div>
                                                                                <div dusk="survey_indicator_invalid_answers_{{ $dusk_number }}" class="invalid-feedback"></div>
                                                                            </div>
                                                                            <div class="col-12 mb-4 form-references {{ ($question->reference_required) ? 'required' : '' }}">
                                                                                <span class="form-required {{ $indicator_assigned_or_not }} {{ ($question->reference_required) ? '' : 'd-none' }}" style="color: var(--brand-color-1);">*</span>
                                                                                <label for="form-indicator-{{ $indicator->id }}-reference-year-{{ $accordion->order }}-{{ $question->order }}"
                                                                                    class="form-label mt-2 {{ $indicator_assigned_or_not }}">{{ __('Reference Year') }}</label>
                                                                                @php
                                                                                    $years = range(2000, date('Y') + 1);
                                                                                    rsort($years);
                                                                                @endphp
                                                                                <select class="form-select {{ $indicator_assigned_or_not }}" name="form-indicator-{{ $indicator->id }}-reference-year-{{ $accordion->order }}-{{ $question->order }}" id="form-indicator-{{ $indicator->id }}-reference-year-{{ $accordion->order }}-{{ $question->order }}" aria-label="Reference Year" style="width: 20%">
                                                                                    <option value="" selected disabled>{{ __('Choose...') }}</option>
                                                                                    @foreach ($years as $year)
                                                                                        <option value="{{ $year }}" {{ ($year == $reference_year) ? 'selected' : '' }}>{{ $year }}</option>
                                                                                    @endforeach
                                                                                </select>
                                                                                <div dusk="survey_indicator_invalid_reference_year_{{ $dusk_number }}" class="invalid-feedback"></div>
                                                                            </div>
                                                                            <div dusk="survey_indicator_reference_source_{{ $dusk_number }}" class="col-12 mb-4 form-references {{ ($question->reference_required) ? 'required' : '' }}">
                                                                                <span class="form-required {{ $indicator_assigned_or_not }} {{ ($question->reference_required) ? '' : 'd-none' }}" style="color: var(--brand-color-1);">*</span>
                                                                                <label for="form-indicator-{{ $indicator->id }}-reference-source-{{ $accordion->order }}-{{ $question->order }}"
                                                                                    class="form-label mt-2 {{ $indicator_assigned_or_not }}">{{ __('Reference Source') }}</label>
                                                                                <textarea class="form-control outline {{ $indicator_assigned_or_not }}" name="form-indicator-{{ $indicator->id }}-reference-source-{{ $accordion->order }}-{{ $question->order }}" id="form-indicator-{{ $indicator->id }}-reference-source-{{ $accordion->order }}-{{ $question->order }}" placeholder="{{ __('Include here the source of data. Note that you should use the latest data available.') }}">{!! $reference_source !!}</textarea>
                                                                                <div dusk="survey_indicator_invalid_reference_source_{{ $dusk_number }}" class="invalid-feedback"></div>
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row btm mt-5">
                                                    <div class="col-md-12">
                                                        <!-- Rating / Comments -->
                                                        <div>
                                                            <form id="comments_and_rating">
                                                                <fieldset>
                                                                    <div dusk="survey_indicator_rating_wrapper_{{ $indicator->id }}" class="col-12 rating-wrapper mb-4">
                                                                        @if ($assigned && !$view_all && $indicator_answers_loaded)
                                                                            <div class="form-indicator-question-load-reset">
                                                                                <p>
                                                                                    <span dusk="survey_indicator_rating_loaded" class="comments-and-rating-loaded">Rating provided by: {{ $last_data['assignee']->name }} {{ $last_data['assignee_info'] }} - on {{ $last_data['saved'] }}</span>
                                                                                </p>
                                                                            </div>
                                                                        @endif
                                                                        <span class="form-required" style="color: var(--brand-color-1);">*</span>
                                                                        <label for="form-indicator-{{ $indicator->id }}-rating"
                                                                            class="{{ $indicator_assigned_or_not }}">{{ __('Please rate the relevance of the indicator by selecting 1-5 stars. The rating of the indicator will be used to calculate the weight for this indicator.') }}</label>
                                                                        <div dusk="survey_indicator_rating_{{ $indicator->id }}" class="form-rating rating {{ $indicator_assigned_or_not }} required">
                                                                            <span class="pe-1">Rating:</span>
                                                                            @for ($i = 5; $i >= 1; $i--)
                                                                                <input
                                                                                    class="form-indicator-{{ $indicator->id }}-rating"
                                                                                    type="radio"
                                                                                    id="form-indicator-{{ $indicator->id }}-rating-{{ $i }}"
                                                                                    name="form-indicator-{{ $indicator->id }}-rating"
                                                                                    value="{{ $i }}"
                                                                                    {{ ($indicator_rating == $i) ? 'checked' : '' }} />
                                                                                <label class="star"
                                                                                    for="form-indicator-{{ $indicator->id }}-rating-{{ $i }}"
                                                                                    aria-hidden="true"></label>
                                                                            @endfor
                                                                        </div>
                                                                        <div dusk="survey_indicator_invalid_rating_{{ $indicator->id }}" class="invalid-feedback"></div>
                                                                    </div>
                                                                    <div dusk="survey_indicator_comments_wrapper_{{ $indicator->id }}" class="form-comments">
                                                                        @if ($assigned && !$view_all && $indicator_answers_loaded)
                                                                            <div class="form-indicator-question-load-reset">
                                                                                <p>
                                                                                    <span dusk="survey_indicator_comments_loaded" class="comments-and-rating-loaded">Comments provided by: {{ $last_data['assignee']->name }} {{ $last_data['assignee_info'] }} - on {{ $last_data['saved'] }}</span>
                                                                                </p>
                                                                            </div>
                                                                        @endif
                                                                        <textarea class="form-control user-comments outline {{ $indicator_assigned_or_not }}" name="form-indicator-{{ $indicator->id }}-comments" id="form-indicator-{{ $indicator->id }}-comments" rows="5" placeholder="{{ __("Include here any remark that could be useful in the processing of data e.g. explaining if / why your country does not collect data\nfor a specific question; or if your country does not want to share the data.") }}">{!! $indicator_comments !!}</textarea>
                                                                    </div>
                                                                </fieldset>
                                                            </form>
                                                        </div>
                                                        <!--End Comments Col -->
                                                    </div>
                                                    @if ($view_all)
                                                        @if ($loop->last)
                                                            @if ($return_to_label)
                                                                <div class="col-12 d-flex justify-content-end mt-4 step-nav-buttons">
                                                                    <button dusk="back_to_page" type="button" class="btn btn-enisa back" onclick="location.href='{{ $previous }}'">{{ __('Back to') }} {{ $return_to_label }}</button>
                                                                </div>
                                                            @endif
                                                        @endif
                                                    @else
                                                        @php
                                                            $previous_btn = '<button dusk="survey_indicator_previous_' . $indicator->id . '" type="button" class="form-wizard-previous-btn btn-enisa-invert btn btn-enisa validate step-change">' . __('Previous') . '</button>';
                                                            $save_btn = '<button dusk="survey_indicator_save_' . $indicator->id . '" type="button" class="btn-enisa-invert btn btn-enisa step-save" disabled>' . __('Save') . '</button>';
                                                            $next_btn = '<button dusk="survey_indicator_next_' . $indicator->id . '" type="button" class="form-wizard-next-btn btn btn-enisa validate step-change">' . __('Next') . '</button>';
                                                        @endphp
                                                        @if ($loop->last)
                                                            @if ($is_admin)
                                                                <div class="col-12 d-flex justify-content-between mt-4 step-nav-buttons">
                                                                    {!! $previous_btn !!}
                                                                    @if ($return_to_label)
                                                                        <button dusk="back_to_page" type="button" class="btn btn-enisa back" onclick="location.href='{{ $previous }}'">{{ __('Back to') }} {{ $return_to_label }}</button>
                                                                    @endif
                                                                </div>
                                                            @else
                                                                <div class="col-12 d-flex mt-4 step-nav-buttons">
                                                                    <div class="me-auto">
                                                                        {!! $previous_btn !!}
                                                                    </div>
                                                                    @if ($assigned && in_array($indicator_state, [2, 3, 6]))
                                                                        <div class="pe-2">
                                                                            {!! $save_btn !!}
                                                                        </div>
                                                                    @endif
                                                                    @if (($is_assigned_exact && !$is_assignee_indicators_submitted) || $is_primary_poc)
                                                                        <div>
                                                                            <button dusk="survey_review_and_submit" type="button" class="btn btn-enisa" id="questionnaire-review">{{ __('Review & Submit') }}</button>
                                                                        </div>
                                                                    @else
                                                                        @if ($return_to_label)
                                                                            <button dusk="back_to_page" type="button" class="btn btn-enisa back" onclick="location.href='{{ $previous }}'">{{ __('Back to') }} {{ $return_to_label }}</button>
                                                                        @endif
                                                                    @endif
                                                                </div>
                                                            @endif
                                                        @else
                                                            <div class="col-12 d-flex mt-4 step-nav-buttons">
                                                                <div class="me-auto">
                                                                    {!! $previous_btn !!}
                                                                </div>
                                                                @if ($assigned && in_array($indicator_state, [2, 3, 6]))
                                                                    <div class="pe-2">
                                                                        {!! $save_btn !!}
                                                                    </div>
                                                                @endif
                                                                <div>
                                                                    {!! $next_btn !!}
                                                                </div>
                                                            </div>
                                                        @endif
                                                    @endif
                                                </div>
                                            </fieldset>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>

        <div dusk="survey_review_and_submit_modal" class="modal fade" id="pageModal" tabindex="-1" aria-labelledby="pageModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header p-3">
                        <h5 dusk="survey_review_and_submit_modal_title" class="modal-title" id="pageModalLabel"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-3"></div>
                    <div class="modal-footer d-flex justify-content-between">
                        <button dusk="survey_review_and_submit_modal_close" type="button" class="btn btn-enisa" data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button dusk="survey_review_and_submit_modal_submit" type="button" class="btn btn-enisa process-data" id='questionnaire-submit'></button>
                    </div>
                </div>
            </div>
        </div>

        <div dusk="survey_unsaved_changes_modal" class="modal fade" id="dirtyModal" tabindex="-1" aria-labelledby="dirtyModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header p-3">
                        <h5 dusk="survey_unsaved_changes_modal_title" class="modal-title" id="dirtyModalLabel">
                            <span id="status-message" class="error">You have unsaved changes!</span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div dusk="survey_unsaved_changes_modal_message" class="modal-body p-3">
                        This page has unsaved changes that will be lost. Save or discard the answers before leaving this page.
                    </div>
                    <div class="modal-footer d-flex">
                        <div class="me-auto">
                            <button dusk="survey_unsaved_changes_modal_close" type="button" class="btn btn-enisa" data-bs-dismiss="modal">{{ __('Close') }}</button>
                        </div>
                        <div>
                            <button dusk="survey_unsaved_changes_modal_discard" type="button" class="btn btn-enisa" id="step-discard-goto">{{ __('Discard changes') }}</button>
                        </div>
                        <div>
                            <button dusk="survey_unsaved_changes_modal_save" type="button" class="btn btn-enisa" id='step-save-goto'>{{ __('Save changes') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="{{ mix('mix/js/main.js') }}" defer></script>
    <script src="{{ mix('mix/js/modal.js') }}" defer></script>
    <script src="{{ mix('mix/js/alert.js') }}" defer></script>
    <script src="{{ mix('mix/js/datepicker-custom.js') }}" defer></script>
    <script src="{{ mix('mix/js/tinymce-custom.js') }}" defer></script>
    <script src="{{ mix('js/tinymce/tinymce.min.js') }}" referrerpolicy="origin"></script>
    <script src="{{ mix('mix/js/questionnaire.js') }}" defer></script>
    <script src="{{ mix('mix/js/questionnaire-actions.js') }}" defer></script>
    
    <script>
        let pageModal = new bootstrap.Modal(document.getElementById('pageModal'));
        let dirtyModal = new bootstrap.Modal(document.getElementById('dirtyModal'));
        let questionnaire_country_id = $('#questionnaire_country_id').val();
        let questionnaire_deadline = "{{ $questionnaire->questionnaire->deadline }}";
        let action = "{{ $action }}";
        let requested_indicator = "{{ $requested_indicator }}";
        let requested_action = "{{ $requested_action }}";
        let view_all = "{{ $view_all }}";
        let is_assigned_exact = "{{ $is_assigned_exact }}";
        let is_assignee_indicators_submitted = "{{ $is_assignee_indicators_submitted }}";
        let is_questionnaire_completed = "{{ $is_questionnaire_completed }}";
        let is_questionnaire_submitted = "{{ $is_questionnaire_submitted }}";
        let is_admin = "{{ $is_admin }}";
        let is_primary_poc = "{{ $is_primary_poc }}";
        let is_primary_poc_active = "{{ $is_primary_poc_active }}";
        let is_poc = "{{ $is_poc }}";
        let user_logged_in = <?php echo json_encode(Auth::user()); ?>;
        let user_group = <?php echo json_encode(config('constants.USER_GROUP')); ?>;
        let pending_requested_changes = <?php echo json_encode($pending_requested_changes); ?>;
        let is_dropdown_menu_opened = false;

        $(document).ready(function() {
            skipFadeOut = true;

            convertToLocalTimestamp();
            
            initDatePicker();
            initTinyMCE({
                'height': 150
            });
            
            $('.wizard-fieldset.not_assigned :input, \
               .wizard-fieldset.assigned[data-state="4"] :input, \
               .wizard-fieldset.assigned[data-state="7"] :input, \
               .wizard-fieldset.assigned[data-state="8"] :input').not(':checked').not(':button').prop('disabled', true);

            if (is_assignee_indicators_submitted ||
                is_questionnaire_submitted)
            {
                $('.wizard-fieldset :input').not(':checked').not(':button').prop('disabled', true);
            }
        });

        $(document).on('click', '.close-alert', function () {
            $('.alert-section').addClass('d-none');
        });

        $(document).on('click', function (e) {
            if ($(e.target).closest('#surveyNavigationDropdown').length === 0 &&
                $(e.target).closest('.icon-dropdown-menu').length === 0)
            {
                is_dropdown_menu_opened = false;
            }
        });

        $(document).on('click', '.icon-dropdown-menu', function (e) {
            if (is_dropdown_menu_opened)
            {
                $('#surveyNavigationDropdown').dropdown('hide');

                is_dropdown_menu_opened = false;
            }
            else
            {
                $('#surveyNavigationDropdown').dropdown('show');

                is_dropdown_menu_opened = true;
            }
        });

        $(document).on('click', '#surveyNavigationDropdown', function (e) {
            if ($(this).hasClass('show')) {
                is_dropdown_menu_opened = true;
            }
            else {
                is_dropdown_menu_opened = false;
            }
        });

        $(document).on('click', '.form-check-input', function (e) {
            let state = $(this).closest('fieldset').attr('data-state');
            
            if ($(this).hasClass('not_assigned') ||
                $.inArray(parseInt(state), [4, 7, 8]) != -1 ||
                is_assignee_indicators_submitted ||
                is_questionnaire_submitted)
            {
                e.preventDefault();
            }
        });
        
        $(document).on('show', '.request-requested-changes-deadline', function() {
            if (datepickerLimit)
            {
                $(this).datepicker('setStartDate', new Date());
                datepickerLimit = false;
            }
        });

        function viewSurvey(obj)
        {
            let requested_indicator = (obj.requested_indicator) ? '<input type="hidden" name="requested_indicator" value="' + obj.requested_indicator + '"/>' : '';
            let requested_action = (obj.requested_action) ? '<input type="hidden" name="requested_action" value="' + obj.requested_action + '"/>' : '';
            let form = `<form action="/questionnaire/view/${obj.questionnaire_country_id}" method="POST">
                            @csrf
                            <input type="hidden" name="action" value="view"/>
                            ${requested_indicator}
                            ${requested_action}
                        </form>`;

            $(form).appendTo($(document.body)).submit();
        }
    </script>
@endsection
