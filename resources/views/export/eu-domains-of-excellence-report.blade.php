<table>
    <caption>Export Report Data</caption>
    <thead>
        <tr>
            <th>Area</th>
            <th>Subarea</th>
            <th>Indicator</th>
            <th>Algorithm</th>
            <th>EU Avg</th>
            <th>Countries Above</th>
            <th>Countries Below</th>
            <th>Countries Around</th>
            <th>Deviation Avg</th>
            <th>Deviation Max</th>
            <th>Deviation Min</th>
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
                <td>{{ $domain['scores'][0]['euAverage'] }}</td>
                <td>{{ $domain['scores'][0]['numberOfCountries']['above'] }}</td>
                <td>{{ $domain['scores'][0]['numberOfCountries']['below'] }}</td>
                <td>{{ $domain['scores'][0]['numberOfCountries']['around'] }}</td>
                <td>{{ $domain['scores'][0]['deviation'][0]['avg'] }}</td>
                <td>{{ $domain['scores'][0]['deviation'][0]['max'] }}</td>
                <td>{{ $domain['scores'][0]['deviation'][0]['min'] }}</td>
                <td>{{ $domain['scores'][0]['speedometer'] }}</td>
                <td>{{ $domain['source'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
