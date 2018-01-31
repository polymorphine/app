<?php

namespace Shudd3r\Http\Src\Routing;

use Psr\Http\Message\ServerRequestInterface;
use Shudd3r\Http\Src\Routing\Gateway;
use Shudd3r\Http\Src\Routing\Exception\GatewayNotFoundException;
use Shudd3r\Http\Tests\Doubles\DummyResponse;
use Closure;


class StaticRoute implements Route
{
    private $path;
    private $callback;

    public function __construct(string $path, Closure $callback) {
        $this->path = $path;
        $this->callback = $callback;
    }

    public function forward(ServerRequestInterface $request) {
        return new DummyResponse();
    }

    public function gateway(string $path): Gateway {
        return new EndpointGateway();
    }

}
