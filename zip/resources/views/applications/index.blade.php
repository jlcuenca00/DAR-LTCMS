<!DOCTYPE html>
<html>
<head>
    <title>Applications</title>
</head>
<body>
    <h1>Land Transfer Applications</h1>

    @if($applications->isEmpty())
        <p>No applications found.</p>
    @else
        <table border="1" cellpadding="8" cellspacing="0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Application Code</th>
                    <th>Status</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach($applications as $a)
                    <tr>
                        <td>{{ $a->id }}</td>
                        <td>{{ $a->application_code }}</td>
                        <td>{{ $a->status }}</td>
                        <td>{{ $a->remarks }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

</body>
</html>
