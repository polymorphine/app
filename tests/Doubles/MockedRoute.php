<?php

namespace Shudd3r\Http\Tests\Doubles;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Shudd3r\Http\Src\Routing\Route;
use Shudd3r\Http\Tests\Message\Doubles\FakeUri;


class MockedRoute implements Route
{
    public $callback;

    public function forward(ServerRequestInterface $request) {
        return $this->callback ? $this->callback->__invoke($request) : null;
    }

    public function gateway(string $path): Route {
        return new self;
    }

    public function uri(array $params, UriInterface $prototype = null): UriInterface {
        return new FakeUri();
    }
}
