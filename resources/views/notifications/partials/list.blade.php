@php
    $notificationTypeLabels = [
        'application_created' => 'Application encoded',
        'application_submitted' => 'Application updated',
        'application_status_updated' => 'Application status updated',
        'application_approved' => 'Clearance released',
        'application_released' => 'Clearance released',
        'application_not_approved' => 'Application denied',
        'application_denied' => 'Application denied',
        'document_uploaded' => 'Document uploaded',
        'document_reviewed' => 'Document reviewed',
        'source_record_created' => 'Source record created',
        'source_package_created' => 'Source package created',
    ];

    $cleanNotificationText = function (?string $value): string {
        $value = (string) $value;

        return str($value)
            ->replace('Approved Clearance', 'Released Clearance')
            ->replace('Approved clearance', 'Released clearance')
            ->replace('Application approved', 'Clearance released')
            ->replace('application approved', 'clearance released')
            ->replace('approved clearance', 'released clearance')
            ->replace('approved application', 'released application')
            ->replace('Not Approved', 'Denied')
            ->replace('not approved', 'denied')
            ->replace('not-approved', 'denied')
            ->replace('APPROVED', 'RELEASED')
            ->replace('NOT APPROVED', 'DENIED')
            ->toString();
    };
@endphp

<div class="space-y-4">
    @if (session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div>
            <p class="text-xs font-black uppercase tracking-[0.18em] text-green-700">Notification Center</p>
            <h2 class="mt-1 text-xl font-black text-slate-950">System Notifications</h2>
            <p class="mt-1 text-sm font-semibold text-slate-500">
                {{ $unreadCount }} unread {{ \Illuminate\Support\Str::plural('notification', $unreadCount) }} for your account.
            </p>
        </div>

        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            @method('PATCH')
            <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-green-700 bg-green-700 px-4 text-sm font-black text-white hover:bg-green-900">
                Mark all as read
            </button>
        </form>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @forelse ($notifications as $notification)
            @php
                $typeLabel = $notificationTypeLabels[$notification->type] ?? str_replace('_', ' ', $notification->type);
                $title = $cleanNotificationText($notification->title);
                $message = $cleanNotificationText($notification->message);
            @endphp

            <div class="flex flex-col gap-4 border-b border-slate-100 p-5 last:border-b-0 md:flex-row md:items-start md:justify-between {{ $notification->read_at ? 'bg-white' : 'bg-green-50/70' }}">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        @if (! $notification->read_at)
                            <span class="inline-flex rounded-full bg-green-700 px-2.5 py-1 text-[11px] font-black uppercase tracking-wide text-white">Unread</span>
                        @else
                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-black uppercase tracking-wide text-slate-500">Read</span>
                        @endif

                        <span class="text-xs font-bold uppercase tracking-wide text-slate-400">
                            {{ $typeLabel }}
                        </span>
                    </div>

                    <a href="{{ route('notifications.open', $notification) }}" class="mt-3 inline-flex items-center gap-2 text-base font-black text-slate-950 hover:text-green-800">
                        {{ $title }}
                        <i class="fa-solid fa-arrow-up-right-from-square text-xs text-slate-400"></i>
                    </a>

                    <p class="mt-1 text-sm font-semibold leading-6 text-slate-600">
                        {{ $message }}
                    </p>

                    <p class="mt-3 text-xs font-bold text-slate-400">
                        {{ $notification->created_at?->timezone('Asia/Manila')->format('M d, Y h:i A') }}
                    </p>
                </div>

                <div class="flex shrink-0 flex-wrap gap-2">
                    <a href="{{ route('notifications.open', $notification) }}" class="inline-flex min-h-9 items-center justify-center rounded-lg border border-green-700 bg-green-700 px-3 text-xs font-black text-white hover:bg-green-900">
                        Open
                    </a>

                    @if (! $notification->read_at)
                        <form method="POST" action="{{ route('notifications.read', $notification) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="inline-flex min-h-9 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 text-xs font-black text-slate-700 hover:bg-slate-50">
                                Mark read
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="p-8 text-center">
                <div class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-green-50 text-green-800">
                    <i class="fa-solid fa-bell"></i>
                </div>
                <h3 class="mt-3 text-base font-black text-slate-950">No notifications yet</h3>
                <p class="mt-1 text-sm font-semibold text-slate-500">New system activity notices for your role will appear here.</p>
            </div>
        @endforelse
    </div>

    <div>
        {{ $notifications->links() }}
    </div>
</div>
