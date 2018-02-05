<?php

namespace Shudd3r\Http\Src;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Container\ContainerInterface;
use Shudd3r\Http\Src\Container\Factory\ContainerFactory;
use Shudd3r\Http\Src\Routing\Route;
use Shudd3r\Http\Src\Message\NotFoundResponse;


abstract class App
{
    private $containerFactory;

    public function __construct(Container\Factory $factory = null) {
        $this->containerFactory = $factory ?: $this->factory();
    }

    public function execute(ServerRequestInterface $request): ResponseInterface {
        $container = $this->containerFactory->container();
        $response  = $this->routing($container)->forward($request);

        return $response ?: $this->notFoundResponse();
    }

    public function config(string $id): InputProxy {
        return new InputProxy($id, $this->containerFactory);
    }

    protected function notFoundResponse() {
        return new NotFoundResponse();
    }

    protected function factory() {
        return new ContainerFactory();
    }

    protected abstract function routing(ContainerInterface $c): Route;
}
