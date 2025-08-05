<table>
    <caption>Export Data</caption>
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Area</th>
            <th>Subarea</th>
            <th>Source</th>
            <th>Year</th>
            @foreach ($countryCodes as $country)
                <th style="text-align: center;">{{ $country }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach ($indicatorValuesData as $identifier => $indicatorValue)
            <tr>
                <td>{{ $identifier }}</td>
                <td>{{ $indicatorValue['name'] }}</td>
                <td>{{ $indicatorValue['area'] }}</td>
                <td>{{ $indicatorValue['subarea'] }}</td>
                <td>{{ $indicatorValue['source'] }}</td>
                <td>{{ $indicatorValue['year'] }}</td>
                @foreach ($countryCodes as $country)
                    <td style="text-align: center;">{{ isset($indicatorValue[$country]) ? $indicatorValue[$country] : 'N/A' }}</td>
                @endforeach
            </tr>
        @endforeach

    </tbody>
</table>
