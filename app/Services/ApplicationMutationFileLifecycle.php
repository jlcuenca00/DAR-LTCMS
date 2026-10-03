<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ApplicationMutationFileLifecycle
{
    public const REQUEST_ATTRIBUTE = 'dar_ltcms_application_mutation_file_lifecycle';

    /** @var array<int, string> */
    private array $createdPaths = [];

    /** @var array<int, string> */
    private array $deleteAfterCommitPaths = [];

    public function __construct(private bool $protectedStorage = false)
    {
    }

    public static function fromRequest(Request $request): ?self
    {
        $lifecycle = $request->attributes->get(self::REQUEST_ATTRIBUTE);

        return $lifecycle instanceof self ? $lifecycle : null;
    }

    public function trackCreated(string $path): void
    {
        if ($path !== '') {
            $this->createdPaths[] = $path;
        }
    }

    public function deleteAfterCommit(?string $path): void
    {
        if (is_string($path) && $path !== '') {
            $this->deleteAfterCommitPaths[] = $path;
        }
    }

    public function commit(): void
    {
        foreach (array_values(array_unique($this->deleteAfterCommitPaths)) as $path) {
            $this->deleteQuietly($path);
        }

        $this->createdPaths = [];
        $this->deleteAfterCommitPaths = [];
    }

    public function rollback(): void
    {
        foreach (array_values(array_unique($this->createdPaths)) as $path) {
            $this->deleteQuietly($path);
        }

        $this->createdPaths = [];
        $this->deleteAfterCommitPaths = [];
    }

    private function deleteQuietly(string $path): void
    {
        try {
            if ($this->protectedStorage) {
                app(ProtectedAdministrativeStorage::class)->delete($path);
                return;
            }
            if (Storage::exists($path)) {
                Storage::delete($path);
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
