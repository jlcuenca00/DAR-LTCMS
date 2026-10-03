<?php

namespace App\Http\Middleware;

use App\Models\LegacyRecord;
use App\Models\SourceRecordPackage;
use App\Services\ApplicationMutationFileLifecycle;
use App\Services\RecordEditRevisionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LockSourceRecordMutation
{
    public function handle(Request $request, Closure $next): Response
    {
        $name = $request->route()?->getName();
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)
            || $request->user()?->role !== 'staff'
            || ! is_string($name)) {
            return $next($request);
        }

        $parameter = str_starts_with($name, 'staff.source-record-packages.') ? 'sourceRecordPackage'
            : (str_starts_with($name, 'staff.legacy-records.') ? 'legacyRecord' : null);
        $bound = $parameter ? $request->route($parameter) : null;
        if (! $bound) {
            return $next($request);
        }

        $fileLifecycle = new ApplicationMutationFileLifecycle(protectedStorage: true);
        $request->attributes->set(ApplicationMutationFileLifecycle::REQUEST_ATTRIBUTE, $fileLifecycle);
        try {
            $response = DB::transaction(function () use ($request, $next, $parameter, $bound) {
                $class = $parameter === 'sourceRecordPackage' ? SourceRecordPackage::class : LegacyRecord::class;
                $record = $class::query()->lockForUpdate()->findOrFail(is_object($bound) ? $bound->getKey() : $bound);
                if ($record instanceof LegacyRecord && $record->source_record_package_id) {
                    throw ValidationException::withMessages([
                        'source_record_package' => 'Manage parcel and landowner links through the source package so all generated records stay consistent.',
                    ]);
                }
                $validated = $request->validate(['expected_record_revision' => ['required', 'integer', 'min:1']]);
                app(RecordEditRevisionService::class)->assertExpected($record, (int) $validated['expected_record_revision']);
                if ($record instanceof SourceRecordPackage) {
                    $record->setRelation('records', $record->records()->orderBy('id')->lockForUpdate()->get());
                }
                $request->route()->setParameter($parameter, $record);
                return $next($request);
            });
            $fileLifecycle->commit();
            return $response;
        } catch (Throwable $exception) {
            $fileLifecycle->rollback();
            throw $exception;
        }
    }
}
