    <table>
        <caption>Area Export</caption>
        <thead>
            <tr style="text-align: center">
                <th>ID</th>
                <th>Name</th>
                <th>Description</th>
                <th>Number</th>
                <th>Weight</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($index as $area)
                <tr style="text-align: center">
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ htmlspecialchars_decode($area->name) }}</td>
                    <td>{{ htmlspecialchars_decode($area->description) }}</td>
                    <td>{{ htmlspecialchars_decode($area->identifier) }}</td>
                    <td>{{ htmlspecialchars_decode($area->default_weight) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
