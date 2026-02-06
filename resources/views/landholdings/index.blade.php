<!DOCTYPE html>
<html>
<head>
    <title>Landholdings</title>
</head>
<body>
    <h1>Landholdings</h1>

    @if($landholdings->isEmpty())
        <p>No landholdings found.</p>
    @else
        <table border="1" cellpadding="8" cellspacing="0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Parcel Code</th>
                    <th>Area (ha)</th>
                    <th>Barangay</th>
                    <th>Municipality</th>
                </tr>
            </thead>
            <tbody>
                @foreach($landholdings as $l)
                    <tr>
                        <td>{{ $l->id }}</td>
                        <td>{{ $l->parcel_code }}</td>
                        <td>{{ $l->area_hectares }}</td>
                        <td>{{ $l->barangay }}</td>
                        <td>{{ $l->municipality }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

</body>
</html>
