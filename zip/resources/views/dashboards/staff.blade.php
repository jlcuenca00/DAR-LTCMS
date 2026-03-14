<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Staff Dashboard</h2>
    </x-slot>

    <div class="p-6">
        Welcome, {{ auth()->user()->name }} ({{ auth()->user()->role }})
    </div>
</x-app-layout>