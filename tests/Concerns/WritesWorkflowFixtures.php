<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;

trait WritesWorkflowFixtures
{
    /**
     * Construct historical/corrupt rows for pre-existing regression fixtures.
     * Only the new workflow guards are suspended in this test-only transaction;
     * existing append-only, foreign-key, and uniqueness protection remains active.
     * New database-guard regressions use real DB writes without this helper.
     */
    protected function writeWorkflowFixture(callable $callback): mixed
    {
        if (! app()->environment('testing')) {
            throw new \LogicException('Workflow fixtures are restricted to tests.');
        }
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return $callback();
        }

        return DB::transaction(function () use ($callback) {
            $triggers = [
                'land_transfer_applications' => ['workflow_final_application_guard'],
                'application_parcels' => ['workflow_child_guard', 'workflow_child_revision'],
                'application_documents' => ['workflow_child_guard', 'workflow_child_revision'],
                'application_compliance_notices' => ['workflow_child_guard', 'workflow_child_revision'],
            ];
            foreach ($triggers as $table => $names) {
                foreach ($names as $name) {
                    DB::statement("ALTER TABLE {$table} DISABLE TRIGGER {$name}");
                }
            }
            $result = $callback();
            foreach ($triggers as $table => $names) {
                foreach ($names as $name) {
                    DB::statement("ALTER TABLE {$table} ENABLE TRIGGER {$name}");
                }
            }

            return $result;
        });
    }
}
