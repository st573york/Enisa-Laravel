    <table>
        <caption>Subarea Export</caption>
        <thead>
            <tr style="text-align: center">
                <th>ID</th>
                <th>Name</th>
                <th>Short Name</th>
                <th>Description</th>
                <th>Number</th>
                <th>Area</th>
                <th>Weight</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($index as $subarea)
                <tr style="text-align: center">
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ htmlspecialchars_decode($subarea->name) }}</td>
                    <td>{{ htmlspecialchars_decode($subarea->short_name) }}</td>
                    <td>{{ htmlspecialchars_decode($subarea->description) }}</td>
                    <td>{{ htmlspecialchars_decode($subarea->identifier) }}</td>
                    <td>{{ ($subarea->default_area) ? htmlspecialchars_decode($subarea->default_area->name) : '-' }}</td>
                    <td>{{ htmlspecialchars_decode($subarea->default_weight) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
