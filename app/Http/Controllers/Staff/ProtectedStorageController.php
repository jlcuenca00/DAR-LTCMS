<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Landholding;
use App\Models\Parcel;
use App\Models\SourceRecordPackage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProtectedStorageController extends Controller
{
    public function __invoke(string $path): StreamedResponse
    {
        abort_if($path === '' || Str::contains($path, ['..', '\\', "\0"]), 404);

        $metadata = $this->resolveRegisteredFile($path);
        abort_unless($metadata !== null, 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);

        $filename = $metadata['filename'] ?: basename($path);
        $mimeType = $metadata['mime_type']
            ?: $disk->mimeType($path)
            ?: 'application/octet-stream';

        return $disk->response(
            $path,
            $filename,
            [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'no-store, no-cache, must-revalidate, private, max-age=0',
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
                'X-Robots-Tag' => 'noindex, nofollow, noarchive',
                'Cross-Origin-Resource-Policy' => 'same-origin',
            ],
            'inline'
        );
    }

    /**
     * Resolve only files that are explicitly registered to administrative records.
     * The route is Staff-only, and this database check prevents the storage folder
     * from becoming a generic file browser even though legacy files remain on the
     * historical public disk for deployment compatibility.
     *
     * @return array{filename: string, mime_type: ?string}|null
     */
    private function resolveRegisteredFile(string $path): ?array
    {
        if (Str::startsWith($path, 'source-record-packages/')) {
            $package = SourceRecordPackage::query()
                ->select(['source_file_original_filename', 'source_file_mime_type'])
                ->where('source_file_path', $path)
                ->first();

            if (! $package) {
                return null;
            }

            return [
                'filename' => $package->source_file_original_filename ?: basename($path),
                'mime_type' => $package->source_file_mime_type,
            ];
        }

        if (Str::startsWith($path, 'reference-photos/landholdings/')) {
            $registered = Landholding::query()
                ->where('reference_photo_path', $path)
                ->exists();

            return $registered
                ? ['filename' => basename($path), 'mime_type' => null]
                : null;
        }

        if (Str::startsWith($path, 'reference-photos/parcels/')) {
            $registered = Parcel::query()
                ->where('reference_photo_path', $path)
                ->exists();

            return $registered
                ? ['filename' => basename($path), 'mime_type' => null]
                : null;
        }

        return null;
    }
}
