<form>
    <input hidden id="item-action"/>
    <input hidden id="item-route"/>
    <div class="alert alert-warning" role="alert">
      <h5>{{ __('Warning') }}</h5>
      <ul>
        <li>{{ __('The uploaded content will overwrite the existing index & survey configuration.') }}</li>
      </ul>
    </div>
    <div class="input-group row mt-3 mb-3 required">
        <label for="formImportFile" class="col-form-label col-sm-2">{{ __('File') }}</label>
        <div class="col-sm-10">
          <input class="form-control form-control-sm" type="file" name="file" id="formImportFile" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet">
          <div class="invalid-feedback"></div>
          <input hidden class="form-control form-control-sm" type="extension" name="extension" id="formImportFileExtension">
          <div class="invalid-feedback"></div>
        </div>
    </div>
</form>