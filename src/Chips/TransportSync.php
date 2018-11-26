<?php
/**
 * Transport endpoint sync
 * User: moyo
 * Date: 24/11/2017
 * Time: 5:21 PM
 */

namespace Carno\Traced\Chips;

use Carno\Net\Address;
use Carno\Tracing\Contracts\Protocol;
use Carno\Tracing\Contracts\Transport;
use Closure;

trait TransportSync
{
    /**
     * @param string $conf
     * @param string $scheme
     * @param string $transport
     * @param string $protocol
     * @param Closure $setter
     */
    private function syncing(
        string $conf,
        string $scheme,
        string $transport,
        string $protocol,
        Closure $setter
    ) : void {
        config()->watching($conf, static function (string $dsn) use ($scheme, $transport, $protocol, $setter) {
            // parsing dsn
            $parsed = parse_url($dsn);

            /**
             * @var Transport $objTrans
             * @var Protocol $objProto
             */

            switch ($parsed['scheme']) {
                case $scheme:
                    $objTrans = new $transport();
                    $objProto = new $protocol();
                    $objTrans->connect(new Address($parsed['host'], $parsed['port'] ?? 80));
                    break;
                default:
                    $objTrans = null;
                    $objProto = null;
            }

            // user setter
            $setter($objTrans, $objProto);
        });
    }
}
