<input hidden id="item-data"/>
<input hidden id="item-action"/>
<input hidden id="item-route"/>
<div class="row">
    <div class="col-12">
        @php
            $indicator = $survey_indicator->indicator;
            $accordions = $indicator->accordions()->orderBy('order')->get();

            $count = 0;
        @endphp
        <div>{!! $indicator->algorithm !!}</div>
        @foreach ($accordions as $accordion)
            <div class="mt-3 mb-3">
                <h6>{!! $accordion->title !!}</h6>
            </div>
            @php
                $questions = $accordion->questions()->orderBy('order')->get();
            @endphp
            @foreach ($questions as $question)
                <div class="mt-3">{{ $indicator->order }}.{{ ++$count }}. {!! $question->title !!}</div>
            @endforeach
        @endforeach
    </div>
</div>