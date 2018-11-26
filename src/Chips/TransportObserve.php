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
    private $tpOpening = [];

    /**
     * @var Closure[]
     */
    private $tpClosed = [];

    /**
     * @param Closure $opening
     * @param Closure $closed
     */
    public function transportable(Closure $opening, Closure $closed) : void
    {
        $this->tpOpening[] = $opening;
        $this->tpClosed[] = $closed;
    }

    /**
     * @param Transport $transport
     */
    protected function changed(Transport $transport = null) : void
    {
        foreach ($transport ? $this->tpOpening : $this->tpClosed as $observer) {
            $observer($transport);
        }

        $transport
            ? logger('traced')->info('Tracing platform activated', ['api' => get_class($transport)])
            : logger('traced')->info('Tracing platform unloaded')
        ;
    }
}
