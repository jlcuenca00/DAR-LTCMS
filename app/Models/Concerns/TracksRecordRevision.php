<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\DB;

trait TracksRecordRevision
{
    protected static function bootTracksRecordRevision(): void
    {
        static::saving(function ($model) {
            if (DB::connection()->getDriverName() === 'pgsql' || ! $model->exists) {
                return;
            }

            $dirty = array_diff_key($model->getDirty(), array_flip(['record_revision', 'updated_at']));
            $model->record_revision = (int) $model->getRawOriginal('record_revision') + (empty($dirty) ? 0 : 1);
        });
    }
}
