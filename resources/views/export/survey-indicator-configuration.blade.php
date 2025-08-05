<table>
    <caption>Export Data</caption>
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Section</th>
            <th>Type</th>
            <th>Question</th>
            <th>Question ID</th>
            <th>Required</th>
            <th>Answer</th>
            <th>Master Option</th>
            <th>Score</th>
            <th>Info</th>
            <th>Compatible</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($data['questions'] as $questionId => $question)
            @if ($question['type'] == 'single-choice' ||
                 $question['type'] == 'multiple-choice')
                @foreach ($question['options'] as $option)
                    <tr>
                        <td>{{ htmlspecialchars_decode($data['identifier']) }}</td>
                        <td>{{ htmlspecialchars_decode($data['name']) }}</td>
                        <td>{{ $question['section'] ? htmlspecialchars_decode($question['section']) : 'Questions' }}</td>
                        <td>{{ htmlspecialchars_decode($question['type']) }}</td>
                        <td>{{ htmlspecialchars_decode($question['name']) }}</td>
                        <td>{{ htmlspecialchars_decode($questionId) }} </td>
                        <td>{{ htmlspecialchars_decode($question['required']) }}</td>
                        <td>{{ htmlspecialchars_decode($option['name']) }}</td>
                        <td>{{ htmlspecialchars_decode($option['master']) }}</td>
                        <td>{{ htmlspecialchars_decode($option['score']) }}</td>
                        <td>{{ htmlspecialchars_decode($question['info']) }}</td>
                        <td>{{ htmlspecialchars_decode($question['compatible']) }}</td>
                    </tr>
                @endforeach
            @elseif ($question['type'] == 'free-text')
                <tr>
                    <td>{{ htmlspecialchars_decode($data['identifier']) }}</td>
                    <td>{{ htmlspecialchars_decode($data['name']) }}</td>
                    <td>{{ $question['section'] ? htmlspecialchars_decode($question['section']) : 'Questions' }}</td>
                    <td>{{ htmlspecialchars_decode($question['type']) }}</td>
                    <td>{{ htmlspecialchars_decode($question['name']) }}</td>
                    <td>{{ htmlspecialchars_decode($questionId) }} </td>
                    <td>{{ htmlspecialchars_decode($question['required']) }}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>{{ htmlspecialchars_decode($question['info']) }}</td>
                    <td>{{ htmlspecialchars_decode($question['compatible']) }}</td>
                </tr>
            @endif
        @endforeach
    </tbody>
</table>
