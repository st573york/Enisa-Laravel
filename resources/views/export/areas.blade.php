    <table>
        <caption>Area Export</caption>
        <thead>
            <tr style="text-align: center">
                <th>Area</th>
                <th>Value</th>
                <th>Weights</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($index['json_data']['contents'] as $area)
                @if ($loop->index == 0)
                @else
                    <tr style="text-align: center">
                        <td>{{ $area['area']['name'] }}</td>
                        <td>{{ $area['area']['values'][0][array_key_first($area['area']['values'][0])] }}</td>
                        <td>{{  $area['area']['weight'] }}</td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>
