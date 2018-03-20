<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

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
            ['?query=foo&bar=baz', 'http://example.com/some/path?query=foo&bar=baz'],
            ['//example.com:9001', 'https://example.com:9001/foo/path']
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
            ['?query=foo&bar=baz', 'http://example.com/some/path?query=foo&bar=qux'],
            ['//example.com:8080', '//example.com:9001'],
            ['//example.com:8080', '//example.com'],
        ];
    }

    public function testUri_returnsUri()
    {
        $this->assertInstanceOf(UriInterface::class, $this->pattern('//example.com')->uri([], new Uri()));
    }

    /**
     * @dataProvider patterns
     *
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
            ['//user:pass@example.com?query=params&foo=bar', 'https://example.com/some/path?query=params&foo=bar', 'https://user:pass@example.com/some/path?query=params&foo=bar'],
            ['//example.com:9001', 'http://example.com/foo/bar', 'http://example.com:9001/foo/bar']
        ];
    }

    /**
     * @dataProvider prototypeConflict
     *
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

    public function testRelativePathIsMatched()
    {
        $pattern = $this->pattern('bar');
        $request = $pattern->matchedRequest($this->request('/foo/bar'));
        $this->assertInstanceOf(ServerRequestInterface::class, $request);
        $this->assertSame([], $request->getAttributes());
    }

    public function testUriFromRelativePathWithRootInPrototype_ReturnsUriWithAppendedPath()
    {
        $pattern = $this->pattern('bar/slug-string');
        $prototype = Uri::fromString('/foo');
        $this->assertSame('/foo/bar/slug-string', (string) $pattern->uri([], $prototype));

        $pattern = $this->pattern('last/segments?query=string');
        $prototype = Uri::fromString('/foo/bar');
        $this->assertSame('/foo/bar/last/segments?query=string', (string) $pattern->uri([], $prototype));
    }

    public function testUriFromRelativePathWithNoRootInPrototype_ThrowsException()
    {
        $pattern = $this->pattern('bar');
        $prototype = new Uri();
        $this->expectException(UnreachableEndpointException::class);
        $pattern->uri([], $prototype);
    }

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
}
