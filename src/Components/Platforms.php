<?php
/**
 * Platforms initialize
 * User: moyo
 * Date: 13/12/2017
 * Time: 10:20 AM
 */

namespace Carno\Traced\Components;

use Carno\Console\Component;
use Carno\Console\Contracts\Application;
use Carno\Console\Contracts\Bootable;
use Carno\Container\DI;
use Carno\Traced\Contracts\Observer;
use Carno\Traced\Platform\Zipkin;
use Carno\Tracing\Contracts\Platform;

class Platforms extends Component implements Bootable
{
    /**
     * @param Application $app
     */
    public function starting(Application $app) : void
    {
        $zpk = new Zipkin;

        DI::set(Platform::class, $zpk);
        DI::set(Observer::class, $zpk);

        $app->stopping()->add(static function () use ($zpk) {
            return $zpk->leave();
        });
    }
}
