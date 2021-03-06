<?php declare(strict_types=1);

/*
 * This file is part of Polymorphine/App package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\App;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;


final class ServerProcess
{
    private RequestHandlerInterface $requestHandler;
    private int                     $outputBufferSize;

    /**
     * When large handler's responses are not expected
     * buffer size parameter might be omitted.
     *
     * @param RequestHandlerInterface $requestHandler
     * @param int                     $outputBufferSize (bytes)
     */
    public function __construct(RequestHandlerInterface $requestHandler, int $outputBufferSize = 0)
    {
        $this->requestHandler   = $requestHandler;
        $this->outputBufferSize = $outputBufferSize;
    }

    /**
     * Emits response for given request.
     *
     * @param ServerRequestInterface $request
     */
    public function execute(ServerRequestInterface $request): void
    {
        $this->emitResponse($this->requestHandler->handle($request));
    }

    private function emitResponse(ResponseInterface $response)
    {
        if (headers_sent()) {
            throw new RuntimeException('Headers already sent (application output side-effect)');
        }

        header_remove();

        $this->setStatus($response);
        $this->setHeaders($response->getHeaders());
        $this->emitBody($response);
    }

    private function setStatus(ResponseInterface $response)
    {
        $status = 'HTTP/' . $response->getProtocolVersion() . ' ' . $response->getStatusCode();
        $reason = $response->getReasonPhrase();
        header($status . ($reason ? ' ' . $reason : ''), true);
    }

    private function setHeaders(array $headers)
    {
        foreach ($headers as $name => $headerValues) {
            $this->setHeaderValues($name, $headerValues);
        }
    }

    private function setHeaderValues($name, array $headerValues)
    {
        foreach ($headerValues as $value) {
            header($name . ': ' . $value, false);
        }
    }

    private function emitBody(ResponseInterface $response)
    {
        $body = $response->getBody();

        if (!$this->chunksRequired($body)) {
            echo $body;
            return;
        }

        if ($body->isSeekable()) { $body->rewind(); }

        while (!$body->eof()) {
            echo $body->read($this->outputBufferSize);
        }
    }

    private function chunksRequired(StreamInterface $body): bool
    {
        return $this->outputBufferSize && $body->isReadable() && $body->getSize() > $this->outputBufferSize;
    }
}
