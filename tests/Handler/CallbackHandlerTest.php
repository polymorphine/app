<?php

/*
 * This file is part of Polymorphine/App package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\App\Tests\Handler;

use PHPUnit\Framework\TestCase;
use Polymorphine\App\Handler\CallbackHandler;
use Polymorphine\App\Tests\Doubles\FakeResponse;
use Polymorphine\App\Tests\Doubles\FakeServerRequest;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;


class CallbackHandlerTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(RequestHandlerInterface::class, new CallbackHandler(function () {}));
    }

    public function testHandle()
    {
        $request = new FakeServerRequest();
        $handler = new CallbackHandler(function (ServerRequestInterface $request) {
            return new FakeResponse($request->getAttribute('test'));
        });

        $this->assertSame('body', (string) $handler->handle($request->withAttribute('test', 'body'))->getBody());
    }
}
