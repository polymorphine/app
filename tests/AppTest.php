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
use Polymorphine\Container\Exception\InvalidIdException;
use Polymorphine\Http\Message\Uri;
use Polymorphine\Http\Tests\Doubles\FakeMiddleware;
use Polymorphine\Http\Tests\Fixtures\HeadersState;
use Polymorphine\Http\Tests\Fixtures\ShutdownState;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Polymorphine\Http\App;
use Polymorphine\Container\Setup;
use Polymorphine\Http\Message\Response\NotFoundResponse;
use Psr\Http\Server\RequestHandlerInterface;

require_once __DIR__ . '/Fixtures/shutdown-functions.php';
require_once __DIR__ . '/Fixtures/header-functions.php';


class AppTest extends TestCase
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
        $app = $this->app([
            'test' => new Setup\Record\DirectRecord('Hello World!')
        ]);
        $app->routeFound = true;

        $response = $app->handle(new Doubles\FakeServerRequest());
        $this->assertSame('//example.com/foo/bar: Hello World!', $response->body);
    }

    public function testRepeatedMiddlewareQueueProcessing()
    {
        $app = $this->middlewareContextsApp();

        $expectedBody = 'outerContext innerContext /test: MAIN innerContext outerContext';
        $responseBody = $app->handle(new Doubles\FakeServerRequest('GET', Uri::fromString('/test')))->getBody();
        $this->assertSame($expectedBody, (string) $responseBody);

        //Middleware processing is handled by recursive calls
        //Queue should be restored after each processing
        $responseBody = $app->handle(new Doubles\FakeServerRequest('GET', Uri::fromString('/test')))->getBody();
        $this->assertSame($expectedBody, (string) $responseBody);
    }

    public function testInstanceWithDefinedInternalContainerId_ThrowsException()
    {
        $this->expectException(InvalidIdException::class);
        $this->app([App::ROUTER_ID => new Setup\Record\DirectRecord('Hello World!')]);
    }

    public function testFallbackNotFoundRoute()
    {
        $app                 = $this->app();
        $app->routeFound     = false;
        $app->overrideParent = false;

        $response = $app->handle(new Doubles\FakeServerRequest());
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertInstanceOf(NotFoundResponse::class, $response);

        $app                 = $this->app();
        $app->routeFound     = false;
        $app->overrideParent = true;

        $response = $app->handle(new Doubles\FakeServerRequest());
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertInstanceOf(Doubles\FakeResponse::class, $response);
        $this->assertSame('Not Found', $response->body);
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

        $app->routeFound = true;
        $app->config('test')->value('MAIN');
        $app->middleware('one')->value(new FakeMiddleware('outerContext'));
        $app->middleware('two')->lazy(function (ContainerInterface $c) {
            return new FakeMiddleware($c->get('one')->inContext ? 'innerContext' : '--- error ---');
        });

        return $app;
    }
}
