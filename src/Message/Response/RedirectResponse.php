<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Message\Response;

use Polymorphine\Container\Exception\InvalidArgumentException;
use Polymorphine\Http\Message\Response;
use Polymorphine\Http\Message\Stream;
use Psr\Http\Message\UriInterface;


class RedirectResponse extends Response
{
    public function __construct(string $uri, int $status = 303)
    {
        if ($status < 300 || $status > 399) {
            throw new InvalidArgumentException('Invalid status code for redirect response');
        }

        parent::__construct($status, new Stream(fopen('php://temp', 'r')), ['Location' => $uri]);
    }

    public static function fromUri(UriInterface $uri, int $status = 303)
    {
        return new self((string) $uri, $status);
    }
}