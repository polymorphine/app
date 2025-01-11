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

use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\StreamInterface;
use RuntimeException;


final class ServerProcess
{
    private Handler $requestHandler;
    private int     $outputBufferBytes;

    /**
     * When large handler's responses are not expected
     * buffer size parameter might be omitted.
     *
     * @param Handler $requestHandler
     * @param int     $outputBufferBytes
     */
    public function __construct(Handler $requestHandler, int $outputBufferBytes = 0)
    {
        $this->requestHandler    = $requestHandler;
        $this->outputBufferBytes = $outputBufferBytes;
    }

    /**
     * Emits response for given request.
     *
     * @param Request $request
     */
    public function execute(Request $request): void
    {
        $this->emitResponse($this->requestHandler->handle($request));
    }

    private function emitResponse(Response $response)
    {
        if (headers_sent()) {
            throw new RuntimeException('Headers already sent (application output side-effect)');
        }

        header_remove();

        $this->setStatus($response);
        $this->setHeaders($response->getHeaders());
        $this->emitBody($response);
    }

    private function setStatus(Response $response)
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

    private function emitBody(Response $response)
    {
        $body = $response->getBody();

        if (!$this->chunksRequired($body)) {
            echo $body;
            return;
        }

        if ($body->isSeekable()) { $body->rewind(); }

        while (!$body->eof()) {
            echo $body->read($this->outputBufferBytes);
        }
    }

    private function chunksRequired(StreamInterface $body): bool
    {
        return $this->outputBufferBytes && $body->isReadable() && $body->getSize() > $this->outputBufferBytes;
    }
}
