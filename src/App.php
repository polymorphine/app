<?php

namespace Shudd3r\Http\Src;

use Psr\Container\ContainerInterface;
use Shudd3r\Http\Src\Routing\Route;
use Shudd3r\Http\Src\Container\Registry;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Shudd3r\Http\Src\Container\Records\RegistryInput;
use Shudd3r\Http\Src\Container\FlatRegistry;


abstract class App
{
    private $registry;

    public function __construct(Registry $registry = null) {
        $this->registry = $registry ?? new FlatRegistry();
    }

    public function execute(ServerRequestInterface $request): ResponseInterface {
        return $this->routing($this->registry->container())->forward($request);
    }

    public function config(string $id): RegistryInput {
        return $this->registry->entry($id);
    }

    protected abstract function routing(ContainerInterface $c): Route;
}
