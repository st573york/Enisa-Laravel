<div wire:poll.3000ms>
    @php
        $is_latest_index = $latest_index_data->id == $loaded_index_data->id ? true : false;
        $task_name = 'No Collection';
        $last_collection_date = '--/--/----';
        
        if ($task)
        {
            $task_name = $task->status->name;
            if ($task->status->id != 1) {
                $last_collection_date = isset($task->payload['last_external_data_collection_at']) ? $task->payload['last_external_data_collection_at'] : $last_collection_date;
            }
        }
        
        // Fire task events
        $this->emit('externalDataCollection' . preg_replace('/[\s_]/', '', $task_name));
    @endphp
    <div class="row task-state mt-3 d-none {{ $task ? 'show' : '' }}">
        <div class="col-10 offset-1 ps-0 pe-0">
            <div class="in-progress-wrap d-none {{ $task && $task->status->id == 1 ? 'show' : '' }}">
                <section class="in-progress-section ps-4 pe-4">
                    <div class="row h-100 align-items-center">
                        <div class="col-md-12">
                            <span>{{ __('Data collection in progress. Please wait...') }}</span>
                        </div>
                    </div>
                </section>
            </div>
            <div class="completed-wrap d-none {{ $task && $task->status->id == 2 ? 'show' : '' }}">
                <section class="completed-section ps-4 pe-4">
                    <div class="row h-100 align-items-center">
                        <div class="col-md-12">
                            <span>{{ __('New data collected. Please select to approve or discard the data collection.') }}</span>
                        </div>
                    </div>
                </section>
            </div>
            <div class="failed-wrap d-none {{ $task && $task->status->id == 3 ? 'show' : '' }}">
                <section class="failed-section ps-4 pe-4">
                    <div class="row h-100 align-items-center">
                        <div class="col-md-12">
                            <span class="svg-failed">{{ __('Data collection failed.') }}</span>
                        </div>
                    </div>
                </section>
            </div>
            <div class="approved-wrap d-none {{ $task && $task->status->id == 4 ? 'show' : '' }}">
                <section class="approved-section ps-4 pe-4">
                    <div class="row h-100 align-items-center">
                        <div class="col-md-12">
                            <span class="svg-approved">{{ __('Data collected and approved.') }}</span>
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
                        </div>
                    </div>
                    <div class="col-12 col-lg-6 col-xl-6">
                        <div class="d-flex gap-2 justify-content-end ps-0 pe-0">
                            <label for="last_collection_date" class="col-form-label mt-1">Last collection date:</label>
                            <div class="col-5 mt-1">
                                <div class="input-group date">
                                    <input type="text" class="form-control datepicker" id="last_collection_date"
                                        value="{{ $last_collection_date }}" disabled />
                                    <span class="input-group-append"></span>
                                    <span class="icon-calendar"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-5">
                    <div class="col-12 col-lg-6 col-xl-6">
                        <div class="d-flex gap-3">
                            <button type="button" class="btn btn-enisa collect-data"
                                {{ ($task && $task->status->id == 1) || !$is_latest_index ? 'disabled' : '' }}>{{ __('Collect Data') }}
                            </button>
                            <span>{{ __('Collection status') }}:<br />
                                <span class="task-status">{{ $task_name }}</span>
                            </span>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6 col-xl-6">
                        <div class="d-flex gap-2 justify-content-end ps-0 pe-0">
                            <button type="button" class="btn btn-enisa-invert discard-data"
                                {{ !$task || ($task && $task->status->id != 2) || !$is_latest_index ? 'disabled' : '' }}>{{ __('Discard Data') }}
                            </button>
                            <span wire:ignore class="download-section" item-id="download-external-data-collection">
                                <button type="button" class="btn btn-enisa-invert download" item-id="all"
                                    {{ (!$task || ($task && in_array($task->status->id, [1, 3]))) ? 'disabled' : '' }}>
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
