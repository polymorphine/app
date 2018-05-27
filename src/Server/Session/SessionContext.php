<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Server\Session;

use Psr\Http\Server\MiddlewareInterface;
use Polymorphine\Http\Server\Response\ResponseHeaders;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;


class SessionContext implements MiddlewareInterface
{
    private $headers;
    private $session;

    private $sessionStarted = false;

    public function __construct(ResponseHeaders $headers)
    {
        $this->headers = $headers;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $cookies     = $request->getCookieParams();
        $sessionName = session_name();

        $this->session = isset($cookies[$sessionName])
            ? $this->start()
            : $this->createStorage();

        $response = $handler->handle($request);

        $this->commit($sessionName, $this->session->toArray());

        return $response;
    }

    public function session(): SessionStorage
    {
        if (!$this->session) {
            throw new RuntimeException('Session context not started');
        }

        return $this->session;
    }

    protected function createStorage(array $data = []): SessionStorage
    {
        return new SessionStorage($data);
    }

    private function start(): SessionStorage
    {
        if (session_status() !== PHP_SESSION_NONE) {
            throw new RuntimeException('Session started in another context');
        }

        session_start();
        $this->sessionStarted = true;

        return $this->createStorage($_SESSION);
    }

    private function commit(string $sessionName, array $data): void
    {
        if (!$data) {
            $this->destroy($sessionName);
            return;
        }

        if (!$this->sessionStarted) {
            $this->start();
            $this->headers->cookie($sessionName)->value(session_id());
        }

        $_SESSION = $data;
        session_write_close();
    }

    private function destroy(string $sessionName): void
    {
        if (!$this->sessionStarted) { return; }

        $this->headers->cookie($sessionName)->remove();
        session_destroy();
    }
}