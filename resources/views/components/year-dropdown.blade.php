<select class="form-select" id="index-year-select">
    @foreach ($years as $id => $year)
        <option value="{{ $year }}" data-id="{{ $id }}" data-year="{{ $year }}">{{ $year }}</option>
    @endforeach
</select>

<script>
    $(document).ready(function() {
        let year = localStorage.getItem('index-year');
        if (year)
        {
            if ($('#index-year-select option[value="' + year + '"]').length > 0) {
                $('#index-year-select').val(year);
            }
        }
    });
</script>
