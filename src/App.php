<?php

namespace Shudd3r\Http\Src;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Shudd3r\Http\Tests\Doubles\DummyResponse;


class App
{
    public function execute(ServerRequestInterface $request): ResponseInterface {
        return new DummyResponse();
    }
}
