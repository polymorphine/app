<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Tests\Server\Handler;

use Polymorphine\Http\Server\Handler\MiddlewareChain;
use PHPUnit\Framework\TestCase;
use Polymorphine\Http\Tests\Doubles\FakeMiddleware;
use Polymorphine\Http\Tests\Doubles\FakeRequestHandler;
use Polymorphine\Http\Tests\Doubles\FakeResponse;
use Polymorphine\Http\Tests\Doubles\FakeServerRequest;
use Psr\Http\Server\RequestHandlerInterface;


class MiddlewareChainTest extends TestCase
{
    public function testInstantiation()
    {
        $chain = new MiddlewareChain($this->handler(), $this->middleware());
        $this->assertInstanceOf(RequestHandlerInterface::class, $chain);
    }

    public function testMiddlewareProcessingOrder()
    {
        $chain    = new MiddlewareChain($this->handler(), $this->middleware());
        $response = $chain->handle(new FakeServerRequest());
        $this->assertSame('a b c  c b a', (string) $response->getBody());
    }

    private function handler()
    {
        return new FakeRequestHandler(function () { return new FakeResponse(); });
    }

    private function middleware()
    {
        return [
            new FakeMiddleware('a', 'a'),
            new FakeMiddleware('b', 'b'),
            new FakeMiddleware('c', 'c')
        ];
    }
}
