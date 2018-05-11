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

    public function add(string $name, string $header): void
    {
        $this->headers[$name][] = $header;
    }

    public function data(): array
    {
        return $this->headers;
    }
}
