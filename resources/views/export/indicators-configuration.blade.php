    <table>
        <caption>Indicator Export</caption>
        <thead>
            <tr style="text-align: center">
                <th>ID</th>
                <th>Name</th>
                <th>Short Name</th>
                <th>Description</th>
                <th>Number</th>
                <th>Source</th>
                <th>Category</th>
                <th>Algorithm</th>
                <th>Comment</th>
                <th>Direction</th>
                <th>New Indicator</th>
                <th>Min Max 0037_1</th>
                <th>Min Max</th>
                <th>Predefined Divider</th>
                <th>Normalize</th>
                <th>Inverse Value</th>
                <th>Report Year</th>
                <th>Subarea</th>
                <th>Weight</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($index as $indicator)
                <tr style="text-align: center">
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ htmlspecialchars_decode($indicator->name) }}</td>
                    <td>{{ htmlspecialchars_decode($indicator->short_name) }}</td>
                    <td>{{ htmlspecialchars_decode($indicator->description) }}</td>
                    <td>{{ htmlspecialchars_decode($indicator->identifier) }}</td>
                    <td>{{ htmlspecialchars_decode($indicator->source) }}</td>
                    <td>{{ htmlspecialchars_decode($indicator->category) }}</td>
                    @php
                        $algorithm = preg_replace('/<\/p[^>]*>/', ' ', $indicator->algorithm);
                    @endphp
                    <td>{{ strip_tags(htmlspecialchars_decode($algorithm)) }}</td>
                    <td>{{ htmlspecialchars_decode($indicator->comment) }}</td>
                    @if ($indicator->disclaimers)
                        <td>{{ htmlspecialchars_decode($indicator->disclaimers->direction) }}</td>
                        <td>{{ htmlspecialchars_decode($indicator->disclaimers->new_indicator) }}</td>
                        <td>{{ htmlspecialchars_decode($indicator->disclaimers->min_max_0037_1) }}</td>
                        <td>{{ htmlspecialchars_decode($indicator->disclaimers->min_max) }}</td>
                    @else
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    @endif
                    @if ($indicator->variables && $indicator->variables->first())
                        <td>{{ htmlspecialchars_decode($indicator->variables->first()->predefined_divider) }}</td>
                        <td>{{ htmlspecialchars_decode($indicator->variables->first()->normalize) }}</td>
                        <td>{{ htmlspecialchars_decode($indicator->variables->first()->inverse_value) }}</td>
                    @else
                        <td></td>
                        <td></td>
                        <td></td>
                    @endif
                    <td>{{ htmlspecialchars_decode($indicator->report_year) }}</td>
                    <td>{{ ($indicator->default_subarea) ? htmlspecialchars_decode($indicator->default_subarea->name) : '-' }}</td>
                    <td>{{ htmlspecialchars_decode($indicator->default_weight) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
