<?php

namespace App\Models;

use App\Services\ApplicationWorkflowRevisionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ApplicationDocument extends Model
{
    use \App\Models\Concerns\RequiresModelEvents;

    protected $fillable = [
        'land_transfer_application_id',
        'required_document_id',
        'file_path',
        'original_filename',
        'annex_reference',
        'remarks',
        'uploaded_by',
        'source_record_id',
        'source_record_package_id',

        'document_reference_number',
        'document_metadata',
        'metadata_encoded_by',
        'metadata_encoded_at',
    ];

    protected $casts = [
        'document_metadata' => 'array',
        'metadata_encoded_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (ApplicationDocument $document) {
            if ($document->exists && $document->isDirty('land_transfer_application_id')) {
                throw ValidationException::withMessages([
                    'application' => 'Persisted application child records cannot be reassigned to another application.',
                ]);
            }

            $application = $document->land_transfer_application_id
                ? LandTransferApplication::query()->find($document->land_transfer_application_id)
                : null;

            if ($application && $application->isFinalized()) {
                throw ValidationException::withMessages([
                    'document' => 'Supporting-document records are immutable after the application is finalized.',
                ]);
            }
        });

        static::deleting(function (ApplicationDocument $document) {
            $application = $document->application;

            if ($application && $application->isFinalized()) {
                throw ValidationException::withMessages([
                    'document' => 'Supporting-document records are immutable after the application is finalized.',
                ]);
            }
        });

        static::saved(function (ApplicationDocument $document) {
            if ($document->land_transfer_application_id) {
                app(ApplicationWorkflowRevisionService::class)
                    ->bump((int) $document->land_transfer_application_id);
            }
        });

        static::deleted(function (ApplicationDocument $document) {
            if ($document->land_transfer_application_id) {
                app(ApplicationWorkflowRevisionService::class)
                    ->bump((int) $document->land_transfer_application_id);
            }
        });
    }


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

    public function metadataEncoder()
    {
        return $this->belongsTo(User::class, 'metadata_encoded_by');
    }

    public function sourceRecord()
    {
        return $this->belongsTo(LegacyRecord::class, 'source_record_id');
    }

    public function sourceRecordPackage()
    {
        return $this->belongsTo(SourceRecordPackage::class, 'source_record_package_id');
    }
}
