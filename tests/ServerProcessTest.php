<?php declare(strict_types=1);

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
use RuntimeException;

require_once __DIR__ . '/Fixtures/header-functions.php';


class ServerProcessTest extends TestCase
{
    public function test_Instantiation()
    {
        $this->assertInstanceOf(ServerProcess::class, $this->server());
    }

    public function test_ResponseBody_IsEmitted()
    {
        $server = $this->server(new Doubles\FakeResponse('Hello World!'));
        $this->assertSame('Hello World!', $this->emit($server));
    }

    public function test_ResponseBodyExceedingOutputBuffer_IsEmitted()
    {
        $response = new Doubles\FakeResponse('Hello World');
        $server   = $this->server($response, 3);
        $this->assertSame('Hello World', $this->emit($server));
    }

    public function test_SendResponse_WhenHeadersAlreadySent_ThrowsException()
    {
        $server = $this->server();

        Fixtures\HeadersState::$outputSent = true;
        $this->expectException(RuntimeException::class);
        $this->emit($server);
    }

    public function test_Headers_AreEmitted()
    {
        $response = new Doubles\FakeResponse();

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
        $this->assertSame($expected, Fixtures\HeadersState::$headers);
    }

    public function test_HeadersSetOutsideServerInstance_AreIgnored()
    {
        $response = new Doubles\FakeResponse();
        $server   = $this->server($response);

        $response->headers = [
            'X-Custom-Header' => ['only this one', 'one more'],
            'Set-Cookie'      => ['my session cookie']
        ];

        Fixtures\HeadersState::$headers['x-custom-header'] = ['X-Custom-Header: this one is removed'];
        Fixtures\HeadersState::$headers['set-cookie'] = ['Set-Cookie: default session cookie'];
        Fixtures\HeadersState::$headers['x-powered-by'] = ['X-Powered-By: PHPUnit Framework'];

        $this->emit($server);

        $this->assertSame(['X-Custom-Header: only this one', 'X-Custom-Header: one more'], Fixtures\HeadersState::$headers['x-custom-header']);
        $this->assertSame(['Set-Cookie: my session cookie'], Fixtures\HeadersState::$headers['set-cookie']);
        $this->assertFalse(isset(Fixtures\HeadersState::$headers['x-powered-by']));
    }

    private function server(?Doubles\FakeResponse $response = null, int $buffer = 0): ServerProcess
    {
        Fixtures\HeadersState::reset();
        $requestHandler = new Doubles\FakeRequestHandler(fn () => $response ?: new Doubles\FakeResponse());
        return new ServerProcess($requestHandler, $buffer);
    }

    private function emit(ServerProcess $server): string
    {
        ob_start();
        try {
            $server->execute(new Doubles\FakeServerRequest());
        } catch (RuntimeException $ex) {
            ob_get_clean();
            throw $ex;
        }
        return ob_get_clean();
    }
}
