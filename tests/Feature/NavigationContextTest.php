<?php

namespace Tests\Feature;

use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationContextTest extends TestCase
{
    use RefreshDatabase;

    private function breadcrumbs(string $html): DOMXPath
    {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        return new DOMXPath($document);
    }

    public function test_shared_pages_return_to_each_users_dashboard(): void
    {
        foreach ([User::ROLE_STAFF => 'staff', User::ROLE_LANDOWNER => 'landowner', User::ROLE_GEODETIC => 'geodetic'] as $role => $portal) {
            $user = User::factory()->create(['role' => $role, 'is_active' => true]);
            foreach (['profile.edit' => 'Profile Settings', 'notifications.index' => 'Notifications'] as $routeName => $title) {
                $response = $this->actingAs($user)->get(route($routeName))->assertOk();
                $xpath = $this->breadcrumbs($response->getContent());
                $links = $xpath->query('//nav[@aria-label="Breadcrumb"]/a');
                $this->assertCount(1, $links);
                $this->assertSame(route($portal . '.dashboard'), $links->item(0)->getAttribute('href'));
                $current = $xpath->query('//nav[@aria-label="Breadcrumb"]/span[@aria-current="page"]');
                $this->assertCount(1, $current);
                $this->assertSame($title, trim($current->item(0)->textContent));
            }
        }
    }

    public function test_source_package_and_import_breadcrumbs_return_to_package_archive(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_STAFF, 'is_active' => true]);
        foreach (['staff.source-record-packages.create', 'staff.source-record-package-imports.create'] as $routeName) {
            $response = $this->actingAs($user)->get(route($routeName))->assertOk();
            $xpath = $this->breadcrumbs($response->getContent());
            $links = $xpath->query('//nav[@aria-label="Breadcrumb"]/a');
            $this->assertCount(2, $links);
            $this->assertSame('Source Packages', trim($links->item(1)->textContent));
            $this->assertSame(route('staff.legacy-records.index', ['view' => 'packages']), $links->item(1)->getAttribute('href'));
        }
    }

    public function test_individual_source_breadcrumb_keeps_its_existing_archive_destination(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_STAFF, 'is_active' => true]);
        $response = $this->actingAs($user)->get(route('staff.legacy-records.create'))->assertOk();
        $xpath = $this->breadcrumbs($response->getContent());
        $links = $xpath->query('//nav[@aria-label="Breadcrumb"]/a');
        $this->assertCount(2, $links);
        $this->assertSame('Source Records', trim($links->item(1)->textContent));
        $this->assertSame(route('staff.legacy-records.index'), $links->item(1)->getAttribute('href'));
    }
}
