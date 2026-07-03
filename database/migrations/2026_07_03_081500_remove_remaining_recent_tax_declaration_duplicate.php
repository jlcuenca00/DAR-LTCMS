<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Remove the remaining old Recent Tax Declaration requirement if a database
     * already ran the earlier UI patch or still has imported duplicate rows.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            $now = now();

            $keepId = DB::table('required_documents')
                ->where('applies_to', 'transferor')
                ->where('name', 'Recent Tax Declaration (if available)')
                ->value('id');

            if (! $keepId) {
                $keepId = DB::table('required_documents')->insertGetId([
                    'name' => 'Recent Tax Declaration (if available)',
                    'applies_to' => 'transferor',
                    'is_mandatory' => false,
                    'legal_basis' => 'DAR A.O. No. 4, s. 2021',
                    'requirement_classification' => 'reference',
                    'blocks_acceptance' => false,
                    'classification_notes' => 'Reference document when available. It may support assessor classification and tax declaration number encoding but is not a release blocker by itself.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $duplicateIds = DB::table('required_documents')
                ->where('applies_to', 'transferor')
                ->where('name', 'Recent Tax Declaration')
                ->pluck('id');

            foreach ($duplicateIds as $duplicateId) {
                $documents = DB::table('application_documents')
                    ->where('required_document_id', $duplicateId)
                    ->get(['id', 'land_transfer_application_id']);

                foreach ($documents as $document) {
                    $alreadyHasKeptRequirement = DB::table('application_documents')
                        ->where('land_transfer_application_id', $document->land_transfer_application_id)
                        ->where('required_document_id', $keepId)
                        ->exists();

                    if ($alreadyHasKeptRequirement) {
                        DB::table('application_documents')
                            ->where('id', $document->id)
                            ->delete();
                    } else {
                        DB::table('application_documents')
                            ->where('id', $document->id)
                            ->update([
                                'required_document_id' => $keepId,
                                'updated_at' => $now,
                            ]);
                    }
                }

                DB::table('required_documents')
                    ->where('id', $duplicateId)
                    ->delete();
            }
        });
    }

    public function down(): void
    {
        // Intentionally empty. Restoring this row would reintroduce the duplicated UI field.
    }
};
