<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\LandTransferApplication;
use App\Models\Landowner;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ApplicationLandownerLinkController extends Controller
{
    public function update(Request $request, LandTransferApplication $application)
    {
        if ($application->isFinalized()) {
            return redirect()
                ->to(route('staff.applications.show', $application) . '#landowner-links')
                ->with('error', 'Finalized applications are locked. Landowner record links can no longer be changed.');
        }

        $validated = $request->validate([
            'transferor_landowner_id' => ['nullable', 'exists:landowners,id'],
            'transferee_landowner_id' => ['nullable', 'exists:landowners,id'],
        ]);

        $oldLinks = [
            'transferor_landowner_id' => $application->transferor_landowner_id,
            'transferee_landowner_id' => $application->transferee_landowner_id,
        ];

        $application->update([
            'transferor_landowner_id' => $validated['transferor_landowner_id'] ?? null,
            'transferee_landowner_id' => $validated['transferee_landowner_id'] ?? null,
        ]);

        AuditLogger::record(
            'application_landowner_links_updated',
            $application,
            $application,
            [
                'old_links' => $oldLinks,
                'new_links' => [
                    'transferor_landowner_id' => $application->transferor_landowner_id,
                    'transferee_landowner_id' => $application->transferee_landowner_id,
                ],
                'scope_note' => 'Landowner records were linked to the clearance application for review, validation, and traceability only. No ownership transfer or registry mutation was performed.',
            ]
        );

        return redirect()
            ->to(route('staff.applications.show', $application) . '#landowner-links')
            ->with('success', 'Landowner record links saved successfully.');
    }

    public function createFromApplicationParty(Request $request, LandTransferApplication $application)
    {
        if ($application->isFinalized()) {
            return redirect()
                ->to(route('staff.applications.show', $application) . '#landowner-links')
                ->with('error', 'Finalized applications are locked. New landowner records can no longer be linked from this application.');
        }

        $validated = $request->validate([
            'party' => ['required', Rule::in(['transferor', 'transferee'])],
        ]);

        $party = $validated['party'];
        $nameField = $party === 'transferor' ? 'transferor_name' : 'transferee_name';
        $linkField = $party === 'transferor' ? 'transferor_landowner_id' : 'transferee_landowner_id';
        $displayParty = $party === 'transferor' ? 'Transferor' : 'Transferee';

        if ($application->{$linkField}) {
            return redirect()
                ->to(route('staff.applications.show', $application) . '#landowner-links')
                ->with('error', "{$displayParty} is already linked to a landowner record.");
        }

        $sourceName = trim((string) $application->{$nameField});

        if ($sourceName === '') {
            return redirect()
                ->to(route('staff.applications.show', $application) . '#landowner-links')
                ->with('error', "Cannot create {$displayParty} record because the application {$party} name is blank.");
        }

        [$firstName, $middleName, $lastName, $suffix] = $this->splitName($sourceName);

        $landowner = null;

        DB::transaction(function () use ($application, $linkField, $party, $displayParty, $sourceName, $firstName, $middleName, $lastName, $suffix, &$landowner) {
            $landowner = Landowner::create([
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'suffix' => $suffix,
                'barangay' => $application->barangay,
                'municipality' => $application->municipality,
                'province' => 'Negros Oriental',
            ]);

            $application->update([
                $linkField => $landowner->id,
            ]);

            AuditLogger::record(
                'landowner_record_created_from_application_party',
                $application,
                $landowner,
                [
                    'party' => $party,
                    'display_party' => $displayParty,
                    'source_name' => $sourceName,
                    'created_landowner_id' => $landowner->id,
                    'linked_field' => $linkField,
                    'scope_note' => 'A landowner/person record was created for application processing and traceability only. This does not transfer ownership, create a landholding, assign a parcel, or mutate registry records.',
                ]
            );
        });

        return redirect()
            ->to(route('staff.applications.show', $application) . '#landowner-links')
            ->with('success', "{$displayParty} landowner record created and linked successfully. This record supports application processing only and does not assign land ownership.");
    }

    private function splitName(string $name): array
    {
        $suffixes = ['Jr.', 'Jr', 'Sr.', 'Sr', 'II', 'III', 'IV', 'V'];
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        if (count($parts) === 0) {
            return ['Unnamed', null, 'Record', null];
        }

        $suffix = null;
        $lastToken = end($parts);

        if ($lastToken !== false && in_array($lastToken, $suffixes, true)) {
            $suffix = array_pop($parts);
        }

        if (count($parts) === 1) {
            return [$parts[0], null, $parts[0], $suffix];
        }

        $firstName = array_shift($parts);
        $lastName = array_pop($parts);
        $middleName = count($parts) > 0 ? implode(' ', $parts) : null;

        return [$firstName, $middleName, $lastName, $suffix];
    }
}
