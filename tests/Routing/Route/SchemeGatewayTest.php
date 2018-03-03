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
use Polymorphine\Http\Routing\Route\SchemeGateway;
use Polymorphine\Http\Tests\Doubles;
use Polymorphine\Http\Tests\Message\Doubles\FakeUri;
use Psr\Http\Message\ResponseInterface;


class SchemeGatewayTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(SchemeGateway::class, $default = $this->route());
        $this->assertInstanceOf(SchemeGateway::class, $https = $this->route('https'));
        $this->assertInstanceOf(SchemeGateway::class, $http = $this->route('http'));

        $this->assertEquals($default, $https);
        $this->assertNotEquals($default, $http);
    }

    public function testNotMatchingScheme_ReturnsNull()
    {
        $this->assertNull($this->route('https')->forward($this->request('http')));
        $this->assertNull($this->route('http')->forward($this->request('https')));
    }

    public function testMatchingScheme_ReturnsForwardedRouteResponse()
    {
        $this->assertInstanceOf(ResponseInterface::class, $this->route('https')->forward($this->request('https')));
        $this->assertInstanceOf(ResponseInterface::class, $this->route('http')->forward($this->request('http')));
    }

    public function testUri_ReturnsUriWithCorrectScheme()
    {
        $subRoute = new Doubles\MockedRoute('default');

        $subRoute->uriScheme = 'http';
        $this->assertSame('https', $this->route('https', $subRoute)->uri()->getScheme());
        $this->assertSame('http', $this->route('http', $subRoute)->uri()->getScheme());

        $subRoute->uriScheme = 'https';
        $this->assertSame('https', $this->route('https', $subRoute)->uri()->getScheme());
        $this->assertSame('http', $this->route('http', $subRoute)->uri()->getScheme());
    }

    public function testGateway_ReturnsRouteWithCorrectSchemeUri()
    {
        $subRoute = new Doubles\MockedRoute('default');

        $subRoute->uriScheme = 'http';
        $this->assertSame('https', $this->route('https', $subRoute)->gateway('some.path')->uri()->getScheme());
        $this->assertSame('http', $this->route('http', $subRoute)->gateway('some.path')->uri()->getScheme());

        $subRoute->uriScheme = 'https';
        $this->assertSame('https', $this->route('https', $subRoute)->gateway('some.path')->uri()->getScheme());
        $this->assertSame('http', $this->route('http', $subRoute)->gateway('some.path')->uri()->getScheme());
    }

    private function route(string $scheme = null, $subRoute = null)
    {
        return ($scheme)
            ? new SchemeGateway($subRoute ?: new Doubles\MockedRoute('default'), $scheme)
            : new SchemeGateway($subRoute ?: new Doubles\MockedRoute('default'));
    }

    private function request($scheme = 'http')
    {
        $request = new Doubles\DummyRequest();
        $request->uri = new FakeUri('example.com', '/foo/bar');
        $request->uri->scheme = $scheme;

        return $request;
    }
}
