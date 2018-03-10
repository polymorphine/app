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

        $routeGet = Route\DynamicEndpoint::get('/home/{#id}', $this->dummyCallback());
        $routePost = Route\DynamicEndpoint::post('/home/path/{$slug}', $this->dummyCallback());

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

    public function testQueryStringIsMatched()
    {
        $response = $this
            ->route('/path/and?user={#id}')
            ->forward($this->request('/path/and?user=938', 'GET'));
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(['id' => '938'], $response->fromRequest->attr);

        $response = $this
            ->route('/path/and?user={#id}&foo={$bar}')
            ->forward($this->request('/path/and?user=938&foo=bar-BAZ', 'GET'));
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(['id' => '938', 'bar' => 'bar-BAZ'], $response->fromRequest->attr);
    }

    public function testQueryStringMatchIgnoresParamOrder()
    {
        $response = $this
            ->route('/path/and?user={#id}&foo={$bar}')
            ->forward($this->request('/path/and?foo=bar-BAZ&user=938', 'GET'));
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(['id' => '938', 'bar' => 'bar-BAZ'], $response->fromRequest->attr);
    }

    public function testQueryStringIsIgnoredWhenNotSpecifiedInRoute()
    {
        $response = $this
            ->route('/path/only')
            ->forward($this->request('/path/only?foo=bar-BAZ&user=938', 'GET'));
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame([], $response->fromRequest->attr);
    }

    public function testNotSpecifiedQueryParamsAreIgnored()
    {
        $response = $this
            ->route('/path/only?name={$slug}&user=938')
            ->forward($this->request('/path/only?foo=bar-BAZ&user=938&name=shudd3r', 'GET'));
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(['slug' => 'shudd3r'], $response->fromRequest->attr);
    }

    public function testMissingQueryParamWontForwardRequest()
    {
        $response = $this
            ->route('/path/only?name={$slug}&user=938')
            ->forward($this->request('/path/only?foo=bar-BAZ&name=shudd3r', 'GET'));
        $this->assertNull($response);
    }

    public function testUriQueryParamsAreAssigned()
    {
        $uri = $this->route('/foo/{%bar}?name={$name}&fizz={%buzz}')->uri(['something', 'slug-name', 'BUZZER']);
        $this->assertSame('/foo/something', $uri->getPath());
        $this->assertSame('name=slug-name&fizz=BUZZER', $uri->getQuery());
    }

    public function testUndefinedQueryParamValueIsIgnoredButKeyIsRequired()
    {
        $route = $this->route('/foo/{%bar}?name={$name}&fizz', 'POST');
        $uri = $route->uri(['something', 'slug-string']);
        $request = $this->request('/foo/bar?fizz&name=slug-example', 'POST');

        $this->assertSame('name=slug-string&fizz', $uri->getQuery());
        $this->assertInstanceOf(ResponseInterface::class, $route->forward($request));

        $request = $this->request('/foo/bar?fizz=value&name=slug-example', 'POST');
        $this->assertInstanceOf(ResponseInterface::class, $route->forward($request));
        $this->assertNull($route->forward($this->request('/foo/bar?name=slug-example', 'POST')));
    }


    public function testEmptyQueryParamValueIsRequiredAsSpecified()
    {
        $route = $this->route('/foo/{%bar}?name={$name}&fizz=', 'POST');
        $uri = $route->uri(['something', 'slug-string']);
        $request_emptyValue = $this->request('/foo/bar?fizz=&name=slug-example', 'POST');
        $request_noValue = $this->request('/foo/bar?fizz&name=slug-example', 'POST');
        $request_givenValue = $this->request('/foo/bar?fizz=value&name=slug-example', 'POST');

        $this->assertSame('name=slug-string&fizz=', $uri->getQuery());
        $this->assertInstanceOf(ResponseInterface::class, $route->forward($request_emptyValue));
        $this->assertInstanceOf(ResponseInterface::class, $route->forward($request_noValue));
        $this->assertNull($route->forward($request_givenValue));
    }

    public function testNamedUriParamsCanBePassedOutOfOrder()
    {
        $route = $this->route('/user/{#id}/{%name}');
        $uri_ordered = $route->uri([22, 'shudd3r'], new FakeUri());
        $uri_named = $route->uri(['name' => 'shudd3r', 'id' => 22], new FakeUri());

        $this->assertEquals($uri_ordered, $uri_named);
    }

    private function route($path = '/', $method = 'GET', $callback = null)
    {
        return new Route\DynamicEndpoint(
            $method,
            new Route\Pattern\TargetPattern($path),
            $callback ?: $this->dummyCallback()
        );
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
