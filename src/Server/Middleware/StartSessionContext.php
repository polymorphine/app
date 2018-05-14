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
use Polymorphine\Http\Message\Response\Headers\ResponseHeadersCollection;
use Polymorphine\Http\Server\Session\SessionStorage;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;


class StartSessionContext implements MiddlewareInterface
{
    private $headers;
    private $session;

    private $sessionContext = false;

    public function __construct(ResponseHeadersCollection $headers, SessionStorage $session)
    {
        $this->session = $session;
        $this->headers = $headers;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->startSession($request->getCookieParams());

        $response = $handler->handle($request);

        $this->closeSession($this->session->getAll());

        return $response;
    }

    private function startSession($cookies): void
    {
        if (!isset($cookies[session_name()])) { return; }

        if (session_status() !== PHP_SESSION_NONE) {
            throw new RuntimeException('Session started outside object context');
        }

        session_start();

        foreach ($_SESSION as $name => $value) {
            $this->session->set($name, $value);
        }

        $this->sessionContext = true;
    }

    private function closeSession(array $data): void
    {
        if (empty($data)) {
            $this->destroySession();
            return;
        }

        if (!$this->sessionContext) {
            session_start();

            $this->headers->cookie(session_name())->value(session_id());
        }

        $_SESSION = $data;

        session_write_close();
    }

    private function destroySession(): void
    {
        if (!$this->sessionContext) { return; }

        $this->headers->cookie(session_name())->remove();
        session_destroy();
    }
}
