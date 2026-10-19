<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class RegistrationEvidence
{
    public const KINDS = ['id_front', 'id_back', 'selfie'];

    public static function idTypes(): array
    {
        return [
            'national_card' => 'National ID — card',
            'national_paper' => 'National ID — printed ePhilID',
            'national_digital' => 'National ID — digital',
            'passport' => 'Philippine passport',
            'drivers_license' => 'Driver’s license',
            'prc' => 'PRC ID',
            'umid' => 'UMID',
            'sss' => 'SSS ID',
            'postal' => 'Philippine Postal ID',
            'pagibig' => 'Pag-IBIG Loyalty Card Plus',
        ];
    }

    public static function rules(): array
    {
        $image = ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=160,min_height=160,max_width=6000,max_height=6000'];

        return [
            'id_type' => ['required', Rule::in(array_keys(self::idTypes()))],
            'id_front' => ['required', ...$image],
            'id_back' => ['nullable', ...$image],
            'selfie' => ['required', ...$image],
            'identity_consent' => ['accepted'],
        ];
    }

    public function store(Request $request): array
    {
        $evidence = ['id_type' => $request->input('id_type'), 'submitted_at' => now()->toIso8601String()];
        try {
            foreach (self::KINDS as $kind) {
                if ($request->hasFile($kind)) {
                    $path = $request->file($kind)->store('identity-evidence', 'local');
                    if (! is_string($path) || $path === '') {
                        throw new RuntimeException('Unable to save registration evidence.');
                    }
                    $evidence[$kind] = $path;
                }
            }
        } catch (Throwable $exception) {
            $this->delete($evidence);
            throw $exception;
        }

        return $evidence;
    }

    public static function isEvidencePath(mixed $path): bool
    {
        return is_string($path) && preg_match('/^identity-evidence\/[a-zA-Z0-9]{40}\.(jpg|jpeg|png|webp)$/', $path) === 1;
    }

    public function delete(?array $evidence): void
    {
        foreach (self::KINDS as $kind) {
            $path = $evidence[$kind] ?? null;
            if (self::isEvidencePath($path)) {
                if (! Storage::disk('local')->delete($path)) {
                    report(new RuntimeException('Registration evidence cleanup failed.'));
                }
            }
        }
    }
}
