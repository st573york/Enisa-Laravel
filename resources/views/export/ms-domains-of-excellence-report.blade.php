<table>
    <caption>Export Report Data</caption>
    <thead>
        <tr>
            <th>Area</th>
            <th>Subarea</th>
            <th>Indicator</th>
            <th>Algorithm</th>
            <th>EU Avg</th>
            <th>{{ $countryCode }}</th>
            <th>Difference</th>
            <th>Speedometer</th>
            <th>Source</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($domains_of_excellence as $domain)
            <tr>
                <td>{{ $domain['areaName'] }}</td>
                <td>{{ $domain['subareaName'] }}</td>
                <td>{{ $domain['indicator'] }}</td>
                <td>{{ $domain['algorithm'] }}</td>
                <td>{{ $domain['euAverage'] }}</td>
                <td>{{ $domain['countryScore'] }}</td>
                <td>{{ $domain['difference'] }}</td>
                <td>{{ $domain['speedometer'] }}</td>
                <td>{{ $domain['source'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
