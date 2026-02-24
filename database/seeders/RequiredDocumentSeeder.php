<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\RequiredDocument;

class RequiredDocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
{
    $docs = [
        // TRANSFEROR (LTC Fee Payment)
        ['name' => 'Official Receipt (LTC Fee Payment)', 'applies_to' => 'transferor', 'is_mandatory' => true,  'legal_basis' => 'DAR A.O. No. 4, s. 2021'],
        ['name' => 'Electronic Copy of Title', 'applies_to' => 'transferor', 'is_mandatory' => true, 'legal_basis' => 'DAR A.O. No. 4, s. 2021'],
        ['name' => 'Recent Tax Declaration', 'applies_to' => 'transferor', 'is_mandatory' => true, 'legal_basis' => 'DAR A.O. No. 4, s. 2021'],
        ['name' => 'Deed of Transfer / Deed of Sale / Donation (Registered)', 'applies_to' => 'transferor', 'is_mandatory' => true, 'legal_basis' => 'DAR A.O. No. 4, s. 2021'],
        ['name' => 'Deed Certificate (if applicable)', 'applies_to' => 'transferor', 'is_mandatory' => false, 'legal_basis' => 'DAR A.O. No. 4, s. 2021'],
        ['name' => 'Affidavit of Transferor', 'applies_to' => 'transferor', 'is_mandatory' => true, 'legal_basis' => 'DAR A.O. No. 4, s. 2021'],
        ['name' => "Municipal Assessor's Certificate of Aggregate Landholding", 'applies_to' => 'transferor', 'is_mandatory' => true, 'legal_basis' => 'DAR A.O. No. 4, s. 2021'],
        ['name' => "City Assessor's Certificate of Aggregate Landholding", 'applies_to' => 'transferor', 'is_mandatory' => false, 'legal_basis' => 'DAR A.O. No. 4, s. 2021'],
        ['name' => "Provincial Assessor's Certificate of Aggregate Landholding", 'applies_to' => 'transferor', 'is_mandatory' => true, 'legal_basis' => 'DAR A.O. No. 4, s. 2021'],

        // TRANSFEREE
        ['name' => 'Affidavit of Transferee', 'applies_to' => 'transferee', 'is_mandatory' => true, 'legal_basis' => 'DAR A.O. No. 4, s. 2021'],
        ['name' => 'Death Certificate (if applicable)', 'applies_to' => 'transferee', 'is_mandatory' => false, 'legal_basis' => 'DAR A.O. No. 4, s. 2021'],
        ['name' => "Municipal Assessor's Certificate of Aggregate Landholding", 'applies_to' => 'transferee', 'is_mandatory' => true, 'legal_basis' => 'DAR A.O. No. 4, s. 2021'],
        ['name' => "City Assessor's Certificate of Aggregate Landholding", 'applies_to' => 'transferee', 'is_mandatory' => false, 'legal_basis' => 'DAR A.O. No. 4, s. 2021'],
        ['name' => "Provincial Assessor's Certificate of Aggregate Landholding", 'applies_to' => 'transferee', 'is_mandatory' => true, 'legal_basis' => 'DAR A.O. No. 4, s. 2021'],
        ['name' => 'MARPO Certification (LTC Form No. 2)', 'applies_to' => 'transferee', 'is_mandatory' => true, 'legal_basis' => 'DAR A.O. No. 4, s. 2021'],
    ];

    foreach ($docs as $doc) {
        RequiredDocument::updateOrCreate(
            ['name' => $doc['name'], 'applies_to' => $doc['applies_to']],
            $doc
        );
    }
}
}
