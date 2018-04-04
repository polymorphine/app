<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Tests\Doubles;

use Polymorphine\Http\Message\Uri;
use Polymorphine\Http\Routing\Route\Pattern;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;


class MockedPattern implements Pattern
{
    private $path;

    public function __construct($path)
    {
        $this->path = $path;
    }

    public function matchedRequest(ServerRequestInterface $request): ?ServerRequestInterface
    {
        $uri = (string) $request->getUri();
        return ($uri === $this->path) ? $request->withAttribute('pattern', 'passed') : null;
    }

    public function uri(array $params, UriInterface $prototype): UriInterface
    {
        return Uri::fromString($this->path);
    }
}
