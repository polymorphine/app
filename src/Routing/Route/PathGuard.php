<?php

namespace Shudd3r\Http\Src\Routing\Route;

use Psr\Http\Message\ServerRequestInterface;
use Shudd3r\Http\Src\Routing\Route;


class PathGuard extends Route
{
    private $path;
    private $routes;

    public function __construct(string $path, Route $routes) {
        $this->path    = $path;
        $this->routes  = $routes;
    }

    public function forward(ServerRequestInterface $request) {
        return (strpos($request->getRequestTarget(), $this->path) === 0)
            ? $this->routes->forward($request)
            : null;
    }

    public function gateway(string $path): Route {
        return $this->routes->gateway($path);
    }
}
