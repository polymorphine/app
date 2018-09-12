<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Tests;

use PHPUnit\Framework\TestCase;
use Polymorphine\Http\App;
use Polymorphine\Http\Tests\Doubles\FakeMiddleware;
use Polymorphine\Http\Tests\Doubles\FakeUri;
use Polymorphine\Http\Tests\Fixtures\HeadersState;
use Polymorphine\Http\Tests\Fixtures\ShutdownState;
use Polymorphine\Container\Setup;
use Polymorphine\Container\Exception;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Container\ContainerInterface;

require_once __DIR__ . '/Fixtures/shutdown-functions.php';
require_once __DIR__ . '/Fixtures/header-functions.php';


class AppIntegrationTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(App::class, $this->app());
        $this->assertInstanceOf(RequestHandlerInterface::class, $this->app());
    }

    public function testConfig_ReturnsRegistryInput()
    {
        $app = $this->app();
        $this->assertInstanceOf(Setup\RecordSetup::class, $app->config('test'));
    }

    public function testRoutingContainerIntegration()
    {
        $app      = $this->app(['test' => new Setup\Record\DirectRecord('Hello World!')]);
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
        $this->app([App::ROUTER_ID => new Setup\Record\DirectRecord('Hello World!')]);
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
        $this->assertFalse(getenv(App::DEV_ENVIRONMENT));
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
        putenv(App::DEV_ENVIRONMENT . '=1');
        $this->assertNotFalse(getenv(App::DEV_ENVIRONMENT));
        $this->app();
        $this->assertFalse(is_callable(ShutdownState::$callback));
    }

    private function app(array $records = [])
    {
        return new Doubles\MockedApp($records);
    }

    private function middlewareContextsApp()
    {
        $app = $this->app();
        $app->config('test')->value('MAIN');
        $app->middleware('one')->value(new FakeMiddleware('outerContext'));
        $app->middleware('two')->lazy(function (ContainerInterface $c) {
            return new FakeMiddleware($c->get('one')->inContext ? 'innerContext' : '--- error ---');
        });

        return $app;
    }
}