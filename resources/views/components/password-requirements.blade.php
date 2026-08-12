@props([
    'passwordId',
    'confirmationId' => null,
])

<div class="password-checklist"
     style="margin-top: 0.75rem;"
     data-password-checklist
     data-password-input="{{ $passwordId }}"
     @if($confirmationId) data-password-confirmation="{{ $confirmationId }}" @endif>
    <p class="password-checklist-title">Password requirements</p>
    <ul class="password-rule-list" aria-live="polite">
        <li class="password-rule" data-password-rule="length"><span class="password-rule-icon">○</span><span>At least 8 characters</span></li>
        <li class="password-rule" data-password-rule="lower"><span class="password-rule-icon">○</span><span>One lowercase letter</span></li>
        <li class="password-rule" data-password-rule="upper"><span class="password-rule-icon">○</span><span>One uppercase letter</span></li>
        <li class="password-rule" data-password-rule="number"><span class="password-rule-icon">○</span><span>One number</span></li>
        <li class="password-rule" data-password-rule="symbol"><span class="password-rule-icon">○</span><span>One symbol</span></li>
        @if($confirmationId)
            <li class="password-rule" data-password-rule="match"><span class="password-rule-icon">○</span><span>Passwords match</span></li>
        @endif
    </ul>
</div>