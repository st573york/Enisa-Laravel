<table>
    <caption>Export Data</caption>
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Source</th>
            <th>Year</th>
            <th>Value</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($indicatorValuesData as $identifier => $indicatorValue)
            <tr>
                <td>{{ $identifier }}</td>
                <td>{{ $indicatorValue['name'] }}</td>
                <td>{{ $indicatorValue['source'] }}</td>
                <td>{{ $indicatorValue['year'] }}</td>
                <td>{{ $indicatorValue['value'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
