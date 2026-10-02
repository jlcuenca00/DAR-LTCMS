<?php

namespace App\Models\Concerns;

use Illuminate\Events\NullDispatcher;

trait RequiresModelEvents
{
    public function save(array $options = [])
    {
        $this->assertModelEventsEnabled();

        return parent::save($options);
    }

    public function delete()
    {
        $this->assertModelEventsEnabled();

        return parent::delete();
    }

    protected function incrementOrDecrement($column, $amount, $extra, $method)
    {
        $this->assertModelEventsEnabled();

        return parent::incrementOrDecrement($column, $amount, $extra, $method);
    }

    private function assertModelEventsEnabled(): void
    {
        $dispatcher = static::getEventDispatcher();

        if ($dispatcher === null || $dispatcher instanceof NullDispatcher) {
            throw new \LogicException('Protected workflow records require model events; event-suppressed writes are not allowed.');
        }
    }
}
