<?php
/**
 * Env info
 * User: moyo
 * Date: 2018/7/23
 * Time: 10:47 AM
 */

namespace Carno\Traced\Utils;

use Carno\Tracing\Contracts\Env;
use Carno\Tracing\Contracts\Vars\TAG;

class Environment implements Env
{
    /**
     * @var string
     */
    private $hostname = 'localhost';

    /**
     * Environment constructor.
     */
    public function __construct()
    {
        $this->hostname = gethostname();
    }

    /**
     * @return array
     */
    public function tags() : array
    {
        return [
            TAG::HOSTNAME => $this->hostname,
            TAG::LANG_EXE => 'php',
        ];
    }
}
