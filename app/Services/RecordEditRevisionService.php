<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class RecordEditRevisionService
{
    public function assertExpected(Model $lockedRecord, int $expectedRevision): void
    {
        if ((int) $lockedRecord->record_revision !== $expectedRevision) {
            throw ValidationException::withMessages([
                'expected_record_revision' => 'This record changed after the edit form was opened. Reload it and review the latest values before saving.',
            ]);
        }
    }
}
