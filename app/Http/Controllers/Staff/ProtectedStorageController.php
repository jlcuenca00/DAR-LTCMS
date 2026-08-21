<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\SourceRecordPackage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProtectedStorageController extends Controller
{
    public function __invoke(string $path): StreamedResponse
    {
        abort_unless(Str::startsWith($path, 'source-record-packages/'), 404);

        $package = SourceRecordPackage::query()
            ->where('source_file_path', $path)
            ->firstOrFail();

        $disk = Storage::disk('public');

        abort_unless($disk->exists($path), 404);

        $filename = $package->source_file_original_filename ?: basename($path);
        $mimeType = $package->source_file_mime_type
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
}
