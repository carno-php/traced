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

trait TransportSync
{
    /**
     * @param Config $source
     * @param string $conf
     * @param array $schemes
     * @param Closure $setter
     */
    private function syncing(
        Config $source,
        string $conf,
        array $schemes,
        Closure $setter
    ) : void {
        $source->watching($conf, static function (string $dsn) use ($schemes, $setter) {
            // parsing dsn
            $parsed = parse_url($dsn) ?: [];

            /**
             * @var Transport $objTrans
             * @var Protocol $objProto
             */

            $objTrans = $objProto = null;

            if ($platform = $schemes[$parsed['scheme']] ?? null) {
                [$transport, $protocol] = $platform;
                $objTrans = DI::object($transport);
                $objProto = DI::object($protocol);
                $objTrans->connect(new Address($parsed['host'], $parsed['port']), $parsed['path'] ?? null);
            }

            // user setter
            $setter($objTrans, $objProto);
        });
    }
}
