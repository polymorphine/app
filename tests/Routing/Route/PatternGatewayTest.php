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
use Polymorphine\Http\Routing\Route\Pattern\StaticUriMask;
use Polymorphine\Http\Routing\Route\PatternGateway;
use Polymorphine\Http\Tests\Doubles;
use Psr\Http\Message\ResponseInterface;


class PatternGatewayTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(PatternGateway::class, $default = $this->staticGate());
        $this->assertInstanceOf(PatternGateway::class, $https = $this->staticGate('https:'));
        $this->assertInstanceOf(PatternGateway::class, $http = $this->staticGate('http:'));

        $this->assertEquals($default, $https);
        $this->assertNotEquals($default, $http);
    }

    public function testNotMatchingPattern_ReturnsNull()
    {
        $this->assertNull($this->staticGate('https:/some/path')->forward($this->request('http:/some/path')));
        $this->assertNull($this->staticGate('example.com/foo/bar')->forward($this->request('http://example.com/foo/baz')));
    }

    public function testMatchingPattern_ReturnsForwardedRouteResponse()
    {
        $this->assertInstanceOf(ResponseInterface::class, $this->staticGate('//example.com')->forward($this->request('http://example.com/some/path')));
        $this->assertInstanceOf(ResponseInterface::class, $this->staticGate('http:?query=string')->forward($this->request('http://www.example.com?query=string')));
    }

    public function testUri_ReturnsUriWithPatternDefinedSegments()
    {
        $subRoute = new Doubles\MockedRoute('/foo/bar');

        $uri = $this->staticGate('https:?some=query', $subRoute)->uri();
        $this->assertSame('https', $uri->getScheme());
        $this->assertSame('', $uri->getHost());
        $this->assertSame('/foo/bar', $uri->getPath());
        $this->assertSame('some=query', $uri->getQuery());

        $uri = $this->staticGate('//example.com', $subRoute)->uri();
        $this->assertSame('', $uri->getScheme());
        $this->assertSame('example.com', $uri->getHost());
        $this->assertSame('/foo/bar', $uri->getPath());
    }

    public function testGateway_ReturnsRouteProducingUriWithDefinedSegments()
    {
        $subRoute = new Doubles\MockedRoute('/foo/bar');

        $this->assertSame('https://example.com/foo/bar', (string) $this->staticGate('https://example.com', $subRoute)->gateway('some.path')->uri());
        $this->assertSame('http:/foo/bar', (string) $this->staticGate('http:', $subRoute)->gateway('some.path')->uri());
    }

    public function testComposedGateway_ReturnsRouteProducingUriWithDefinedSegments()
    {
        $subRoute = $this->staticGate('//example.com', new Doubles\MockedRoute('/foo/bar'));

        $this->assertSame('https://example.com/foo/bar', (string) $this->staticGate('https:', $subRoute)->gateway('some.path')->uri());
    }

    private function staticGate(string $uriPattern = 'https:', $subRoute = null)
    {
        return new PatternGateway(
            StaticUriMask::fromUriString($uriPattern),
            $subRoute ?: new Doubles\MockedRoute('default')
        );
    }

    private function request($uri = 'http://example.com/foo/bar?query=string')
    {
        $request = new Doubles\FakeServerRequest();

        $request->uri = Uri::fromString($uri);

        return $request;
    }
}
