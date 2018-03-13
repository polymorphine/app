<?php

namespace Polymorphine\Http\Tests\Routing\Route\Pattern;

use Polymorphine\Http\Message\Uri;
use Polymorphine\Http\Routing\Route\Pattern\UriMask;
use PHPUnit\Framework\TestCase;
use Polymorphine\Http\Tests\Doubles\DummyRequest;
use Polymorphine\Http\Tests\Message\Doubles\FakeUri;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;


class UriMaskTest extends TestCase
{
    private function pattern(string $uri)
    {
        return UriMask::fromUriString($uri);
    }

    private function request(string $uri)
    {
        $request = new DummyRequest();
        $request->uri = Uri::fromString($uri);

        return $request;
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf(UriMask::class, $this->pattern('http:/some/path&query=foo'));
    }

    /**
     * @dataProvider matchingPatterns
     *
     * @param $pattern
     * @param $uri
     */
    public function testMatchAgainstDefinedUriParts($pattern, $uri)
    {
        $request = $this->request($uri);
        $this->assertInstanceOf(ServerRequestInterface::class, $this->pattern($pattern)->matchedRequest($request));
    }

    public function matchingPatterns()
    {
        return [
            ['https:', 'https://example.com'],
            ['//www.example.com', 'http://www.example.com/some/path'],
            ['http:/some/path', 'http://whatever.com/some/path?query=part&ignored=values'],
            ['?query=foo&bar=baz', 'http://example.com/some/path?query=foo&bar=baz']
        ];
    }

    /**
     * @dataProvider notMatchingPatterns
     *
     * @param $pattern
     * @param $uri
     */
    public function testNotMatchAgainstDefinedUriParts($pattern, $uri)
    {
        $request = $this->request($uri);
        $this->assertNull($this->pattern($pattern)->matchedRequest($request));
    }

    public function notMatchingPatterns()
    {
        return [
            ['https:', 'http://example.com'],
            ['//www.example.com', 'http://example.com/some/path'],
            ['http:/some/path', 'http://whatever.com/some/other/path?query=part&ignored=values'],
            ['?query=foo&bar=baz', 'http://example.com/some/path?query=foo&bar=qux']
        ];
    }

    public function testUri_returnsUri()
    {
        $this->assertInstanceOf(UriInterface::class, $this->pattern('//example.com')->uri([], new Uri()));
    }

    /**
     * @dataProvider patterns
     * @param $pattern
     * @param $expected
     */
    public function testUriIsReturnedWithDefinedUriParts($pattern, $expected)
    {
        $uri = Uri::fromString('http://example.com/some/path?query=params&foo=bar');

        $mask = $this->pattern($pattern);
        $this->assertSame($expected, (string) $mask->uri([], $uri));
    }

    public function patterns()
    {
        return [
            ['', 'http://example.com/some/path?query=params&foo=bar'],
            ['https:', 'https://example.com/some/path?query=params&foo=bar'],
            ['//www.example.com', 'http://www.example.com/some/path?query=params&foo=bar'],
            ['/some/other/path', 'http://example.com/some/other/path?query=params&foo=bar'],
            ['?different=param', 'http://example.com/some/path?different=param'],
            ['https:?foo=bar', 'https://example.com/some/path?foo=bar'],
            ['//localhost/other/path', 'http://localhost/other/path?query=params&foo=bar'],
            ['//user:pass@example.com', 'http://user:pass@example.com/some/path?query=params&foo=bar']
        ];
    }
}
