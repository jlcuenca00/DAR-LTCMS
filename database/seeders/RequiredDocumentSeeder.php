<?php

namespace Database\Seeders;

use App\Models\RequiredDocument;
use Illuminate\Database\Seeder;

class RequiredDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $mandatory = RequiredDocument::CLASSIFICATION_MANDATORY;
        $caseDependent = RequiredDocument::CLASSIFICATION_CASE_DEPENDENT;
        $validityMonths = (int) config('dar_ltc.document_validity_months', 6);

        // Remove earlier draft/reference rows that do not match the current
        // Citizen's Charter checklist or that have been replaced by clearer names.
        RequiredDocument::query()
            ->whereIn('name', [
                'Deed Certificate (if applicable)',
                'Official Receipt (LTC Fee Payment)',
                'Electronic Copy of Title',
                'Recent Tax Declaration (if available)',
                'Affidavit of Transferee',
                'Death Certificate (if applicable)',
            ])
            ->delete();

        $docs = [
            // APPLICATION / TRANSFEROR SIDE
            [
                'name' => 'Notarized Application (LTC Form No. 1)',
                'applies_to' => 'transferor',
                'is_mandatory' => true,
                'requirement_classification' => $mandatory,
                'blocks_acceptance' => true,
                'condition_key' => null,
                'max_age_months' => null,
                'legal_basis' => 'DAR A.O. No. 4, s. 2021',
                'classification_notes' => 'Notarized LTC Form No. 1 received as part of the application intake folder.',
            ],
            [
                'name' => 'Electronic Copy of Original OCT/TCT from Register of Deeds',
                'applies_to' => 'transferor',
                'is_mandatory' => false,
                'requirement_classification' => $caseDependent,
                'blocks_acceptance' => true,
                'condition_key' => RequiredDocument::CONDITION_TITLED_LAND,
                'max_age_months' => $validityMonths,
                'legal_basis' => 'DAR A.O. No. 4, s. 2021',
                'classification_notes' => 'Required for titled land. Date issued must be within six months of the application date.',
            ],
            [
                'name' => 'Certified True Copy of Current Tax Declaration (Untitled Land)',
                'applies_to' => 'transferor',
                'is_mandatory' => false,
                'requirement_classification' => $caseDependent,
                'blocks_acceptance' => true,
                'condition_key' => RequiredDocument::CONDITION_UNTITLED_LAND,
                'max_age_months' => null,
                'legal_basis' => 'DAR A.O. No. 4, s. 2021',
                'classification_notes' => 'Required when any subject parcel is untitled.',
            ],
            [
                'name' => 'Original Notarized Deed or Document to be Registered',
                'applies_to' => 'transferor',
                'is_mandatory' => true,
                'requirement_classification' => $mandatory,
                'blocks_acceptance' => true,
                'condition_key' => null,
                'max_age_months' => null,
                'legal_basis' => 'DAR A.O. No. 4, s. 2021',
                'classification_notes' => 'Original notarized conveyance/transfer instrument presented for review and registration reference.',
            ],
            [
                'name' => 'Affidavit of Transferor',
                'applies_to' => 'transferor',
                'is_mandatory' => true,
                'requirement_classification' => $mandatory,
                'blocks_acceptance' => true,
                'condition_key' => null,
                'max_age_months' => null,
                'legal_basis' => 'DAR A.O. No. 4, s. 2021',
                'classification_notes' => 'Sworn statement on retention area, tenancy/pre-emption compliance, and pending/conflicting claims as applicable.',
            ],
            [
                'name' => "Municipal Assessor's Certificate of Aggregate Landholding",
                'applies_to' => 'transferor',
                'is_mandatory' => false,
                'requirement_classification' => $caseDependent,
                'blocks_acceptance' => true,
                'condition_key' => RequiredDocument::CONDITION_MUNICIPAL_JURISDICTION,
                'max_age_months' => $validityMonths,
                'legal_basis' => 'DAR A.O. No. 4, s. 2021',
                'classification_notes' => 'Required when the subject jurisdiction is a municipality; includes spouse where applicable.',
            ],
            [
                'name' => "City Assessor's Certificate of Aggregate Landholding",
                'applies_to' => 'transferor',
                'is_mandatory' => false,
                'requirement_classification' => $caseDependent,
                'blocks_acceptance' => true,
                'condition_key' => RequiredDocument::CONDITION_CITY_JURISDICTION,
                'max_age_months' => $validityMonths,
                'legal_basis' => 'DAR A.O. No. 4, s. 2021',
                'classification_notes' => 'Required when the subject jurisdiction is a city; includes spouse where applicable.',
            ],
            [
                'name' => "Provincial Assessor's Certificate of Aggregate Landholding",
                'applies_to' => 'transferor',
                'is_mandatory' => true,
                'requirement_classification' => $mandatory,
                'blocks_acceptance' => true,
                'condition_key' => null,
                'max_age_months' => $validityMonths,
                'legal_basis' => 'DAR A.O. No. 4, s. 2021',
                'classification_notes' => 'Aggregate landholding certification for the transferor and spouse, if married.',
            ],
            [
                'name' => "Secretary's Certificate or Board Resolution",
                'applies_to' => 'transferor',
                'is_mandatory' => false,
                'requirement_classification' => $caseDependent,
                'blocks_acceptance' => true,
                'condition_key' => RequiredDocument::CONDITION_JURIDICAL_ENTITY,
                'max_age_months' => null,
                'legal_basis' => 'DAR A.O. No. 4, s. 2021',
                'classification_notes' => 'Required when the applicant is a juridical entity.',
            ],
            [
                'name' => 'Special Power of Attorney',
                'applies_to' => 'transferor',
                'is_mandatory' => false,
                'requirement_classification' => $caseDependent,
                'blocks_acceptance' => true,
                'condition_key' => RequiredDocument::CONDITION_AUTHORIZED_REPRESENTATIVE,
                'max_age_months' => null,
                'legal_basis' => 'DAR A.O. No. 4, s. 2021',
                'classification_notes' => 'Required when the application is filed through an authorized representative.',
            ],

            // TRANSFEREE SIDE
            [
                'name' => 'Affidavit of Aggregate Landholding of Transferee and Spouse',
                'applies_to' => 'transferee',
                'is_mandatory' => true,
                'requirement_classification' => $mandatory,
                'blocks_acceptance' => true,
                'condition_key' => null,
                'max_age_months' => null,
                'legal_basis' => 'DAR A.O. No. 4, s. 2021',
                'classification_notes' => 'Transferee affidavit, including spouse if married, attesting compliance with the landownership ceiling.',
            ],
            [
                'name' => 'MARPO Certification (LTC Form No. 2)',
                'applies_to' => 'transferee',
                'is_mandatory' => true,
                'requirement_classification' => $mandatory,
                'blocks_acceptance' => true,
                'condition_key' => null,
                'max_age_months' => null,
                'legal_basis' => 'DAR A.O. No. 4, s. 2021',
                'classification_notes' => 'MARPO/DARMO certification for tenancy, use/conversion, and conflict-of-claims verification.',
            ],
            [
                'name' => "Municipal Assessor's Certificate of Aggregate Landholding",
                'applies_to' => 'transferee',
                'is_mandatory' => false,
                'requirement_classification' => $caseDependent,
                'blocks_acceptance' => true,
                'condition_key' => RequiredDocument::CONDITION_MUNICIPAL_JURISDICTION,
                'max_age_months' => $validityMonths,
                'legal_basis' => 'DAR A.O. No. 4, s. 2021',
                'classification_notes' => 'Required when the subject jurisdiction is a municipality; includes spouse where applicable.',
            ],
            [
                'name' => "City Assessor's Certificate of Aggregate Landholding",
                'applies_to' => 'transferee',
                'is_mandatory' => false,
                'requirement_classification' => $caseDependent,
                'blocks_acceptance' => true,
                'condition_key' => RequiredDocument::CONDITION_CITY_JURISDICTION,
                'max_age_months' => $validityMonths,
                'legal_basis' => 'DAR A.O. No. 4, s. 2021',
                'classification_notes' => 'Required when the subject jurisdiction is a city; includes spouse where applicable.',
            ],
            [
                'name' => "Provincial Assessor's Certificate of Aggregate Landholding",
                'applies_to' => 'transferee',
                'is_mandatory' => true,
                'requirement_classification' => $mandatory,
                'blocks_acceptance' => true,
                'condition_key' => null,
                'max_age_months' => $validityMonths,
                'legal_basis' => 'DAR A.O. No. 4, s. 2021',
                'classification_notes' => 'Aggregate landholding certification for the transferee and spouse, if married.',
            ],
        ];

        foreach ($docs as $doc) {
            RequiredDocument::updateOrCreate(
                ['name' => $doc['name'], 'applies_to' => $doc['applies_to']],
                $doc
            );
        }
    }
}
