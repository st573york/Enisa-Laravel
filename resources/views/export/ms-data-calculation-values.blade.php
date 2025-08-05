<table>
    <caption>Export Data Calculation Values</caption>
    <thead>
        <tr>
            <th></th>
            <th></th>
            <th>Raw</th>
            <th></th>
            <th>Treated</th>
            <th></th>
            <th>Imputed</th>
            <th></th>
            <th>Normalised</th>
            <th></th>
            <th>Aggregated</th>
            <th></th>
            <th>Treated Points</th>
            <th>Full Score</th>
            <th></th>
        </tr>
        <tr>
            <th>uCode</th>
            <th>uName</th>
            <th>{{ $uCode['raw']['eu'] }}</th>
            <th>{{ $uCode['raw']['country'] }}</th>
            <th>{{ $uCode['treated']['eu'] }}</th>
            <th>{{ $uCode['treated']['country'] }}</th>
            <th>{{ $uCode['imputed']['eu'] }}</th>
            <th>{{ $uCode['imputed']['country'] }}</th>
            <th>{{ $uCode['normalised']['eu'] }}</th>
            <th>{{ $uCode['normalised']['country'] }}</th>
            <th>{{ $uCode['aggregated']['eu'] }}</th>
            <th>{{ $uCode['aggregated']['country'] }}</th>
            <th>{{ $uCode['treated_points']['country'] }}</th>
            <th>{{ $uCode['fullscore']['eu'] }}</th>
            <th>{{ $uCode['fullscore']['country'] }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($data as $key => $val)
            <tr>
                <td>{{ $key }}</td>
                <td>{{ $val['name'] }}</td>
                @if (isset($val['raw']))
                    <td>{{ $val['raw']['eu'] }}</td>
                    <td>{{ $val['raw']['country'] }}</td>
                @else
                    <td></td>
                    <td></td>
                @endif
                @if (isset($val['treated']))
                    <td>{{ $val['treated']['eu'] }}</td>
                    <td>{{ $val['treated']['country'] }}</td>
                @else
                    <td></td>
                    <td></td>
                @endif
                @if (isset($val['imputed']))
                    <td>{{ $val['imputed']['eu'] }}</td>
                    <td>{{ $val['imputed']['country'] }}</td>
                @else
                    <td></td>
                    <td></td>
                @endif
                @if (isset($val['normalised']))
                    <td>{{ $val['normalised']['eu'] }}</td>
                    <td>{{ $val['normalised']['country'] }}</td>
                @else
                    <td></td>
                    <td></td>
                @endif
                @if (isset($val['aggregated']))
                    <td>{{ $val['aggregated']['eu'] }}</td>
                    <td>{{ $val['aggregated']['country'] }}</td>
                @else
                    <td></td>
                    <td></td>
                @endif
                @if (isset($val['treated_points']))
                    <td>{{ $val['treated_points']['country'] }}</td>
                @else
                    <td></td>
                @endif
                @if (isset($val['fullscore']))
                    <td>{{ $val['fullscore']['eu'] }}</td>
                    <td>{{ $val['fullscore']['country'] }}</td>
                @else
                    <td></td>
                    <td></td>
                @endif
            </tr>
        @endforeach
    </tbody>
</table>
