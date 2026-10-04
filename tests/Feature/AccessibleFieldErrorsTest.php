<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class AccessibleFieldErrorsTest extends TestCase
{
    use RefreshDatabase;

    private function xpath(string $html): \DOMXPath
    {
        $document = new \DOMDocument();
        @$document->loadHTML($html);
        return new \DOMXPath($document);
    }

    private function assertErrorBinding(\DOMXPath $xpath, string $id, string $message): void
    {
        $control = $xpath->query('//*[@id="'.$id.'"]')->item(0);
        $this->assertNotNull($control);
        $this->assertSame('true', $control->getAttribute('aria-invalid'));
        $errorId = $control->getAttribute('aria-describedby');
        $this->assertNotSame('', $errorId);
        $error = $xpath->query('//*[@id="'.$errorId.'"]')->item(0);
        $this->assertNotNull($error);
        $this->assertStringContainsString($message, $error->textContent);
    }

    public function test_profile_named_password_errors_do_not_mark_email_password_field_invalid(): void
    {
        $user = User::factory()->create();
        $errors = (new ViewErrorBag())->put('updatePassword', new MessageBag([
            'current_password' => 'Incorrect password.',
            'password' => 'Use a stronger password.',
        ]));
        $response = $this->actingAs($user)->withSession(['errors' => $errors])->get('/profile')->assertOk();
        $xpath = $this->xpath($response->getContent());
        $this->assertErrorBinding($xpath, 'update_password_current_password', 'Incorrect password.');
        $this->assertErrorBinding($xpath, 'update_password_password', 'Use a stronger password.');
        $this->assertFalse($xpath->query('//*[@id="profile_current_password"]')->item(0)->hasAttribute('aria-invalid'));
    }

    public function test_default_profile_error_does_not_mark_password_change_form_invalid(): void
    {
        $user = User::factory()->create();
        $errors = (new ViewErrorBag())->put('default', new MessageBag(['current_password' => 'Confirm the email change.']));
        $response = $this->actingAs($user)->withSession(['errors' => $errors])->get('/profile')->assertOk();
        $xpath = $this->xpath($response->getContent());
        $this->assertErrorBinding($xpath, 'profile_current_password', 'Confirm the email change.');
        $this->assertFalse($xpath->query('//*[@id="update_password_current_password"]')->item(0)->hasAttribute('aria-invalid'));
    }

    public function test_parcel_server_errors_are_bound_in_rendered_html(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF, 'is_active' => true]);
        $errors = (new ViewErrorBag())->put('default', new MessageBag(['parcel_code' => 'Code already exists.']));
        $response = $this->actingAs($staff)->withSession(['errors' => $errors])
            ->get(route('staff.records.parcels.create'))->assertOk();
        $this->assertErrorBinding($this->xpath($response->getContent()), 'parcel_code', 'Code already exists.');
    }

    public function test_landowner_labels_and_errors_work_without_javascript(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF, 'is_active' => true]);
        $errors = (new ViewErrorBag())->put('default', new MessageBag(['first_name' => 'First name is required.']));
        $response = $this->actingAs($staff)->withSession(['errors' => $errors])
            ->get(route('staff.records.landowners.create'))->assertOk();
        $xpath = $this->xpath($response->getContent());
        $this->assertErrorBinding($xpath, 'landowner-first_name', 'First name is required.');
        foreach (['first_name', 'last_name', 'user_id', 'registered_owner_status'] as $field) {
            $this->assertSame(1, $xpath->query('//label[@for="landowner-'.$field.'"]')->length);
            $this->assertSame(1, $xpath->query('//*[@id="landowner-'.$field.'"]')->length);
        }
    }
}
