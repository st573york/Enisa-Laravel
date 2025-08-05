<table>
    <caption>Export Data</caption>
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Area</th>
            <th>Subarea</th>
            <th>Section</th>
            <th>Type</th>
            <th>Question</th>
            <th>Question ID</th>
            <th>Answer</th>
            <th>Score</th>
            @foreach ($countryCodes as $country)
                <th style="text-align: center;">{{ $country }}</th>
            @endforeach
            @if (sizeof($countryCodes) > 1) 
                <th style="text-align: center;">Total</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @foreach ($indicatorValuesData as $identifier => $indicatorValue)
            @foreach ($indicatorValue['questions'] as $questionId => $question)
                @if ($question['type'] == 'single-choice' ||
                     $question['type'] == 'multiple-choice')
                    @foreach ($question['options'] as $option)
                        <tr>
                            <td>{{ $identifier }}</td>
                            <td>{{ $indicatorValue['name'] }}</td>
                            <td>{{ $indicatorValue['area'] }}</td>
                            <td>{{ $indicatorValue['subarea'] }}</td>
                            <td>{{ $question['section'] ? $question['section'] : 'Questions' }}</td>
                            <td>{{ $question['type'] }}</td>
                            <td>{{ $question['name'] }}</td>
                            <td>{{ $questionId }} </td>
                            <td>{{ $option['name'] }}</td>
                            <td>{{ $option['score'] }}</td>
                            @foreach ($countryCodes as $country)
                                <td style="text-align: center;">{{ in_array($country, $option['selected']) ? 'x' : '' }}</td>
                            @endforeach
                            @if (sizeof($countryCodes) > 1) 
                                <td style="text-align: center;">{{ count($option['selected']) }}</td>
                            @endif
                        </tr>
                    @endforeach
                @elseif ($question['type'] == 'free-text')
                    <tr>
                        <td>{{ $identifier }}</td>
                        <td>{{ $indicatorValue['name'] }}</td>
                        <td>{{ $indicatorValue['area'] }}</td>
                        <td>{{ $indicatorValue['subarea'] }}</td>
                        <td>{{ $question['section'] ? $question['section'] : 'Questions' }}</td>
                        <td>{{ $question['type'] }}</td>
                        <td>{{ $question['name'] }}</td>
                        <td>{{ $questionId }} </td>
                        <td></td>
                        <td></td>
                        @foreach ($countryCodes as $country)
                            <td></td>
                        @endforeach
                        <td></td>
                    </tr>
                @endif
            @endforeach
        @endforeach
    </tbody>
</table>
