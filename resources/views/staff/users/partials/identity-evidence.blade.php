@if($user->identity_evidence && ! $user->account_deleted_at)
    <section class="account-form-section">
        <h3>Submitted identity evidence</h3>
        <p class="account-muted">{{ \App\Services\RegistrationEvidence::idTypes()[$user->identity_evidence['id_type'] ?? ''] ?? 'ID submission' }} · For manual staff assessment. No automated face matching or liveness check was performed.</p>
        <div class="account-evidence-grid">
            @foreach(['id_front' => 'Submitted ID front', 'id_back' => 'Submitted ID back', 'selfie' => 'Submitted selfie'] as $kind => $label)
                @if(filled($user->identity_evidence[$kind] ?? null))
                    <figure>
                        <a href="{{ route('staff.users.identity-evidence', [$user, $kind]) }}" target="_blank" rel="noopener">
                            <img src="{{ route('staff.users.identity-evidence', [$user, $kind]) }}" alt="{{ $label }}" loading="lazy">
                            <figcaption>{{ $label }} · Open full image</figcaption>
                        </a>
                    </figure>
                @endif
            @endforeach
        </div>
        <p class="account-muted">Compare the ID, selfie, and submitted details before choosing the registration review status below.</p>
    </section>
@endif
