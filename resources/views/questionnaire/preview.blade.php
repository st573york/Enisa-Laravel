@extends('layouts.headless')

<style>
   
@page {
    size: A4;
    margin: 0 15mm 20mm 15mm!important;

    @bottom-left {
        content: element(footerSurveyRunning);
    }
}
  
@media print {
    #surveyPrintWrap {
        border-radius: 4px;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

        #surveyPrintWrap p,
        #surveyPrintWrap ul,
        #surveyPrintWrap ol,  
        #surveyPrintWrap span, 
        #surveyPrintWrap label,  
        #surveyPrintWrap .indicator-head .form-indicator-info{
            font-size: 0.75rem;
        }

    #surveyPrintWrap .form-indicator-name,
    #surveyPrintWrap .form-indicator-id,
    #surveyPrintWrap .form-questions-title {
        font-size: 1rem;
    }

    #surveyPrintWrap .form-check-input {
        width: .75em;
        height: .75em;
        margin-top: .19em !important;
        margin-left: -1em !important;
    }

    #surveyPrintWrap .form-select {
        font-size: .75rem!important;
    }

    #surveyPrintWrap .form-control {
        padding: .25rem;
        font-size: .75rem;
    }

    #surveyPrintWrap .form-check {
        min-height: .5rem;
    }

    #surveyPrintWrap .rating-wrapper {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    #surveyPrintWrap .rating-label {
        display: flex;
    }

    #survey-footer {
        position: running(footerSurveyRunning);
        height: 60px;
        display: block;
    }
        
    #survey-footer::after {
        content: "Page "counter(page);
        font-size: 10px;
        font-weight: 500;
        display: flex;
        justify-content: end;
        padding-right: 12px;
        color: #9D9C9C;
    }

    .form-check-input:focus {
        box-shadow: unset;
    }
}

.form-check-input:focus {
    border-color: #141414;
    box-shadow: unset;
}

</style>

@section('title', 'Survey Preview')

@section('content')

@php
    use App\Models\IndicatorQuestionChoice;
    use App\Models\SurveyIndicator;
    use App\Models\SurveyIndicatorAnswer;
    use App\Models\SurveyIndicatorOption;
