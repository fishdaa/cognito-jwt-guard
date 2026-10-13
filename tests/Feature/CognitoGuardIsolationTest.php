<?php
namespace Fishdaa\CognitoGuard\Tests\Feature;

use Fishdaa\CognitoGuard\CognitoGuard;
use Fishdaa\CognitoGuard\Tests\TestCase;
use Illuminate\Http\Request;

/**
 * Guards a long-lived worker (Laravel Octane / Swoole) against leaking the
 * first request's Request/user into later requests.
 */
class CognitoGuardIsolationTest extends TestCase
{
    public function testGuardIsRebuiltAfterScopedInstancesAreForgotten()
    {
        $first = $this->app->make(CognitoGuard::class);
        $this->assertSame($first, $this->app->make(CognitoGuard::class));

        $this->app->forgetScopedInstances();

        $this->assertNotSame($first, $this->app->make(CognitoGuard::class));
    }

    public function testAuthGuardsCarryTheCurrentRequest()
    {
        $this->app->instance('request', Request::create('/a', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer one']));
        $first = $this->app['auth']->guard('api');

        $this->app->instance('request', Request::create('/b', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer two']));
        $this->app['auth']->forgetGuards();
        $second = $this->app['auth']->guard('api');

        $this->assertNotSame($first, $second);
    }
}
