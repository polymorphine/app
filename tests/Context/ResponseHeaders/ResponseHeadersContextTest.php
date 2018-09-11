<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Tests\Context\ResponseHeaders;

use PHPUnit\Framework\TestCase;
use Polymorphine\Http\Context\ResponseHeaders;
use Polymorphine\Http\Tests\Doubles\FakeRequestHandler;
use Polymorphine\Http\Tests\Doubles\FakeServerRequest;
use Polymorphine\Http\Tests\Doubles\FakeResponse;
use Psr\Http\Server\MiddlewareInterface;


class ResponseHeadersContextTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(ResponseHeaders::class, $this->collection());
        $this->assertInstanceOf(MiddlewareInterface::class, $this->collection());
    }

    public function testProcessing()
    {
        $headers = [
            'Set-Cookie' => [
                'fullCookie=foo; Domain=example.com; Path=/directory/; Expires=Tuesday, 01-May-2018 01:00:00 UTC; MaxAge=3600; Secure; HttpOnly',
                'myCookie=; Expires=Thursday, 02-May-2013 00:00:00 UTC; MaxAge=-157680000'
            ],
            'X-Foo-Header' => ['foo'],
            'X-Bar-Header' => ['bar']
        ];

        $handler  = new FakeRequestHandler(new FakeResponse('test'));
        $response = $this->collection($headers)->process(new FakeServerRequest(), $handler);

        $this->assertSame('test', (string) $response->getBody());
        $this->assertSame($headers, $response->getHeaders());
    }

    public function testAddHeaderToCollection()
    {
        $expectedHeaders = [
            'Set-Cookie'   => ['fullCookie=foo; Domain=example.com; Path=/directory/; Expires=Tuesday, 01-May-2018 01:00:00 UTC; MaxAge=3600; Secure; HttpOnly'],
            'X-Foo-Header' => ['foo'],
            'X-Bar-Header' => ['bar']
        ];

        $collection = $this->collection($expectedHeaders);

        $expectedHeaders['Set-Cookie'][] = 'myCookie=; Expires=Thursday, 02-May-2013 00:00:00 UTC; MaxAge=-157680000';
        $collection->add('Set-Cookie', 'myCookie=; Expires=Thursday, 02-May-2013 00:00:00 UTC; MaxAge=-157680000');

        $handler  = new FakeRequestHandler(new FakeResponse('test'));
        $response = $this->collection($expectedHeaders)->process(new FakeServerRequest(), $handler);
        $this->assertSame($expectedHeaders, $response->getHeaders());
    }

    public function testCookieSetupInstance()
    {
        $this->assertInstanceOf(ResponseHeaders\CookieSetup::class, $this->collection()->cookie('test'));
    }

    /**
     * @dataProvider cookieData
     *
     * @param string $headerLine
     * @param array  $cookieData
     */
    public function testCookieHeaders(string $headerLine, array $cookieData)
    {
        $collection = $this->collection();

        $cookie = $collection->cookie($cookieData['name']);
        isset($cookieData['time']) and $cookie = $cookie->expires($cookieData['time']);
        isset($cookieData['perm']) and $cookie = $cookie->permanent();
        isset($cookieData['domain']) and $cookie = $cookie->domain($cookieData['domain']);
        isset($cookieData['path']) and $cookie = $cookie->path($cookieData['path']);
        isset($cookieData['secure']) and $cookie = $cookie->secure($cookieData['secure']);
        isset($cookieData['http']) and $cookie = $cookie->httpOnly($cookieData['http']);

        $cookieData['value'] ? $cookie->value($cookieData['value']) : $cookie->remove();
        $this->assertEquals($this->collection(['Set-Cookie' => [$headerLine]]), $collection);
    }

    public function cookieData()
    {
        return [
            ['myCookie=; Path=/; Expires=Thursday, 02-May-2013 00:00:00 UTC; MaxAge=-157680000', [
                'name'  => 'myCookie',
                'value' => null
            ]],
            ['fullCookie=foo; Domain=example.com; Path=/directory/; Expires=Tuesday, 01-May-2018 01:00:00 UTC; MaxAge=3600; Secure; HttpOnly', [
                'name'   => 'fullCookie',
                'value'  => 'foo',
                'secure' => true,
                'time'   => 60,
                'http'   => true,
                'domain' => 'example.com',
                'path'   => '/directory/'
            ]],
            ['permanentCookie=hash-3284682736487236; Expires=Sunday, 30-Apr-2023 00:00:00 UTC; MaxAge=157680000; HttpOnly', [
                'name'  => 'permanentCookie',
                'value' => 'hash-3284682736487236',
                'perm'  => true,
                'http'  => true,
                'path'  => ''
            ]]
        ];
    }

    private function collection(array $headers = [])
    {
        return new ResponseHeaders\ResponseHeadersContext($headers);
    }
}
