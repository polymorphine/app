<?php

namespace Polymorphine\Http\Routing\Route;

use Polymorphine\Http\Routing\Route;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Polymorphine\Http\Message\Uri;
use Closure;


class StaticEndpoint extends Route
{
    private $method;
    private $path;
    private $callback;

    public function __construct(string $method, string $path, Closure $callback) {
        $this->method   = $method;
        $this->path     = $path;
        $this->callback = $callback;
    }

    public static function post(string $path, Closure $callback) {
        return new self('POST', $path, $callback);
    }

    public static function get(string $path, Closure $callback) {
        return new self('GET', $path, $callback);
    }

    public function forward(ServerRequestInterface $request) {
        return ($this->methodMatch($request) && $this->targetMatch($request))
            ? $this->callback->__invoke($request)
            : null;
    }

    public function uri(array $params = [], UriInterface $prototype = null): UriInterface {
        $uri = $prototype ? $prototype->withPath($this->path) : Uri::fromString($this->path);
        return !empty($params) ? $uri->withQuery($this->buildQueryString($params)) : $uri;
    }

    private function methodMatch(ServerRequestInterface $request): bool {
        return ($this->method === $request->getMethod());
    }

    private function targetMatch(ServerRequestInterface $request): bool {
        $target = $request->getRequestTarget();
        if ($this->path === $target) { return true; }
        if (!$pos = strpos($target, '?')) { return false; } //0 means empty path anyway
        return ($this->path === substr($target, 0, $pos));
    }

    private function buildQueryString(array $params) {
        foreach ($params as $name => &$param) {
            $param = $name . '=' . $param;
        }

        return implode('&', $params);
    }
}
