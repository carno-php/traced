<?php
/**
 * Eat anything
 * User: moyo
 * Date: 2018/6/11
 * Time: 2:08 PM
 */

namespace Carno\Traced\Transport;

use Carno\Net\Address;
use Carno\Promise\Promise;
use Carno\Promise\Promised;
use Carno\Tracing\Contracts\Transport;

class Blackhole implements Transport
{
    /**
     * @param Address $endpoint
     * @param string $identify
     */
    public function connect(Address $endpoint, string $identify = null) : void
    {
        // do nothing
    }

    /**
     * @return Promised
     */
    public function disconnect() : Promised
    {
        return Promise::resolved();
    }

    /**
     * @param string $data
     */
    public function loading(string $data) : void
    {
        // do nothing
    }

    /**
     */
    public function flushing() : void
    {
        // do nothing
    }
}
