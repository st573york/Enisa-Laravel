<form class="row g-3">
    <input hidden id="item-id"/>
    <input hidden id="item-type"/>
    <input hidden id="item-action"/>
    <input hidden id="item-route"/>
    <div class="col-md-6 required">
        <label for="formIndicatorLink" class="col-form-label">{{ __('Link To Past Indicator') }}</label>
        <select class="form-select mr-sm-2 indicator_link" name="indicator_link" id="formIndicatorLink">
            <option value="" selected disabled>{{ __('Choose...') }}</option>
            <option value="new_indicator" {{ ($selected_indicator && !$is_identifier_linked) ? "selected" : "" }}>{{ __('New Indicator') }}</option>
            <option disabled>--------------</option>
            @foreach ($not_linked_indicators as $not_linked_indicator)
                <option value="{{ $not_linked_indicator->id }}" {{ ($selected_indicator && $selected_indicator->identifier == $not_linked_indicator->identifier) ? "selected" : "" }}>{{ $not_linked_indicator->name }}</option>
            @endforeach
        </select>
        <div class="invalid-feedback"></div>
    </div>
    <div class="col-md-6 required">
        <label for="formIdentifier" class="col-form-label">{{ __('Unique Indicator ID') }}</label>
        <input class="form-control identifier" type="text" name="identifier" id="formIdentifier" placeholder="Unique Indicator ID" value="{!! ($selected_indicator) ? $selected_indicator->identifier : "" !!}" disabled>
        <div class="invalid-feedback"></div>
    </div>
    <div class="col-md-6 {{ (!$selected_indicator || $selected_indicator->category == 'eu-wide') ? '' : 'required' }}">
        <label for="formSubarea" class="col-form-label">{{ __('Subarea') }}</label>
        <select class="form-select mr-sm-2 subarea" name="default_subarea_id" id="formSubarea">
            <option value="" selected disabled>{{ __('Choose...') }}</option>
            @foreach ($subareas as $subarea)
                <option value={{ $subarea->id }} {{ ($selected_indicator && $selected_indicator->default_subarea_id == $subarea->id) ? "selected" : "" }}>{{ $subarea->name }}</option>
            @endforeach
        </select>
        <div class="invalid-feedback"></div>
    </div>
    <div class="col-md-6 required">
        <label for="formCategory" class="col-form-label">{{ __('Indicator Type') }}</label>
        <select class="form-select mr-sm-2 category" name="category" id="formCategory">
            <option value="" selected disabled>{{ __('Choose...') }}</option>
            @foreach ($categories as $key => $val)
                <option value={{ $key }} {{ ($selected_indicator && $selected_indicator->category == $key) ? "selected" : "" }}>{{ $val }}</option>
            @endforeach
        </select>
        <div class="invalid-feedback"></div>
    </div>
    <div class="col-md-12 required">
        <label for="formName" class="col-form-label">{{ __('Name') }}</label>
        <textarea class="form-control name" name="name" id="formName" placeholder="Name">{!! ($selected_indicator) ? $selected_indicator->name : "" !!}</textarea>
        <div class="invalid-feedback"></div>
    </div>
    <div class="col-md-12">
        <label for="formDescription" class="col-form-label">{{ __('Description') }}</label>
        <textarea class="form-control description" name="description" id="formDescription" placeholder="Description used in the MS/EU reports">{!! ($selected_indicator) ? $selected_indicator->description : "" !!}</textarea>
    </div>
    <div class="col-md-12">
        <label for="formAlgorithm" class="col-form-label">{{ __('Algorithm') }}</label>
        <textarea class="form-control tinymce algorithm" name="algorithm" id="formAlgorithm" placeholder="Algorithm">{!! ($selected_indicator) ? $selected_indicator->algorithm : "" !!}</textarea>
    </div>
    <div class="col-md-6 required">
        <label for="formDefaultWeight" class="col-form-label">{{ __('Weight') }}</label>
        <input class="form-control default_weight" type="text" name="default_weight" id="formDefaultWeight" placeholder="Weight" value="{!! ($selected_indicator) ? $selected_indicator->default_weight : "" !!}">
        <div class="invalid-feedback"></div>
    </div>
    <div class="col-md-6">
        <label for="formSource" class="col-form-label">{{ __('Source') }}</label>
        <textarea class="form-control source" name="source" id="formSource" rows="1" placeholder="Source">{!! ($selected_indicator) ? $selected_indicator->source : "" !!}</textarea>
    </div>
    <div class="col-md-12">
        <label for="formComment" class="col-form-label">{{ __('Comment') }}</label>
        <textarea class="form-control comment" name="comment" id="formComment" placeholder="Please write any comment that will be used internally for tracking indicators (end users will not see this comment)">{!! ($selected_indicator) ? $selected_indicator->comment : "" !!}</textarea>
    </div>
</form>

<script>
    var not_linked_indicators = <?php echo json_encode($not_linked_indicators); ?>;
    var max_identifier = <?php echo json_encode($max_identifier); ?>;

    $(document).on('change', '.indicator_link', function() {
        let that = $(this);
        let is_new_indicator = (that.val() == 'new_indicator') ? true : false;
        
        let indicator_data = null;
        $.each(not_linked_indicators, function(key, indicator) {
            if (that.val() == indicator.id)
            {
                indicator_data = indicator;

                return false;
            }
        });
        
        if (is_new_indicator)
        {
            resetForm();

            $('.identifier').val(max_identifier);
        }
        else
        {
            clearInputErrors();
            let subarea = indicator_data.default_subarea ? indicator_data.default_subarea.name : null;

            $('.subarea option').each(function() {
                if ($(this).text() === subarea)
                {
                    $(this).prop('selected', true);

                    return false;
                }
            });
            
            $('.identifier').val(indicator_data.identifier);
            $('.category').val(indicator_data.category);
            $('.name').val(indicator_data.name);
            $('.description').val(indicator_data.description);
            tinymce.get('formAlgorithm').setContent(indicator_data.algorithm);
            $('.default_weight').val(indicator_data.default_weight);
            $('.source').val(indicator_data.source);
            $('.comment').val(indicator_data.comment);
        }
    });

    $(document).on('change', '.subarea', function() {
        let category = $('.category option:selected').val();

        if (category == 'eu-wide') {
            $('.category').val('');
        }
    });

    $(document).on('change', '.category', function() {
        let category = $(this).val();

        if (category == 'eu-wide') {
            $('.subarea').val('').parent().removeClass('required');
        }
        else {
            $('.subarea').parent().addClass('required');
        }
    });

    function resetForm()
    {
        $('form').find('input, select, textarea').not(':hidden, .indicator_link').each(function() {
            $(this).val('');
        });

        tinymce.get('formAlgorithm').setContent('');
    }
</script>