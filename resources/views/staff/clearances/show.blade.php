<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $clearance->clearance_number }} | LTC Form No. 5 Print View</title>
</head>
<body>
    @include('staff.clearances.partials.form5-content', ['showToolbar' => true, 'returnRoute' => $returnRoute ?? null, 'returnLabel' => $returnLabel ?? null])
</body>
</html>
