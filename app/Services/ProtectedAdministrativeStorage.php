<?php

namespace App\Services;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class ProtectedAdministrativeStorage
{
    public const PRIVATE_DISK = 'local';
    public const LEGACY_DISK = 'public';

    public function diskNameForPath(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        foreach ([self::PRIVATE_DISK, self::LEGACY_DISK] as $diskName) {
            if (Storage::disk($diskName)->exists($path)) {
                return $diskName;
            }
        }

        return null;
    }

    public function diskForPath(?string $path): ?FilesystemAdapter
    {
        $diskName = $this->diskNameForPath($path);

        return $diskName ? Storage::disk($diskName) : null;
    }

    public function exists(?string $path): bool
    {
        return $this->diskNameForPath($path) !== null;
    }

    public function mimeType(?string $path): ?string
    {
        $disk = $this->diskForPath($path);

        return $disk && $path ? ($disk->mimeType($path) ?: null) : null;
    }

    public function delete(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        foreach ([self::PRIVATE_DISK, self::LEGACY_DISK] as $diskName) {
            $disk = Storage::disk($diskName);

            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        }
    }
}
