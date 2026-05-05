<div class="space-y-4">
    <div>
        <a href="{{ route('landowner.parcels.index') }}"
           class="inline-flex items-center px-4 py-2 bg-gray-800 text-white rounded-md">
            View My Parcel Records
        </a>
    </div>
    <div>
        <a href="{{ route('landowner.parcel-map.index') }}"
   class="block border rounded-lg p-5 hover:bg-gray-50 transition">
    <h4 class="font-semibold text-gray-900">
        My Parcel Map
    </h4>
    <p class="text-sm text-gray-600 mt-1">
        View mapped parcel records linked to your landowner account through a read-only map.
    </p>
</a>
    <div>
        <a href="{{ route('landowner.applications.index') }}"
           class="inline-flex items-center px-4 py-2 bg-gray-800 text-white rounded-md">
            View My Application Status
        </a>
    </div>
</div>