<?php

namespace Polymorphine\Http\Routing\Route;

use Psr\Http\Message\ServerRequestInterface;
use Polymorphine\Http\Routing\Exception;
use Polymorphine\Http\Routing\Route;


class FirstMatchForwardGateway extends Route
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
            throw new Exception\GatewayCallException('Invalid gateway path - non empty string required');
        }

        if (!isset($this->routes[$id])) {
            throw new Exception\GatewayCallException(sprintf('Gateway `%s` not found', $id));
        }

        return $path ? $this->route($this->routes[$id], $path) : $this->routes[$id];
    }

    private function response(Route $route, ServerRequestInterface $request) {
        return $route->forward($request);
    }

    private function route(Route $route, string $path) {
        return $route->gateway($path);
    }
}
