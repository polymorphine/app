<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Context\Security;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Polymorphine\Http\Context\Session\SessionStorage;
use Polymorphine\Http\Message\Response\NotFoundResponse;


class CsrfProtectionContext implements MiddlewareInterface
{
    public const SESSION_CSRF_KEY   = 'csrf_key';
    public const SESSION_CSRF_TOKEN = 'csrf_token';

    private $session;
    private $token;

    public function __construct(SessionStorage $session)
    {
        $this->session = $session;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $unsafeMethods     = ['POST', 'PUT', 'DELETE', 'PATCH', 'TRACE', 'CONNECT'];
        $signatureRequired = in_array($request->getMethod(), $unsafeMethods, true);
        $validRequest      = !$signatureRequired || $this->tokenMatch($request->getParsedBody());

        return $validRequest ? $handler->handle($request) : new NotFoundResponse();
    }

    public function appSignature(): CsrfToken
    {
        return $this->token ?: $this->token = $this->generateToken();
    }

    private function tokenMatch(array $payload): bool
    {
        if (!$token = $this->sessionToken()) { return false; }

        $isValid = isset($payload[$token->name]) && hash_equals($token->hash, $payload[$token->name]);

        $this->session->remove(self::SESSION_CSRF_KEY);
        $this->session->remove(self::SESSION_CSRF_TOKEN);

        return $isValid;
    }

    private function sessionToken(): ?CsrfToken
    {
        if (!$this->session->exists(self::SESSION_CSRF_KEY)) { return null; }

        return new CsrfToken(
            $this->session->get(self::SESSION_CSRF_KEY),
            $this->session->get(self::SESSION_CSRF_TOKEN)
        );
    }

    private function generateToken(): CsrfToken
    {
        $this->session->set(self::SESSION_CSRF_KEY, uniqid());
        $this->session->set(self::SESSION_CSRF_TOKEN, bin2hex(random_bytes(32)));

        return $this->sessionToken();
    }
}
