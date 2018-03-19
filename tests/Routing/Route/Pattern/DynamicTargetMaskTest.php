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

use PHPUnit\Framework\TestCase;
use Polymorphine\Http\Message\Uri;
use Polymorphine\Http\Routing\Exception\UnreachableEndpointException;
use Polymorphine\Http\Routing\Exception\UriParamsException;
use Polymorphine\Http\Routing\Route\Pattern;
use Polymorphine\Http\Routing\Route\Pattern\DynamicTargetMask;
use Polymorphine\Http\Tests\Doubles\DummyRequest;
use Psr\Http\Message\ServerRequestInterface;


class DynamicTargetMaskTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(DynamicTargetMask::class, $this->pattern());
        $this->assertInstanceOf(Pattern::class, $this->pattern());
    }

    public function testNotMatchingRequest_ReturnsNull()
    {
        $pattern = $this->pattern('/page/{#no}');
        $this->assertNull($pattern->matchedRequest($this->request('/page/next')));
        $this->assertNull($pattern->matchedRequest($this->request('/page')));
    }

    /**
     * @dataProvider matchingRequests
     *
     * @param $pattern
     * @param $uri
     */
    public function testMatchingRequest_ReturnsRequestBack($pattern, $uri)
    {
        $pattern = $this->pattern($pattern);
        $request = $this->request($uri);
        $this->assertInstanceOf(ServerRequestInterface::class, $pattern->matchedRequest($request));
    }

    /**
     * @dataProvider matchingRequests
     *
     * @param $pattern
     * @param $uri
     * @param $attr
     */
    public function testMatchingRequest_ReturnsRequestWithMatchedAttributes($pattern, $uri, $attr)
    {
        $pattern = $this->pattern($pattern);
        $request = $this->request($uri);
        $this->assertSame($attr, $pattern->matchedRequest($request)->getAttributes());
    }

    /**
     * @dataProvider matchingRequests
     *
     * @param $pattern
     * @param $uri
     * @param $attr
     */
    public function testUriReplacesProvidedValues($pattern, $uri, $attr)
    {
        $pattern = $this->pattern($pattern);
        $this->assertSame($uri, (string) $pattern->uri(array_values($attr), new Uri()));
    }

    /**
     * @dataProvider matchingRequests
     *
     * @param $pattern
     * @param $uri
     * @param $attr
     */
    public function testUriNamedParamsCanBePassedOutOfOrder($pattern, $uri, $attr)
    {
        $pattern = $this->pattern($pattern);
        $this->assertSame($uri, (string) $pattern->uri(array_reverse($attr, true), new Uri()));
    }

    public function matchingRequests()
    {
        return [
            'no-params' => ['/path/only', '/path/only', []],
            'id' => ['/page/{#no}', '/page/4', ['no' => '4']],
            'id+slug' => ['/page/{#no}/{$title}', '/page/576/foo-bar-45', ['no' => '576', 'title' => 'foo-bar-45']],
            'literal-id' => ['/foo-{%name}', '/foo-bar5000', ['name' => 'bar5000']],
            'query' => ['/path/and?user={#id}', '/path/and?user=938', ['id' => '938']],
            'query+path' => ['/path/user/{#id}?foo={$bar}', '/path/user/938?foo=bar-BAZ', ['id' => '938', 'bar' => 'bar-BAZ']]
        ];
    }

    public function testUriInsufficientParams_ThrowsException()
    {
        $pattern = $this->pattern('/some-{#number}/{$slug}');
        $this->expectException(UriParamsException::class);
        $pattern->uri([22], new Uri());
    }

    public function testUriInvalidTypeParams_ThrowsException()
    {
        $pattern = $this->pattern('/user/{#countryId}');
        $this->expectException(UriParamsException::class);
        $pattern->uri(['Poland'], new Uri());
    }

    public function testQueryStringMatchIgnoresParamOrder()
    {
        $request = $this
            ->pattern('/path/and?user={#id}&foo={$bar}')
            ->matchedRequest($this->request('/path/and?foo=bar-BAZ&user=938'));
        $this->assertInstanceOf(ServerRequestInterface::class, $request);
        $this->assertSame(['id' => '938', 'bar' => 'bar-BAZ'], $request->getAttributes());
    }

    public function testQueryStringIsIgnoredWhenNotSpecifiedInRoute()
    {
        $request = $this
            ->pattern('/path/{%directory}')
            ->matchedRequest($this->request('/path/something?foo=bar-BAZ&user=938'));
        $this->assertInstanceOf(ServerRequestInterface::class, $request);
        $this->assertSame(['directory' => 'something'], $request->getAttributes());
    }

    public function testNotSpecifiedQueryParamsAreIgnored()
    {
        $request = $this
            ->pattern('/path/only?name={$slug}&user=938')
            ->matchedRequest($this->request('/path/only?foo=bar-BAZ&user=938&name=shudd3r'));
        $this->assertInstanceOf(ServerRequestInterface::class, $request);
        $this->assertSame(['slug' => 'shudd3r'], $request->getAttributes());
    }

    public function testMissingQueryParamWontMatchRequest()
    {
        $request = $this
            ->pattern('/path/only?name={$slug}&user=938')
            ->matchedRequest($this->request('/path/only?foo=bar-BAZ&name=shudd3r'));
        $this->assertNull($request);
    }

    public function testPatternQueryKeyWithoutValue()
    {
        $pattern = $this->pattern('/foo/{%bar}?name={$name}&fizz');

        $request = $pattern->matchedRequest($this->request('/foo/bar?name=slug-example'));
        $this->assertNull($request);

        $request = $pattern->matchedRequest($this->request('/foo/bar?name=slug-example&fizz'));
        $this->assertInstanceOf(ServerRequestInterface::class, $request);

        $request = $pattern->matchedRequest($this->request('/foo/bar?fizz=anything&name=slug-example'));
        $this->assertInstanceOf(ServerRequestInterface::class, $request);
        $this->assertSame(['bar' => 'bar', 'name' => 'slug-example'], $request->getAttributes());

        $uri = $pattern->uri(['something', 'slug-string'], Uri::fromString('http://example.com'));
        $this->assertSame('http://example.com/foo/something?name=slug-string&fizz', (string) $uri);
    }

    public function testEmptyQueryParamValueIsRequiredAsSpecified()
    {
        $pattern = $this->pattern('/foo/{%bar}?name={$name}&fizz=');

        $request = $pattern->matchedRequest($this->request('/foo/bar?name=slug-example&fizz=something'));
        $this->assertNull($request);

        $request = $pattern->matchedRequest($this->request('/foo/bar?name=slug-example&fizz'));
        $this->assertInstanceOf(ServerRequestInterface::class, $request);

        $request = $pattern->matchedRequest($this->request('/foo/bar?name=slug-example&fizz='));
        $this->assertInstanceOf(ServerRequestInterface::class, $request);
    }

    public function testUnusedUriParamsAreIgnored()
    {
        $pattern = $this->pattern('/foo/{%bar}?name={$name}&fizz=buzz');

        $uri = $pattern->uri(['something', 'slug-string', 'unused-param'], Uri::fromString('https://www.example.com'));
        $this->assertSame('https://www.example.com/foo/something?name=slug-string&fizz=buzz', (string) $uri);

        $uri = $pattern->uri(['unused' => 'something', 'name' => 'slug-string', 'bar' => 'name'], Uri::fromString('https://www.example.com'));
        $this->assertSame('https://www.example.com/foo/name?name=slug-string&fizz=buzz', (string) $uri);
    }

    /**
     * @dataProvider prototypeConflict
     *
     * @param $pattern
     * @param $uri
     */
    public function testUriOverwritingPrototypeSegment_ThrowsException($pattern, $uri)
    {
        $pattern = $this->pattern($pattern);
        $this->expectException(UnreachableEndpointException::class);
        $pattern->uri(['id' => 1500], Uri::fromString($uri));
    }

    public function prototypeConflict()
    {
        return [
            ['/user/{#id}', '/some/other/path'],
            ['/foo/{#id}?some=query', '?other=query']
        ];
    }

    public function testMatchWithProvidedPattern()
    {
        $pattern = new DynamicTargetMask('/some/path/{hex}', ['hex' => '[A-F0-9]+']);
        $request = $this->request('/some/path/D6E8A9F6');
        $this->assertInstanceOf(ServerRequestInterface::class, $pattern->matchedRequest($request));
        $this->assertSame(['hex' => 'D6E8A9F6'], $pattern->matchedRequest($request)->getAttributes());

        $request = $this->request('/some/path/d6e8a9f6');
        $this->assertNull($pattern->matchedRequest($request));
    }

    public function testUriValidParamWithProvidedPattern()
    {
        $pattern = new DynamicTargetMask('/{lang}/foo', ['lang' => '(en|pl|fr)']);
        $this->assertSame('/en/foo', (string) $pattern->uri(['en'], new Uri()));
    }

    public function testUriInvalidParamWithProvidedPattern()
    {
        $pattern = new DynamicTargetMask('/{lang}/foo', ['lang' => '(en|pl|fr)']);
        $this->expectException(UriParamsException::class);
        $pattern->uri(['es'], new Uri());
    }

    private function pattern($pattern = '')
    {
        return new DynamicTargetMask($pattern);
    }

    private function request($path)
    {
        $request = new DummyRequest();
        $request->uri = Uri::fromString('//example.com' . $path);

        return $request;
    }
}
