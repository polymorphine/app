<?php

namespace Shudd3r\Http\Src;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Shudd3r\Http\Src\Container\Records\RegistryInput;
use Shudd3r\Http\Src\Container\Registry;
use Shudd3r\Http\Tests\Doubles\DummyResponse;


class App
{
    private $registry;

    public function __construct(Registry $registry = null) {
        $this->registry = $registry ?? new Registry();
    }

    public function execute(ServerRequestInterface $request): ResponseInterface {
        return new DummyResponse($this->registry->container());
    }

    public function config(string $id): RegistryInput {
        return $this->registry->entry($id);
    }
}
