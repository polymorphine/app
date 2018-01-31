<?php

namespace Shudd3r\Http\Tests\Doubles;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shudd3r\Http\Src\Routing\Route;
use Shudd3r\Http\Src\App;
use Shudd3r\Http\Src\Routing\StaticRoute;


class DummyApp extends App
{
    protected function routing(ContainerInterface $c): Route {
        return new StaticRoute('/path', function (ServerRequestInterface $request) use ($c) {
            return new DummyResponse();
        });
    }
}
