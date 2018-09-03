<?php
/**
 * Transfer queued
 * User: moyo
 * Date: 21/11/2017
 * Time: 4:24 PM
 */

namespace Carno\Traced\Chips;

use Closure;

trait TransferQueued
{
    /**
     * @var array
     */
    private $buffer = [];

    /**
     * @var int
     */
    private $packed = 1;

    /**
     * @var int
     */
    private $overflow = 0;

    /**
     * @param int $packed
     * @param int $overflow
     */
    private function options(int $packed = 200, int $overflow = 20000) : void
    {
        $this->packed = $packed;
        $this->overflow = $overflow;
    }

    /**
     * @param string $data
     */
    private function stashing(string $data) : void
    {
        count($this->buffer) < $this->overflow && $this->buffer[] = $data;
    }

    /**
     * @return int
     */
    private function stashed() : int
    {
        return count($this->buffer);
    }

    /**
     * @return array
     */
    private function segments() : array
    {
        return array_splice($this->buffer, 0, $this->packed);
    }

    /**
     * @param Closure $program
     */
    private function spouting(Closure $program)
    {
        while ($this->stashed()) {
            $program($this->segments());
        }
    }
}
