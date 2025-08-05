<table>
    <caption>Export Report Data</caption>
    <thead>
        <tr>
            <th>Name</th>
            <th>Algorithm</th>
            <th>Score</th>
            <th>Reference Year</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($euWideIndicators as $indicator)
            <tr>
                <td>{{ $indicator['indicator'] }}</td>
                <td>{{ $indicator['algorithm'] }}</td>
                <td>{{ $indicator['score'] }}</td>
                <td>{{ $indicator['referenceYear'] ?? '-' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
