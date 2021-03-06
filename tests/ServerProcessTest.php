<?php

/*
 * This file is part of Polymorphine/App package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\App\Tests;

use PHPUnit\Framework\TestCase;
use Polymorphine\App\ServerProcess;
use Polymorphine\App\Tests\Doubles\FakeRequestHandler;
use Polymorphine\App\Tests\Doubles\FakeServerRequest;
use Polymorphine\App\Tests\Doubles\FakeResponse;
use Polymorphine\App\Tests\Doubles\FakeStream;
use Polymorphine\App\Tests\Fixtures\HeadersState;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

require_once __DIR__ . '/Fixtures/header-functions.php';


class ServerProcessTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(ServerProcess::class, $this->server());
    }

    public function testResponseBodyIsEmitted()
    {
        $server = $this->server(new FakeResponse('Hello World!'));
        $this->assertSame('Hello World!', $this->emit($server));
    }

    public function testResponseBodyExceedingOutputBufferIsEmitted()
    {
        $response = new FakeResponse(new FakeStream('Hello World'));
        $server   = $this->server($response, 3);
        $this->assertSame('Hello World', $this->emit($server));
    }

    public function testSendResponseWhenHeadersAlreadySent_ThrowsException()
    {
        $server = $this->server();

        HeadersState::$outputSent = true;
        $this->expectException(RuntimeException::class);
        $this->emit($server);
    }

    public function testHeadersAreEmitted()
    {
        $response = new FakeResponse();

        $response->headers = [
            'Header-Name'     => ['value1', 'value2'],
            'X-Custom-Header' => ['very important']
        ];

        $response->status   = 205;
        $response->protocol = '2';
        $response->reason   = 'Custom reason';

        $this->emit($this->server($response));

        $expected = [
            'status'          => ['HTTP/2 205 Custom reason'],
            'header-name'     => ['Header-Name: value1', 'Header-Name: value2'],
            'x-custom-header' => ['X-Custom-Header: very important']
        ];
        $this->assertSame($expected, HeadersState::$headers);
    }

    public function testHeadersSetOutsideServerInstanceAreIgnored()
    {
        $response = new FakeResponse();
        $server   = $this->server($response);

        $response->headers = [
            'X-Custom-Header' => ['only this one', 'one more'],
            'Set-Cookie'      => ['my session cookie']
        ];

        HeadersState::$headers['x-custom-header'] = ['X-Custom-Header: this one is removed'];
        HeadersState::$headers['set-cookie']      = ['Set-Cookie: default session cookie'];
        HeadersState::$headers['x-powered-by']    = ['X-Powered-By: PHPUnit Framework'];

        $this->emit($server);

        $this->assertSame(['X-Custom-Header: only this one', 'X-Custom-Header: one more'], HeadersState::$headers['x-custom-header']);
        $this->assertSame(['Set-Cookie: my session cookie'], HeadersState::$headers['set-cookie']);
        $this->assertFalse(isset(HeadersState::$headers['x-powered-by']));
    }

    private function server(ResponseInterface $response = null, int $buffer = 0)
    {
        HeadersState::reset();
        $requestHandler = new FakeRequestHandler(function (ServerRequestInterface $request) use ($response) {
            return $response ?: new FakeResponse();
        });
        return new ServerProcess($requestHandler, $buffer);
    }

    private function emit(ServerProcess $server, ServerRequestInterface $request = null)
    {
        ob_start();
        try {
            $server->execute($request ?: new FakeServerRequest());
        } catch (RuntimeException $ex) {
            ob_get_clean();
            throw $ex;
        }
        return ob_get_clean();
    }
}
