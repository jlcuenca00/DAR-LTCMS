<option value="">No linked landowner record</option>
@foreach ($landowners as $landowner)
    <option value="{{ $landowner->id }}" data-name="{{ $landowner->full_name }}">
        {{ $landowner->full_name }} — {{ $landowner->municipality ?? 'No municipality' }}
    </option>
@endforeach
