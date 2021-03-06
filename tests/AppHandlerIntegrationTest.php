<?php declare(strict_types=1);

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
use Polymorphine\Container;

require_once __DIR__ . '/Fixtures/shutdown-functions.php';
require_once __DIR__ . '/Fixtures/header-functions.php';


class AppHandlerIntegrationTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(AppHandler::class, $this->app());
    }

    public function testConfig_ReturnsSetupEntry()
    {
        $app = $this->app();
        $this->assertInstanceOf(Container\Setup\Entry::class, $app->config('test'));
    }

    public function testRoutingContainerIntegration()
    {
        $app      = $this->app(['test' => new Container\Records\Record\ValueRecord('Hello World!')]);
        $response = $app->handle(new Doubles\FakeServerRequest());
        $this->assertSame('//example.com/foo/bar: Hello World!', (string) $response->getBody());
    }

    public function testRepeatedHandleCallsWithMiddlewareProcessing_ReturnsEqualResponse()
    {
        $app      = $this->middlewareContextsApp();
        $request  = new Doubles\FakeServerRequest('GET', Doubles\FakeUri::fromString('/test'));
        $response = $app->handle($request);

        $expectedBody = 'outerContext innerContext /test: MAIN innerContext outerContext';
        $this->assertSame($expectedBody, (string) $response->getBody());
        $this->assertEquals($response, $app->handle($request));
    }

    public function testInstanceWithDefinedInternalContainerId_ThrowsException()
    {
        $this->expectException(Container\Setup\Exception\OverwriteRuleException::class);
        $this->app([AppHandler::ROUTER_ID => new Container\Records\Record\ValueRecord('Hello World!')]);
    }

    public function testFallbackNotFoundRoute()
    {
        $app = $this->app();
        $app->routeFound       = false;
        $app->notFoundResponse = new Doubles\FakeResponse();

        $response = $app->handle(new Doubles\FakeServerRequest());
        $this->assertInstanceOf(Doubles\FakeResponse::class, $response);
        $this->assertSame($app->notFoundResponse, $response);
    }

    public function testShutdownRegisteredOnProduction()
    {
        Fixtures\ShutdownState::reset();
        Fixtures\ShutdownState::$override = true;
        $this->assertFalse(getenv(AppHandler::DEV_ENVIRONMENT));
        $this->app();
        $this->assertTrue(is_callable($callback = Fixtures\ShutdownState::$callback));
        $callback();
        $this->assertSame(503, Fixtures\ShutdownState::$status);
        $this->assertSame([], Fixtures\HeadersState::$headers);
        $this->assertTrue(Fixtures\ShutdownState::$outputBufferCleared);
    }

    public function testShutdownNotRegisteredOnDevelopment()
    {
        Fixtures\ShutdownState::reset();
        Fixtures\ShutdownState::$override = true;
        putenv(AppHandler::DEV_ENVIRONMENT . '=1');
        $this->assertNotFalse(getenv(AppHandler::DEV_ENVIRONMENT));
        $this->app();
        $this->assertFalse(is_callable(Fixtures\ShutdownState::$callback));
    }

    private function app(array $records = [], bool $secure = false): Doubles\MockedAppHandler
    {
        $setup = $secure ? new Container\Setup\Build\ValidatedBuild($records) : new Container\Setup\Build($records);
        return new Doubles\MockedAppHandler($setup);
    }

    private function middlewareContextsApp(): Doubles\MockedAppHandler
    {
        $app = $this->app();
        $app->config('test')->value('MAIN');
        $app->middleware('one')->value(new Doubles\FakeMiddleware('outerContext'));
        $app->middleware('two')->callback(function ($c) {
            return new Doubles\FakeMiddleware($c->get('one')->inContext ? 'innerContext' : '--- error ---');
        });

        return $app;
    }
}
