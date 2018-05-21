<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Server\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Polymorphine\Http\Message\Response\Headers\ResponseHeaders;
use Polymorphine\Http\Server\Session;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;


class SessionContext implements MiddlewareInterface
{
    private $headers;
    private $session;

    public function __construct(ResponseHeaders $headers, Session $session)
    {
        $this->session = $session;
        $this->headers = $headers;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $cookies    = $request->getCookieParams();
        $cookieName = $this->session->name();

        if (isset($cookies[$cookieName])) { $this->session->start(); }

        $response = $handler->handle($request);

        $this->session->stop($this->headers->cookie($cookieName));

        return $response;
    }
}
