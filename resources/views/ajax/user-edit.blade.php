<form dusk="user_edit_modal_data">
    <input hidden id="item-route"/>
    <div class="row">
        @if (count($users) == 1)
            <div class="col-12 mb-2 required">
                <label class="col-form-label" for="country-select">{{ __('Country') }}</label>
                @php
                    $db_country = ($users[0]->permissions->first()) ? $users[0]->permissions->first()->country->name : $users[0]->country;
                @endphp
                <select dusk="user_edit_modal_country" class="form-select me-2" aria-label="Country" name="country" id="country-select">
                    <option value="" selected disabled>{{ __('Choose...') }}</option>
                    @foreach ($countries as $country)
                        <option value="{{ $country }}"
                            {{ ($db_country == $country) ? 'selected' : '' }}>{{ $country }}</option>
                    @endforeach
                </select>
                <div class="invalid-feedback"></div>
            </div>
            <div class="col-12 mb-2 required">
                <label class="col-form-label" for="role-select">{{ __('Role') }}</label>
                <select dusk="user_edit_modal_role" class="form-select me-2" aria-label="{{ __('Role') }}" name="role" id="role-select">
                    <option value="" selected disabled>{{ __('Choose...') }}</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role }}"
                            {{ ($users[0]->permissions->first() && $users[0]->permissions->first()->role->name == $role) ? 'selected' : '' }}>{{ $role }}</option>
                    @endforeach
                </select>
                <div class="invalid-feedback"></div>
            </div>
        @endif
        <div class="col-12 d-flex flex-column">
            <span>{{ __('Status') }}</span>
            <div>
                <div class="form-check form-switch d-flex gap-2 justify-content-start">
                    @php
                        $id = 'id="toggle-text"';
                        $dusk = 'dusk="user_edit_modal_status_text"';
                        $blocked = '<span ' . $dusk . ' ' . $id . ' class="text-danger pt-1">' . __('Blocked') . '</span>';
                        $enabled = '<span ' . $dusk . ' ' . $id . ' class="pt-1">' . __('Enabled') . '</span>';
                    @endphp
                    <input dusk="user_edit_modal_status_switch" class="form-check-input switch modal-switch {{ (count($users) == 1 && $users[0]->id == Auth::user()->id) ? 'switch-deactivated' : '' }}" type="checkbox" name="blocked" id="status" {{ (count($users) == 1 && $users[0]->blocked) ? 'checked' : '' }}>
                    @if (count($users) == 1)
                        @if($users[0]->blocked)
                            {!! $blocked !!}
                        @else
                            {!! $enabled !!}
                        @endif
                    @else
                        {!! $enabled !!}
                    @endif
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    var user_group = <?php echo json_encode(config('constants.USER_GROUP')); ?>;

    $(document).on('change', 'select[name="country"]', function() {
        toggleRoles(user_group);
    });

    $(document).on('change', 'select[name="role"]', function() {
        toggleCountries(user_group);
    });

    $(document).on('click', '.modal-switch', function() {
        var checked = $(this).is(':checked');
        $('#toggle-text')
            .text(checked ? 'Blocked' : 'Enabled')
            .toggleClass('text-danger', checked);
    });
</script>

