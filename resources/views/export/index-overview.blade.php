<table>
    <caption>Export Data</caption>
    <thead>
        <tr>
            <th></th>
            <th>Area</th>
            <th>Subarea</th>
            <th>Weight</th>
            @foreach ($countryCodes as $country)
                <th>{{ $country }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Index</td>
            <td>-</td>
            <td>-</td>
            <td>-</td>
            @foreach ($indiceRows['index'] as $row => $value)
                <td>{{ $value }}</td>
            @endforeach
        </tr>
        <tr></tr>
        <tr></tr>
        @foreach ($indiceRows['areas'] as $row => $area)
            <tr>
                <td>{{ $row }}</td>
                <td>-</td>
                <td>-</td>
                <td>{{ $area['weight'] }}</td>
                @foreach ($countryCodes as $country)
                    <td>{{ $area[$country] }}</td>
                @endforeach
            </tr>
        @endforeach
        <tr></tr>
        <tr></tr>
        @foreach ($indiceRows['subareas'] as $row => $subarea)
            <tr>
                <td>{{ $row }}</td>
                <td>{{ $subarea['area'] }}</td>
                <td>-</td>
                <td>{{ $subarea['weight'] }}</td>
                @foreach ($countryCodes as $country)
                    <td>{{ $subarea[$country] }}</td>
                @endforeach
            </tr>
        @endforeach
        <tr></tr>
        <tr></tr>
        @foreach ($indiceRows['indicators'] as $row => $indicator)
            <tr>
                <td>{{ $row }}</td>
                <td>{{ $indicator['area'] }}</td>
                <td>{{ $indicator['subarea'] }}</td>
                <td>{{ $indicator['weight'] }}</td>
                @foreach ($countryCodes as $country)
                    <td>{{ $indicator[$country] }}</td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
