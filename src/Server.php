<?php

/*
 * This file is part of Polymorphine/App package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;


class Server
{
    private $app;
    private $buffer;

    /**
     * When large handler's responses are not expected
     * buffer size parameter might be omitted.
     *
     * @param RequestHandlerInterface $app
     * @param int                     $outputBufferSize (bytes)
     */
    public function __construct(RequestHandlerInterface $app, int $outputBufferSize = 0)
    {
        $this->app    = $app;
        $this->buffer = $outputBufferSize;
    }

    public function sendResponse(ServerRequestInterface $request): void
    {
        $response = $this->app->handle($request);
        $this->emit($response);
    }

    private function emit(ResponseInterface $response)
    {
        if (headers_sent()) {
            throw new RuntimeException('Headers already sent (application output side-effect)');
        }

        header_remove();

        $this->status($response);
        $this->headers($response->getHeaders());
        $this->body($response);
    }

    private function status(ResponseInterface $response)
    {
        $status = 'HTTP/' . $response->getProtocolVersion() . ' ' . $response->getStatusCode();
        $reason = $response->getReasonPhrase();
        header($status . ($reason ? ' ' . $reason : ''), true);
    }

    private function headers(array $headers)
    {
        foreach ($headers as $name => $headerValues) {
            $this->sendHeaderValues($name, $headerValues);
        }
    }

    private function sendHeaderValues($name, array $headerValues)
    {
        foreach ($headerValues as $value) {
            header($name . ': ' . $value, false);
        }
    }

    private function body(ResponseInterface $response)
    {
        $body = $response->getBody();

        if (!$this->chunksRequired($body)) {
            echo $body;
            return;
        }

        if ($body->isSeekable()) { $body->rewind(); }

        while (!$body->eof()) {
            echo $body->read($this->buffer);
        }
    }

    private function chunksRequired(StreamInterface $body)
    {
        return $this->buffer && $body->isReadable() && $body->getSize() > $this->buffer;
    }
}
