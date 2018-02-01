<?php

namespace Shudd3r\Http\Tests\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Shudd3r\Http\Src\Routing\Exception\GatewayCallException;
use Shudd3r\Http\Src\Routing\Route;
use Shudd3r\Http\Src\Routing\StaticEndpoint;
use PHPUnit\Framework\TestCase;
use Shudd3r\Http\Tests\Doubles\DummyRequest;
use Shudd3r\Http\Tests\Doubles\DummyResponse;
use Shudd3r\Http\Tests\Message\Doubles\FakeUri;


class StaticEndpointTest extends TestCase
{
    private function route($path = '/', $method = 'GET', $callback = null) {
        return new StaticEndpoint($method, $path, $callback ?: $this->dummyCallback());
    }

    private function dummyCallback() {
        return function ($request) { return new DummyResponse(); };
    }

    private function request($path, $method) {
        $request = new DummyRequest();
        $request->method = $method;
        $request->uri = new FakeUri('example.com', $path);
        return $request;
    }

    public function testInstantiation() {
        $this->assertInstanceOf(Route::class, $this->route());

        $routeGet  = StaticEndpoint::get('/home', $this->dummyCallback());
        $this->assertInstanceOf(Route::class, $routeGet);
        $this->assertEquals($routeGet, $this->route('/home', 'GET', $this->dummyCallback()));

        $routePost = StaticEndpoint::post('/home/path', $this->dummyCallback());
        $this->assertInstanceOf(Route::class, $routePost);
        $this->assertEquals($routePost, $this->route('/home/path', 'POST', $this->dummyCallback()));
        $this->assertNotEquals($routeGet, $this->route('/home/path', 'POST', $this->dummyCallback()));
    }

    public function testNotMatchingRequest_ReturnsNull() {
        $route = $this->route('/page/index', 'GET', $this->dummyCallback());
        $this->assertNull($route->forward($this->request('/page/index', 'POST')));
        $this->assertNull($route->forward($this->request('/page', 'GET')));
    }

    public function testMatchingRequest_ReturnsResponse() {
        $route = $this->route('/page/index', 'GET', $this->dummyCallback());
        $this->assertInstanceOf(ResponseInterface::class, $route->forward($this->request('/page/index', 'GET')));
        $route = $this->route('/page/index/title', 'UPDATE', $this->dummyCallback());
        $this->assertInstanceOf(ResponseInterface::class, $route->forward($this->request('/page/index/title', 'UPDATE')));
    }

    public function testGatewayCall_ThrowsException() {
        $this->expectException(GatewayCallException::class);
        $this->route('/home', 'GET')->gateway('/home');
    }

    public function testUriCallWithNoParams_ReturnsUri() {
        $this->assertInstanceOf(UriInterface::class, $this->route('/home')->uri());
        $this->assertInstanceOf(UriInterface::class, $this->route('/index')->uri([]));
    }

    public function testUriCallWithPrototype_ReturnsPrototypeWithPath() {
        $proto = new FakeUri('', '/foo/bar');
        $this->assertSame('/foo/bar', $proto->getPath());
        $uri = $this->route('/home/page')->uri([], new FakeUri());
        $this->assertInstanceOf(UriInterface::class, $uri);
        $this->assertSame('/home/page', $uri->getPath());
    }

    //TODO: uri produces query string from unnecessary params
}
