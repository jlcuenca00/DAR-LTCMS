<?php

namespace App\Models\Concerns;

use App\Services\DarLocationService;

trait NormalizesDarLocation
{
    protected static function bootNormalizesDarLocation(): void
    {
        static::saving(function ($model) {
            if ($model->exists && ! $model->isDirty(['municipality', 'barangay', 'province'])) {
                return;
            }

            $normalized = app(DarLocationService::class)->normalize(
                $model->getAttribute('municipality'),
                $model->getAttribute('barangay'),
                $model->getAttribute('province')
            );

            foreach ($normalized as $field => $value) {
                if ($field === 'province' && $value === null && ! array_key_exists('province', $model->getAttributes())) {
                    continue;
                }

                $model->setAttribute($field, $value);
            }
        });
    }
}
