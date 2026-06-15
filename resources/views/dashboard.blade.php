@php
    use Illuminate\Support\Facades\Route;

    $user = auth()->user();
    $role = $user?->role;

    $targetRoute = match ($role) {
        'staff', 'admin' => Route::has('staff.dashboard') ? 'staff.dashboard' : null,
        'landowner' => Route::has('landowner.dashboard') ? 'landowner.dashboard' : null,
        'geodetic', 'geodetic_engineer', 'geodetic_personnel' => Route::has('geodetic.dashboard') ? 'geodetic.dashboard' : null,
        default => null,
    };

    $targetUrl = $targetRoute ? route($targetRoute) : url('/');
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="0;url={{ $targetUrl }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting | DAR-iLAND</title>
    <script>
        window.location.replace(@json($targetUrl));
    </script>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: #f8fafc;
            color: #0f172a;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .card {
            width: min(420px, calc(100% - 32px));
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            background: #ffffff;
            padding: 28px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, .08);
            text-align: center;
        }

        h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 900;
        }

        p {
            margin: 10px 0 18px;
            color: #64748b;
            font-size: 14px;
            line-height: 1.5;
        }

        a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: #14532d;
            color: #ffffff;
            padding: 10px 16px;
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <main class="card">
        <h1>Redirecting to your dashboard</h1>
        <p>The default Laravel dashboard page has been removed from the user flow.</p>
        <a href="{{ $targetUrl }}">Continue</a>
    </main>
</body>
</html>
