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

use Polymorphine\Container\Exception\InvalidIdException;
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
    public const APP_ROUTER_ID = 'app.router';

    private $setup;

    /**
     * @param Record[] $records
     */
    public function __construct(array $records = [])
    {
        $this->setup = new ContainerSetup($records);
        $this->environmentSetup();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $rootRoute = $this->setup->container()->get(static::APP_ROUTER_ID);

        return $rootRoute->forward($request) ?: $this->notFoundResponse();
    }

    public function config(string $id): RecordSetup
    {
        return $this->setup->entry($id);
    }

    protected function environmentSetup()
    {
        if ($this->setup->exists(static::APP_ROUTER_ID)) {
            $message = 'Internal router key `%s` used as container entry - rename entry or %s APP_ROUTER_ID constant';
            $override = static::APP_ROUTER_ID === self::APP_ROUTER_ID ? 'override' : 'change';
            throw new InvalidIdException(sprintf($message, static::APP_ROUTER_ID, $override));
        }

        $this->setup->entry(static::APP_ROUTER_ID)->lazy(function (ContainerInterface $container) {
            return $this->routing($container);
        });
    }

    protected function notFoundResponse()
    {
        return new NotFoundResponse();
    }

    abstract protected function routing(ContainerInterface $c): Route;
}
