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

use Polymorphine\Http\Message\ServerRequestFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;
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

    public function sendResponse(ServerRequestInterface $request = null): void
    {
        $response = $this->app->handle($request ?: ServerRequestFactory::fromGlobals());

        $this->emit($response);
    }

    private function emit(ResponseInterface $response)
    {
        $protocol     = $response->getProtocolVersion();
        $statusCode   = $response->getStatusCode();
        $reasonPhrase = $response->getReasonPhrase();
        $headers      = $response->getHeaders();

        $this->status($protocol, $statusCode, $reasonPhrase);
        $this->headers($headers);
        $this->body($response);
    }

    private function status($protocol, $statusCode, $reasonPhrase)
    {
        if (headers_sent()) {
            throw new RuntimeException('Headers already sent (application output side-effect)');
        }

        $string = 'HTTP/' . $protocol . ' ' . $statusCode . ($reasonPhrase ? ' ' . $reasonPhrase : '');
        header($string, true);
    }

    private function headers(array $headers)
    {
        foreach ($headers as $name => $values) {
            $this->removePredefined($name);
            $this->sendHeaderValues($name, $values);
        }
    }

    private function removePredefined(string $name)
    {
        header_remove($name);
    }

    private function sendHeaderValues($name, array $values)
    {
        foreach ($values as $value) {
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
