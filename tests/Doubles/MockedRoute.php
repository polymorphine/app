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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Polymorphine\Http\Routing\Route;
use Closure;


class MockedRoute extends Route
{
    public $id;
    public $callback;
    public $path;
    public $uriScheme;

    public function __construct(string $id, Closure $callback = null)
    {
        $this->id = $id;
        $this->callback = $callback;
    }

    public function forward(ServerRequestInterface $request): ?ResponseInterface
    {
        if ($this->callback) {
            return $this->callback->__invoke($request);
        }

        return $this->id ? new DummyResponse($this->id) : null;
    }

    public function gateway(string $path): Route
    {
        $this->path = $path;

        return $this;
    }

    public function uri(array $params = [], UriInterface $prototype = null): UriInterface
    {
        $uri = $this->id ? Uri::fromString($this->id) : Uri::fromString();

        if ($this->uriScheme) {
            $uri = $uri->withScheme($this->uriScheme);
        }

        return $uri;
    }
}
