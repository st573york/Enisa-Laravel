<div wire:poll.3000ms>
    @php
        $is_latest_index = $latest_index_data->id == $loaded_index_data->id ? true : false;
        $task_name = 'No Calculation';
        $last_calculation_date = '--/--/----';
        
        if ($task)
        {
            $task_name = $task->status->name;
            if ($task->status->id != 1) {
                $last_calculation_date = isset($task->payload['last_index_calculation_at']) ? $task->payload['last_index_calculation_at'] : $last_calculation_date;
            }
        }
        
        // Fire task events
        $this->emit('indexCalculation' . preg_replace('/[\s_]/', '', $task_name));
    @endphp
    <div class="row task-state mt-3 d-none {{ $task ? 'show' : '' }}">
        <div class="col-10 offset-1 ps-0 pe-0">
            <div class="in-progress-wrap d-none {{ $task && $task->status->id == 1 ? 'show' : '' }}">
                <section class="in-progress-section ps-4 pe-4">
                    <div class="row h-100 align-items-center">
                        <div class="col-md-12">
                            <span>{{ __('Index calculation in progress. Please wait...') }}</span>
                        </div>
                    </div>
                </section>
            </div>
            <div class="approved-wrap d-none {{ $task && $task->status->id == 2 ? 'show' : '' }}">
                <section class="approved-section ps-4 pe-4">
                    <div class="row h-100 align-items-center">
                        <div class="col-md-12">
                            <span class="svg-approved">{{ __('Index calculation completed.') }}</span>
                        </div>
                    </div>
                </section>
            </div>
            <div class="failed-wrap d-none {{ $task && $task->status->id == 3 ? 'show' : '' }}">
                <section class="failed-section ps-4 pe-4">
                    <div class="row h-100 align-items-center">
                        <div class="col-md-12">
                            <span class="svg-failed">{{ __('Index calculation failed.') }}</span>
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
                                <h2 class="mt-2">{{ __('Index') }} -</h2>
                            </div>
                            <div class="mt-1">
                                <select class="form-select loaded-index" name="loaded_index" id="index-year-select">
                                    <option value="" data-year="" disabled>{{ __('Choose...') }}</option>
                                    @foreach ($published_indexes as $index_data)
                                        <option value={{ $index_data->id }} data-year={{ $index_data->year }}
                                            {{ $loaded_index_data->id == $index_data->id ? 'selected' : '' }}>
                                            {!! $index_data->name !!}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div wire:ignore class="d-flex gap-1 ps-2 overall d-none">
                                <div class="mt-2">
                                    <span style="line-height: 1.9;">{{ __('Overall') }}:</span>
                                </div>
                                <div>
                                    <h2 class="mt-2"></h2>
                                </div>
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
                            <button type="button" class="btn btn-enisa calculate-index"
                                {{ (($task && $task->status->id == 1) || !$is_latest_index) ? 'disabled' : '' }}>{{ __('Calculate Index') }}
                            </button>
                            <span>{{ __('Calculation status') }}:<br />
                                <span class="task-status">{{ $task_name }}</span>
                            </span>
                        </div>
                    </div>
                    <div class="col-12 col-lg-8 col-xl-8">
                        <div class="d-flex gap-1 justify-content-end ps-0 pe-0">
                            <button type="button" class="btn btn-enisa-invert" onclick="location.href='/index/datacollection/importdata'"
                                {{ ($task && $task->status->id == 1) ? 'disabled' : '' }}>{{ __('Import Data') }}
                            </button>
                            <button type="button" class="btn btn-enisa-invert" {!! $questionnaire ? 'onclick="location.href=\'/questionnaire/admin/dashboard/' . $questionnaire->id . '\'"' : '' !!}
                                {{ (($task && $task->status->id == 1) || !$questionnaire) ? 'disabled' : '' }}>{{ __('Survey Dashboard') }}
                            </button>
                            <button type="button" class="btn btn-enisa-invert" onclick="location.href='/index/datacollection/external'"
                                {{ ($task && $task->status->id == 1) ? 'disabled' : '' }}>{{ __('External Sources') }}
                            </button>
                            <span wire:ignore class="download-section" item-id="download-data-collection">
                                <button type="button" class="btn btn-enisa-invert download {{ (!$task || ($task && $task->status->id != 2)) ? 'cannot-download' : '' }}" item-id="all"
                                    {{ (!$task || ($task && $task->status->id != 2)) ? 'disabled' : '' }}>
                                    <i id="button-spinner" class="fa fa-spinner fa-spin d-none"></i>
                                    <span class="in-progress d-none">{{ __('Downloading Data') }}</span>
                                    <span class="start">{{ __('Download Data') }}</span>
                                </button>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
