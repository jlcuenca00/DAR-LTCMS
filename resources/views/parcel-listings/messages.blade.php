@if (session('status'))
    <p role="status" class="rounded bg-emerald-50 p-3 text-emerald-900">{{ session('status') }}</p>
@endif
@if ($errors->any())
    <ul role="alert" class="list-inside list-disc rounded bg-red-50 p-3 text-red-900">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@endif
