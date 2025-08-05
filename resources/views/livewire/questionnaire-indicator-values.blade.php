<div wire:poll.3000ms>
    @php
        $is_latest_questionnaire = $latest_questionnaire_data->id == $loaded_questionnaire_data->id ? true : false;
        $task_name = 'No Calculation';
        $last_calculation_date = '--/--/----';
        
        if ($task) {
            $task_name = $task->status->name;
            if ($task->status->id != 1) {
                $last_calculation_date = isset($task->payload['last_indicator_values_calculation_at']) ? $task->payload['last_indicator_values_calculation_at'] : $last_calculation_date;
            }
        }
        
        // Fire task event
        $this->emit('indicatorValuesCalculation' . preg_replace('/[\s_]/', '', $task_name));
    @endphp
    <div class="row task-state mt-3 d-none {{ $task ? 'show' : '' }}">
        <div class="col-10 offset-1 ps-0 pe-0">
            <div class="in-progress-wrap d-none {{ $task && $task->status->id == 1 ? 'show' : '' }}">
                <section class="in-progress-section ps-4 pe-4">
                    <div class="row h-100 align-items-center">
                        <div class="col-md-12">
                            <span>{{ __('Indicator values calculation in progress. Please wait...') }}</span>
                        </div>
                    </div>
                </section>
            </div>
            <div class="approved-wrap d-none {{ $task && $task->status->id == 2 ? 'show' : '' }}">
                <section class="approved-section ps-4 pe-4">
                    <div class="row h-100 align-items-center">
                        <div class="col-md-12">
                            <span class="svg-approved">{{ __('Indicator values calculation completed.') }}</span>
                        </div>
                    </div>
                </section>
            </div>
            <div class="failed-wrap d-none {{ $task && $task->status->id == 3 ? 'show' : '' }}">
                <section class="failed-section ps-4 pe-4">
                    <div class="row h-100 align-items-center">
                        <div class="col-md-12">
                            <span class="svg-failed">{{ __('Indicator values calculation failed.') }}</span>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-10 offset-1 ps-0 pe-0">
            <div class="table-section col-12 mt-2">
                <div class="row">
                    <div class="col-12 col-lg-6 col-xl-6">
                        <div class="d-flex gap-2">
                            <div>
                                <h2 class="mt-2">{{ __('Survey') }} -</h2>
                            </div>
                            <div class="mt-1">
                                <select class="form-select loaded-questionnaire" name="loaded_questionnaire" id="formLoadedQuestionnaire">
                                    <option value="" disabled>{{ __('Choose...') }}</option>
                                    @foreach ($published_questionnaires as $questionnaire_data)
                                        <option value={{ $questionnaire_data->id }}
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
                            <label for="last_calculation_date" class="col-form-label mt-1">Last calculation date:</label>
                            <div class="col-5 mt-1">
                                <div class="input-group date">
                                    <input type="text" class="form-control datepicker" id="last_calculation_date"
                                        value="{{ $last_calculation_date }}" disabled />
                                    <span class="input-group-append"></span>
                                    <span class="icon-calendar"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-5">
                    <div class="col-12 col-lg-4 col-xl-4">
                        <div class="d-flex gap-3">
                            <button type="button" class="btn btn-enisa calculate-indicator-values"
                                {{ ($task && $task->status->id == 1) || !$is_latest_questionnaire ? 'disabled' : '' }}>{{ __('Calculate Values') }}
                            </button>
                            <span>{{ __('Calculation status') }}:<br />
                                <span class="task-status">{{ $task_name }}</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
