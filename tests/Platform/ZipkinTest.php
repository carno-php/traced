<?php
/**
 * Zipkin Test
 * User: moyo
 * Date: Jul 26, 2019
 * Time: 16:16
 */

namespace Carno\Traced\Tests\Platform;

use Carno\Console\App;
use Carno\Container\DI;
use Carno\Traced\Components\Platforms;
use Carno\Traced\Protocol\ZipkinJFV2;
use Carno\Traced\Transport\UDPRelays;
use Carno\Tracing\Contracts\Platform;
use PHPUnit\Framework\TestCase;

class ZipkinTest extends TestCase
{
    public function testUDPRelays()
    {
        ($com = new Platforms())->starting($app = new App());

        $app->starting()->perform();

        $app->conf()->set('tracing.addr', 'zk-udp://127.0.0.1:1234/zipkin');

        /**
         * @var Platform $zipkin
         */
        $zipkin = DI::get(Platform::class);

        $this->assertEquals(true, $zipkin->joined());

        $this->assertEquals(ZipkinJFV2::class, get_class($zipkin->serializer()));
        $this->assertEquals(UDPRelays::class, get_class($zipkin->transporter()));

        $app->conf()->set('tracing.addr', 'dev://null');

        $this->assertEquals(false, $zipkin->joined());
    }
}
