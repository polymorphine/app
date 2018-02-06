<?php

namespace Shudd3r\Http\Tests\Doubles;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Shudd3r\Http\Src\Routing\Route;
use Shudd3r\Http\Tests\Message\Doubles\FakeUri;


class MockedRoute implements Route
{
    public $id;
    public $callback;
    public $path;

    public function forward(ServerRequestInterface $request) {
        if ($this->callback) { return $this->callback->__invoke($request); }
        return $this->id ? new DummyResponse($this->id) : null;
    }

    public function gateway(string $path): Route {
        $this->path = $path;
        return $this;
    }

    public function uri(array $params = [], UriInterface $prototype = null): UriInterface {
        return $this->id ? new FakeUri($this->id) : new FakeUri();
    }
}
