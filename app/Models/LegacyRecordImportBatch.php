<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegacyRecordImportBatch extends Model
{
    protected $fillable = [
        'record_type',
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
        'summary',
    ];

    protected $casts = [
        'summary' => 'array',
        'committed_at' => 'datetime',
    ];

    public function records()
    {
        return $this->hasMany(LegacyRecord::class);
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