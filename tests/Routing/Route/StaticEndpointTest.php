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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Polymorphine\Http\Routing\Exception\GatewayCallException;
use Polymorphine\Http\Routing\Route;
use Polymorphine\Http\Routing\Route\StaticEndpoint;
use Polymorphine\Http\Tests\Doubles\DummyRequest;
use Polymorphine\Http\Tests\Doubles\DummyResponse;
use Polymorphine\Http\Tests\Message\Doubles\FakeUri;


class StaticEndpointTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, $this->route());

        $routeGet = StaticEndpoint::get('/home', $this->dummyCallback());
        $this->assertInstanceOf(Route::class, $routeGet);
        $this->assertEquals($routeGet, $this->route('/home', 'GET', $this->dummyCallback()));

        $routePost = StaticEndpoint::post('/home/path', $this->dummyCallback());
        $this->assertInstanceOf(Route::class, $routePost);
        $this->assertEquals($routePost, $this->route('/home/path', 'POST', $this->dummyCallback()));
        $this->assertNotEquals($routeGet, $this->route('/home/path', 'POST', $this->dummyCallback()));
    }

    public function testNotMatchingRequest_ReturnsNull()
    {
        $route = $this->route('/page/index', 'GET', $this->dummyCallback());
        $this->assertNull($route->forward($this->request('/page/index', 'POST')));
        $this->assertNull($route->forward($this->request('/page', 'GET')));
    }

    public function testMatchingRequest_ReturnsResponse()
    {
        $route = $this->route('/page/index', 'GET', $this->dummyCallback());
        $this->assertInstanceOf(ResponseInterface::class, $route->forward($this->request('/page/index', 'GET')));
        $route = $this->route('/page/index/title', 'UPDATE', $this->dummyCallback());
        $this->assertInstanceOf(ResponseInterface::class, $route->forward($this->request('/page/index/title', 'UPDATE')));
    }

    public function testGatewayCall_ThrowsException()
    {
        $this->expectException(GatewayCallException::class);
        $this->route('/home', 'GET')->gateway('/home');
    }

    public function testUriCallWithNoParams_ReturnsUri()
    {
        $this->assertInstanceOf(UriInterface::class, $this->route('/home')->uri());
        $this->assertInstanceOf(UriInterface::class, $this->route('/index')->uri([]));
    }

    public function testUriCallWithPrototype_ReturnsPrototypeWithPath()
    {
        $proto = new FakeUri('', '/foo/bar');
        $this->assertSame('/foo/bar', $proto->getPath());
        $uri = $this->route('/home/page')->uri([], new FakeUri());
        $this->assertInstanceOf(UriInterface::class, $uri);
        $this->assertSame('/home/page', $uri->getPath());
    }

    public function testQueryParamsAreIgnoredInRequestMatch()
    {
        $route = $this->route('/home/page');
        $request = $this->request('/home/page', 'GET', 'query=foo&params=bar');
        $this->assertInstanceOf(ResponseInterface::class, $route->forward($request));
    }

    public function testUriParamsProduceQueryString()
    {
        $uri = $this->route('/hello/world')->uri(['first' => 'one', 'second' => 'two'], new FakeUri());
        $this->assertSame('first=one&second=two', $uri->getQuery());
    }

    private function route($path = '/', $method = 'GET', $callback = null)
    {
        return new StaticEndpoint($method, $path, $callback ?: $this->dummyCallback());
    }

    private function dummyCallback()
    {
        return function ($request) { return new DummyResponse(); };
    }

    private function request($path, $method, $query = '')
    {
        $request = new DummyRequest();
        $request->method = $method;
        $request->uri = new FakeUri('example.com', $path, $query);

        return $request;
    }
}
