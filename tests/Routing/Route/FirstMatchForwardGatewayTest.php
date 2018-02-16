<?php

namespace Polymorphine\Http\Tests\Routing\Route;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Polymorphine\Http\Routing\Exception\EndpointCallException;
use Polymorphine\Http\Routing\Exception\GatewayCallException;
use Polymorphine\Http\Routing\Route;
use Polymorphine\Http\Routing\Route\FirstMatchForwardGateway;
use Polymorphine\Http\Tests\Doubles;


class FirstMatchForwardGatewayTest extends TestCase
{
    private function route(array $routes = []) {
        $dummy = new Doubles\MockedRoute('DUMMY');
        $dummy->callback = function () { return null; };
        return new FirstMatchForwardGateway(['example' => $dummy] + $routes);
    }

    public function testInstantiation() {
        $this->assertInstanceOf(Route::class, $this->route());
    }

    public function testForwardingNotMatchingRequest_ReturnsNull() {
        $this->assertNull($this->route()->forward(new Doubles\DummyRequest()));
        $this->assertNull($this->route(['name' => new Doubles\MockedRoute('')])->forward(new Doubles\DummyRequest()));
    }

    public function testForwardingMatchingRequest_ReturnsResponse() {
        $route = new Doubles\MockedRoute('', function () { return new Doubles\DummyResponse(); });
        $route = $this->route(['name' => $route]);
        $this->assertInstanceOf(ResponseInterface::class, $route->forward(new Doubles\DummyRequest()));
    }

    public function testForwardingMatchingRequest_ReturnsCorrectResponse() {
        $routeA = new Doubles\MockedRoute('', function ($request) { return ($request->method === 'POST') ? new Doubles\DummyResponse('A') : null; });
        $routeB = new Doubles\MockedRoute('', function ($request) { return ($request->method === 'GET') ? new Doubles\DummyResponse('B') : null; });
        $route = $this->route(['A' => $routeA, 'B' => $routeB]);
        $requestA = new Doubles\DummyRequest('POST');
        $requestB = new Doubles\DummyRequest('GET');
        $this->assertSame('A', $route->forward($requestA)->body);
        $this->assertSame('B', $route->forward($requestB)->body);
    }

    public function testUriMethod_ThrowsException() {
        $this->expectException(EndpointCallException::class);
        $this->route()->uri();
    }

    public function testGatewayMethodEndpointCall_ReturnsFoundRoute() {
        $routeA = new Doubles\MockedRoute('A');
        $routeB = new Doubles\MockedRoute('B');
        $route = $this->route(['A' => $routeA, 'B' => $routeB]);
        $this->assertSame('A', $route->gateway('A')->id);
        $this->assertSame('B', $route->gateway('B')->id);
    }

    public function testGatewayMethodGatewayCall_AsksNextGateway() {
        $routeA = new Doubles\MockedRoute('A');
        $routeB = new Doubles\MockedRoute('B');
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
