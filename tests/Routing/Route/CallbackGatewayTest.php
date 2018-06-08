<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Tests\Routing\Route;

use PHPUnit\Framework\TestCase;
use Polymorphine\Http\Routing\Route;
use Polymorphine\Http\Routing\Route\CallbackGateway;
use Polymorphine\Http\Tests\Doubles\MockedRoute;
use Polymorphine\Http\Tests\Doubles\FakeServerRequest;
use Polymorphine\Http\Tests\Doubles\FakeResponse;
use Polymorphine\Http\Tests\Doubles\FakeUri;
use Psr\Http\Message\ServerRequestInterface;
use Closure;


class CallbackGatewayTest extends TestCase
{
    private static $notFound;

    public static function setUpBeforeClass()
    {
        self::$notFound = new FakeResponse();
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, $route = $this->middleware());
        $this->assertInstanceOf(CallbackGateway::class, $route);
    }

    public function testClosurePreventsForwardingRequest()
    {
        $request  = new FakeServerRequest();
        $this->assertSame(self::$notFound, $this->middleware()->forward($request, self::$notFound));
    }

    public function testMiddlewareForwardsRequest()
    {
        $request = new FakeServerRequest('POST');
        $this->assertNotSame(self::$notFound, $this->middleware()->forward($request, self::$notFound));
    }

    public function testGatewayCallsRouteWithSameParameter()
    {
        $this->assertInstanceOf(Route::class, $route = $this->middleware()->gateway('some.name'));
        $this->assertSame('some.name', $route->path);
    }

    public function testUriCallIsPassedToWrappedRoute()
    {
        $uri   = 'http://example.com/foo/bar?test=baz';
        $route = new CallbackGateway($this->basicCallback(), new MockedRoute($uri));
        $this->assertSame($uri, (string) $route->uri(new FakeUri(), []));
    }

    private function middleware(Closure $callback = null)
    {
        if (!$callback) {
            $callback = $this->basicCallback();
        }
        return new CallbackGateway($callback, new MockedRoute('default'));
    }

    private function basicCallback()
    {
        return function (ServerRequestInterface $request, Closure $forward) {
            return $request->getMethod() === 'POST' ? $forward($request) : null;
        };
    }
}
