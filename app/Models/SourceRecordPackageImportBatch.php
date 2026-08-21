<?php

namespace App\Models;

use App\Services\SourceRecordReferenceIntegrityService;
use Illuminate\Database\Eloquent\Model;

class SourceRecordPackageImportBatch extends Model
{
    protected $fillable = [
        'original_filename',
        'status',
        'total_rows',
        'valid_rows',
        'error_rows',
        'duplicate_rows',
        'committed_rows',
        'uploaded_by_user_id',
        'committed_by_user_id',
        'committed_at',
        'preview_rows',
        'summary',
    ];

    protected $casts = [
        'preview_rows' => 'array',
        'summary' => 'array',
        'committed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (SourceRecordPackageImportBatch $batch) {
            if ($batch->status !== 'previewed' || ! $batch->isDirty('preview_rows')) {
                return;
            }

            $rows = app(SourceRecordReferenceIntegrityService::class)
                ->hardenPreviewRows((array) $batch->preview_rows);

            $validRows = collect($rows)->where('status', 'valid')->count();
            $errorRows = collect($rows)->where('status', 'error')->count();
            $duplicateRows = collect($rows)->where('possible_duplicate', true)->count();

            $batch->preview_rows = $rows;
            $batch->total_rows = count($rows);
            $batch->valid_rows = $validRows;
            $batch->error_rows = $errorRows;
            $batch->duplicate_rows = $duplicateRows;
            $batch->summary = [
                'valid_rows' => $validRows,
                'error_rows' => $errorRows,
                'duplicate_rows' => $duplicateRows,
            ];
        });
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function committedBy()
    {
        return $this->belongsTo(User::class, 'committed_by_user_id');
    }
}
