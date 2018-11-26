<?php
/**
 * Zipkin json format v2
 * User: moyo
 * Date: 24/11/2017
 * Time: 11:21 AM
 */

namespace Carno\Traced\Protocol;

use Carno\Tracing\Contracts\Protocol;
use Carno\Tracing\Contracts\Vars\CTX;
use Carno\Tracing\Contracts\Vars\EXT;
use Carno\Tracing\Contracts\Vars\TAG;
use Carno\Tracing\Standard\Endpoint;
use Carno\Tracing\Standard\Span;

class ZipkinJFV2 implements Protocol
{
    /**
     * @param Span $span
     * @return string
     */
    public function serialize(Span $span) : string
    {
        $data = [
            'traceId' => $span->getBaggageItem(CTX::TRACE_ID),
            'id' => $span->getBaggageItem(CTX::SPAN_ID),
            'name' => $span->getOperationName(),
        ];

        $parentID = $span->getBaggageItem(CTX::PARENT_SPAN_ID);
        if ($parentID) {
            $data['parentId'] = str_pad($parentID, 16, '0', STR_PAD_LEFT);
        }

        $data['timestamp'] = $startTime = $span->getStartTime();
        $finishTime = $span->getFinishTime();
        if ($finishTime) {
            $data['duration'] = $finishTime - $startTime;
        }

        $tags = $span->getTags();

        $this->extractKind($tags, $data);
        $this->extractEndpoint(EXT::LOCAL_ENDPOINT, 'localEndpoint', $tags, $data);
        $this->extractEndpoint(EXT::REMOTE_ENDPOINT, 'remoteEndpoint', $tags, $data);

        array_walk($tags, function (string &$v) {
            // values type convert .. do nothing
        });

        $tags && $data['tags'] = $tags;

        if ($logs = $span->getLogs()) {
            foreach ($logs as $log) {
                list($timestamp, $fields) = $log;

                $items = [];
                array_walk($fields, function ($v, $k) use (&$items) {
                    $items[] = "{$k}={$v}";
                });

                $data['annotations'][] = [
                    'timestamp' => $timestamp,
                    'value' => implode(' ', $items),
                ];
            }
        }

        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param array $tags
     * @param array $data
     */
    private function extractKind(array &$tags, array &$data) : void
    {
        if (isset($tags[TAG::SPAN_KIND])) {
            $data['kind'] = strtoupper($tags[TAG::SPAN_KIND]);
            unset($tags[TAG::SPAN_KIND]);
        }
    }

    /**
     * @param string $from
     * @param string $to
     * @param array $tags
     * @param array $data
     */
    private function extractEndpoint(string $from, string $to, array &$tags, array &$data) : void
    {
        if (isset($tags[$from])) {
            /**
             * @var Endpoint $ep
             */
            $ep = $tags[$from];
            $data[$to] = [
                'serviceName' => $ep->service(),
                'ipv4' => $ep->ipv4(),
                'port' => $ep->port(),
            ];
            unset($tags[$from]);
        }
    }
}
