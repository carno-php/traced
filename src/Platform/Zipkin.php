<?php
/**
 * Zipkin implementer
 * User: moyo
 * Date: 23/11/2017
 * Time: 4:22 PM
 */

namespace Carno\Traced\Platform;

use Carno\Promise\Promise;
use Carno\Promise\Promised;
use Carno\Traced\Chips\TransportObserve;
use Carno\Traced\Chips\TransportSync;
use Carno\Traced\Contracts\Observer;
use Carno\Traced\Protocol\ZipkinJSON;
use Carno\Traced\Transport\Blackhole;
use Carno\Traced\Transport\ZipkinHTTP;
use Carno\Traced\Utils\Environment;
use Carno\Tracing\Contracts\Carrier;
use Carno\Tracing\Contracts\Env;
use Carno\Tracing\Contracts\Platform;
use Carno\Tracing\Contracts\Protocol;
use Carno\Tracing\Contracts\Transport;
use Carno\Tracing\Standard\Carriers\HTTP;

class Zipkin implements Platform, Observer
{
    use TransportSync, TransportObserve;

    /**
     * @var Environment
     */
    private $env = null;

    /**
     * @var Carrier
     */
    private $carrier = null;

    /**
     * @var Protocol
     */
    private $protocol = null;

    /**
     * @var Transport
     */
    private $transport = null;

    /**
     * Zipkin constructor.
     */
    public function __construct()
    {
        $this->env = new Environment;
        $this->carrier = new HTTP;
        $this->protocol = new ZipkinJSON;

        $this->syncing('tracing.addr', 'http', ZipkinHTTP::class, function (Transport $transport = null) {
            $this->transport && $this->transport->disconnect();
            $this->transport = $transport;
            $this->changed($transport);
        });
    }

    /**
     * @return Environment
     */
    public function env() : Env
    {
        return $this->env;
    }

    /**
     * @return bool
     */
    public function joined() : bool
    {
        return $this->carrier && $this->protocol && $this->transport;
    }

    /**
     * @return Promised
     */
    public function leave() : Promised
    {
        return $this->transport ? $this->transport->disconnect() : Promise::resolved();
    }

    /**
     * @return Carrier
     */
    public function carrier() : Carrier
    {
        return $this->carrier;
    }

    /**
     * @return Protocol
     */
    public function serializer() : Protocol
    {
        return $this->protocol;
    }

    /**
     * @return Transport
     */
    public function transporter() : Transport
    {
        return $this->transport ?? new Blackhole;
    }
}
