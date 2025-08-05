    <table>
        <caption>Subarea Export</caption>
        <thead>
            <tr style="text-align: center">
                <th>Subarea</th>
                <th>Area</th>
                <th>Subarea Value</th>
                <th>Weights</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($index['json_data']['contents'] as $area)
                @if ($loop->index == 0)
                @else
                    @foreach ($area['area']['subareas'] as $subarea)
                        <tr style="text-align: center">
                            <td>{{ $subarea['name'] }}</td>
                            <td>{{ $area['area']['name'] }}</td>
                            <td>{{ $subarea['values'][0][array_key_first($subarea['values'][0])] }}</td>
                            <td>{{ $subarea['weight'] }}</td>
                        </tr>
                    @endforeach
                @endif
            @endforeach
        </tbody>
    </table>
