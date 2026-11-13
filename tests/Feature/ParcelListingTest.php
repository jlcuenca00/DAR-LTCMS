<?php

namespace Tests\Feature;

use App\Models\Landholding;
use App\Models\Landowner;
use App\Models\Parcel;
use App\Models\ParcelListing;
use App\Models\User;
use Database\Seeders\ParcelListingDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParcelListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_listing_requires_owner_consent_and_staff_approval_and_can_be_withdrawn(): void
    {
        $owner = User::factory()->create(['role' => 'landowner', 'registration_status' => User::REGISTRATION_APPROVED]);
        $other = User::factory()->create(['role' => 'landowner', 'registration_status' => User::REGISTRATION_APPROVED]);
        $staff = User::factory()->create(['role' => 'staff']);
        $landowner = Landowner::create(['first_name' => 'Private', 'last_name' => 'Owner', 'user_id' => $owner->id]);
        $parcel = Parcel::create([
            'parcel_code' => 'PRIVATE-PARCEL',
            'title_no' => 'PRIVATE-TITLE',
            'municipality' => 'Dumaguete City',
            'barangay' => 'Bantayan',
            'area_hectares' => 1,
            'status' => 'active',
            'geometry_geojson' => ['type' => 'Polygon', 'coordinates' => [[
                [123.31, 9.32], [123.311, 9.32], [123.311, 9.321], [123.31, 9.32],
            ]], 'internal_note' => 'PRIVATE-GEOMETRY-NOTE'],
        ]);
        $holding = Landholding::create([
            'landowner_id' => $landowner->id,
            'parcel_id' => $parcel->id,
            'area_hectares' => 1,
            'status' => 'active',
        ]);
        $submit = route('landowner.parcel-listings.submit', $holding);
        $features = route('parcel-listings.features');
        $contact = ['display_name' => 'Public Owner', 'mobile' => '09123456789', 'publish_consent' => '1'];

        $this->actingAs($other)->post($submit, $contact)->assertNotFound();
        $this->actingAs($owner)->post($submit, array_diff_key($contact, ['publish_consent' => true]))
            ->assertSessionHasErrors('publish_consent');
        $this->post($submit, $contact)->assertSessionHasNoErrors();
        $listing = ParcelListing::sole();
        $review = route('staff.parcel-listings.review', $listing);
        $this->post($review, ['decision' => 'publish'])->assertForbidden();

        auth()->logout();
        $this->getJson($features)->assertOk()->assertJsonCount(0, 'features');

        $this->actingAs($staff)->post($review, ['decision' => 'publish'])->assertSessionHasNoErrors();
        auth()->logout();
        $this->getJson($features)->assertOk()->assertJsonCount(1, 'features')
            ->assertJsonPath('features.0.properties.display_name', 'Public Owner')
            ->assertJsonPath('features.0.properties.mobile', '+639123456789')
            ->assertDontSee('PRIVATE-TITLE')->assertDontSee('PRIVATE-PARCEL')->assertDontSee('PRIVATE-GEOMETRY-NOTE');
        $this->assertDatabaseHas('audit_logs', ['action' => 'parcel_listing.published', 'actor_user_id' => $staff->id]);

        $parcel->update(['remarks' => 'Changed after review']);
        $this->getJson($features)->assertOk()->assertJsonCount(0, 'features');
        $this->actingAs($owner)->post($submit, $contact)->assertSessionHasNoErrors();
        $this->actingAs($staff)->post($review, ['decision' => 'publish'])->assertSessionHasNoErrors();

        $this->actingAs($owner)->delete(route('landowner.parcel-listings.withdraw', $holding))->assertRedirect();
        auth()->logout();
        $this->getJson($features)->assertOk()->assertJsonCount(0, 'features');
        $this->assertDatabaseHas('parcel_listings', ['id' => $listing->id, 'mobile' => null, 'status' => 'withdrawn']);
    }

    public function test_demo_seeder_creates_fifteen_linked_listings_without_contact_numbers(): void
    {
        $this->seed(ParcelListingDemoSeeder::class);
        $this->seed(ParcelListingDemoSeeder::class);

        $this->assertSame(15, ParcelListing::query()->where('is_demo', true)->whereNull('mobile')->count());
        $this->assertSame(15, ParcelListing::query()->current()->where('is_demo', true)->count());
        $this->getJson(route('parcel-listings.features'))->assertOk()->assertJsonCount(15, 'features');
    }
}
