<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Tests\Context\Security;

use PHPUnit\Framework\TestCase;
use Polymorphine\Http\Context\CallbackHandler;
use Polymorphine\Http\Context\Security\CsrfProtectionContext;
use Polymorphine\Http\Context\Session\SessionStorage;
use Polymorphine\Http\Tests\Doubles\FakeResponse;
use Polymorphine\Http\Tests\Doubles\FakeServerRequest;


class CsrfProtectionContextTest extends TestCase
{
    public function testInstantiation()
    {
        $session = new SessionStorage($this->token('foo', 'bar'));
        $guard   = new CsrfProtectionContext($session);
        $this->assertInstanceOf(CsrfProtectionContext::class, $guard);
        $this->assertEquals('foo', $session->get(CsrfProtectionContext::SESSION_CSRF_KEY));
        $this->assertEquals('bar', $session->get(CsrfProtectionContext::SESSION_CSRF_TOKEN));
    }

    /**
     * @dataProvider safeMethods
     *
     * @param $method
     */
    public function testMatchingSkippedForSafeMethodRequests($method)
    {
        $handler = $this->handler();
        $guard   = $this->guard();
        $request = $this->request($method);
        $this->assertSame(200, $guard->process($request, $handler)->getStatusCode());

        $guard   = $this->guard($this->token('foo', 'bar'));
        $request = $this->request($method);
        $this->assertSame(200, $guard->process($request, $handler)->getStatusCode());

        $guard   = $this->guard($this->token('foo', 'bar'));
        $request = $this->request($method, ['baz' => 'something']);
        $this->assertSame(200, $guard->process($request, $handler)->getStatusCode());
    }

    /**
     * @dataProvider unsafeMethods
     *
     * @param $method
     */
    public function testMissingSessionTokenBlocksUnsafeRequests($method)
    {
        $handler = $this->handler();
        $guard   = $this->guard();
        $request = $this->request($method);
        $this->assertSame(404, $guard->process($request, $handler)->getStatusCode());
    }

    /**
     * @dataProvider unsafeMethods
     *
     * @param $method
     */
    public function testMatchingRequestToken_ReturnsOKResponse($method)
    {
        $token   = $this->token('name', 'hash');
        $handler = $this->handler();
        $guard   = $this->guard($token);
        $request = $this->request($method, ['name' => 'hash']);
        $this->assertSame(200, $guard->process($request, $handler)->getStatusCode());
    }

    /**
     * @dataProvider unsafeMethods
     *
     * @param $method
     */
    public function testNotMatchingRequestTokenHash_ReturnsNotFoundResponse($method)
    {
        $token   = $this->token('name', 'hash');
        $handler = $this->handler();
        $guard   = $this->guard($token);
        $request = $this->request($method, ['name' => 'something']);
        $this->assertSame(404, $guard->process($request, $handler)->getStatusCode());

        $guard   = $this->guard($token);
        $request = $this->request($method, ['something' => 'name']);
        $this->assertSame(404, $guard->process($request, $handler)->getStatusCode());
    }

    public function testSessionTokenIsRemovedOnEveryMatch()
    {
        $session = new SessionStorage($this->token('foo', 'bar'));
        $guard   = new CsrfProtectionContext($session);
        $request = $this->request('POST', ['something' => 'name']);
        $guard->process($request, $this->handler());

        $this->assertFalse($session->exists(CsrfProtectionContext::SESSION_CSRF_KEY));
        $this->assertFalse($session->exists(CsrfProtectionContext::SESSION_CSRF_TOKEN));

        $session = new SessionStorage($this->token('foo', 'bar'));
        $guard   = new CsrfProtectionContext($session);
        $request = $this->request('POST', ['foo' => 'bar']);
        $guard->process($request, $this->handler());

        $this->assertFalse($session->exists(CsrfProtectionContext::SESSION_CSRF_KEY));
        $this->assertFalse($session->exists(CsrfProtectionContext::SESSION_CSRF_TOKEN));
    }

    public function testGenerateTokenGeneratesTokenOnce()
    {
        $guard = $this->guard($this->token('name', 'hash'));
        $token = $guard->appSignature();

        $this->assertNotEquals('name', $token->name);
        $this->assertNotEquals('hash', $token->hash);

        $token2 = $guard->appSignature();
        $this->assertEquals($token->name, $token2->name);
        $this->assertEquals($token->hash, $token2->hash);
    }

    public function unsafeMethods()
    {
        return [['POST'], ['PUT'], ['DELETE'], ['PATCH'], ['TRACE'], ['CONNECT']];
    }

    public function safeMethods()
    {
        return [['GET'], ['HEAD'], ['OPTIONS']];
    }

    private function guard(array $token = []): CsrfProtectionContext
    {
        return new CsrfProtectionContext(new SessionStorage($token));
    }

    private function handler()
    {
        return new CallbackHandler(function () { return new FakeResponse(); });
    }

    private function request(string $method = 'GET', array $token = [])
    {
        $request = new FakeServerRequest($method);

        $request->parsed = $token;
        return $request;
    }

    private function token($key, $value): array
    {
        return [
            CsrfProtectionContext::SESSION_CSRF_KEY   => $key,
            CsrfProtectionContext::SESSION_CSRF_TOKEN => $value
        ];
    }
}
