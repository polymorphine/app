<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Tests\Server\Middleware;

use PHPUnit\Framework\TestCase;
use Polymorphine\Http\Message\Response\Headers\ResponseHeadersCollection;
use Polymorphine\Http\Server\Middleware\SetResponseHeaders;
use Polymorphine\Http\Tests\Doubles\FakeRequestHandler;
use Polymorphine\Http\Tests\Doubles\FakeResponse;
use Polymorphine\Http\Tests\Doubles\FakeServerRequest;


class SetResponseHeadersTest extends TestCase
{
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

        $middleware = new SetResponseHeaders(new ResponseHeadersCollection($headers));
        $handler = new FakeRequestHandler(new FakeResponse('test'));
        $response = $middleware->process(new FakeServerRequest(), $handler);

        $this->assertSame('test', (string) $response->getBody());

        $this->assertSame($headers, $response->getHeaders());
    }
}
