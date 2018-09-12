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
use Polymorphine\Http\Context\ResponseHeaders\CookieSetup;
use Polymorphine\Http\Tests\Doubles\FakeResponseHeaders;


class CookieSetupTest extends TestCase
{
    /** @var FakeResponseHeaders */
    private $headers;

    public function setUp()
    {
        $this->headers = new FakeResponseHeaders();
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf(CookieSetup::class, new CookieSetup('new', $this->headers));
    }

    /**
     * @dataProvider cookieData
     *
     * @param string $headerLine
     * @param array  $data
     */
    public function testCookieHeaders(string $headerLine, array $data)
    {
        $cookie = new CookieSetup($data['name'], $this->headers);
        isset($data['time']) and $cookie = $cookie->expires($data['time']);
        isset($data['perm']) and $cookie = $cookie->permanent();
        isset($data['domain']) and $cookie = $cookie->domain($data['domain']);
        isset($data['path']) and $cookie = $cookie->path($data['path']);
        isset($data['secure']) and $cookie = $cookie->secure();
        isset($data['http']) and $cookie = $cookie->httpOnly();
        isset($data['site']) and $cookie = $data['site'] ? $cookie->sameSiteStrict() : $cookie->sameSiteLax();

        $data['value'] ? $cookie->value($data['value']) : $cookie->remove();
        $this->assertEquals([$headerLine], $this->headers->data['Set-Cookie']);
    }

    public function cookieData()
    {
        return [
            ['myCookie=; Path=/; Expires=Thursday, 02-May-2013 00:00:00 UTC; MaxAge=-157680000', [
                'name'  => 'myCookie',
                'value' => null
            ]],
            ['fullCookie=foo; Domain=example.com; Path=/directory/; Expires=Tuesday, 01-May-2018 01:00:00 UTC; MaxAge=3600; Secure; HttpOnly; SameSite=Lax', [
                'name'   => 'fullCookie',
                'value'  => 'foo',
                'secure' => true,
                'time'   => 60,
                'http'   => true,
                'domain' => 'example.com',
                'path'   => '/directory/',
                'site'   => false
            ]],
            ['permanentCookie=hash-3284682736487236; Expires=Sunday, 30-Apr-2023 00:00:00 UTC; MaxAge=157680000; HttpOnly; SameSite=Strict', [
                'name'  => 'permanentCookie',
                'value' => 'hash-3284682736487236',
                'perm'  => true,
                'http'  => true,
                'path'  => '',
                'site'  => true
            ]]
        ];
    }
}
