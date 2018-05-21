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
use Polymorphine\Http\Server\Session;
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
        $session = $this->session();
        $this->assertInstanceOf(MiddlewareInterface::class, $session);
        $this->assertInstanceOf(SessionContext::class, $session);
    }

    public function testSessionInitialization()
    {
        $session  = new Session(SessionGlobalState::$sessionName);
        $headers  = new ResponseHeaders();
        $response = function () use ($session) {
            $session->storage()->set('foo', 'bar');
            return new FakeResponse();
        };
        $cookie = ['Set-Cookie' => [
            SessionGlobalState::$sessionName . '=12345657890ABCD'
        ]];

        $this->process($headers, $session, $response);
        $this->assertSame(['foo' => 'bar'], SessionGlobalState::$sessionData);
        $this->assertSame($cookie, $headers->data());
    }

    public function testSessionResume()
    {
        SessionGlobalState::$sessionData = ['foo' => 'bar'];

        $session  = new Session();
        $headers  = new ResponseHeaders();
        $response = function () use ($session) {
            $session->storage()->set('foo', $session->storage()->get('foo') . '-baz');
            return new FakeResponse();
        };

        $this->process($headers, $session, $response, true);
        $this->assertSame(['foo' => 'bar-baz'], SessionGlobalState::$sessionData);
        $this->assertSame([], $headers->data());
    }

    public function testSessionDestroy()
    {
        SessionGlobalState::$sessionData = ['foo' => 'bar'];

        $session  = new Session();
        $headers  = new ResponseHeaders();
        $response = function () use ($session) {
            $session->storage()->clear('foo');
            return new FakeResponse();
        };
        $cookie = ['Set-Cookie' => [
            SessionGlobalState::$sessionName . '=; Expires=Thursday, 02-May-2013 00:00:00 UTC; MaxAge=-157680000'
        ]];

        $this->process($headers, $session, $response, true);
        $this->assertSame([], SessionGlobalState::$sessionData);
        $this->assertSame($cookie, $headers->data());
    }

    public function testProcessingWithStartedSession_ThrowsException()
    {
        $this->expectException(RuntimeException::class);

        SessionGlobalState::$sessionStatus = PHP_SESSION_ACTIVE;

        $response = function () { return new FakeResponse(); };
        $this->process(new ResponseHeaders(), new Session(), $response, true);
    }

    private function session(ResponseHeaders $headers = null, Session $session = null)
    {
        return new SessionContext($headers ?? new ResponseHeaders(), $session ?? new Session());
    }

    private function request($cookie = false)
    {
        $request = new FakeServerRequest();

        if ($cookie) {
            $request->cookies[SessionGlobalState::$sessionName] = SessionGlobalState::$sessionId;
        }

        return $request;
    }

    private function handler(Closure $response = null)
    {
        $response = $response ?? function () { return new FakeResponse(); };
        return new FakeRequestHandler($response);
    }

    private function process($headers, $session, $response, $cookie = false)
    {
        return $this->session($headers, $session)->process($this->request($cookie), $this->handler($response));
    }
}
