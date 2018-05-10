<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Container\ContainerInterface;
use Polymorphine\Container\ContainerSetup;
use Polymorphine\Container\Setup\Record;
use Polymorphine\Container\Setup\RecordSetup;
use Polymorphine\Http\Routing\Route;
use Polymorphine\Http\Message\Response\NotFoundResponse;


abstract class App implements RequestHandlerInterface
{
    protected const APP_ROUTER_ID = 'app.router';

    private $setup;

    /**
     * @param Record[] $records
     */
    public function __construct(array $records = [])
    {
        $this->setup = $this->containerSetup($records);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $container = $this->setup->container();
        $routing   = $this->routing($container);

        $this->setup->entry(static::APP_ROUTER_ID)->value($routing);

        return $routing->forward($request) ?: $this->notFoundResponse();
    }

    public function config(string $id): RecordSetup
    {
        return $this->setup->entry($id);
    }

    protected function notFoundResponse()
    {
        return new NotFoundResponse();
    }

    protected function containerSetup(array $records)
    {
        return new ContainerSetup($records);
    }

    abstract protected function routing(ContainerInterface $c): Route;
}
