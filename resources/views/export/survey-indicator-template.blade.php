<table>
    <caption>Survey Indicator Template Export</caption>
    <thead>
        <tr>
            <th style="font-weight: bold; font-size: 14px;">{{ htmlspecialchars_decode($data['name']) }}</th>
        </tr>
    </thead>
    <tbody>
        @php
            $accordion = null;
        @endphp
        @foreach ($data['questions'] as $questionId => $question)
            @php
                $parts = explode('-', $questionId);
            @endphp
            @if ($accordion != $parts[1])
                <tr>
                    <td></td>
                    <td style="background-color: #bf8f00; color: #ffffff; font-weight: bold; vertical-align: top;">{{ htmlspecialchars_decode($question['section']) }}</td>
                    <td></td>
                </tr>
            @endif
            @php
                $accordion = $parts[1];
            @endphp
            <tr>
                <td style="font-weight: bold; vertical-align: top;">{{ 'Question (' . $question['type'] . ')' }}</td>
                <td style="background-color: #fff2cc; font-weight: bold; vertical-align: top;">{{ htmlspecialchars_decode($question['name']) }}</td>
                <td style="vertical-align: top;">{{ ($question['type'] == 'free-text') ? 'Provide your answer' : 'Choose answer' }}</td>
                <td>{{ 'choice-' . $parts[1] . '-' . $parts[2] }}</td>
            </tr>
            @if ($question['type'] == 'single-choice' ||
                 $question['type'] == 'free-text')
                <tr>
                    <td></td>
                    <td></td>
                    <td style="background-color: #ebebeb; border: 2px solid black;"></td>
                    <td>{{ $question['type'] }}</td>
                    <td>{{ 'option-' . $parts[1] . '-' . $parts[2] }}</td>
                </tr>
            @elseif ($question['type'] == 'multiple-choice')
                @foreach ($question['options'] as $option)
                    <tr>
                        <td></td>
                        <td>{{ htmlspecialchars_decode($option['name']) }}</td>
                        <td style="background-color: #ebebeb; border: 2px solid black;"></td>
                        <td>{{ $question['type'] }}</td>
                        <td>{{ 'option-' . $parts[1] . '-' . $parts[2] }}</td>
                    </tr>
                @endforeach
            @endif
            <tr></tr>
            <tr>
                <td style="font-weight: bold; vertical-align: top;">Reference Year</td>
                <td style="background-color: #ebebeb; border: 2px solid black; vertical-align: top;"></td>
                <td></td>
                <td>{{ 'reference_year-' . $parts[1] . '-' . $parts[2] }}</td>
            </tr>
            <tr></tr>
            <tr>
                <td style="font-weight: bold; vertical-align: top;">Reference Source</td>
                <td style="background-color: #ebebeb; border: 2px solid black; vertical-align: top;"></td>
                <td></td>
                <td>{{ 'reference_source-' . $parts[1] . '-' . $parts[2] }}</td>
            </tr>
            <tr></tr>
        @endforeach
        <tr>
            <td style="font-weight: bold;">Rating</td>
            <td style="background-color: #ebebeb; border: 2px solid black;"></td>
        </tr>
        <tr></tr>
        <tr>
            <td style="font-weight: bold; vertical-align: top;">Comments</td>
            <td style="background-color: #ebebeb; border: 2px solid black; vertical-align: top;"></td>
        </tr>
    </tbody>
</table>
