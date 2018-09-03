<?php
/**
 * Zipkin http collector
 * User: moyo
 * Date: 24/11/2017
 * Time: 11:21 AM
 */

namespace Carno\Traced\Transport;

use function Carno\Coroutine\co;
use Carno\HTTP\Client;
use Carno\HTTP\Exception\RequestException;
use Carno\HTTP\Options as HOptions;
use Carno\HTTP\Standard\Request;
use Carno\HTTP\Standard\Response;
use Carno\HTTP\Standard\Streams\Body;
use Carno\HTTP\Standard\Uri;
use Carno\Net\Address;
use Carno\Pool\Exception\SelectWaitException;
use Carno\Pool\Options as POptions;
use Carno\Promise\Promised;
use Carno\Timer\Timer;
use Carno\Traced\Chips\TransferQueued;
use Carno\Tracing\Contracts\Transport;
use Closure;

class ZipkinHTTP implements Transport
{
    use TransferQueued;

    /**
     * HTTP API endpoint
     */
    private const API = '/api/v2/spans';

    /**
     * batch operating
     */
    private const BATCH_INV = 1500;
    private const BATCH_PACK = 100;
    private const BATCH_STACK = 10000;

    /**
     * @var Address
     */
    private $endpoint = null;

    /**
     * @var Client
     */
    private $client = null;

    /**
     * @var string
     */
    private $daemon = null;

    /**
     * @param Address $endpoint
     */
    public function connect(Address $endpoint) : void
    {
        $this->options(self::BATCH_PACK, self::BATCH_STACK);

        $this->endpoint = $endpoint;

        $this->client = new Client(
            (new HOptions)
                ->setTimeouts(1000)
                ->keepalive(new POptions(1, 10, 1, 1, 90, 30, 0, 1000, 800), "zipkin:{$endpoint}"),
            $endpoint
        );

        $this->daemon = Timer::loop(self::BATCH_INV, function () {
            $this->submitting();
        });
    }

    /**
     * @return Promised
     */
    public function disconnect() : Promised
    {
        Timer::clear($this->daemon);

        $closing = function () {
            $this->client->closed()->pended() && $this->client->close();
        };

        $this->stashed() ? $this->submitting($closing) : $closing();

        return $this->client->closed();
    }

    /**
     * @param string $data
     */
    public function loading(string $data) : void
    {
        $this->stashing($data);
    }

    /**
     * flush queued data
     */
    public function flushing() : void
    {
        if ($this->stashed() >= self::BATCH_PACK) {
            $this->submitting();
        }
    }

    /**
     * really submit to remote
     * @param Closure $then
     */
    private function submitting(Closure $then = null) : void
    {
        $this->spouting(co(function (array $spans) use ($then) {
            $request = new Request(
                'POST',
                new Uri('http', $this->endpoint->host(), $this->endpoint->port(), self::API),
                [
                    'Content-Type' => 'application/json',
                ],
                new Body(sprintf('[%s]', implode(',', $spans)))
            );
            try {
                /**
                 * @var Response $response
                 */
                $response = yield $this->client->perform($request);
                if ($response->getStatusCode() !== 202) {
                    logger('tracing')->notice(
                        'Server not accepting',
                        [
                            'endpoint' => (string)$request->getUri(),
                            'error' => sprintf('#%d->%s', $response->getStatusCode(), (string)$response->getBody()),
                            'payload' => debug() ? (string)$request->getBody() : '[IGNORED]',
                        ]
                    );
                }
            } catch (RequestException | SelectWaitException $e) {
                logger('tracing')->notice(
                    'Posting failed',
                    [
                        'endpoint' => (string)$request->getUri(),
                        'error' => sprintf('%s::%s', get_class($e), $e->getMessage()),
                    ]
                );
            }
            $then && $then();
        }));
    }
}
