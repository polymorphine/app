<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Tests\Context;

use PHPUnit\Framework\TestCase;
use Polymorphine\Http\Context\MiddlewareChain;
use Polymorphine\Http\Tests\Doubles\FakeRequestHandler;
use Polymorphine\Http\Tests\Doubles\FakeMiddleware;
use Polymorphine\Http\Tests\Doubles\FakeServerRequest;
use Polymorphine\Http\Tests\Doubles\FakeResponse;
use Psr\Http\Server\MiddlewareInterface;


class MiddlewareChainTest extends TestCase
{
    public function testInstantiation()
    {
        $chain = new MiddlewareChain(new FakeMiddleware('a'), new FakeMiddleware('b'), new FakeMiddleware('c'));
        $this->assertInstanceOf(MiddlewareInterface::class, $chain);
    }

    public function testMiddlewareProcessingOrder()
    {
        $chain    = new MiddlewareChain(...$this->middleware());
        $response = $chain->process(new FakeServerRequest(), $this->handler());
        $this->assertSame('a b c response c b a', (string) $response->getBody());
    }

    private function handler()
    {
        return new FakeRequestHandler(new FakeResponse('response'));
    }

    private function middleware()
    {
        return [
            new FakeMiddleware('a'),
            new FakeMiddleware('b'),
            new FakeMiddleware('c')
        ];
    }
}
