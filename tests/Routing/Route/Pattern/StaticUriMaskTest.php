<?php

namespace Polymorphine\Http\Tests\Routing\Route\Pattern;

use Polymorphine\Http\Message\Uri;
use Polymorphine\Http\Routing\Exception\UnreachableEndpointException;
use Polymorphine\Http\Routing\Route\Pattern\StaticUriMask;
use PHPUnit\Framework\TestCase;
use Polymorphine\Http\Tests\Doubles\DummyRequest;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;


class StaticUriMaskTest extends TestCase
{
    private function pattern(string $uri)
    {
        return StaticUriMask::fromUriString($uri);
    }

    private function request(string $uri)
    {
        $request = new DummyRequest();
        $request->uri = Uri::fromString($uri);

        return $request;
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf(StaticUriMask::class, $this->pattern('http:/some/path&query=foo'));
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
     * @param $uriString
     * @param $expected
     */
    public function testUriIsReturnedWithDefinedUriParts($pattern, $uriString, $expected)
    {
        $uri = Uri::fromString($uriString);

        $mask = $this->pattern($pattern);
        $this->assertSame($expected, (string) $mask->uri([], $uri));
    }

    public function patterns()
    {
        return [
            ['', 'https://example.com/some/path?query=params&foo=bar', 'https://example.com/some/path?query=params&foo=bar'],
            ['https:', '//example.com/some/path?query=params&foo=bar', 'https://example.com/some/path?query=params&foo=bar'],
            ['//example.com', 'https:/some/path?query=params&foo=bar', 'https://example.com/some/path?query=params&foo=bar'],
            ['/some/path', 'https://example.com?query=params&foo=bar', 'https://example.com/some/path?query=params&foo=bar'],
            ['?query=params&foo=bar', 'https://example.com/some/path', 'https://example.com/some/path?query=params&foo=bar'],
            ['https://example.com?query=params&foo=bar', '//example.com/some/path', 'https://example.com/some/path?query=params&foo=bar'],
            ['//example.com/some/path', 'https:?query=params&foo=bar', 'https://example.com/some/path?query=params&foo=bar'],
            ['//user:pass@example.com?query=params&foo=bar', 'https://example.com/some/path?query=params&foo=bar', 'https://user:pass@example.com/some/path?query=params&foo=bar']
        ];
    }

    /**
     * @dataProvider prototypeConflict
     * @param $pattern
     * @param $uriString
     */
    public function testUriOverwritingPrototypeSegment_ThrowsException($pattern, $uriString)
    {
        $this->expectException(UnreachableEndpointException::class);
        $this->pattern($pattern)->uri([], Uri::fromString($uriString));
    }

    public function prototypeConflict()
    {
        return [
            ['http:', 'https://example.com'],
            ['https://www.example.com', 'https://example.com'],
            ['/foo/bar', '/baz'],
            ['//user:pass@example.com', '//www.example.com']
        ];
    }
}
