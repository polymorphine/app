<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Tests\Doubles;

use Polymorphine\Http\Message\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;


class FakeMiddleware implements MiddlewareInterface
{
    private $begin;
    private $end;

    public function __construct(string $begin = 'processed', string $end = 'response')
    {
        $this->begin = $begin;
        $this->end   = $end;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request  = $request->withAttribute('Middleware', 'processed request');
        $response = $handler->handle($request)->withHeader('Middleware', 'processed response');
        $body     = $this->begin . ' ' . $response->getBody() . ' ' . $this->end;

        $response->fromRequest = $request;

        return $response->withBody(Stream::fromBodyString($body));
    }
}
