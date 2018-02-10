<?php

namespace Shudd3r\Http\Tests\Routing\Route;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shudd3r\Http\Src\Routing\Route;
use Shudd3r\Http\Src\Routing\Route\RequestFirewall;
use Shudd3r\Http\Tests\Doubles;
use Shudd3r\Http\Tests\Message\Doubles\FakeUri;


class RequestFirewallTest extends TestCase
{
    private function route($closure = null, $route = null) {
        return new RequestFirewall(
            $closure ?: function (ServerRequestInterface $request) { return (strpos($request->getRequestTarget(), '/foo/bar') === 0); },
            $route ?: new Doubles\MockedRoute('default')
        );
    }

    private function request($path = '/') {
        $request = new Doubles\DummyRequest();
        $request->uri = new FakeUri('example.com', $path);
        return $request;
    }

    public function testInstantiation() {
        $this->assertInstanceOf(Route::class, $this->route());
    }

    public function testNotMatchingPath_ReturnsNull() {
        $route = $this->route(function () { return false; });
        $this->assertNull($route->forward($this->request()));
        $this->assertNull($route->forward($this->request('/bar/foo')));
        $this->assertNull($route->forward($this->request('anything')));
    }

    public function testMatchingPathForwardsRequest() {
        $route = $this->route();
        $this->assertInstanceOf(ResponseInterface::class, $route->forward($this->request('/foo/bar')));
        $this->assertSame('default', $route->forward($this->request('/foo/bar'))->body);
        $route = $this->route(function ($request) { return ($request instanceof Doubles\DummyRequest); });
        $response = $route->forward($this->request('anything'));
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('default', $response->body);
    }

    public function testGatewayCallIsPassedToWrappedRoute() {
        $route = $this->route();
        $this->assertSame('path.forwarded', $route->gateway('path.forwarded')->path);
    }
}
