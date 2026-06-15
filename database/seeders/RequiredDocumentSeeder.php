<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RequiredDocument;

class RequiredDocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mandatory = RequiredDocument::CLASSIFICATION_MANDATORY;
        $caseDependent = RequiredDocument::CLASSIFICATION_CASE_DEPENDENT;
        $reference = RequiredDocument::CLASSIFICATION_REFERENCE;

        // Clean up earlier draft/typo entries before aligning with LTC Form No. 1.
        RequiredDocument::where('name', 'Deed Certificate (if applicable)')
            ->where('applies_to', 'transferor')
            ->delete();

        $docs = [
            // TRANSFEROR / APPLICATION INTAKE
            [
                'name' => 'Official Receipt (LTC Fee Payment)',
                'applies_to' => 'transferor',
                'is_mandatory' => true,
                'requirement_classification' => $mandatory,
                'blocks_acceptance' => true,
                'legal_basis' => 'DAR A.O. No. 4, s. 2021',
                'classification_notes' => 'Payment/reference intake document for accepted application records.',
            ],
            [
                'name' => 'Electronic Copy of Title',
                'applies_to' => 'transferor',
                'is_mandatory' => true,
                'requirement_classification' => $mandatory,
                'blocks_acceptance' => true,
                'legal_basis' => 'DAR A.O. No. 4, s. 2021',
                'classification_notes' => 'Title proof required for parcel/title review. Certified True Copy details may be encoded in document metadata.',
            ],
            [
                'name' => 'Recent Tax Declaration (if available)',
                'applies_to' => 'transferor',
                'is_mandatory' => false,
                'requirement_classification' => $reference,
                'blocks_acceptance' => false,
                'legal_basis' => 'DAR A.O. No. 4, s. 2021',
                'classification_notes' => 'Reference document when available. It may support assessor classification and tax declaration number encoding but is not a release blocker by itself.',
            ],
            [
                'name' => 'Deed or Document to be Registered',
                'applies_to' => 'transferor',
                'is_mandatory' => true,
                'requirement_classification' => $mandatory,
                'blocks_acceptance' => true,
                'legal_basis' => 'DAR A.O. No. 4, s. 2021',
                'classification_notes' => 'Transfer instrument required for review of transfer parties and registration details. Metadata may include notarization date, notary public, page, book, document number, and series.',
            ],
            [
                'name' => 'Death Certificate (if applicable)',
                'applies_to' => 'transferor',
                'is_mandatory' => false,
                'requirement_classification' => $caseDependent,
                'blocks_acceptance' => false,
                'legal_basis' => 'DAR A.O. No. 4, s. 2021',
                'classification_notes' => 'Required only when deceased persons are indicated in the transfer instrument.',
            ],
            [
                'name' => 'Affidavit of Transferor',
                'applies_to' => 'transferor',
                'is_mandatory' => true,
                'requirement_classification' => $mandatory,
                'blocks_acceptance' => true,
                'legal_basis' => 'DAR A.O. No. 4, s. 2021',
                'classification_notes' => 'Required sworn statement used for transferor review.',
            ],
            [
                'name' => "Municipal Assessor's Certificate of Aggregate Landholding",
                'applies_to' => 'transferor',
                'is_mandatory' => true,
                'requirement_classification' => $mandatory,
                'blocks_acceptance' => true,
                'legal_basis' => 'DAR A.O. No. 4, s. 2021',
                'classification_notes' => 'Aggregate landholding certification used for 5-hectare review.',
            ],
            [
                'name' => "City Assessor's Certificate of Aggregate Landholding",
                'applies_to' => 'transferor',
                'is_mandatory' => false,
                'requirement_classification' => $caseDependent,
                'blocks_acceptance' => false,
                'legal_basis' => 'DAR A.O. No. 4, s. 2021',
                'classification_notes' => 'Case-dependent assessor certification depending on location/jurisdiction.',
            ],
            [
                'name' => "Provincial Assessor's Certificate of Aggregate Landholding",
                'applies_to' => 'transferor',
                'is_mandatory' => true,
                'requirement_classification' => $mandatory,
                'blocks_acceptance' => true,
                'legal_basis' => 'DAR A.O. No. 4, s. 2021',
                'classification_notes' => 'Aggregate landholding certification used for 5-hectare review.',
            ],

            // TRANSFEREE
            [
                'name' => 'Affidavit of Transferee',
                'applies_to' => 'transferee',
                'is_mandatory' => true,
                'requirement_classification' => $mandatory,
                'blocks_acceptance' => true,
                'legal_basis' => 'DAR A.O. No. 4, s. 2021',
                'classification_notes' => 'Required sworn statement used for transferee review.',
            ],
            [
                'name' => 'Death Certificate (if applicable)',
                'applies_to' => 'transferee',
                'is_mandatory' => false,
                'requirement_classification' => $caseDependent,
                'blocks_acceptance' => false,
                'legal_basis' => 'DAR A.O. No. 4, s. 2021',
                'classification_notes' => 'Required only when deceased persons are indicated in the transfer instrument.',
            ],
            [
                'name' => "Municipal Assessor's Certificate of Aggregate Landholding",
                'applies_to' => 'transferee',
                'is_mandatory' => true,
                'requirement_classification' => $mandatory,
                'blocks_acceptance' => true,
                'legal_basis' => 'DAR A.O. No. 4, s. 2021',
                'classification_notes' => 'Aggregate landholding certification used for 5-hectare review.',
            ],
            [
                'name' => "City Assessor's Certificate of Aggregate Landholding",
                'applies_to' => 'transferee',
                'is_mandatory' => false,
                'requirement_classification' => $caseDependent,
                'blocks_acceptance' => false,
                'legal_basis' => 'DAR A.O. No. 4, s. 2021',
                'classification_notes' => 'Case-dependent assessor certification depending on location/jurisdiction.',
            ],
            [
                'name' => "Provincial Assessor's Certificate of Aggregate Landholding",
                'applies_to' => 'transferee',
                'is_mandatory' => true,
                'requirement_classification' => $mandatory,
                'blocks_acceptance' => true,
                'legal_basis' => 'DAR A.O. No. 4, s. 2021',
                'classification_notes' => 'Aggregate landholding certification used for 5-hectare review.',
            ],
            [
                'name' => 'MARPO Certification (LTC Form No. 2)',
                'applies_to' => 'transferee',
                'is_mandatory' => true,
                'requirement_classification' => $mandatory,
                'blocks_acceptance' => true,
                'legal_basis' => 'DAR A.O. No. 4, s. 2021',
                'classification_notes' => 'Required certification for tenant/program coverage review.',
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
