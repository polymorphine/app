<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Server\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Polymorphine\Http\Message\Response\Headers\ResponseHeadersCollection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;


class SetResponseHeaders implements MiddlewareInterface
{
    private $headers;

    public function __construct(ResponseHeadersCollection $headers)
    {
        $this->headers = $headers;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        foreach ($this->headers->data() as $name => $headerLines) {
            $response = $this->addHeaderLines($response, $name, $headerLines);
        }

        return $response;
    }

    private function addHeaderLines(ResponseInterface $response, string $name, array $headerLines): ResponseInterface
    {
        foreach ($headerLines as $header) {
            $response = $response->withAddedHeader($name, $header);
        }

        return $response;
    }
}
