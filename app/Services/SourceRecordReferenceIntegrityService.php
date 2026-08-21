<?php

namespace App\Services;

use App\Models\LegacyRecord;
use Illuminate\Validation\ValidationException;

class SourceRecordReferenceIntegrityService
{
    public function assertUniqueRecord(LegacyRecord $record): void
    {
        [$field, $label] = $this->referenceFieldForType($record->record_type);
        if (! $field) {
            return;
        }

        $value = trim((string) $record->{$field});
        if ($value === '') {
            return;
        }

        $exists = LegacyRecord::query()
            ->where('record_type', $record->record_type)
            ->whereRaw('lower('.$field.') = ?', [mb_strtolower($value)])
            ->when($record->exists, fn ($query) => $query->whereKeyNot($record->getKey()))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                $field => 'Duplicate '.$label.' already exists in source records.',
            ]);
        }
    }

    public function hardenPreviewRows(array $rows): array
    {
        $groups = [];

        foreach ($rows as $index => $row) {
            foreach ($this->referenceKeysForData((array) ($row['data'] ?? [])) as $key => $label) {
                $groups[$key]['label'] = $label;
                $groups[$key]['indexes'][] = $index;
            }
        }

        foreach ($groups as $group) {
            $indexes = array_values(array_unique($group['indexes'] ?? []));
            if (count($indexes) < 2) {
                continue;
            }

            foreach ($indexes as $index) {
                $message = 'Duplicate '.$group['label'].' appears more than once in this import file.';
                $rows[$index]['possible_duplicate'] = true;
                $rows[$index]['status'] = 'error';
                $rows[$index]['errors'] = array_values(array_unique(array_merge(
                    (array) ($rows[$index]['errors'] ?? []),
                    [$message]
                )));
            }
        }

        return array_values($rows);
    }

    public function referenceKeysForData(array $data): array
    {
        $keys = [];

        $this->appendKey($keys, (bool) ($data['include_title'] ?? false), 'title', $data['title_number'] ?? null, 'title_number');
        $this->appendKey($keys, (bool) ($data['include_landholding'] ?? false), 'landholding', $data['landholding_reference_number'] ?? null, 'landholding_reference_number');
        $this->appendKey($keys, (bool) ($data['include_parcel_source'] ?? false), 'parcel_source', $data['parcel_code'] ?? null, 'parcel_code');
        $this->appendKey($keys, (bool) ($data['include_historical_clearance'] ?? false), 'historical_clearance', $data['control_number'] ?? null, 'control_number');

        return $keys;
    }

    private function appendKey(array &$keys, bool $included, string $type, $value, string $label): void
    {
        $value = trim((string) $value);
        if (! $included || $value === '') {
            return;
        }

        $keys[$type.':'.mb_strtolower($value)] = $label;
    }

    private function referenceFieldForType(?string $type): array
    {
        return match ($type) {
            LegacyRecord::TYPE_TITLE => ['title_number', 'title_number'],
            LegacyRecord::TYPE_LANDHOLDING => ['landholding_reference_number', 'landholding_reference_number'],
            LegacyRecord::TYPE_PARCEL_SOURCE => ['parcel_code', 'parcel_code'],
            LegacyRecord::TYPE_HISTORICAL_CLEARANCE => ['control_number', 'control_number'],
            default => [null, null],
        };
    }
}
