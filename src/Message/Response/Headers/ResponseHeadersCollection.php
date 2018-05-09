<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Message\Response\Headers;

use Psr\Http\Message\ResponseInterface;


class ResponseHeadersCollection
{
    private $headers;

    public function __construct(array $defaultHeaders = [])
    {
        $this->headers = $defaultHeaders;
    }

    public function cookie(string $name): CookieSetup
    {
        return new CookieSetup($name, $this);
    }

    public function addHeader(string $name, string $header): void
    {
        $this->headers[$name][] = $header;
    }

    public function setHeaders(ResponseInterface $response): ResponseInterface
    {
        foreach ($this->headers as $name => $headerLines) {
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
