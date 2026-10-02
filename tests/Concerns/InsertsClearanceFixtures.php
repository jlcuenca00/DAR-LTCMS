<?php

namespace Tests\Concerns;

use App\Models\ApplicationClearance;
use Illuminate\Support\Facades\DB;

trait InsertsClearanceFixtures
{
    // Deliberate historical/anomalous database fixtures, outside runtime creation.
    protected function insertClearanceFixture(array $attributes): ApplicationClearance
    {
        $model = new ApplicationClearance($attributes);
        $id = DB::table('application_clearances')->insertGetId(array_merge(
            $model->getAttributes(),
            ['created_at' => now(), 'updated_at' => now()]
        ));

        return ApplicationClearance::findOrFail($id);
    }
}
