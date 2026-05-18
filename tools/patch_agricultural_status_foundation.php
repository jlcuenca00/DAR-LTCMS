<?php

$root = dirname(__DIR__);
$parcelModel = $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Models' . DIRECTORY_SEPARATOR . 'Parcel.php';

if (! file_exists($parcelModel)) {
    fwrite(STDERR, "ERROR: app/Models/Parcel.php not found. Run this from the extracted project root.\n");
    exit(1);
}

$contents = file_get_contents($parcelModel);
$original = $contents;

// Add fillable field, keeping the existing form of the model intact.
if (! str_contains($contents, "'agricultural_status'")) {
    if (str_contains($contents, "'status',")) {
        $contents = str_replace("'status',", "'status',\n        'agricultural_status',", $contents);
    } elseif (str_contains($contents, "'remarks',")) {
        $contents = str_replace("'remarks',", "'agricultural_status',\n        'remarks',", $contents);
    } else {
        fwrite(STDERR, "ERROR: Could not safely locate the Parcel fillable list. Add 'agricultural_status' manually.\n");
        exit(1);
    }
}

$insert = <<<'PHP_CODE'
    public const AGRICULTURAL_STATUSES = [
        'private_agricultural' => 'Private Agricultural Land',
        'awarded_cloa' => 'Awarded CLOA Land',
        'emancipation_patent' => 'Emancipation Patent Land',
        'carp_covered' => 'CARP-Covered Land',
        'not_yet_determined' => 'Not Yet Determined',
        'non_agricultural' => 'Non-Agricultural / Reference Only',
    ];

    public static function agriculturalStatusOptions(): array
    {
        return self::AGRICULTURAL_STATUSES;
    }

    public static function agriculturalStatusLabel(?string $status): string
    {
        return self::AGRICULTURAL_STATUSES[$status ?: 'not_yet_determined'] ?? self::AGRICULTURAL_STATUSES['not_yet_determined'];
    }

    public function getAgriculturalStatusLabelAttribute(): string
    {
        return self::agriculturalStatusLabel($this->agricultural_status);
    }

PHP_CODE;

if (! str_contains($contents, 'AGRICULTURAL_STATUSES')) {
    $contents = preg_replace('/class\s+Parcel\s+extends\s+Model\s*\{\s*/', "$0" . $insert, $contents, 1, $count);

    if ($count !== 1) {
        fwrite(STDERR, "ERROR: Could not safely insert agricultural status helpers in Parcel model.\n");
        exit(1);
    }
}

if ($contents !== $original) {
    file_put_contents($parcelModel, $contents);
    echo "Updated app/Models/Parcel.php\n";
} else {
    echo "app/Models/Parcel.php already contains agricultural status helpers.\n";
}

echo "Foundation patch applied. Next run: php artisan migrate && php artisan test\n";
