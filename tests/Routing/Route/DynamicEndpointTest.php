<?php

namespace Polymorphine\Http\Tests\Routing\Route;

use Polymorphine\Http\Routing\Exception\UriParamsException;
use Polymorphine\Http\Routing\Route;
use Polymorphine\Http\Routing\Route\DynamicEndpoint;
use PHPUnit\Framework\TestCase;
use Polymorphine\Http\Tests\Doubles\DummyRequest;
use Polymorphine\Http\Tests\Doubles\DummyResponse;
use Polymorphine\Http\Tests\Message\Doubles\FakeUri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


class DynamicEndpointTest extends TestCase
{
    private function route($path = '/', $method = 'GET', $callback = null)
    {
        return new DynamicEndpoint($method, $path, $callback ?: $this->dummyCallback());
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

    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, $this->route());

        $routeGet = DynamicEndpoint::get('/home/{#id}', $this->dummyCallback());
        $routePost = DynamicEndpoint::post('/home/path/{$slug}', $this->dummyCallback());

        $this->assertInstanceOf(Route::class, $routeGet);
        $this->assertEquals($routeGet, $this->route('/home/{#id}', 'GET', $this->dummyCallback()));
        $this->assertNotEquals($routePost, $this->route('/home/{#id}', 'GET', $this->dummyCallback()));

        $this->assertInstanceOf(Route::class, $routePost);
        $this->assertEquals($routePost, $this->route('/home/path/{$slug}', 'POST', $this->dummyCallback()));
        $this->assertNotEquals($routeGet, $this->route('/home/path/{$slug}', 'POST', $this->dummyCallback()));
    }

    public function testNotMatchingRequest_ReturnsNull()
    {
        $route = $this->route('/page/{#no}', 'GET', $this->dummyCallback());
        $this->assertNull($route->forward($this->request('/page/3', 'POST')));
        $this->assertNull($route->forward($this->request('/page', 'GET')));
    }

    public function testMatchingRequest_ReturnsResponse()
    {
        $response = $this->route('/page/{#no}', 'GET', $this->dummyCallback())
                         ->forward($this->request('/page/3', 'GET'));
        $this->assertInstanceOf(ResponseInterface::class, $response);

        $response = $this->route('/page/{#no}/{$title}', 'UPDATE', $this->dummyCallback())
                         ->forward($this->request('/page/576/foo-bar-45', 'UPDATE'));
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testRequestIsForwardedWithMatchedAttributes()
    {
        $callback = function (ServerRequestInterface $request) {
            return new DummyResponse($request->getAttributes());
        };

        $response = $this->route('/page/{#no}', 'GET', $callback)
                         ->forward($this->request('/page/3', 'GET'));
        $this->assertSame(['no' => '3'], $response->body);

        $response = $this->route('/page/{#page}/{$title}', 'UPDATE', $callback)
                         ->forward($this->request('/page/576/foo-bar-45', 'UPDATE'));
        $this->assertSame(['page' => '576', 'title' => 'foo-bar-45'], $response->body);
    }

    public function testUriReplacesProvidedValues()
    {
        $route = $this->route('/page-{#no}/{%title}');
        $this->assertSame('/page-12/something', $route->uri([12, 'something'])->getPath());
        $this->assertSame('/page-852/foobar12', $route->uri([852, 'foobar12'])->getPath());
    }

    public function testUriInsufficientParams_ThrowsException()
    {
        $route = $this->route('/some-{#number}/{$slug}');
        $this->expectException(UriParamsException::class);
        $route->uri([22]);
    }

    public function testUriInvalidTypeParams_ThrowsException()
    {
        $route = $this->route('/user/{#country}/');
        $this->expectException(UriParamsException::class);
        $route->uri(['Poland']);
    }
}
