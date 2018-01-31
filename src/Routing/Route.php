<?php

namespace Shudd3r\Http\Src\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shudd3r\Http\Src\Routing\Exception\GatewayNotFoundException;


interface Route
{
    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface|null
     */
    public function forward(ServerRequestInterface $request);

    /**
     * @param string $path
     * @return Gateway
     * @throws GatewayNotFoundException
     */
    public function gateway(string $path): Gateway;
}
