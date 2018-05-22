<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Server;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;


class MiddlewareChain implements RequestHandlerInterface
{
    private $middleware;
    private $handler;
    private $composedHandler;

    public function __construct(RequestHandlerInterface $handler, array $middleware = [])
    {
        $this->handler = $handler;
        $this->setMiddleware(...$middleware);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->composedHandler or $this->composedHandler = $this->compose();

        return $this->composedHandler->handle($request);
    }

    private function compose(): RequestHandlerInterface
    {
        $handler = $this->handler;
        while ($middleware = array_pop($this->middleware)) {
            $handler = new MiddlewareHandler($middleware, $handler);
        }

        return $handler;
    }

    private function setMiddleware(MiddlewareInterface ...$middleware)
    {
        $this->middleware = $middleware;
    }
}
