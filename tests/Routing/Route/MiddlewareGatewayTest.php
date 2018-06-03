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
use Polymorphine\Http\Routing\Route\MiddlewareGateway;
use Polymorphine\Http\Tests\Doubles\FakeServerRequest;
use Polymorphine\Http\Tests\Doubles\FakeMiddleware;
use Polymorphine\Http\Tests\Doubles\FakeUri;
use Polymorphine\Http\Tests\Doubles\MockedRoute;
use Psr\Http\Message\ResponseInterface;


class MiddlewareGatewayTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, $route = $this->middleware());
        $this->assertInstanceOf(MiddlewareGateway::class, $route);
    }

    public function testMiddlewareForwardsRequest()
    {
        $request  = new FakeServerRequest('POST');
        $response = $this->middleware()->forward($request->withAttribute('middleware', 'processed'));

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('processed: wrap response wrap', (string) $response->getBody());
    }

    public function testGatewayCallsRouteWithSameParameter()
    {
        $this->assertInstanceOf(Route::class, $route = $this->middleware()->gateway('some.name'));
        $this->assertSame('some.name', $route->path);
    }

    public function testUriCallIsPassedToWrappedRoute()
    {
        $uri   = 'http://example.com/foo/bar?test=baz';
        $route = new MiddlewareGateway(new FakeMiddleware('wrap'), new MockedRoute($uri));
        $this->assertSame($uri, (string) $route->uri(new FakeUri()));
    }

    private function middleware()
    {
        return new MiddlewareGateway(new FakeMiddleware('wrap'), new MockedRoute('response'));
    }
}
