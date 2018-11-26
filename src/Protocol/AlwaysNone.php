<?php
/**
 * Always none
 * User: moyo
 * Date: 2018-11-26
 * Time: 15:47
 */

namespace Carno\Traced\Protocol;

use Carno\Tracing\Contracts\Protocol;
use Carno\Tracing\Standard\Span;

class AlwaysNone implements Protocol
{
    /**
     * @param Span $span
     * @return string
     */
    public function serialize(Span $span) : string
    {
        return '';
    }
}
