<?php

namespace Shudd3r\Http\Src\Routing;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Shudd3r\Http\Src\Message\Uri;
use Shudd3r\Http\Src\Routing\Exception\GatewayCallException;
use Closure;


class StaticEndpoint implements Route
{
    private $path;
    private $callback;

    public function __construct(string $path, Closure $callback) {
        $this->path     = $path;
        $this->callback = $callback;
    }

    public function forward(ServerRequestInterface $request) {
        if ($this->path !== $request->getUri()->getPath()) { return null; }
        return $this->callback->__invoke($request);
    }

    public function gateway(string $path): Route {
        throw new GatewayCallException();
    }

    public function uri(array $params = [], UriInterface $prototype): UriInterface {
        return $prototype ? $prototype->withPath($this->path) : new Uri($this->path);
    }
}
