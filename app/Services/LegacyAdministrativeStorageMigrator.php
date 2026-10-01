<?php

namespace App\Services;

use App\Models\ApplicationDocument;
use App\Models\Landholding;
use App\Models\Parcel;
use App\Models\SourceRecordPackage;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class LegacyAdministrativeStorageMigrator
{
    public function migrate(): array
    {
        $private = Storage::disk(ProtectedAdministrativeStorage::PRIVATE_DISK);
        $legacy = Storage::disk(ProtectedAdministrativeStorage::LEGACY_DISK);

        $result = [
            'registered_paths' => 0,
            'migrated' => 0,
            'already_private' => 0,
            'missing' => 0,
            'conflicts' => 0,
        ];

        foreach ($this->registeredPaths() as $path) {
            $result['registered_paths']++;

            $legacyExists = $legacy->exists($path);
            $privateExists = $private->exists($path);

            if (! $legacyExists) {
                $privateExists ? $result['already_private']++ : $result['missing']++;

                continue;
            }

            if ($privateExists) {
                if (! $this->sameContents($private, $legacy, $path)) {
                    $result['conflicts']++;

                    continue;
                }

                $legacy->delete($path);
                $result['already_private']++;

                continue;
            }

            $stream = $legacy->readStream($path);

            if ($stream === false) {
                throw new RuntimeException("Could not read legacy administrative file: {$path}");
            }

            try {
                $private->put($path, $stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            if (! $private->exists($path) || ! $this->sameContents($private, $legacy, $path)) {
                $private->delete($path);

                throw new RuntimeException("Private copy verification failed for legacy administrative file: {$path}");
            }

            $legacy->delete($path);
            $result['migrated']++;
        }

        return $result;
    }

    private function registeredPaths(): Collection
    {
        return collect()
            ->merge(SourceRecordPackage::query()->whereNotNull('source_file_path')->pluck('source_file_path'))
            ->merge(Parcel::query()->whereNotNull('reference_photo_path')->pluck('reference_photo_path'))
            ->merge(Landholding::query()->whereNotNull('reference_photo_path')->pluck('reference_photo_path'))
            ->merge(User::query()->whereNotNull('profile_photo_path')->pluck('profile_photo_path'))
            ->merge(ApplicationDocument::query()->whereNotNull('file_path')->pluck('file_path'))
            ->filter(fn ($path) => is_string($path) && trim($path) !== '')
            ->map(fn ($path) => ltrim(trim($path), '/'))
            ->unique()
            ->values();
    }

    private function sameContents(
        FilesystemAdapter $private,
        FilesystemAdapter $legacy,
        string $path
    ): bool {
        if ($private->size($path) !== $legacy->size($path)) {
            return false;
        }

        return hash_file('sha256', $private->path($path))
            === hash_file('sha256', $legacy->path($path));
    }
}
