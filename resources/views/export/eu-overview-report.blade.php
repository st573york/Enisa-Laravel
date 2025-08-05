<table>
    <caption>Export Report Data</caption>
    <thead>
        <tr>
            <th></th>
            <th>Area</th>
            <th>Subarea</th>
            <th>EU Avg</th>
            <th>Countries Above</th>
            <th>Countries Below</th>
            <th>Countries Around</th>
            <th>Deviation Avg</th>
            <th>Deviation Max</th>
            <th>Deviation Min</th>
            <th>Speedometer</th>
            <th>Reference Year</th>
            <th>Source</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Index</td>
            <td>-</td>
            <td>-</td>
            <td>{{ $scores['euAverage'] }}</td>
            <td>{{ $scores['numberOfCountries']['above'] }}</td>
            <td>{{ $scores['numberOfCountries']['below'] }}</td>
            <td>{{ $scores['numberOfCountries']['around'] }}</td>
            <td>{{ $deviation['avg'] }}</td>
            <td>{{ $deviation['max'] }}</td>
            <td>{{ $deviation['min'] }}</td>
            <td>{{ $speedometer }}</td>
            <td>-</td>
            <td>-</td>
        </tr>
        <tr></tr>
        <tr></tr>
        @foreach ($areas as $area)
            <tr>
                <td>{{ $area['name'] }}</td>
                <td>-</td>
                <td>-</td>
                <td>{{ $area['scores'][0]['euAverage'] }}</td>
                <td>{{ $area['scores'][0]['numberOfCountries']['above'] }}</td>
                <td>{{ $area['scores'][0]['numberOfCountries']['below'] }}</td>
                <td>{{ $area['scores'][0]['numberOfCountries']['around'] }}</td>
                <td>{{ $area['scores'][0]['deviation'][0]['avg'] }}</td>
                <td>{{ $area['scores'][0]['deviation'][0]['max'] }}</td>
                <td>{{ $area['scores'][0]['deviation'][0]['min'] }}</td>
                <td>{{ $area['scores'][0]['speedometer'] }}</td>
                <td>-</td>
                <td>-</td>
            </tr>
        @endforeach
        <tr></tr>
        <tr></tr>
        @foreach ($areas as $area)
            @foreach ($area['subareas'] as $subarea)
                <tr>
                    <td>{{ $subarea['name'] }}</td>
                    <td>{{ $area['name'] }}</td>
                    <td>-</td>
                    <td>{{ $subarea['scores'][0]['euAverage'] }}</td>
                    <td>{{ $subarea['scores'][0]['numberOfCountries']['above'] }}</td>
                    <td>{{ $subarea['scores'][0]['numberOfCountries']['below'] }}</td>
                    <td>{{ $subarea['scores'][0]['numberOfCountries']['around'] }}</td>
                    <td>{{ $subarea['scores'][0]['deviation'][0]['avg'] }}</td>
                    <td>{{ $subarea['scores'][0]['deviation'][0]['max'] }}</td>
                    <td>{{ $subarea['scores'][0]['deviation'][0]['min'] }}</td>
                    <td>{{ $subarea['scores'][0]['speedometer'] }}</td>
                    <td>-</td>
                    <td>-</td>
                </tr>
            @endforeach
        @endforeach
        <tr></tr>
        <tr></tr>
        @foreach($allIndicators as $areas)
            @foreach ($areas['areas'] as $area)
                @foreach ($area['subareas'] as $subarea)
                    @foreach ($subarea['indicators'] as $indicator)
                        <tr>
                            <td>{{ $indicator['indicator'] }}</td>
                            <td>{{ $subarea['name'] }}</td>
                            <td>{{ $area['name'] }}</td>
                            <td>{{ $indicator['scores'][0]['euAverage'] }}</td>
                            <td>{{ $indicator['scores'][0]['numberOfCountries']['above'] }}</td>
                            <td>{{ $indicator['scores'][0]['numberOfCountries']['below'] }}</td>
                            <td>{{ $indicator['scores'][0]['numberOfCountries']['around'] }}</td>
                            <td>{{ $indicator['scores'][0]['deviation'][0]['avg'] }}</td>
                            <td>{{ $indicator['scores'][0]['deviation'][0]['max'] }}</td>
                            <td>{{ $indicator['scores'][0]['deviation'][0]['min'] }}</td>
                            <td>{{ $indicator['scores'][0]['speedometer'] }}</td>
                            <td>{{ $indicator['referenceYear'] ?? '-' }}</td>
                            <td>{{ $indicator['source'] }}</td>
                        </tr>
                    @endforeach
                @endforeach
            @endforeach
        @endforeach
    </tbody>
</table>
