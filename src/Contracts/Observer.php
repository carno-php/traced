<?php
/**
 * Platform observer
 * User: moyo
 * Date: 2018/7/23
 * Time: 4:55 PM
 */

namespace Carno\Traced\Contracts;

use Closure;

interface Observer
{
    /**
     * @param Closure $opening
     * @param Closure $closed
     */
    public function transportable(Closure $opening, Closure $closed) : void;
}
