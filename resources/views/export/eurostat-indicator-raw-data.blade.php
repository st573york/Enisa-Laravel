<table>
    <caption>Export Data</caption>
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Variable Name</th>
            <th>Variable Code</th>
            <th>Area</th>
            <th>Subarea</th>
            <th>Year</th>
            @foreach ($countryCodes as $country)
                <th style="text-align: center;">{{ $country }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach ($indicatorValuesData as $indicatorValue)
            <tr>
                <td>{{ $indicatorValue['identifier'] }}</td>
                <td>{{ $indicatorValue['name'] }}</td>
                <td>{{ $indicatorValue['variable_name'] }}</td>
                <td>{{ $indicatorValue['variable_code'] }}</td>
                <td>{{ $indicatorValue['area'] }}</td>
                <td>{{ $indicatorValue['subarea'] }}</td>
                <td>{{ $indicatorValue['year'] }}</td>
                @foreach ($countryCodes as $country)
                    <td style="text-align: center;">{{ isset($indicatorValue[$country]) ? $indicatorValue[$country] : 'N/A' }}</td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
