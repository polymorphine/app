<?php

namespace Shudd3r\Http\Src;

use Psr\Container\ContainerInterface;
use Shudd3r\Http\Src\Container\Factory\ContainerFactory;
use Shudd3r\Http\Src\Routing\Route;
use Shudd3r\Http\Src\Container\Registry;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Shudd3r\Http\Src\Container\Factory\RegistryInput;
use Shudd3r\Http\Src\Container\Registry\FlatRegistry;
use Shudd3r\Http\Src\Message\NotFoundResponse;


abstract class App
{
    private $containerFactory;

    public function __construct(Registry $registry = null) {
        $this->containerFactory = new ContainerFactory($registry ?: $this->registry());
    }

    public function execute(ServerRequestInterface $request): ResponseInterface {
        $container = $this->containerFactory->container();
        $response  = $this->routing($container)->forward($request);

        return $response ?: $this->notFoundResponse();
    }

    public function config(string $id): RegistryInput {
        return $this->containerFactory->addRecord($id);
    }

    protected function notFoundResponse() {
        return new NotFoundResponse();
    }

    protected function registry() {
        return new FlatRegistry();
    }

    protected abstract function routing(ContainerInterface $c): Route;
}
