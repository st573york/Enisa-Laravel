    <table>
        <caption>Indicator Export</caption>
        <thead>
            <tr style="text-align: center">
                <th>Key Indicator Name</th>
                <th>Subarea</th>
                <th>Area</th>
                <th>Key Indicator Value</th>
                <th>Weights</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($index['json_data']['contents'] as $area)
                @if ($loop->index == 0)
                @else
                    @foreach ($area['area']['subareas'] as $subarea)
                        @foreach ($subarea['indicators'] as $indicator)
                            <tr style="text-align: center">
                                <td>{{ $indicator['name'] }}</td>
                                <td>{{ $subarea['name'] }}</td>
                                <td>{{ $area['area']['name'] }}</td>
                                @if(isset($indicator['values']))
                                    <td>{{ $indicator['values'][0][array_key_first($indicator['values'][0])] }}</td>
                                @else
                                    <td></td>
                                @endif
                                <td>{{ $indicator['weight'] }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                @endif
            @endforeach
        </tbody>
    </table>
