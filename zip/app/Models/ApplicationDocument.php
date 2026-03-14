<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationDocument extends Model
{
    protected $fillable = [
        'land_transfer_application_id',
        'required_document_id',
        'file_path',
        'original_filename',
        'annex_reference',
        'remarks',
        'uploaded_by',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function application()
    {
        return $this->belongsTo(LandTransferApplication::class, 'land_transfer_application_id');
    }

    public function requiredDocument()
    {
        return $this->belongsTo(RequiredDocument::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}