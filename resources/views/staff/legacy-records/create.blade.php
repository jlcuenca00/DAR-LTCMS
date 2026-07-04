<x-staff-shell
    title="Source Package Workspace"
    active="source-records"
    maxWidth="max-w-3xl"
>
    <x-slot name="actions">
        <a href="{{ route('staff.legacy-records.index') }}" class="staff-button staff-button-light">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Source Records
        </a>
    </x-slot>

    <div class="staff-panel staff-panel-pad text-center">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl border border-green-200 bg-green-50 text-2xl text-green-700">
            <i class="fa-solid fa-boxes-stacked"></i>
        </div>

        <h1 class="font-heading text-2xl font-black text-gray-950">Source encoding is now handled in one workspace</h1>
        <p class="mx-auto mt-3 max-w-2xl text-sm font-semibold leading-6 text-gray-600">
            Use the Source Package Workspace for both single source records and grouped source packages. Select one source section to encode a single source record, or select multiple sections to generate connected records under one package.
        </p>

        <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
            <a href="{{ route('staff.source-record-packages.create') }}" class="staff-button staff-button-primary justify-center">
                <i class="fa-solid fa-plus"></i>
                Open Source Package Workspace
            </a>
            <a href="{{ route('staff.legacy-records.index') }}" class="staff-button staff-button-light justify-center">
                View Source Records
            </a>
        </div>
    </div>

    <script>
        window.setTimeout(function () {
            window.location.href = @json(route('staff.source-record-packages.create'));
        }, 900);
    </script>
</x-staff-shell>
