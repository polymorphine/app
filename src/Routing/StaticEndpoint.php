<?php

namespace Shudd3r\Http\Src\Routing;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Shudd3r\Http\Src\Message\Uri;
use Shudd3r\Http\Src\Routing\Exception\GatewayCallException;
use Closure;


class StaticEndpoint implements Route
{
    private $method;
    private $path;
    private $callback;

    public function __construct(string $method, string $path, Closure $callback) {
        $this->method   = $method;
        $this->path     = $path;
        $this->callback = $callback;
    }

    public function forward(ServerRequestInterface $request) {
        if ($this->method !== $request->getMethod()) { return null; }
        if ($this->path !== $request->getUri()->getPath()) { return null; }
        return $this->callback->__invoke($request);
    }

    public function gateway(string $path): Route {
        throw new GatewayCallException();
    }

    public function uri(array $params = [], UriInterface $prototype = null): UriInterface {
        return $prototype ? $prototype->withPath($this->path) : Uri::fromString($this->path);
    }

    public static function post(string $path, Closure $callback) {
        return new self('POST', $path, $callback);
    }

    public static function get(string $path, Closure $callback) {
        return new self('GET', $path, $callback);
    }
}
