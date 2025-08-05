<form dusk="survey_manage_modal_data">
    <input hidden id="item-id"/>
    <input hidden id="item-action"/>
    <input hidden id="item-route"/>
    @if ($action == 'create' || $action == 'show')
        <div class="col-md-6 required">
            <label for="formIndex" class="col-form-label">{{ __('Index') }}</label>
            <select dusk="survey_manage_modal_index" class="form-select" name="index_configuration_id" for="formIndex">
                <option value="" selected disabled>{{ __('Choose...') }}</option>
                @foreach ($indexes as $index_data)
                    <option value={{ $index_data->id }} {{ ($data && $data->index_configuration_id == $index_data->id) ? "selected" : "" }}>{!! $index_data->name !!}</option>
                @endforeach
            </select>
            <div dusk="survey_manage_modal_index_invalid" class="invalid-feedback"></div>
        </div>
        <div class="col-md-6 required">
            <label for="formTitle" class="col-form-label">{{ __('Title') }}</label>
            <input dusk="survey_manage_modal_name" class="form-control" type="text" name="title" id="formTitle" placeholder="Title" value="{!! ($data) ? $data->title : "" !!}">
            <div dusk="survey_manage_modal_name_invalid" class="invalid-feedback"></div>
        </div>
        <div class="col-md-6 required">
            <label for="formDate" class="col-form-label">{{ __('Deadline') }}</label>
            <div class="input-group date">
                <input dusk="survey_manage_modal_deadline" type="text" class="form-control datepicker" name="deadline" id="formDate" placeholder="Deadline" value="{{ ($data) ? $data->deadline : "" }}"/>
                <span class="input-group-append"></span>
                <span class="icon-calendar"> </span>
                <div dusk="survey_manage_modal_deadline_invalid" class="invalid-feedback"></div>
            </div>
        </div>
        <div class="col-md-12">
            <label for="formScope" class="col-form-label">{{ __('Scope') }}</label>
            <textarea dusk="survey_manage_modal_scope" class="form-control tinymce" name="description" id="formScope" placeholder="Scope">{{ ($data) ? $data->description : "" }}</textarea>
        </div>
    @elseif ($action == 'publish')
        <div class="col-12 mt-2">
            <p dusk="survey_publish_modal_data_text"><span class="fw-bold">{{ __('You are about to publish the following survey:') }}</span>
                <span class="modal-content-title">{!! $data->title !!}</span>
                <br>
                <span class="small">{{ __('When published, this survey will be only accessible by EUCSI
                    registered users. You can either publish this survey to all EUCSI Country PPoCs,
                    or select specific registered users.') }}
                </span>
            </p>
        </div>
        <div class="col-12 mt-2">
            <p class="fw-bold">{{ __('How would you like to proceed?') }}</p>
            <div class="d-flex gap-4">
                <div class="form-check">
                    <input dusk="survey_publish_modal_notify_all_input" type="radio" id="radio-all" class="form-input form-check-input notify_users" name="notify_users" value="{{ __('Notify all PPoCs') }}" checked/>
                    <label dusk="survey_publish_modal_notify_all_label" class="form-check-label" for="radio-all">{{ __('Notify all PPoCs') }}</label>
                </div>
                <div>
                    <input dusk="survey_publish_modal_select_users_input" type="radio" id="radio-specific" class="form-input form-check-input notify_users" name="notify_users" value="{{ __('Select specific EUCSI users') }}"/>
                    <label dusk="survey_publish_modal_select_users_label" class="form-check-label" for="radio-specific">{{ __('Select specific EUCSI users') }}</label>
                </div>
            </div>
        </div>
        @include('components.alert', ['type' => 'pageModalAlert'])
        <div class="table-section col-12 mt-2" style="transform: none;">
            <h2 class="mb-2">Users</h2>
            <table dusk="users_table" id="notify-users-table" class="display enisa-table-group">
                <thead>
                    <tr>
                        <th></th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Role') }}</th>
                        <th>{{ __('Country') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endif
</form>
