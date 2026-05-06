<?php

namespace App\Models;

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

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function committedBy()
    {
        return $this->belongsTo(User::class, 'committed_by_user_id');
    }
}