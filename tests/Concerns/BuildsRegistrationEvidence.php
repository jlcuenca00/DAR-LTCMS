<?php

namespace Tests\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait BuildsRegistrationEvidence
{
    protected function evidenceFiles(): array
    {
        Storage::fake('local');

        return [
            'id_type' => 'passport',
            'id_front' => UploadedFile::fake()->image('id.jpg', 640, 400),
            'selfie' => UploadedFile::fake()->image('selfie.jpg', 400, 400),
            'identity_consent' => '1',
        ];
    }
}
