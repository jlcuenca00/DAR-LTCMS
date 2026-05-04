<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Audit Log Viewer
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm sm:rounded-lg p-6 border">
                <h3 class="text-lg font-semibold text-gray-900">
                    System Activity History
                </h3>

                <p class="text-sm text-gray-600 mt-1">
                    This page provides a read-only audit trail of important staff-side actions.
                    It supports traceability, accountability, and monitoring of clearance application processing.
                    It does not perform ownership transfer or registry mutation.
                </p>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 border">
                <h3 class="font-semibold text-gray-900 mb-4">
                    Filter Audit Logs
                </h3>

                <form method="GET"
                      action="{{ route('staff.audit-logs.index') }}"
                      class="grid grid-cols-1 md:grid-cols-4 gap-4">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Action
                        </label>

                        <select name="action"
                                class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">All actions</option>

                            @foreach ($actions as $action)
                                <option value="{{ $action }}"
                                    {{ ($filters['action'] ?? '') === $action ? 'selected' : '' }}>
                                    {{ ucwords(str_replace('_', ' ', $action)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Application Code
                        </label>

                        <input type="text"
                               name="application_code"
                               value="{{ $filters['application_code'] ?? '' }}"
                               placeholder="e.g., LTC-2026-0001"
                               class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Actor
                        </label>

                        <input type="text"
                               name="actor"
                               value="{{ $filters['actor'] ?? '' }}"
                               placeholder="Name or email"
                               class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                    </div>

                    <div class="flex items-end gap-2">
                        <button type="submit"
                                class="px-4 py-2 bg-gray-900 text-white rounded-md text-sm hover:bg-gray-800">
                            Apply Filters
                        </button>

                        <a href="{{ route('staff.audit-logs.index') }}"
                           class="px-4 py-2 border rounded-md text-sm text-gray-700 hover:bg-gray-50">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 border">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-900">
                        Audit Logs
                    </h3>

                    <div class="text-sm text-gray-500">
                        Showing {{ $auditLogs->count() }} of {{ $auditLogs->total() }} record(s)
                    </div>
                </div>

                @if ($auditLogs->isEmpty())
                    <p class="text-sm text-gray-500">
                        No audit logs found.
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full border text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="border px-3 py-2 text-left">Date / Time</th>
                                    <th class="border px-3 py-2 text-left">Actor</th>
                                    <th class="border px-3 py-2 text-left">Action</th>
                                    <th class="border px-3 py-2 text-left">Application</th>
                                    <th class="border px-3 py-2 text-left">Auditable Record</th>
                                    <th class="border px-3 py-2 text-left">IP Address</th>
                                    <th class="border px-3 py-2 text-left">Details</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($auditLogs as $log)
                                    <tr class="align-top">
                                        <td class="border px-3 py-2 whitespace-nowrap">
                                            {{ $log->created_at?->format('M d, Y h:i A') ?? 'N/A' }}
                                        </td>

                                        <td class="border px-3 py-2">
                                            @if ($log->actor)
                                                <div class="font-medium text-gray-900">
                                                    {{ $log->actor->name }}
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    {{ $log->actor->email }}
                                                </div>
                                            @else
                                                <span class="text-gray-500">Unknown user</span>
                                            @endif
                                        </td>

                                        <td class="border px-3 py-2">
                                            <span class="inline-flex px-2 py-1 rounded bg-gray-100 text-gray-800 text-xs font-semibold">
                                                {{ ucwords(str_replace('_', ' ', $log->action)) }}
                                            </span>
                                        </td>

                                        <td class="border px-3 py-2">
                                            @if ($log->application)
                                                <a href="{{ route('staff.applications.show', $log->application) }}"
                                                   class="text-blue-700 hover:underline font-mono">
                                                    {{ $log->application->application_code }}
                                                </a>
                                            @else
                                                <span class="text-gray-500">N/A</span>
                                            @endif
                                        </td>

                                        <td class="border px-3 py-2">
                                            @if ($log->auditable_type)
                                                <div class="text-gray-900">
                                                    {{ class_basename($log->auditable_type) }}
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    ID: {{ $log->auditable_id ?? 'N/A' }}
                                                </div>
                                            @else
                                                <span class="text-gray-500">N/A</span>
                                            @endif
                                        </td>

                                        <td class="border px-3 py-2 font-mono text-xs">
                                            {{ $log->ip_address ?? 'N/A' }}
                                        </td>

                                        <td class="border px-3 py-2">
                                            @if (! empty($log->metadata))
                                                <details>
                                                    <summary class="cursor-pointer text-blue-700 hover:underline">
                                                        View metadata
                                                    </summary>

                                                    <pre class="mt-2 p-3 bg-gray-50 border rounded text-xs overflow-x-auto text-gray-700">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                </details>
                                            @else
                                                <span class="text-gray-500">No details</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $auditLogs->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>