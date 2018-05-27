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

use Polymorphine\Http\Message\Uri;
use Polymorphine\Http\Routing\Exception\GatewayCallException;
use Polymorphine\Http\Routing\Route;
use PHPUnit\Framework\TestCase;
use Polymorphine\Http\Tests\Doubles\FakeServerRequest;
use Polymorphine\Http\Tests\Doubles\FakeResponse;
use Polymorphine\Http\Tests\Doubles\MockedPattern;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;


class PatternEndpointTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, $this->route());

        $route = Route\PatternEndpoint::post('/home/{#id}', $this->dummyCallback());
        $this->assertInstanceOf(Route::class, $route);

        $route = Route\PatternEndpoint::get('http://example.com/home/path', $this->dummyCallback());
        $this->assertInstanceOf(Route::class, $route);
    }

    public function testNotMatchingRequest_ReturnsNull()
    {
        $route = $this->route('/page/3', 'GET', $this->dummyCallback());
        $this->assertNull($route->forward($this->request('/page/3', 'POST')));
        $this->assertNull($route->forward($this->request('/page/4', 'GET')));
    }

    public function testMatchingRequest_ReturnsResponse()
    {
        $response = $this->route('/page/3', 'GET', $this->dummyCallback())
                         ->forward($this->request('/page/3', 'GET'));
        $this->assertInstanceOf(ResponseInterface::class, $response);

        $response = $this->route('/page/576/foo-bar-45', 'UPDATE', $this->dummyCallback())
                         ->forward($this->request('/page/576/foo-bar-45', 'UPDATE'));
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testRequestIsForwardedWithMatchedAttributes()
    {
        $response = $this->route('/page/3', 'GET')
                         ->forward($this->request('/page/3', 'GET'));
        $this->assertSame(['pattern' => 'passed'], $response->fromRequest->attr);

        $response = $this->route('/page/576/foo-bar-45', 'UPDATE')
                         ->forward($this->request('/page/576/foo-bar-45', 'UPDATE'));
        $this->assertSame(['pattern' => 'passed'], $response->fromRequest->attr);
    }

    public function testUri_ReturnsUri()
    {
        $this->assertInstanceOf(UriInterface::class, $this->route('/foo/bar')->uri([], new Uri()));
    }

    public function testGateway_ThrowsException()
    {
        $route = $this->route('//example.com');
        $this->expectException(GatewayCallException::class);
        $route->gateway('route.path');
    }

    private function route($path = '/', $method = 'GET', $callback = null)
    {
        return new Route\PatternEndpoint(
            $method,
            new MockedPattern($path),
            $callback ?: $this->dummyCallback()
        );
    }

    private function dummyCallback()
    {
        return function ($request) {
            $response = new FakeResponse();

            $response->fromRequest = $request;

            return $response;
        };
    }

    private function request($path, $method)
    {
        $request = new FakeServerRequest();

        $request->method = $method;
        $request->uri    = Uri::fromString($path);

        return $request;
    }
}
