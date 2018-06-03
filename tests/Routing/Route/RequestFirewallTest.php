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
use Psr\Http\Message\ServerRequestInterface;
use Polymorphine\Http\Routing\Route;
use Polymorphine\Http\Routing\Route\RequestFirewall;
use Polymorphine\Http\Tests\Doubles;


class RequestFirewallTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Route::class, $this->route());
    }

    public function testNotMatchingPath_ReturnsNull()
    {
        $route = $this->route(function () { return false; });
        $this->assertNull($route->forward($this->request()));
        $this->assertNull($route->forward($this->request('/bar/foo')));
        $this->assertNull($route->forward($this->request('anything')));
    }

    public function testMatchingPathForwardsRequest()
    {
        $route = $this->route();
        $this->assertInstanceOf(ResponseInterface::class, $route->forward($this->request('/foo/bar')));
        $this->assertSame('default', $route->forward($this->request('/foo/bar'))->body);

        $route    = $this->route(function ($request) { return $request instanceof Doubles\FakeServerRequest; });
        $response = $route->forward($this->request('anything'));
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('default', $response->body);
    }

    public function testGatewayCallIsPassedToWrappedRoute()
    {
        $route = $this->route();
        $this->assertSame('path.forwarded', $route->gateway('path.forwarded')->path);
    }

    public function testUriCallIsPassedToWrappedRoute()
    {
        $uri   = 'http://example.com/foo/bar?test=baz';
        $route = $this->route(null, new Doubles\MockedRoute($uri));
        $this->assertSame($uri, (string) $route->uri(new Doubles\FakeUri()));
    }

    private function route($closure = null, $route = null)
    {
        return new RequestFirewall(
            $closure ?: function (ServerRequestInterface $request) { return strpos($request->getRequestTarget(), '/foo/bar') === 0; },
            $route ?: new Doubles\MockedRoute('default')
        );
    }

    private function request($path = '/')
    {
        $request = new Doubles\FakeServerRequest();

        $request->uri = Doubles\FakeUri::fromString('//example.com' . $path);

        return $request;
    }
}
