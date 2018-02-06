<?php

namespace Shudd3r\Http\Src\Routing\Route;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Shudd3r\Http\Src\Routing\Exception\EndpointCallException;
use Shudd3r\Http\Src\Routing\Exception\GatewayCallException;
use Shudd3r\Http\Src\Routing\Route;


class RouteSelection implements Route
{
    private $routes = [];

    public function __construct(array $routes) {
        $this->routes = $routes;
    }

    public function forward(ServerRequestInterface $request) {
        $response = null;
        foreach ($this->routes as $route) {
            $response = $this->response($route, $request);
            if ($response) { break; }
        }

        return $response;
    }

    public function gateway(string $path): Route {
        list($id, $path) = explode(self::PATH_SEPARATOR, $path, 2) + [false, false];

        if (!$id) {
            throw new GatewayCallException('Invalid gateway path - non empty string required');
        }

        if (!isset($this->routes[$id])) {
            throw new GatewayCallException(sprintf('Gateway `%s` not found', $id));
        }

        return (!$path) ? $this->routes[$id] : $this->route($this->routes[$id], $path);
    }

    public function uri(array $params = [], UriInterface $prototype = null): UriInterface {
        throw new EndpointCallException('Cannot get Uri from gateway route');
    }

    private function response(Route $route, ServerRequestInterface $request) {
        return $route->forward($request);
    }

    private function route(Route $route, string $path) {
        return $route->gateway($path);
    }
}
