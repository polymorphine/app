<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http;

use Polymorphine\Http\Message\ServerRequestFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;


class Server
{
    protected $app;
    protected $outputBufferSize;

    public function __construct(RequestHandlerInterface $app, int $outputBufferSize = 0)
    {
        $this->app = $app;
        $this->outputBufferSize = $outputBufferSize;
    }

    public function sendResponse(ServerRequestInterface $request = null): void
    {
        $response = $this->app->handle($request ?: ServerRequestFactory::fromGlobals());

        $this->emit($response);
    }

    protected function emit(ResponseInterface $response)
    {
        $this->status($response->getProtocolVersion(), $response->getStatusCode(), $response->getReasonPhrase());
        $this->headers($response->getHeaders());
        $this->body($response);
    }

    protected function status($protocol, $statusCode, $reasonPhrase)
    {
        if (headers_sent()) {
            throw new RuntimeException('Headers already sent (application output side-effect)');
        }

        $string = 'HTTP/' . $protocol . ' ' . $statusCode . ($reasonPhrase ? ' ' . $reasonPhrase : '');
        header($string, true);
    }

    protected function headers(array $headers)
    {
        foreach ($headers as $name => $values) {
            $this->removePredefined($name);
            $this->sendHeaderValues($name, $values);
        }
    }

    protected function removePredefined(string $name)
    {
        if (strtolower($name) === 'set-cookie') { return; }
        header_remove($name);
    }

    protected function sendHeaderValues($name, array $values)
    {
        foreach ($values as $value) {
            header($name . ': ' . $value, false);
        }
    }

    protected function body(ResponseInterface $response)
    {
        $body = $response->getBody();

        if (!$this->chunksRequired($body)) {
            echo $body;
            return;
        }

        if ($body->isSeekable()) {
            $body->rewind();
        }

        while (!$body->eof()) {
            echo $body->read($this->outputBufferSize);
        }
    }

    private function chunksRequired(StreamInterface $body)
    {
        return ($this->outputBufferSize && $body->isReadable() && $body->getSize() > $this->outputBufferSize);
    }
}
