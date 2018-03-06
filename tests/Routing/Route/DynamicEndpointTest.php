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

use Polymorphine\Http\Routing\Exception\UriParamsException;
use Polymorphine\Http\Routing\Route;
use Polymorphine\Http\Routing\Route\DynamicEndpoint;
use PHPUnit\Framework\TestCase;
use Polymorphine\Http\Tests\Doubles\DummyRequest;
use Polymorphine\Http\Tests\Doubles\DummyResponse;
use Polymorphine\Http\Tests\Message\Doubles\FakeUri;
use Psr\Http\Message\ResponseInterface;


class DynamicEndpointTest extends TestCase
{
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
        $response = $this
            ->route('/page/{#no}', 'GET', $this->dummyCallback())
            ->forward($this->request('/page/3', 'GET'));
        $this->assertInstanceOf(ResponseInterface::class, $response);

        $response = $this
            ->route('/page/{#no}/{$title}', 'UPDATE', $this->dummyCallback())
            ->forward($this->request('/page/576/foo-bar-45', 'UPDATE'));
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testRequestIsForwardedWithMatchedAttributes()
    {
        $response = $this
            ->route('/page/{#no}', 'GET')
            ->forward($this->request('/page/3', 'GET'));
        $this->assertSame(['no' => '3'], $response->fromRequest->attr);

        $response = $this
            ->route('/page/{#page}/{$title}', 'UPDATE')
            ->forward($this->request('/page/576/foo-bar-45', 'UPDATE'));
        $this->assertSame(['page' => '576', 'title' => 'foo-bar-45'], $response->fromRequest->attr);
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
        $route = $this->route('/user/{#countryId}');
        $this->expectException(UriParamsException::class);
        $route->uri(['Poland']);
    }

    public function testRouteWithoutParametersIsMatched()
    {
        $response = $this
            ->route('/path/only')
            ->forward($this->request('/path/only', 'GET'));
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame([], $response->fromRequest->attr);
    }

    public function testRouteWithoutParametersReturnsUriWithProvidedPath()
    {
        $route = $this->route('/user/name');
        $this->assertSame('/user/name', (string) $route->uri());
    }

    private function route($path = '/', $method = 'GET', $callback = null)
    {
        return new DynamicEndpoint($method, $path, $callback ?: $this->dummyCallback());
    }

    private function dummyCallback()
    {
        return function ($request) {
            $response = new DummyResponse();
            $response->fromRequest = $request;

            return $response;
        };
    }

    private function request($path, $method, $query = '')
    {
        $request = new DummyRequest();
        $request->method = $method;
        $request->uri = new FakeUri('example.com', $path, $query);

        return $request;
    }
}
