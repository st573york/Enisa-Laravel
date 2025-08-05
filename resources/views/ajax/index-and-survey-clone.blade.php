<form>
    <input hidden id="item-action"/>
    <input hidden id="item-route"/>
    <div class="alert alert-warning" role="alert">
      <strong>{{ __('By clicking Clone, you will create an exact copy of the selected year\'s Index & Survey, ovewriting any changes that you have created on the current Index configuration.') }}</strong>
    </div>
    <div class="input-group row mt-3 mb-3 required">
      <label for="formCloneIndex" class="col-form-label col-sm-4">{{ __('Clone Index') }}</label>
      <div class="col-sm-8">
        <select id="formCloneIndex" class="form-select mr-sm-2" name="clone-index">
          <option value="" selected disabled>{{ __('Choose...') }}</option>
          @foreach ($published_indexes as $index_data)
            <option value={{ $index_data->id }}>{{ $index_data->year }}</option>
          @endforeach
        </select>
        <div class="invalid-feedback"></div>
      </div>
    </div>
    <div class="input-group row mt-3 mb-3">
      <label for="formCloneSurvey" class="col-form-label col-sm-4 pt-0">{{ __('Clone Survey') }}</label>
      <div class="col-sm-8">
        <div class="form-check form-switch float-start">
          <input id="formCloneSurvey" class="form-check-input switch unchecked-style switch-positive" type="checkbox" name="clone-survey">
        </div>
      </div>
    </div>
</form>
