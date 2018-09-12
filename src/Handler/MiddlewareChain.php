<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;


class MiddlewareChain implements MiddlewareInterface
{
    private $middleware;

    public function __construct(MiddlewareInterface ...$middleware)
    {
        $this->middleware = $middleware;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->compose($handler, $this->middleware)->handle($request);
    }

    private function compose(RequestHandlerInterface $handler, $middleware): RequestHandlerInterface
    {
        return empty($middleware)
            ? $handler
            : $this->compose(new MiddlewareHandler(array_pop($middleware), $handler), $middleware);
    }
}