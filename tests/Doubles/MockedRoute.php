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

use Polymorphine\Routing\Route;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Closure;


class MockedRoute implements Route
{
    public $path;
    public $callback;
    public $selected;

    public function __construct(string $path, Closure $callback = null)
    {
        $this->path     = $path;
        $this->callback = $callback;
    }

    public function forward(ServerRequestInterface $request, ResponseInterface $notFound): ResponseInterface
    {
        if ($this->callback) {
            return $this->callback->__invoke($request) ?? $notFound;
        }
        return $this->path ? new FakeResponse($this->path) : $notFound;
    }

    public function select(string $path): Route
    {
        $this->selected = $path;
        return $this;
    }

    public function uri(UriInterface $prototype, array $params): UriInterface
    {
        return $this->path ? $prototype->withPath($this->path) : $prototype;
    }
}
