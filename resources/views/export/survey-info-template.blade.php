<table>
    <caption>Survey Info Template Export</caption>
    <thead>
        <tr>
            <th></th>
            <th style="font-weight: bold; font-size: 18px; color: #cb0538;">EU Cybersecurity Index</th>
        </tr>
    </thead>
    <tbody>
        <tr></tr>
        <tr>
            <td style="font-weight: bold; font-size: 14px;">List of Indicators</td>
        </tr>
        @php
            $number = 0;
        @endphp
        @foreach ($indicators as $indicator)
            <tr>
                <td>{{ ++$number }}</td>
                <td>{{ htmlspecialchars_decode($indicator->name) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
