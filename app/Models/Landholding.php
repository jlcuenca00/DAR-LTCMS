<?php

namespace App\Models;

use App\Models\Concerns\TracksRecordRevision;

use App\Services\ParcelAreaIntegrityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class Landholding extends Model
{
    use TracksRecordRevision;
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_HISTORICAL = 'historical';
    public const STATUS_TRANSFERRED = 'transferred';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
        self::STATUS_HISTORICAL,
        self::STATUS_TRANSFERRED,
    ];

    protected $fillable = [
        'landowner_id',
        'parcel_id',
        'area_hectares',
        'status',
        'date_acquired',
        'date_transferred',
        'source_application_id',
        'source_record_package_id',
        'source_reference_number',
        'remarks',
        'reference_photo_path',
    ];

    protected $casts = [
        'record_revision' => 'integer',
        'area_hectares' => 'decimal:4',
        'date_acquired' => 'date',
        'date_transferred' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (Landholding $landholding) {
            $landholding->assertSourceProvenance();
            app(ParcelAreaIntegrityService::class)->assertLandholdingCapacity($landholding);
        });
    }

    private function assertSourceProvenance(): void
    {
        if ($this->exists && $this->getRawOriginal('source_record_package_id') !== null
            && $this->isDirty('source_record_package_id')) {
            throw ValidationException::withMessages([
                'source_record_package_id' => 'The verified source package cannot be removed or replaced.',
            ]);
        }

        if (! $this->source_record_package_id) {
            return;
        }

        $package = SourceRecordPackage::query()->findOrFail($this->source_record_package_id);
        if ((int) $this->parcel_id !== (int) $package->parcel_id
            || (int) $this->landowner_id !== (int) $package->landowner_id) {
            throw ValidationException::withMessages([
                'parcel_id' => 'A source-linked Landholding must retain its verified Landowner and Parcel. Review the source package before correcting these links.',
            ]);
        }

        if ($this->status === self::STATUS_ACTIVE
            && $package->source_record_scope !== LegacyRecord::SOURCE_SCOPE_CURRENT_ACTIVE) {
            throw ValidationException::withMessages([
                'status' => 'Historical or reference-only source evidence cannot establish an active Landholding.',
            ]);
        }
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function landowner()
    {
        return $this->belongsTo(Landowner::class);
    }

    public function parcel()
    {
        return $this->belongsTo(Parcel::class);
    }

    public function sourceRecordPackage()
    {
        return $this->belongsTo(SourceRecordPackage::class);
    }

    public function sourceApplication()
    {
        return $this->belongsTo(LandTransferApplication::class, 'source_application_id');
    }
}
