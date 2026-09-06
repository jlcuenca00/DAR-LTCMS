<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class VerifyAddedEmailController extends Controller
{
    public function __invoke(Request $request, User $user, string $hash): RedirectResponse
    {
        if (blank($user->email)) {
            return $this->redirectAfterVerification(
                'This verification link is no longer valid because the account does not currently have an email address.'
            );
        }

        $expectedHash = sha1(Str::lower((string) $user->email));

        if (! hash_equals($expectedHash, $hash)) {
            abort(403);
        }

        if ($user->email_verified_at === null) {
            $user->forceFill([
                'email_verified_at' => now(),
            ])->save();

            AuditLogger::record(
                'user_email_verified',
                null,
                $user,
                [
                    'verified_user_id' => $user->id,
                    'verified_username' => $user->username,
                    'verified_email' => $user->email,
                    'verification_method' => 'signed_email_link',
                ],
                $user->id
            );
        }

        return $this->redirectAfterVerification(
            'Your email address has been verified successfully for your DAR-LTCMS account.'
        );
    }

    private function redirectAfterVerification(string $status): RedirectResponse
    {
        if (Auth::check()) {
            return redirect()
                ->route('dashboard')
                ->with('status', $status);
        }

        return redirect()
            ->route('login')
            ->with('status', $status);
    }
}
