<table>
    <caption>Export Treated Points</caption>
    <thead>
        <tr>
            <th>uCode</th>
            <th>{{ $data['uCode'] }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($data as $key => $val)
            <tr>
                @if ($key != 'uCode')
                    <td>{{ $key  }}</td>
                    <td>{{ $val }}</td>
                @endif
            </tr>
        @endforeach
    </tbody>
</table>
