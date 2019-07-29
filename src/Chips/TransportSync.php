<?php
/**
 * Transport endpoint sync
 * User: moyo
 * Date: 24/11/2017
 * Time: 5:21 PM
 */

namespace Carno\Traced\Chips;

use Carno\Config\Config;
use Carno\Container\DI;
use Carno\Net\Address;
use Carno\Tracing\Contracts\Protocol;
use Carno\Tracing\Contracts\Transport;
use Closure;
use Throwable;

trait TransportSync
{
    /**
     * @param Config $source
     * @param string $conf
     * @param array $schemes
     * @param Closure $setter
     * @param Closure $failure
     */
    private function syncing(
        Config $source,
        string $conf,
        array $schemes,
        Closure $setter,
        Closure $failure = null
    ) : void {
        $source->watching($conf, static function (string $dsn) use ($schemes, $setter, $failure) {
            // parsing dsn
            $parsed = parse_url($dsn) ?: [];

            /**
             * @var Transport $transfer
             * @var Protocol $codec
             */

            $transfer = $codec = null;

            if ($platform = $schemes[$parsed['scheme']] ?? null) {
                [$transport, $protocol] = $platform;

                $transfer = DI::object($transport);
                $codec = DI::object($protocol);

                try {
                    $transfer->connect(
                        new Address($parsed['host'], $parsed['port'] ?? null),
                        $parsed['path'] ?? null
                    );
                } catch (Throwable $e) {
                    $failure && $failure($e);
                }
            }

            // user setter
            $setter($transfer, $codec);
        });
    }
}
