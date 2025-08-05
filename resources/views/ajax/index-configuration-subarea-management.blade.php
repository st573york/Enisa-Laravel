<form>
  <input hidden id="item-id"/>
  <input hidden id="item-type"/>
  <input hidden id="item-action"/>
  <input hidden id="item-route"/>
  <div class="input-group row mb-3 required">
      <label for="formName" class="col-form-label col-sm-4">{{ __('Name') }}</label>
      <div class="col-sm-8">
          <input class="form-control" type="text" name="name" id="formName" placeholder="Name" value="{{ ($selected_subarea) ? $selected_subarea->name : "" }}">
          <div class="invalid-feedback"></div>
      </div>
  </div>
  <div class="input-group row mb-3">
      <label for="formDescription" class="col-form-label col-sm-4">{{ __('Description') }}</label>
      <div class="col-sm-8">
          <textarea class="form-control" name="description" id="formDescription" placeholder="Description">{{ ($selected_subarea) ? $selected_subarea->description : "" }}</textarea>
          <div class="invalid-feedback"></div>
      </div>
  </div>
  <div class="input-group row mb-3 required">
      <label for="formArea" class="col-form-label col-sm-4">{{ __('Area') }}</label>
      <div class="col-sm-8">
          <select class="form-select mr-sm-2" name="default_area_id" id="formArea">
              <option value="" selected disabled>{{ __('Choose...') }}</option>
              @foreach ($areas as $area)
                  <option value={{ $area->id }} {{ ( $selected_subarea && $selected_subarea->default_area_id == $area->id )? "selected" : "" }}>{{ $area->name }}</option>
              @endforeach
          </select>
          <div class="invalid-feedback"></div>
      </div>
  </div>
  <div class="input-group row mb-3 required">
      <label for="formDefaultWeight" class="col-form-label col-sm-4">{{ __('Weight') }}</label>
      <div class="col-sm-8">
          <input class="form-control" type="text" name="default_weight" id="formDefaultWeight" placeholder="Weight" value="{{ ($selected_subarea) ? $selected_subarea->default_weight : 1 }}">
          <div class="invalid-feedback"></div>
      </div>
  </div>
</form>

<script>
    var max_identifier = 0;
</script>