<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $clearance->clearance_number }}</title>
</head>
<body class="pdf-output">
    @include('staff.clearances.partials.form5-content', ['showToolbar' => false])
</body>
</html>
