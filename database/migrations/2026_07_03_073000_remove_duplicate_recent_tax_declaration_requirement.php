<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Keep only the current tax declaration requirement label and safely move
     * any document rows from the earlier duplicate requirement before removal.
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
                $applicationIds = DB::table('application_documents')
                    ->where('required_document_id', $duplicateId)
                    ->pluck('land_transfer_application_id');

                foreach ($applicationIds as $applicationId) {
                    $alreadyHasKeptRequirement = DB::table('application_documents')
                        ->where('land_transfer_application_id', $applicationId)
                        ->where('required_document_id', $keepId)
                        ->exists();

                    if ($alreadyHasKeptRequirement) {
                        DB::table('application_documents')
                            ->where('land_transfer_application_id', $applicationId)
                            ->where('required_document_id', $duplicateId)
                            ->delete();
                    } else {
                        DB::table('application_documents')
                            ->where('land_transfer_application_id', $applicationId)
                            ->where('required_document_id', $duplicateId)
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
        // No rollback needed. Recreating the duplicate requirement would reintroduce the UI duplicate.
    }
};
