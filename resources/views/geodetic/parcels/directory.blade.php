<x-geodetic-shell title="Parcel Directory" active="parcels">
    <style>
        .geo-directory { display: grid; gap: 18px; }
        .geo-directory-panel { padding: 20px; background: #fff; border: 1px solid var(--geo-line); border-radius: 12px; min-width: 0; }
        .geo-directory-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 14px; }
        .geo-directory-filters { display: grid; grid-template-columns: minmax(200px, 2fr) 1fr 1fr auto; align-items: end; gap: 12px; }
        .geo-directory-field { display: grid; gap: 6px; font-size: 13px; font-weight: 700; }
        .geo-directory-filters input, .geo-directory-filters select { width: 100%; min-height: 44px; padding: 8px; border: 1px solid #cbd5d1; border-radius: 8px; font: inherit; background: #fff; color: #111827; }
        .geo-directory-table-wrap { overflow-x: auto; }
        .geo-directory-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .geo-directory-table th, .geo-directory-table td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; vertical-align: top; overflow-wrap: anywhere; }
        .geo-directory-table th { background: #f8faf9; }
        .geo-directory-table small { display: block; margin-top: 4px; color: #667085; }
        .geo-directory-code { color: #14532d; font-weight: 800; }
        .geo-directory-actions .geo-button, .geo-directory-filters .geo-button { min-height: 44px; }
        @media (max-width: 900px) { .geo-directory-filters { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 600px) {
            .geo-directory-filters { grid-template-columns: 1fr; }
            .geo-directory-table thead { display: none; }
            .geo-directory-table tr { display: block; padding: 10px 0; border-bottom: 1px solid #e5e7eb; }
            .geo-directory-table td { display: grid; grid-template-columns: 95px minmax(0, 1fr); gap: 10px; border: 0; padding: 8px 0; }
            .geo-directory-table td::before { content: attr(data-label); font-weight: 700; color: #667085; }
            .geo-directory-filters input, .geo-directory-filters select { font-size: 16px; }
        }
    </style>
    <section class="geo-directory">
        <article class="geo-directory-panel">
            <h2>Parcel Directory</h2>
            <p>Find parcel records with or without linked landholdings or map geometry. Archived records remain available for reference.</p>
            <div class="geo-directory-actions">
                <a class="geo-button geo-button-primary" href="{{ route('geodetic.parcels.directory', ['geometry' => 'unmapped', 'status' => 'active']) }}">Awaiting Geometry</a>
                <a class="geo-button" href="{{ route('geodetic.parcels.index') }}">Landholding References</a>
                <a class="geo-button" href="{{ route('geodetic.parcel-map.index') }}">Open Map</a>
            </div>
        </article>
        <article class="geo-directory-panel">
            <form method="GET" action="{{ route('geodetic.parcels.directory') }}" class="geo-directory-filters">
                <div class="geo-directory-field"><label for="directory-q">Search references, landowner or location</label>
                    <input id="directory-q" type="search" name="q" maxlength="100" value="{{ $filters['q'] }}">
                </div>
                <div class="geo-directory-field"><label for="directory-geometry">Geometry</label>
                    <select id="directory-geometry" name="geometry">
                        @foreach (['all' => 'All geometry states', 'mapped' => 'Display bounds available', 'unmapped' => 'No geometry', 'unavailable' => 'Display bounds unavailable'] as $value => $label)
                            <option value="{{ $value }}" @selected($filters['geometry'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="geo-directory-field"><label for="directory-status">Record status</label>
                    <select id="directory-status" name="status">
                        @foreach (['active' => 'Active', 'inactive' => 'Archived', 'all' => 'All records'] as $value => $label)
                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="geo-button geo-button-primary" type="submit">Search</button>
            </form>
            @if ($errors->any())
                <p role="alert">{{ $errors->first() }}</p>
            @endif
            <p role="status">{{ $parcels->total() }} matching parcel records</p>
            @if ($parcels->isEmpty())
                <p>No parcels match these filters.</p>
            @else
                <div class="geo-directory-table-wrap">
                    <table class="geo-directory-table">
                        <caption class="sr-only">Parcel search results</caption>
                        <thead><tr><th scope="col">Parcel</th><th scope="col">References</th><th scope="col">Location</th><th scope="col">Parcel Area</th><th scope="col">Linked Landholdings</th><th scope="col">State</th><th scope="col">Actions</th></tr></thead>
                        <tbody>
                            @foreach ($parcels as $parcel)
                                @php
                                    $hasBounds = collect(\App\Services\ParcelMapBounds::COLUMNS)->every(fn ($column) => $parcel->getAttribute($column) !== null);
                                @endphp
                                <tr>
                                    <td data-label="Parcel"><a class="geo-directory-code" href="{{ route('geodetic.parcels.show', $parcel) }}">{{ $parcel->parcel_code }}</a></td>
                                    <td data-label="References"><div>Title: {{ $parcel->title_no ?: 'N/A' }}<small>Tax Dec.: {{ $parcel->tax_decl_no ?: 'N/A' }}</small><small>Survey: {{ $parcel->survey_plan_number ?: 'N/A' }}</small></div></td>
                                    <td data-label="Location"><div>{{ $parcel->municipality ?: 'N/A' }}<small>{{ $parcel->barangay ?: 'N/A' }}</small></div></td>
                                    <td data-label="Parcel Area"><div>{{ $parcel->area_hectares !== null ? number_format((float) $parcel->area_hectares, 4).' ha' : 'N/A' }}</div></td>
                                    <td data-label="Landholdings"><div>
                                        {{ $parcel->landholdings_count }} reference records
                                        @foreach ($parcel->landholdings as $holding)
                                            <small>{{ $holding->landowner?->full_name ?: 'No linked landowner' }} · {{ ucwords(str_replace('_', ' ', $holding->status)) }}</small>
                                        @endforeach
                                        @if ($parcel->landholdings_count > $parcel->landholdings->count())
                                            <small>Additional references are available in parcel details.</small>
                                        @endif
                                    </div></td>
                                    <td data-label="State"><div>{{ $parcel->status === 'inactive' ? 'Archived' : 'Active' }}<small>{{ ! $parcel->has_stored_geometry ? 'No Geometry' : ($hasBounds ? 'Display bounds available' : 'Display bounds unavailable') }}</small></div></td>
                                    <td data-label="Actions"><div><a href="{{ route('geodetic.parcels.show', $parcel) }}">View record</a>
                                        @if ($parcel->status === 'active')
                                            <small><a href="{{ route('geodetic.parcels.geometry.edit', $parcel) }}">{{ $parcel->has_stored_geometry ? 'Review geometry' : 'Add geometry' }}</a></small>
                                        @endif
                                    </div></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $parcels->links() }}
            @endif
        </article>
    </section>
</x-geodetic-shell>
