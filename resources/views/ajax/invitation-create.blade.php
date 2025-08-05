<form dusk="invitation_manage_modal_data">
    <input hidden id="item-route"/>
    <div class="row">
        <div class="col-12 mb-2 required">
            <label class="col-form-label" for="formFirstName">{{ __('First Name') }}</label>
            <input dusk="invitation_manage_modal_firstname" class="form-control" type="text" name="firstname" id="formFirstName" placeholder="First Name">
            <div dusk="invitation_manage_modal_firstname_invalid" class="invalid-feedback"></div>
        </div>
        <div class="col-12 mb-2 required">
            <label class="col-form-label" for="formLastName">{{ __('Last Name') }}</label>
            <input dusk="invitation_manage_modal_lastname" class="form-control" type="text" name="lastname" id="formLastName" placeholder="Last Name">
            <div dusk="invitation_manage_modal_lastname_invalid" class="invalid-feedback"></div>
        </div>
        <div class="col-12 mb-2 required">
            <label class="col-form-label" for="formEmailAddress">{{ __('Email Address') }}</label>
            <input dusk="invitation_manage_modal_email" class="form-control" type="text" name="email" id="formEmailAddress" placeholder="Email Address">
            <div dusk="invitation_manage_modal_email_invalid" class="invalid-feedback"></div>
        </div>
        <div class="col-12 mb-2 required">
            <label class="col-form-label" for="country-select">{{ __('Country') }}</label>
            <select dusk="invitation_manage_modal_country" class="form-select me-2" aria-label="Country" name="country" id="country-select">
                <option value="" selected disabled>{{ __('Choose...') }}</option>
                @foreach ($countries as $country)
                    <option value="{{ $country }}">{{ $country }}</option>
                @endforeach
            </select>
            <div dusk="invitation_manage_modal_country_invalid" class="invalid-feedback"></div>
        </div>
        <div class="col-12 mb-2 required">
            <label class="col-form-label" for="role-select">{{ __('Role') }}</label>
            <select dusk="invitation_manage_modal_role" class="form-select me-2" aria-label="{{ __('Role') }}" name="role" id="role-select">
                <option value="" selected disabled>{{ __('Choose...') }}</option>
                @foreach ($roles as $role)
                    <option value="{{ $role }}">{{ $role }}</option>
                @endforeach
            </select>
            <div dusk="invitation_manage_modal_role_invalid" class="invalid-feedback"></div>
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
</script>

