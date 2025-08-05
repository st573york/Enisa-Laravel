<table>
    <caption>Export Report Data</caption>
    <thead>
        <tr>
            <th></th>
            <th>Area</th>
            <th>Subarea</th>
            <th>EU Avg</th>
            <th>{{ $countryCode }}</th>
            <th>Difference</th>
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
            <td>{{ $scores['country'] }}</td>
            <td>{{ $scores['difference'] }}</td>
            <td>{{ $scores['speedometer'] }}</td>
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
                <td>{{ $area['scores']['euAverage'] }}</td>
                <td>{{ $area['scores']['country'] }}</td>
                <td>{{ $area['scores']['difference'] }}</td>
                <td>{{ $area['scores']['speedometer'] }}</td>
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
                    <td>{{ $subarea['scores']['euAverage'] }}</td>
                    <td>{{ $subarea['scores']['country'] }}</td>
                    <td>{{ $subarea['scores']['difference'] }}</td>
                    <td>{{ $subarea['scores']['speedometer'] }}</td>
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
                            <td>{{ $indicator['euAverage'] }}</td>
                            <td>{{ $indicator['countryScore'] }}</td>
                            <td>{{ $indicator['difference'] }}</td>
                            <td>{{ $indicator['speedometer'] }}</td>
                            <td>{{ $indicator['referenceYear'] ?? '-' }}</td>
                            <td>{{ $indicator['source'] }}</td>
                        </tr>
                    @endforeach
                @endforeach
            @endforeach
        @endforeach
    </tbody>
</table>
