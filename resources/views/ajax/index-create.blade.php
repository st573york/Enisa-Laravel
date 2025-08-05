<form dusk="index_manage_modal_data">
    <input hidden id="item-action"/>
    <input hidden id="item-route"/>
    <div class="input-group row mb-3 required">
        <label for="formName" class="col-form-label col-sm-3">{{ __('Name') }}</label>
        <div class="col-sm-9">
          <input dusk="index_manage_modal_name" class="form-control" type="text" name="name" id="formName" placeholder="Name">
          <div dusk="index_manage_modal_name_invalid" class="invalid-feedback"></div>
        </div>
    </div>
    <div class="input-group row mb-3">
        <label for="formDescription" class="col-form-label col-sm-3">{{ __('Description') }}</label>
        <div class="col-sm-9">
          <textarea dusk="index_manage_modal_description" class="form-control" name="description" id="formDescription" placeholder="Description"></textarea>
          <div class="invalid-feedback"></div>
        </div>
    </div>
    <div class="input-group row mb-3 required">
      <label for="formYear" class="col-form-label col-sm-3">{{ __('Year') }}</label>
      <div class="col-sm-9">
        <select dusk="index_manage_modal_year" class="form-select mr-sm-2" name="year" id="formYear">
          <option value="" selected disabled>{{ __('Choose...') }}</option>
          @foreach( $years as $year )
            <option value="{{ $year }}">{{ $year }}</option>
          @endforeach
        </select>
        <div class="invalid-feedback"></div>
      </div>
    </div>
</form>
