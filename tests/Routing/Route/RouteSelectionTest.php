<?php

namespace Shudd3r\Http\Tests\Routing\Route;

use Psr\Http\Message\ResponseInterface;
use Shudd3r\Http\Src\Routing\Exception\EndpointCallException;
use Shudd3r\Http\Src\Routing\Exception\GatewayCallException;
use Shudd3r\Http\Src\Routing\Route;
use Shudd3r\Http\Src\Routing\Route\FirstMatchForwardGateway;
use PHPUnit\Framework\TestCase;
use Shudd3r\Http\Tests\Doubles\DummyRequest;
use Shudd3r\Http\Tests\Doubles\DummyResponse;
use Shudd3r\Http\Tests\Doubles\MockedRoute;


class RouteSelectionTest extends TestCase
{
    private function route(array $routes = []) {
        $dummy = new MockedRoute();
        $dummy->callback = function () { return null; };
        $dummy->id = 'DUMMY';
        return new FirstMatchForwardGateway(['example' => $dummy] + $routes);
    }

    public function testInstantiation() {
        $this->assertInstanceOf(Route::class, $this->route());
    }

    public function testForwardingNotMatchingRequest_ReturnsNull() {
        $this->assertNull($this->route()->forward(new DummyRequest()));
        $this->assertNull($this->route(['name' => new MockedRoute()])->forward(new DummyRequest()));
    }

    public function testForwardingMatchingRequest_ReturnsResponse() {
        $route = new MockedRoute();
        $route->callback = function () { return new DummyResponse(); };
        $route = $this->route(['name' => $route]);
        $this->assertInstanceOf(ResponseInterface::class, $route->forward(new DummyRequest()));
    }

    public function testForwardingMatchingRequest_ReturnsCorrectResponse() {
        $routeA = new MockedRoute();
        $routeA->callback = function ($request) { return ($request->method === 'POST') ? new DummyResponse('A') : null; };
        $routeB = new MockedRoute();
        $routeB->callback = function ($request) { return ($request->method === 'GET') ? new DummyResponse('B') : null; };
        $route = $this->route(['A' => $routeA, 'B' => $routeB]);
        $requestA = new DummyRequest();
        $requestA->method = 'POST';
        $requestB = new DummyRequest();
        $requestB->method = 'GET';
        $this->assertSame('A', $route->forward($requestA)->body);
        $this->assertSame('B', $route->forward($requestB)->body);
    }

    public function testUriMethod_ThrowsException() {
        $this->expectException(EndpointCallException::class);
        $this->route()->uri();
    }

    public function testGatewayMethodEndpointCall_ReturnsFoundRoute() {
        $routeA = new MockedRoute();
        $routeA->id = 'A';
        $routeB = new MockedRoute();
        $routeB->id = 'B';
        $route = $this->route(['A' => $routeA, 'B' => $routeB]);
        $this->assertSame('A', $route->gateway('A')->id);
        $this->assertSame('B', $route->gateway('B')->id);
    }

    public function testGatewayMethodGatewayCall_AsksNextGateway() {
        $routeA = new MockedRoute();
        $routeA->id = 'A';
        $routeB = new MockedRoute();
        $routeB->id = 'B';
        $route = $this->route(['AFound' => $routeA, 'BFound' => $routeB]);
        $this->assertSame('PathA', $route->gateway('AFound.PathA')->path);
        $this->assertSame('PathB', $route->gateway('BFound.PathB')->path);
    }

    public function testGatewayWithEmptyPath_ThrowsException() {
        $this->expectException(GatewayCallException::class);
        $this->route()->gateway('');
    }

    public function testGatewayWithUnknownName_ThrowsException() {
        $this->assertInstanceOf(Route::class, $this->route()->gateway('example'));
        $this->expectException(GatewayCallException::class);
        $this->route()->gateway('NotDefined');
    }
}
