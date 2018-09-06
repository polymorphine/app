<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Context\Session;

use Polymorphine\Http\Context\SessionManager;
use Polymorphine\Http\Context\Session;
use Polymorphine\Http\Context\Response\ResponseHeaders;
use Polymorphine\Http\Context\Security\CsrfPersistentTokenContext;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;


class SessionContext implements MiddlewareInterface, SessionManager
{
    private $headers;

    /** @var Session */
    private $session;

    private $sessionName;
    private $sessionStarted = false;

    public function __construct(ResponseHeaders $headers)
    {
        $this->headers = $headers;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $cookies = $request->getCookieParams();

        $this->sessionName = session_name();
        if (isset($cookies[$this->sessionName])) { $this->startSession(); }
        $this->createStorage($_SESSION ?? []);

        $response = $handler->handle($request);
        $this->session()->commit();
        return $response;
    }

    public function session(): Session
    {
        if (!$this->session) {
            throw new RuntimeException('Session context not started');
        }
        return $this->session;
    }

    public function startSession(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            throw new RuntimeException('Session started in another context');
        }

        session_start();
        $this->sessionStarted = true;
    }

    public function regenerateId(): void
    {
        if (!$this->sessionStarted) { return; }
        session_regenerate_id(true);
        $this->setSessionCookie();
        $this->session->remove(CsrfPersistentTokenContext::SESSION_CSRF_KEY);
        $this->session->remove(CsrfPersistentTokenContext::SESSION_CSRF_TOKEN);
    }

    public function commitSession(array $data): void
    {
        if (!$data) {
            $this->destroy();
            return;
        }

        if (!$this->sessionStarted) {
            $this->startSession();
            $this->setSessionCookie();
        }

        $_SESSION = $data;
        session_write_close();
    }

    protected function createStorage(array $data = []): void
    {
        $this->session = new SessionStorage($this, $data);
    }

    protected function setSessionCookie(): void
    {
        $cookie = $this->headers->cookie($this->sessionName);
        $cookie->domain(ini_get('session.cookie_domain'))
               ->path(ini_get('session.cookie_path') ?: '/')
               ->httpOnly(true)
               ->value(session_id());
    }

    private function destroy(): void
    {
        if (!$this->sessionStarted) { return; }

        $this->headers->cookie($this->sessionName)->remove();
        session_destroy();
    }
}
