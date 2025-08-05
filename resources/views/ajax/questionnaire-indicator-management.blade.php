<form dusk="dashboard_edit_modal_data">
    <input hidden id="item-action" />
    <input hidden id="item-route" />
    @if (count($processedIndicators) > 0)
        <div class="alert alert-warning" role="alert">
            @if (count($indicators) > 1)
                <strong>The following indicators re-assigning, have already been processed. You can either keep or reset the answers by choosing one of the options below.</strong>
                <hr>
                <ul>
                    @foreach ($processedIndicators as $indicator)
                        <li>{{ $indicator->number }}. {!! $indicator->name !!}</li>
                    @endforeach
                </ul>
            @else
                <strong>The indicator you are re-assigning has already been processed. You can either keep or reset the answers by choosing one of the options below.</strong>
            @endif
        </div>
        <div class="row mt-2">
            <div class="col-12 d-flex flex-column">
                <div>
                    <div class="form-check form-switch d-flex gap-2 justify-content-start">
                        <input class="form-check-input switch modal-switch" type="checkbox" name="reset-answers">
                        <span id="toggle-text" class="pt-1">{{ __('Keep Answers') }}</span>
                    </div>
                </div>
            </div>
        </div>
    @endif
    <div class="row">
        <div class="col-md-6 required">
            <label class="col-form-label" for="assignee-select">{{ __('Assignee') }}</label>
            <select dusk="dashboard_edit_modal_assignee" class="form-select me-2" aria-label="Assignee" name="assignee" id="assignee-select">
                <option value="" selected disabled>{{ __('Choose...') }}</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" dusk="dashboard_edit_modal_assignee_{{ $user->id }}"
                        {{ count($indicators) == 1 && $indicators[0]->assignee == $user->id ? 'selected' : '' }}>
                        {{ $user->name }}
                        ({{ Auth::user()->id == $user->id ? 'you' : $user->permissions->first()->role['name'] }})
                    </option>
                @endforeach
            </select>
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-6 required">
            <label for="formDate" class="col-form-label">{{ __('Deadline') }}</label>
            <div class="input-group date">
                <input dusk="dashboard_edit_modal_deadline" type="text" class="form-control datepicker" name="deadline" id="formDate"
                    placeholder="Deadline"
                    value="{{ count($indicators) == 1 ? $indicators[0]->deadline : $questionnaire->questionnaire->deadline }}" />
                <span class="input-group-append"></span>
                <span class="icon-calendar"> </span>
                <div class="invalid-feedback"></div>
            </div>
        </div>
    </div>
</form>

<script>
    $(document).on('click', '.modal-switch', function() {
        var checked = $(this).is(':checked');
        $('#toggle-text')
            .text((checked ? 'Reset' : 'Keep') + ' Answers')
            .toggleClass('text-danger', checked);
    });
</script>
