<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * @deprecated Automatic landholding/registry mutation is outside DAR-LTCMS scope.
 */
final class LandholdingMutation extends Model
{
    protected static function booted(): void
    {
        $reject = static function (): never {
            throw new LogicException(
                'Automatic landholding or registry mutation is disabled. DAR-LTCMS only records and monitors clearance decisions.'
            );
        };

        static::creating($reject);
        static::updating($reject);
        static::deleting($reject);
    }
}
