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
        $signatureRequired = in_array($request->getMethod(), ['POST', 'PUT', 'DELETE', 'PATCH'], true);
        $validRequest      = !$signatureRequired || $this->tokenMatch($request->getParsedBody());

        return $validRequest ? $handler->handle($request) : new NotFoundResponse();
    }

    public function appSignature(): CsrfToken
    {
        if ($this->token) { return $this->token; }

        if (!$this->session->exists(self::SESSION_CSRF_KEY)) {
            $this->session->set(self::SESSION_CSRF_KEY, uniqid());
            $this->session->set(self::SESSION_CSRF_TOKEN, bin2hex(random_bytes(32)));
        }

        return $this->token = new CsrfToken(
            $this->session->get(self::SESSION_CSRF_KEY),
            $this->session->get(self::SESSION_CSRF_TOKEN)
        );
    }

    private function tokenMatch(array $payload): bool
    {
        if (!$this->session->exists(self::SESSION_CSRF_KEY)) { return false; }

        $token   = $this->appSignature();
        $name    = $token->name();
        $isValid = isset($payload[$name]) && hash_equals($token->signature(), $payload[$name]);

        if (!$isValid) {
            $this->session->remove(self::SESSION_CSRF_KEY);
            $this->session->remove(self::SESSION_CSRF_TOKEN);
        }

        return $isValid;
    }
}
