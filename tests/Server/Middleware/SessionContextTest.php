<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Tests\Server\Middleware;

use PHPUnit\Framework\TestCase;
use Polymorphine\Http\Message\Response\Headers\ResponseHeaders;
use Polymorphine\Http\Server\Middleware\SessionContext;
use Polymorphine\Http\Tests\Doubles\FakeRequestHandler;
use Polymorphine\Http\Tests\Doubles\FakeResponse;
use Polymorphine\Http\Tests\Doubles\FakeServerRequest;
use Polymorphine\Http\Tests\Fixtures\SessionGlobalState;
use Psr\Http\Server\MiddlewareInterface;
use Closure;
use RuntimeException;

require_once dirname(dirname(__DIR__)) . '/Fixtures/session-functions.php';
require_once dirname(dirname(__DIR__)) . '/Fixtures/time-functions.php';


class SessionContextTest extends TestCase
{
    public function tearDown()
    {
        SessionGlobalState::reset();
    }

    public function testInstantiation()
    {
        $context = $this->context();
        $this->assertInstanceOf(MiddlewareInterface::class, $context);
        $this->assertInstanceOf(SessionContext::class, $context);
    }

    public function testSessionInitialization()
    {
        $headers = new ResponseHeaders();
        $context = new SessionContext($headers);

        $handler = $this->handler(function () use ($context) {
            $context->session()->set('foo', 'bar');
            return new FakeResponse();
        });
        $cookie = ['Set-Cookie' => [
            SessionGlobalState::$name . '=12345657890ABCD'
        ]];

        $context->process($this->request(), $handler);

        $this->assertSame(['foo' => 'bar'], SessionGlobalState::$data);
        $this->assertSame($cookie, $headers->data());
    }

    public function testSessionResume()
    {
        SessionGlobalState::$data = ['foo' => 'bar'];

        $headers = new ResponseHeaders();
        $context = new SessionContext($headers);

        $handler = $this->handler(function () use ($context) {
            $session = $context->session();
            $session->set('foo', $session->get('foo') . '-baz');
            return new FakeResponse();
        });

        $context->process($this->request(true), $handler);

        $this->assertSame(['foo' => 'bar-baz'], SessionGlobalState::$data);
        $this->assertSame([], $headers->data());
    }

    public function testSessionDestroy()
    {
        SessionGlobalState::$data = ['foo' => 'bar'];

        $headers = new ResponseHeaders();
        $context = new SessionContext($headers);

        $handler = $this->handler(function () use ($context) {
            $session = $context->session();
            $session->clear();
            return new FakeResponse();
        });

        $context->process($this->request(true), $handler);

        $cookie = ['Set-Cookie' => [
            SessionGlobalState::$name . '=; Expires=Thursday, 02-May-2013 00:00:00 UTC; MaxAge=-157680000'
        ]];

        $this->assertSame([], SessionGlobalState::$data);
        $this->assertSame($cookie, $headers->data());
    }

    public function testProcessingWhileSessionStarted_ThrowsException()
    {
        $this->expectException(RuntimeException::class);

        SessionGlobalState::$status = PHP_SESSION_ACTIVE;

        $context = $this->context();
        $context->process($this->request(true), $this->handler());
    }

    public function testCallingSessionWithoutContextProcessing_ThrowsException()
    {
        $this->expectException(RuntimeException::class);

        $context = $this->context();
        $context->session();
    }

    private function context(ResponseHeaders $headers = null)
    {
        return new SessionContext($headers ?? new ResponseHeaders());
    }

    private function request($cookie = false)
    {
        $request = new FakeServerRequest();

        if ($cookie) {
            $request->cookies[SessionGlobalState::$name] = SessionGlobalState::$id;
        }

        return $request;
    }

    private function handler(Closure $response = null)
    {
        $response = $response ?? function () { return new FakeResponse(); };
        return new FakeRequestHandler($response);
    }
}
