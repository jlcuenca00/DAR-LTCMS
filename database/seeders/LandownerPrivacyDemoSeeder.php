<?php

namespace Database\Seeders;

use App\Models\Landowner;
use App\Models\Landholding;
use App\Models\LandTransferApplication;
use App\Models\Parcel;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LandownerPrivacyDemoSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * Demo purpose:
         * Creates two landowner accounts with separate parcel,
         * landholding, and application records.
         *
         * This is only for manual privacy testing.
         * It does NOT perform ownership transfer or registry mutation.
         */

        $staffUser = User::updateOrCreate(
            ['email' => 'staff.demo@test.com'],
            [
                'name' => 'Staff Demo Encoder',
                'password' => Hash::make('password'),
                'role' => 'staff',
            ]
        );

        $userA = User::updateOrCreate(
            ['email' => 'landowner.a@test.com'],
            [
                'name' => 'Landowner A',
                'password' => Hash::make('password'),
                'role' => 'landowner',
            ]
        );

        $userB = User::updateOrCreate(
            ['email' => 'landowner.b@test.com'],
            [
                'name' => 'Landowner B',
                'password' => Hash::make('password'),
                'role' => 'landowner',
            ]
        );

        $ownerA = Landowner::updateOrCreate(
            ['user_id' => $userA->id],
            [
                'first_name' => 'Alpha',
                'middle_name' => 'Demo',
                'last_name' => 'Landowner',
                'contact_number' => '09170000001',
                'address_line' => 'Demo Address Alpha',
                'barangay' => 'Barangay Alpha',
                'municipality' => 'Dumaguete City',
                'province' => 'Negros Oriental',
            ]
        );

        $ownerB = Landowner::updateOrCreate(
            ['user_id' => $userB->id],
            [
                'first_name' => 'Bravo',
                'middle_name' => 'Demo',
                'last_name' => 'Landowner',
                'contact_number' => '09170000002',
                'address_line' => 'Demo Address Bravo',
                'barangay' => 'Barangay Bravo',
                'municipality' => 'Bais City',
                'province' => 'Negros Oriental',
            ]
        );

        $parcelA = Parcel::updateOrCreate(
            ['parcel_code' => 'DEMO-PARCEL-ALPHA'],
            [
                'title_no' => 'T-ALPHA-001',
                'tax_decl_no' => 'TD-ALPHA-001',
                'municipality' => 'Dumaguete City',
                'barangay' => 'Barangay Alpha',
                'province' => 'Negros Oriental',
                'area_hectares' => 1.2500,
                'status' => 'active',
                'remarks' => 'Demo parcel for Landowner A privacy test.',
            ]
        );

        $parcelB = Parcel::updateOrCreate(
            ['parcel_code' => 'DEMO-PARCEL-BRAVO'],
            [
                'title_no' => 'T-BRAVO-001',
                'tax_decl_no' => 'TD-BRAVO-001',
                'municipality' => 'Bais City',
                'barangay' => 'Barangay Bravo',
                'province' => 'Negros Oriental',
                'area_hectares' => 2.5000,
                'status' => 'active',
                'remarks' => 'Demo parcel for Landowner B privacy test.',
            ]
        );

        Landholding::updateOrCreate(
            [
                'landowner_id' => $ownerA->id,
                'parcel_id' => $parcelA->id,
            ],
            [
                'area_hectares' => 1.2500,
                'status' => 'active',
                'remarks' => 'Demo active holding for Landowner A.',
            ]
        );

        Landholding::updateOrCreate(
            [
                'landowner_id' => $ownerB->id,
                'parcel_id' => $parcelB->id,
            ],
            [
                'area_hectares' => 2.5000,
                'status' => 'active',
                'remarks' => 'Demo active holding for Landowner B.',
            ]
        );

        LandTransferApplication::updateOrCreate(
            ['application_code' => 'DEMO-APP-ALPHA'],
            [
                'transferor_landowner_id' => $ownerA->id,
                'transferee_landowner_id' => $ownerA->id,
                'transferor_name' => 'Alpha Demo Landowner',
                'transferee_name' => 'Alpha Demo Landowner',
                'barangay' => 'Barangay Alpha',
                'municipality' => 'Dumaguete City',
                'status' => 'pending_review',
                'encoded_by' => $staffUser->id,
                'remarks' => 'Demo application for Landowner A privacy test.',
            ]
        );

        LandTransferApplication::updateOrCreate(
            ['application_code' => 'DEMO-APP-BRAVO'],
            [
                'transferor_landowner_id' => $ownerB->id,
                'transferee_landowner_id' => $ownerB->id,
                'transferor_name' => 'Bravo Demo Landowner',
                'transferee_name' => 'Bravo Demo Landowner',
                'barangay' => 'Barangay Bravo',
                'municipality' => 'Bais City',
                'status' => 'pending_review',
                'encoded_by' => $staffUser->id,
                'remarks' => 'Demo application for Landowner B privacy test.',
            ]
        );
    }
}