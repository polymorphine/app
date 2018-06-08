<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Tests\Routing;

use PHPUnit\Framework\TestCase;
use Polymorphine\Http\Routing\Router;
use Polymorphine\Http\Tests\Doubles\MockedRoute;
use Polymorphine\Http\Tests\Doubles\FakeServerRequest;
use Polymorphine\Http\Tests\Doubles\FakeResponse;
use Polymorphine\Http\Tests\Doubles\FakeUri;
use Psr\Http\Message\UriInterface;


class RouterTest extends TestCase
{
    private static $notFound;

    public static function setUpBeforeClass()
    {
        self::$notFound = new FakeResponse();
    }

    private function router(bool $matched = true, UriInterface $uri = null)
    {
        return new Router(
            new MockedRoute($matched ? 'matched' : ''),
            $uri ?? new FakeUri(),
            self::$notFound
        );
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf(Router::class, $this->router());
    }

    public function testNotMatchedRequestDispatch_ReturnsNotFoundResponseInstance()
    {
        $router = $this->router(false);
        $this->assertSame(self::$notFound, $router->dispatch(new FakeServerRequest()));
    }

    public function testMatchingRequestDispatch_ReturnsEndpointResponse()
    {
        $router = $this->router(true);
        $this->assertNotSame(self::$notFound, $router->dispatch(new FakeServerRequest()));
        $this->assertSame('matched', $router->dispatch(new FakeServerRequest())->body);
    }

    public function testUri_ReturnsUriBasedOnDefault()
    {
        $router = $this->router(false, $uri = new FakeUri());
        $this->assertSame($uri, $router->uri('anything'));

        $router = $this->router(true, $uri);
        $this->assertNotSame($uri, $response = $router->uri('anything'));
        $this->assertEquals($uri->withPath('matched'), $response);
    }

    public function testRouteMethod_ReturnsRouterInstanceWithNewRootRoute()
    {
        $router = new Router(
            $route = new MockedRoute('matched'),
            $uri ?? new FakeUri(),
            self::$notFound
        );

        $route->path = 'root.context';

        $router = $router->route('new.context');
        $this->assertInstanceOf(Router::class, $router);
        $this->assertSame('new.context', $route->path);
    }
}
