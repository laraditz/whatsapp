<?php

namespace Laraditz\Whatsapp\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Laraditz\Whatsapp\Skeleton\SkeletonClass
 */
class Whatsapp extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'whatsapp';
    }
}
