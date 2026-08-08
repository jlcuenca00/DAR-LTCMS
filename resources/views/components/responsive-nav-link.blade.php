@props(['active'])

@php
$classes = ($active ?? false)
            ? 'flex min-h-11 w-full items-center border-l-4 border-indigo-400 bg-indigo-50 ps-4 pe-4 py-2 text-start text-base font-medium leading-5 text-indigo-700 transition duration-150 ease-in-out focus:outline-none focus:text-indigo-800 focus:bg-indigo-100 focus:border-indigo-700'
            : 'flex min-h-11 w-full items-center border-l-4 border-transparent ps-4 pe-4 py-2 text-start text-base font-medium leading-5 text-gray-600 transition duration-150 ease-in-out hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800 focus:outline-none focus:border-gray-300 focus:bg-gray-50 focus:text-gray-800';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    <span class="min-w-0 break-words">{{ $slot }}</span>
</a>
