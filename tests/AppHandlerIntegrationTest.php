<?php

/*
 * This file is part of Polymorphine/App package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\App\Tests;

use PHPUnit\Framework\TestCase;
use Polymorphine\App\AppHandler;
use Polymorphine\App\Tests\Doubles\FakeMiddleware;
use Polymorphine\App\Tests\Doubles\FakeUri;
use Polymorphine\App\Tests\Fixtures\HeadersState;
use Polymorphine\App\Tests\Fixtures\ShutdownState;
use Polymorphine\Container;
use Polymorphine\Container\Exception;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Container\ContainerInterface;

require_once __DIR__ . '/Fixtures/shutdown-functions.php';
require_once __DIR__ . '/Fixtures/header-functions.php';


class AppHandlerIntegrationTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(AppHandler::class, $this->app());
        $this->assertInstanceOf(RequestHandlerInterface::class, $this->app());
    }

    public function testConfig_ReturnsRegistryInput()
    {
        $app = $this->app();
        $this->assertInstanceOf(Container\RecordSetup::class, $app->config('test'));
    }

    public function testRoutingContainerIntegration()
    {
        $app      = $this->app(['test' => new Container\Record\ValueRecord('Hello World!')]);
        $response = $app->handle(new Doubles\FakeServerRequest());
        $this->assertSame('//example.com/foo/bar: Hello World!', $response->body);
    }

    public function testRepeatedHandleCallsWithMiddlewareProcessing_ReturnsEqualResponse()
    {
        $app      = $this->middlewareContextsApp();
        $request  = new Doubles\FakeServerRequest('GET', FakeUri::fromString('/test'));
        $response = $app->handle($request);

        $expectedBody = 'outerContext innerContext /test: MAIN innerContext outerContext';
        $this->assertSame($expectedBody, (string) $response->getBody());
        $this->assertEquals($response, $app->handle($request));
    }

    public function testInstanceWithDefinedInternalContainerId_ThrowsException()
    {
        $this->expectException(Exception\InvalidIdException::class);
        $this->app([AppHandler::ROUTER_ID => new Container\Record\ValueRecord('Hello World!')]);
    }

    public function testFallbackNotFoundRoute()
    {
        $app = $this->app();
        $app->routeFound       = false;
        $app->notFoundResponse = new Doubles\FakeResponse();

        $response = $app->handle(new Doubles\FakeServerRequest());
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertInstanceOf(Doubles\FakeResponse::class, $response);
        $this->assertSame($app->notFoundResponse, $response);
    }

    public function testShutdownRegisteredOnProduction()
    {
        ShutdownState::reset();
        ShutdownState::$override = true;
        $this->assertFalse(getenv(AppHandler::DEV_ENVIRONMENT));
        $this->app();
        $this->assertTrue(is_callable($callback = ShutdownState::$callback));
        $callback();
        $this->assertSame(503, ShutdownState::$status);
        $this->assertSame([], HeadersState::$headers);
        $this->assertTrue(ShutdownState::$outputBufferCleared);
    }

    public function testShutdownNotRegisteredOnDevelopment()
    {
        ShutdownState::reset();
        ShutdownState::$override = true;
        putenv(AppHandler::DEV_ENVIRONMENT . '=1');
        $this->assertNotFalse(getenv(AppHandler::DEV_ENVIRONMENT));
        $this->app();
        $this->assertFalse(is_callable(ShutdownState::$callback));
    }

    private function app(array $records = [], bool $secure = false)
    {
        $setup = $secure ? new Container\TrackingContainerSetup($records) : new Container\ContainerSetup($records);
        return new Doubles\MockedAppHandler($setup);
    }

    private function middlewareContextsApp()
    {
        $app = $this->app();
        $app->config('test')->set('MAIN');
        $app->middleware('one')->set(new FakeMiddleware('outerContext'));
        $app->middleware('two')->invoke(function (ContainerInterface $c) {
            return new FakeMiddleware($c->get('one')->inContext ? 'innerContext' : '--- error ---');
        });

        return $app;
    }
}
