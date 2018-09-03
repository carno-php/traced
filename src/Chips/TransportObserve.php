<?php
/**
 * Transport changes observe
 * User: moyo
 * Date: 2018/7/23
 * Time: 4:19 PM
 */

namespace Carno\Traced\Chips;

use Carno\Tracing\Contracts\Transport;
use Closure;

trait TransportObserve
{
    /**
     * @var Closure[]
     */
    private $tpOpened = [];

    /**
     * @var Closure[]
     */
    private $tpClosed = [];

    /**
     * @param Closure $open
     * @param Closure $close
     */
    public function transportable(Closure $open, Closure $close) : void
    {
        $this->tpOpened[] = $open;
        $this->tpClosed[] = $close;
    }

    /**
     * @param Transport $transport
     */
    protected function changed(Transport $transport = null) : void
    {
        foreach ($transport ? $this->tpOpened : $this->tpClosed as $observer) {
            $observer($transport);
        }
    }
}
