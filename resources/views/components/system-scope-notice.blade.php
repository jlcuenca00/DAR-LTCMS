@props([
    'title' => 'System Scope Notice',
    'variant' => 'amber',
])

@php
    $styles = [
        'amber' => [
            'wrapper' => 'bg-amber-50 border-amber-200',
            'title' => 'text-amber-900',
            'text' => 'text-amber-800',
            'badge' => 'bg-amber-100 text-amber-800 border-amber-200',
        ],
        'green' => [
            'wrapper' => 'bg-green-50 border-green-200',
            'title' => 'text-green-900',
            'text' => 'text-green-800',
            'badge' => 'bg-green-100 text-green-800 border-green-200',
        ],
        'blue' => [
            'wrapper' => 'bg-blue-50 border-blue-200',
            'title' => 'text-blue-900',
            'text' => 'text-blue-800',
            'badge' => 'bg-blue-100 text-blue-800 border-blue-200',
        ],
    ];

    $style = $styles[$variant] ?? $styles['amber'];
@endphp

<div {{ $attributes->merge(['class' => 'border rounded-xl p-5 ' . $style['wrapper']]) }}>
    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
        <div>
            <h3 class="font-semibold {{ $style['title'] }}">
                {{ $title }}
            </h3>

            <p class="text-sm mt-2 leading-relaxed {{ $style['text'] }}">
                This system supports land transfer clearance application processing, clearance generation,
                records management, monitoring, and reporting only. Approval of a clearance application does
                not automatically transfer land ownership, mutate registry records, or finalize legal land
                transfer. Actual ownership transfer and registry changes remain subject to separate legal
                and administrative procedures.
            </p>
        </div>

        <span class="inline-flex items-center px-3 py-1 rounded-full border text-xs font-semibold whitespace-nowrap {{ $style['badge'] }}">
            Clearance System Only
        </span>
    </div>
</div>