@endphp

    <div id="surveyPrintWrap">
        <div id="survey-footer"></div>
 
        <section class="wizard-section">
            <div>
                <div class="col-12 ps-0 pe-0">
                    <div>
                        <div>
                            @foreach ($indicators as $indicator)
                                @php
                                    $accordions = $indicator->accordions()->orderBy('order')->get();
                                    $indicator_id = $indicator->id;
                                    $indicator_area = $indicator->default_subarea->default_area->name;
                                    $indicator_subarea = $indicator->default_subarea->name;
                                    $indicator_number = $indicator->order;
                                    $survey_indicator = ($with_answers) ? SurveyIndicator::getSurveyIndicator($questionnaire_country, $indicator) : null;
                                @endphp
                                <fieldset class="mt-2 show outline page-break-after" id="page-{{ $loop->iteration + 1 }}">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="indicator-head">
                                                <div class="col-8">
                                                    <h5>
                                                        <div>
                                                            <span class="form-indicator-info">{!! $indicator_area !!} /</span>
                                                            <span class="form-indicator-info">{!! $indicator_subarea !!}</span>
                                                        </div>
                                                        <div>
                                                            <span class="form-indicator-id">{{ $indicator_number }}</span>.
                                                            <span class="form-indicator-name">{!! $indicator->name !!}</span>
                                                        </div>
                                                    </h5>
                                                </div>
                                            </div>
                                            <div class="indicator-body accordion-body pt-2">
                                                @php
                                                    $algorithm = ($indicator->algorithm != strip_tags($indicator->algorithm)) ? $indicator->algorithm : '<p class="form-indicator-text-content">' . $indicator->algorithm . '</p>';
                                                    $count = 0;
                                                @endphp
                                                <div class="col-10 offset-1">
                                                    {!! $algorithm !!}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="accordion  id="accordionPanelsStayOpen">
                                                @foreach ($accordions as $accordion)
                                                    @php
                                                        $title = '<span class="form-questions-title">' . $accordion->title . '</span>';
                                                        $questions = $accordion->questions()->orderBy('order')->get();
                                                    @endphp
                                                    <div class="indicator-head">
                                                        <h5>
                                                            {!! $title !!}
                                                        </h5>
                                                    </div>
                                                    <div id="accordion-collapse-{{ $indicator_id }}-{{ $loop->iteration }}"
                                                        class="accordion-collapse collapse show"
                                                        aria-labelledby="accordion-heading-{{ $indicator_id }}-{{ $loop->iteration }}">
                                                        @foreach ($questions as $question)
                                                            @php
                                                                $count++;
                                                                $question_number = $indicator_number . '.' . $count;
                                                                $info = ($question->info) ? '<span class="info-icon-black" data-bs-toggle="tooltip" data-bs-placement="right" title="' . $question->info . '"></span>' : '';

                                                                $survey_indicator_answer = (!is_null($survey_indicator)) ? SurveyIndicatorAnswer::getSurveyIndicatorAnswer($survey_indicator, $question) : null;
                                                                $survey_indicator_options = (!is_null($survey_indicator)) ? SurveyIndicatorOption::getSurveyIndicatorOptions($survey_indicator, $question) : [];
                                                            @endphp
                                                            <div class="accordion-body indicator-body page-break-after pt-2">
                                                                <div class="form-question-text">
                                                                    <p class="fw-bold">
                                                                        <span class="form-required {{ ($question->answers_required) ? '' : 'd-none' }}" style="color: var(--brand-color-1);">*</span>
                                                                        <span>{{ $question_number }}</span>. {!! $question->title !!}{!! $info !!}
                                                                    </p>
                                                                </div>
                                                                <div id="form-indicator-{{ $indicator_id }}" class="col-12 pe-0 mb-2 form-indicators">
                                                                    <div class="input-wrapper d-flex gap-4 mb-3 input-choice required">
                                                                        @php
                                                                            $choices = ($question->type_id == 3) ? IndicatorQuestionChoice::whereIn('id', [2, 3])->get() : IndicatorQuestionChoice::whereIn('id', [1, 3])->get();
                                                                            $options = $question->options()->get();

                                                                            $choice_id = (!is_null($survey_indicator_answer)) ? $survey_indicator_answer->choice_id : '';
                                                                            $free_text = (!is_null($survey_indicator_answer)) ? $survey_indicator_answer->free_text : '';
                                                                            $reference_year = (!is_null($survey_indicator_answer)) ? $survey_indicator_answer->reference_year : '';
                                                                            $reference_source = (!is_null($survey_indicator_answer)) ? $survey_indicator_answer->reference_source : '';
                                                                        @endphp
                                                                        @foreach ($choices as $choice)
                                                                            <div class="form-check" data-toggle="buttons">
                                                                                <input type="radio"
                                                                                    class="form-input form-check-input"
                                                                                    name="form-indicator-{{ $indicator_id }}-choice-{{ $accordion->order }}-{{ $question->order }}"
                                                                                    id="form-indicator-{{ $indicator_id }}-choice-{{ $accordion->order }}-{{ $question->order }}-{{ $loop->iteration }}"
                                                                                    value="{{ $choice->id }}"
                                                                                    {{ ($choice->id == $choice_id || $loop->first) ? 'checked' : '' }}>
                                                                                <label class="form-check-label"
                                                                                    for="form-indicator-{{ $indicator_id }}-choice-{{ $accordion->order }}-{{ $question->order }}-{{ $loop->iteration }}">
                                                                                    {!! $choice->text !!}
                                                                                </label>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                    <div class="input-wrapper actual-answers {{ $question->type()->first()->type }} {{ ($question->answers_required) ? 'required' : '' }}">
                                                                        @if ($question->type_id == 2)
                                                                            @foreach ($options as $option)
                                                                                <div class="form-check">
                                                                                    <input type="checkbox"
                                                                                        class="form-input form-check-input {{ $option->master ? 'master' : '' }}"
                                                                                        name="form-indicator-{{ $indicator_id }}-answers-{{ $accordion->order }}-{{ $question->order }}"
                                                                                        id="form-indicator-{{ $indicator_id }}-answers-{{ $accordion->order }}-{{ $question->order }}-{{ $loop->iteration }}"
                                                                                        value="{{ $option->value }}"
                                                                                        {{ (in_array($option->value, $survey_indicator_options)) ? 'checked' : '' }}>
                                                                                    <label class="form-check-label"
                                                                                        for="form-indicator-{{ $indicator_id }}-answers-{{ $accordion->order }}-{{ $question->order }}-{{ $loop->iteration }}">
                                                                                        {!! $option->text !!}
                                                                                    </label>
                                                                                </div>
                                                                            @endforeach
                                                                        @elseif ($question->type_id == 1)
                                                                            @foreach ($options as $option)
                                                                                <div class="form-check"
                                                                                    data-toggle="buttons">
                                                                                    <input type="radio"
                                                                                        class="form-input form-check-input"
                                                                                        name="form-indicator-{{ $indicator_id }}-answers-{{ $accordion->order }}-{{ $question->order }}"
                                                                                        id="form-indicator-{{ $indicator_id }}-answers-{{ $accordion->order }}-{{ $question->order }}-{{ $loop->iteration }}"
                                                                                        value="{{ $option->value }}"
                                                                                        {{ (in_array($option->value, $survey_indicator_options)) ? 'checked' : '' }}>
                                                                                    <label class="form-check-label"
                                                                                        for="form-indicator-{{ $indicator_id }}-answers-{{ $accordion->order }}-{{ $question->order }}-{{ $loop->iteration }}">
                                                                                        {!! $option->text !!}
                                                                                    </label>
                                                                                </div>
                                                                            @endforeach
                                                                        @elseif ($question->type_id == 3)
                                                                            <div>
                                                                                <input type="text"
                                                                                    class="form-input form-control"
                                                                                    name="form-indicator-{{ $indicator_id }}-answers-{{ $accordion->order }}-{{ $question->order }}"
                                                                                    id="form-indicator-{{ $indicator_id }}-answers-{{ $accordion->order }}-{{ $question->order }}-{{ $loop->iteration }}"
                                                                                    value="{!! $free_text !!}" />
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                <div class="col-12 mb-2 form-references {{ ($question->reference_required) ? 'required' : '' }}">
                                                                    <span class="form-required {{ ($question->reference_required) ? '' : 'd-none' }}" style="color: var(--brand-color-1);">*</span>
                                                                    <label for="form-indicator-{{ $indicator_id }}-reference-year-{{ $accordion->order }}-{{ $question->order }}" class="form-label mt-2">{{ __('Reference Year') }}</label>
                                                                    @php
                                                                        $years = range(2000, date('Y') + 1);
                                                                        rsort($years);
                                                                    @endphp
                                                                    <select class="form-select" name="form-indicator-{{ $indicator->id }}-reference-year-{{ $accordion->order }}-{{ $question->order }}" id="form-indicator-{{ $indicator->id }}-reference-year-{{ $accordion->order }}-{{ $question->order }}" aria-label="Reference Year" style="width: 20%">
                                                                        <option value="" selected disabled>{{ __('Choose...') }}</option>
                                                                        @foreach ($years as $year)
                                                                            <option value="{{ $year }}" {{ ($year == $reference_year) ? 'selected' : '' }}>{{ $year }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="col-12 mb-4 form-references {{ ($question->reference_required) ? 'required' : '' }}">
                                                                    <span class="form-required {{ ($question->reference_required) ? '' : 'd-none' }}" style="color: var(--brand-color-1);">*</span>
                                                                    <label for="form-indicator-{{ $indicator_id }}-reference-source-{{ $accordion->order }}-{{ $question->order }}" class="form-label mt-2">{{ __('Reference Source') }}</label>
                                                                    <textarea class="form-control outline" name="form-indicator-{{ $indicator_id }}-reference-source-{{ $accordion->order }}-{{ $question->order }}" id="form-indicator-{{ $indicator_id }}-reference-source-{{ $accordion->order }}-{{ $question->order }}" placeholder="{{ __('Include here the source of data. Note that you should use the latest data available.') }}">{!! $reference_source !!}</textarea>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    @if (!is_null($survey_indicator))
                                        <div class="row btm">
                                            <div class="col-md-12 accordion-body">
                                                <!-- Rating / Comments -->
                                                <div>
                                                    <form id="comments_and_rating" class="">
                                                        <fieldset>
                                                            <div class="col-12 rating-wrapper mb-4">
                                                                <div class="rating-label">
                                                                    <span class="form-required" style="color: var(--brand-color-1);">*</span>
                                                                    <label for="form-indicator-{{ $indicator_id }}-rating">{{ __('Please rate the relevance of the indicator by selecting 1-5 stars. The rating of the indicator will be used to calculate the weight for this indicator.') }}</label>
                                                                </div>
                                                                <div class="form-rating rating required" class="form-rating rating required">
                                                                    <span class="pe-1">Rating:</span>
                                                                    @for ($i = 5; $i >= 1; $i--)
                                                                        <input
                                                                            class="form-indicator-{{ $indicator_id }}-rating"
                                                                            type="radio"
                                                                            id="form-indicator-{{ $indicator_id }}-rating-{{ $i }}"
                                                                            name="form-indicator-{{ $indicator_id }}-rating"
                                                                            value="{{ $i }}"
                                                                            {{ ($survey_indicator->rating == $i) ? 'checked' : '' }} />
                                                                        <label class="star" for="form-indicator-{{ $indicator_id }}-rating-{{ $i }}" aria-hidden="true"></label>
                                                                    @endfor
                                                                </div>
                                                            </div>
                                                            <div class="form-comments">
                                                                <textarea class="form-control user-comments outline" name="form-indicator-{{ $indicator_id }}-comments" id="form-indicator-{{ $indicator_id }}-comments" rows="5" placeholder="{{ __("Include here any remark that could be useful in the processing of data e.g. explaining if / why your country does not collect data\nfor a specific question; or if your country does not want to share the data.") }}">{!! $survey_indicator->comments !!}</textarea>
                                                            </div>
                                                        </fieldset>
                                                    </form>
                                                </div>
                                                <!--End Comments Col -->
                                            </div>
                                        </div>
                                    @endif
                                </fieldset>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

<script src="{{ mix('mix/js/questionnaire-actions.js') }}" defer></script>
<script src="{{ mix('mix/js/paged.polyfill.js') }}"></script>

<script>
    class PagedHandler extends Paged.Handler
    {
        constructor(chunker, polisher, caller)
        {
            super(chunker, polisher, caller);
        }

        afterRendered(pages)
        {
            expandTextareas();
            toggleElements();

            var changeEvent = new Event('pagedRendered');
            parent.window.dispatchEvent(changeEvent);
        }
    }

    Paged.registerHandlers(PagedHandler);

    function expandTextareas()
    {
        $('textarea').each(function (i, el) {
            el.style.height = 'auto';
            el.style.height = el.scrollHeight + 'px';
        });
    }

    function toggleElements()
    {
        $('.form-indicators .input-choice input:checked').each(function (i, el) {
            toggleAnswers(el);
            toggleReference(el);

            let choice = $(el).val();
            if (choice != 3)
            {
                let master = $(el).closest('.input-choice').siblings('.actual-answers').find('.form-input.master');
                if (master.length) {
                    toggleOptions(master);
                }
            }
        });
    }
</script>