<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Landholding;
use App\Models\LandTransferApplication;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $darStaff = User::create([
            'name' => 'Juan Dela Cruz',
            'email' => 'darstaff@dar.gov.ph',
            'password' => Hash::make('password'),
            'role' => 'dar_staff',
        ]);

        $landowner = User::create([
            'name' => 'Pedro Landowner',
            'email' => 'landowner@test.com',
            'password' => Hash::make('password'),
            'role' => 'landowner',
        ]);

        $landholding1 = Landholding::create([
            'landowner_user_id' => $landowner->id,
            'parcel_code' => 'NO-001',
            'area_hectares' => 2.5,
            'barangay' => 'Barangay Uno',
            'municipality' => 'Dumaguete City',
        ]);

        $landholding2 = Landholding::create([
            'landowner_user_id' => $landowner->id,
            'parcel_code' => 'NO-002',
            'area_hectares' => 1.8,
            'barangay' => 'Barangay Dos',
            'municipality' => 'Valencia',
        ]);

        LandTransferApplication::create([
            'application_code' => 'APP-0001',
            'applicant_user_id' => $landowner->id,
            'status' => 'Pending',
            'remarks' => 'Initial seeded application',
        ]);
    }
}
