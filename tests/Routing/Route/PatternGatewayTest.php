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
use Polymorphine\Http\Routing\Route\Pattern\StaticUriMask;
use Polymorphine\Http\Routing\Route\PatternGateway;
use Polymorphine\Http\Tests\Doubles\MockedRoute;
use Polymorphine\Http\Tests\Doubles\FakeServerRequest;
use Polymorphine\Http\Tests\Doubles\FakeResponse;
use Polymorphine\Http\Tests\Doubles\FakeUri;


class PatternGatewayTest extends TestCase
{
    private static $notFound;

    public static function setUpBeforeClass()
    {
        self::$notFound = new FakeResponse();
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf(PatternGateway::class, $default = $this->staticGate());
        $this->assertInstanceOf(PatternGateway::class, $https = $this->staticGate('https:'));
        $this->assertInstanceOf(PatternGateway::class, $http = $this->staticGate('http:'));

        $this->assertEquals($default, $https);
        $this->assertNotEquals($default, $http);

        $gateway = PatternGateway::withPatternString('/test/{#testId}', new MockedRoute('default'));
        $this->assertInstanceOf(PatternGateway::class, $gateway);

        $gateway = PatternGateway::withPatternString('//domain.com/test/foo', new MockedRoute('default'));
        $this->assertInstanceOf(PatternGateway::class, $gateway);
    }

    public function testNotMatchingPattern_ReturnsNotFoundResponseInstance()
    {
        $this->assertSame(self::$notFound, $this->staticGate('https:/some/path')->forward($this->request('http:/some/path'), self::$notFound));
        $this->assertSame(self::$notFound, $this->staticGate('example.com/foo/bar')->forward($this->request('http://example.com/foo/baz'), self::$notFound));
    }

    public function testMatchingPattern_ReturnsForwardedRouteResponse()
    {
        $this->assertNotSame(self::$notFound, $this->staticGate('//example.com')->forward($this->request('http://example.com/some/path'), self::$notFound));
        $this->assertNotSame(self::$notFound, $this->staticGate('http:?query=string')->forward($this->request('http://www.example.com?query=string'), self::$notFound));
    }

    public function testUri_ReturnsUriWithPatternDefinedSegments()
    {
        $subRoute = new MockedRoute('/foo/bar');

        $uri = $this->staticGate('https:?some=query', $subRoute)->uri(new FakeUri(), []);
        $this->assertSame('https', $uri->getScheme());
        $this->assertSame('', $uri->getHost());
        $this->assertSame('/foo/bar', $uri->getPath());
        $this->assertSame('some=query', $uri->getQuery());

        $uri = $this->staticGate('//example.com', $subRoute)->uri(new FakeUri(), []);
        $this->assertSame('', $uri->getScheme());
        $this->assertSame('example.com', $uri->getHost());
        $this->assertSame('/foo/bar', $uri->getPath());
    }

    public function testGateway_ReturnsRouteProducingUriWithDefinedSegments()
    {
        $subRoute = new MockedRoute('/foo/bar');

        $this->assertSame('https://example.com/foo/bar', (string) $this->staticGate('https://example.com', $subRoute)->gateway('some.path')->uri(new FakeUri(), []));
        $this->assertSame('http:/foo/bar', (string) $this->staticGate('http:', $subRoute)->gateway('some.path')->uri(new FakeUri(), []));
    }

    public function testComposedGateway_ReturnsRouteProducingUriWithDefinedSegments()
    {
        $subRoute = $this->staticGate('//example.com', new MockedRoute('/foo/bar'));
        $this->assertSame('https://example.com/foo/bar', (string) $this->staticGate('https:', $subRoute)->gateway('some.path')->uri(new FakeUri(), []));
    }

    private function staticGate(string $uriPattern = 'https:', $subRoute = null)
    {
        return new PatternGateway(
            new StaticUriMask($uriPattern),
            $subRoute ?: new MockedRoute('default')
        );
    }

    private function request($uri = 'http://example.com/foo/bar?query=string')
    {
        $request      = new FakeServerRequest();
        $request->uri = FakeUri::fromString($uri);

        return $request;
    }
}
