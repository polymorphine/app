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
use Polymorphine\Http\Routing\Exception\EndpointCallException;
use Polymorphine\Http\Routing\Route;
use Polymorphine\Http\Routing\Route\MiddlewareGateway;
use Polymorphine\Http\Tests\Doubles\FakeServerRequest;
use Polymorphine\Http\Tests\Doubles\FakeMiddleware;
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
        $request = new FakeServerRequest('POST');
        $this->assertInstanceOf(ResponseInterface::class, $response = $this->middleware()->forward($request));
        $this->assertSame(['Middleware' => 'processed request'], $response->fromRequest->getAttributes());
        $this->assertSame(['Middleware' => 'processed response'], $response->getheaders());
    }

    public function testGatewayCallsRouteWithSameParameter()
    {
        $this->assertInstanceOf(Route::class, $route = $this->middleware()->gateway('some.name'));
        $this->assertSame('some.name', $route->path);
    }

    public function testUri_ThrowsException()
    {
        $this->expectException(EndpointCallException::class);
        $this->middleware()->uri();
    }

    private function middleware()
    {
        return new MiddlewareGateway(new FakeMiddleware(), new MockedRoute('default'));
    }
}
