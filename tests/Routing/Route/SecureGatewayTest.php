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
use Polymorphine\Http\Routing\Route\SecureGateway;
use Polymorphine\Http\Tests\Doubles;
use Polymorphine\Http\Tests\Message\Doubles\FakeUri;
use Psr\Http\Message\ResponseInterface;


class SecureGatewayTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(SecureGateway::class, $this->route());
    }

    public function testNotSecureScheme_ReturnsNull()
    {
        $this->assertNull($this->route()->forward($this->request()));
    }

    public function testSecureSchemeIsForwarded_ReturnsResponse()
    {
        $request = $this->request();
        $request->uri->scheme = 'https';
        $this->assertInstanceOf(ResponseInterface::class, $this->route()->forward($request));
    }

    public function testUri_ReturnsSecureUri()
    {
        $subRoute = new Doubles\MockedRoute('default');

        $this->assertSame('http', $subRoute->uri()->getScheme());
        $this->assertSame('https', $this->route($subRoute)->uri()->getScheme());
    }

    public function testGateway_ReturnsRoutesWithSecureUri()
    {
        $subRoute = new Doubles\MockedRoute('default');

        $this->assertSame('http', $subRoute->uri()->getScheme());
        $this->assertSame('https', $this->route($subRoute)->gateway('foo.bar')->uri()->getScheme());
    }

    private function route($route = null)
    {
        return new SecureGateway(
            $route ?: new Doubles\MockedRoute('default')
        );
    }

    private function request($path = '/')
    {
        $request = new Doubles\DummyRequest();
        $request->uri = new FakeUri('example.com', $path);

        return $request;
    }
}
