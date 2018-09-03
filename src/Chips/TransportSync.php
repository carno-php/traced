<?php
/**
 * Transport endpoint sync
 * User: moyo
 * Date: 24/11/2017
 * Time: 5:21 PM
 */

namespace Carno\Traced\Chips;

use Carno\Net\Address;
use Carno\Tracing\Contracts\Transport;
use Closure;

trait TransportSync
{
    /**
     * @param string $conf
     * @param string $scheme
     * @param string $implementer
     * @param Closure $setter
     */
    private function syncing(string $conf, string $scheme, string $implementer, Closure $setter) : void
    {
        config()->watching($conf, static function (string $dsn) use ($scheme, $implementer, $setter) {
            // parsing dsn
            $parsed = parse_url($dsn);

            switch ($parsed['scheme']) {
                case $scheme:
                    /**
                     * @var Transport $instance
                     */
                    $instance = new $implementer();
                    $instance->connect(new Address($parsed['host'], $parsed['port'] ?? 80));
                    break;
                default:
                    $instance = null;
            }

            // user setter
            $setter($instance);
        });
    }
}
