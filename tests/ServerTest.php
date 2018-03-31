<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Tests;

use PHPUnit\Framework\TestCase;
use Polymorphine\Http\Headers;
use Polymorphine\Http\Message\Stream;
use Polymorphine\Http\Server;
use Polymorphine\Http\Tests\Doubles\DummyRequest;
use Polymorphine\Http\Tests\Doubles\DummyResponse;
use Polymorphine\Http\Tests\Doubles\FakeRequestHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;


class ServerTest extends TestCase
{
    private function server(ResponseInterface $response = null, int $buffer = 0)
    {
        Headers::reset();
        return new Server(new FakeRequestHandler($response ?: new DummyResponse()), $buffer);
    }

    private function emit(Server $server, ServerRequestInterface $request = null)
    {
        ob_start();
        try {
            $server->sendResponse($request ?: new DummyRequest());
        } catch (RuntimeException $ex) {
            ob_get_clean();
            throw $ex;
        }
        return ob_get_clean();
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf(Server::class, $this->server());
    }

    public function testResponseBodyIsEmitted()
    {
        $server = $this->server(new DummyResponse('Hello World!'));
        $this->assertSame('Hello World!', $this->emit($server));
    }

    public function testResponseBodyExceedingOutputBufferIsEmitted()
    {
        $response = new DummyResponse(Stream::fromBodyString('Hello World'));
        $server = $this->server($response, 3);
        $this->assertSame('Hello World', $this->emit($server));
    }

    public function testSendResponseWhenHeadersAlreadySent_ThrowsException()
    {
        $server = $this->server();
        Headers::$sent = true;
        $this->expectException(RuntimeException::class);
        $this->emit($server);
    }

    public function testHeadersAreEmitted()
    {
        $response = new DummyResponse();

        $response->headers = [
            'Header-Name' => ['value1', 'value2'],
            'X-Custom-Header' => ['very important']
        ];

        $response->status = 205;
        $response->protocol = '2';
        $response->reason = 'Custom reason';

        $this->emit($this->server($response));

        $expected = [
            'status' => ['HTTP/2 205 Custom reason'],
            'header-name' => ['Header-Name: value1', 'Header-Name: value2'],
            'x-custom-header' => ['X-Custom-Header: very important']
        ];
        $this->assertSame($expected, Headers::$headers);
    }

    public function testHeadersOverwriteSetOutsideServerInstance()
    {
        $response = new DummyResponse();
        $server = $this->server($response);

        $response->headers = [
            'X-Custom-Header' => ['only this one']
        ];

        Headers::$headers['x-custom-header'] = ['X-Custom-Header: this one is removed'];

        $this->emit($server);

        $this->assertSame(['X-Custom-Header: only this one'], Headers::$headers['x-custom-header']);
    }

    public function testCookieHeaderIsPreserved()
    {
        $response = new DummyResponse();
        $server = $this->server($response);

        $response->headers = [
            'Set-Cookie' => ['yay cookie!']
        ];

        Headers::$headers['set-cookie'] = ['Set-Cookie: session cookie'];

        $this->emit($server);

        $this->assertSame(['Set-Cookie: session cookie', 'Set-Cookie: yay cookie!'], Headers::$headers['set-cookie']);
    }
}

//TODO: extract to separate file
namespace Polymorphine\Http;

class Headers
{
    public static $headers = [];
    public static $sent    = false;

    public static function set(string $headerLine, $remove = true)
    {
        [$name, $type] = explode(':', $headerLine, 2) + [false, 'STATUS'];
        if ($type === 'STATUS') { $name = $type; }
        if ($remove) { self::remove($name); }

        self::$headers[strtolower($name)][] = $headerLine;
    }

    public static function remove(string $name)
    {
        unset(self::$headers[strtolower($name)]);
    }

    public static function reset()
    {
        self::$headers = [];
        self::$sent = false;
    }
}

function header($headerLine, $remove = true) {
    Headers::set($headerLine, $remove);
}

function headers_sent() {
    return Headers::$sent;
}

function header_remove($name) {
    Headers::remove($name);
}
