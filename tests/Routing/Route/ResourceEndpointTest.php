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
use Polymorphine\Http\Message\Uri;
use Polymorphine\Http\Routing\Exception\UnreachableEndpointException;
use Polymorphine\Http\Routing\Exception\UriParamsException;
use Polymorphine\Http\Routing\Route;
use Polymorphine\Http\Routing\Route\ResourceEndpoint;
use Polymorphine\Http\Tests\Doubles\DummyRequest;
use Polymorphine\Http\Tests\Doubles\DummyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


class ResourceEndpointTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, $resource = $this->resource('/some/path'));
        $this->assertInstanceOf(ResourceEndpoint::class, $resource);
    }

    /**
     * @dataProvider notMatchingRequests
     *
     * @param ServerRequestInterface $request
     * @param Route                  $resource
     */
    public function testNotMatchingRequest_ReturnsNull(ServerRequestInterface $request, Route $resource)
    {
        $this->assertNull($resource->forward($request));
    }

    public function notMatchingRequests()
    {
        return [
            'method not allowed' => [$this->request('/foo/bar', 'POST'), $this->resource('/foo/bar', ['GET', 'INDEX'])],
            'methods other than POST or GET require resource id request' => [$this->request('/foo/bar', 'DOIT'), $this->resource('/foo/bar', ['DOIT', 'INDEX'])],
            'path not match' => [$this->request('/foo/something', 'GET'), $this->resource('/foo/bar')],
            'cannot post to id' => [$this->request('/foo/bar/123', 'POST'), $this->resource('/foo/bar')],
            'invalid id' => [$this->request('/foo/bar/a8b3ccf0', 'GET'), $this->resource('/foo/bar', ['GET'])],
            'resource list (without id) handler is defined by route INDEX pseudo-method' => [$this->request('/foo/bar', 'GET'), $this->resource('/foo/bar', ['GET'])],
            'relative resource path is not substring of request path' => [$this->request('/foo/bar'), $this->resource('baz')],
            'no resource id' => [$this->request('/some/path/foo'), $this->resource('some/path')],
            'not a path segment' => [$this->request('/some/path/666'), $this->resource('me/path')]
        ];
    }

    /**
     * @dataProvider matchingRequests
     *
     * @param ServerRequestInterface $request
     * @param Route                  $resource
     */
    public function testMatchingRequest_ReturnsResponse(ServerRequestInterface $request, Route $resource)
    {
        $this->assertInstanceOf(ResponseInterface::class, $resource->forward($request));
    }

    public function matchingRequests()
    {
        return [
            [$this->request('/foo/bar', 'POST'), $this->resource('/foo/bar', ['POST', 'PUT'])],
            [$this->request('/foo/bar', 'GET'), $this->resource('/foo/bar', ['INDEX', 'POST'])],
            [$this->request('/foo/bar/7645', 'GET'), $this->resource('/foo/bar', ['GET'])],
            [$this->request('/foo/bar/7645/slug-name', 'PUT'), $this->resource('/foo/bar', ['PUT'])],
            [$this->request('/foo/bar/7645/some-string-300', 'ANYTHING'), $this->resource('/foo/bar', ['ANYTHING'])],
            [$this->request('/foo/bar/baz'), $this->resource('bar/baz')],
            [$this->request('/foo/bar/baz/600'), $this->resource('bar/baz')],
            [$this->request('/some/path/500/slug-string-1000'), $this->resource('some/path')]
        ];
    }

    public function testMatchingIdRequestIsForwardedWithIdAttribute()
    {
        $response = $this->resource('/foo')->forward($this->request('/foo/345'));
        $this->assertSame(['id' => '345'], $response->fromRequest->getAttributes());

        $response = $this->resource('/foo', ['PATCH'])->forward($this->request('/foo/666/slug/3000', 'PATCH'));
        $this->assertSame(['id' => '666'], $response->fromRequest->getAttributes());

        $response = $this->resource('baz')->forward($this->request('/foo/bar/baz/554', 'PATCH'));
        $this->assertSame(['id' => '554'], $response->fromRequest->getAttributes());

        $response = $this->resource('some/path')->forward($this->request('/some/path/500/slug-string-1000', 'PATCH'));
        $this->assertSame(['id' => '500'], $response->fromRequest->getAttributes());
    }

    public function testUriMethod_ReturnsUriWithPath()
    {
        $resource = $this->resource('/foo/bar');
        $this->assertSame('/foo/bar', (string) $resource->uri());

        $uri = Uri::fromString('http://example.com:9000?query=string');
        $this->assertSame('http://example.com:9000/foo/bar?query=string', (string) $resource->uri([], $uri));
    }

    public function testUriMethodWithIdParam_ReturnsUriWithIdPath()
    {
        $resource = $this->resource('/some/path');
        $this->assertSame('/some/path/239', (string) $resource->uri([239]));

        $uri = Uri::fromString('http://example.com:9000?query=string');
        $this->assertSame('http://example.com:9000/some/path/300?query=string', (string) $resource->uri(['id' => 300], $uri));
    }

    public function testUriWithInvalidIdParam_ThrowsException()
    {
        $this->expectException(UriParamsException::class);
        $this->resource('/path/to/resource')->uri(['id' => '08ab']);
    }

    public function testUriPrototypeWithDefinedPath_ThrowsException()
    {
        $this->expectException(UnreachableEndpointException::class);
        $this->resource('/foo/bar')->uri([], Uri::fromString('/other/path'));
    }

    public function testUriForRelativePathWithoutPrototypePath_throwsException()
    {
        $resource = $this->resource('bar/baz');
        $this->expectException(UnreachableEndpointException::class);
        $resource->uri([], Uri::fromString('http://example.com'));
    }

    public function testUriForRelativePath_ReturnsUriWithPathAppendedToPrototype()
    {
        $resource = $this->resource('bar/baz');
        $uri = $resource->uri(['id'=> '3456'], Uri::fromString('http://example.com/'));
        $this->assertSame('http://example.com/bar/baz/3456', (string) $uri);
    }

    private function resource(string $path, array $methods = ['INDEX', 'POST', 'GET', 'PUT', 'PATCH', 'DELETE'])
    {
        $handlers = [];
        foreach ($methods as $method) {
            $handlers[$method] = $this->dummyCallback();
        }

        return new ResourceEndpoint($path, $handlers);
    }

    private function dummyCallback()
    {
        return function ($request) {
            $response = new DummyResponse();
            $response->fromRequest = $request;

            return $response;
        };
    }

    private function request($path, $method = null)
    {
        $request = new DummyRequest();
        $request->method = $method ?? 'GET';
        $request->uri = Uri::fromString($path);

        return $request;
    }
}
