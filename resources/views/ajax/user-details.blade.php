<div class="row mt-5">
  <div class="col-10 offset-1 ps-0 pe-0">
    <div class="table-section col-12 mt-2">
      <form dusk="user_data" id="user-data-form">
        <div class="input-group mb-3">
          <label for="formName" class="col-form-label col-sm-2">{{ __('Name') }}</label>
          <div class="col-sm-10">
            <input dusk="name" class="form-control" disabled type="text" name="name" id="formName" placeholder="Name" value="{{ $data->name }}">
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="input-group mb-3">
          <label for="formEmail" class="col-form-label col-sm-2">{{ __('Email') }}</label>
          <div class="col-sm-10">
            <input dusk="email" class="form-control" disabled type="email" name="email" id="formEmail" placeholder="Email" value="{{ $data->email }}">
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="input-group mb-3 required">
          <label for="formCountry" class="col-form-label col-sm-2">{{ __('Country') }}</label>
          <div class="col-sm-10">
            <select dusk="country" class="form-select mr-sm-2" data-flag="true" id="country_code" name="country_code" {{ ($data->country_code) ? "disabled" : "" }}>
              <option value="" selected disabled>{{ __('Choose...') }}</option>
              @foreach ($countries as $code => $country)
                <option value="{{ $code }}"
                  {{ ($data->country_code == $code) ? 'selected' : '' }}>{{ $country }}</option>
              @endforeach
            </select>
            <div dusk="country_invalid" class="invalid-feedback"></div>
          </div>
        </div>
      </form>
    </div>
    <div class="row mt-3">
      <div class="d-flex justify-content-start">
        <button dusk="save_changes" id="process-data" type="button" class="btn btn-enisa">{{ __('Save changes') }}</button>
      </div>
  </div>
  </div>
</div>