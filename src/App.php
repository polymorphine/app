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

use Polymorphine\Http\Message\Uri;
use Polymorphine\Http\Message\Response\NotFoundResponse;
use Polymorphine\Container\ContainerSetup;
use Polymorphine\Container\Setup\RecordSetup;
use Polymorphine\Container\Setup\Record;
use Polymorphine\Container\Exception\InvalidIdException;
use Polymorphine\Routing\Router;
use Polymorphine\Routing\Route;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Container\ContainerInterface;


abstract class App implements RequestHandlerInterface
{
    public const ROUTER_ID       = 'app.router';
    public const DEV_ENVIRONMENT = 'APP_DEV';

    private $setup;
    private $container;
    private $middleware   = [];
    private $processQueue = [];

    /**
     * @param Record[] $records
     */
    public function __construct(array $records = [])
    {
        $this->registerShutdown();
        $this->setup = new ContainerSetup($records);
        $this->environmentSetup();
    }

    final public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->container or $this->container = $this->setup->container();

        while ($middlewareId = array_shift($this->processQueue)) {
            return $this->process($this->container->get($middlewareId), $request);
        }

        $this->processQueue = $this->middleware;

        return $this->container->get(static::ROUTER_ID)->handle($request);
    }

    final public function config(string $id): RecordSetup
    {
        return $this->setup->entry($id);
    }

    final public function middleware(string $id): RecordSetup
    {
        $this->middleware[]   = $id;
        $this->processQueue[] = $id;
        return $this->setup->entry($id);
    }

    protected function environmentSetup()
    {
        if ($this->setup->exists(static::ROUTER_ID)) {
            $message  = 'Internal router key `%s` used as container entry (rename entry or %s ROUTER_ID constant)';
            $override = static::ROUTER_ID === self::ROUTER_ID ? 'override' : 'change';
            throw new InvalidIdException(sprintf($message, static::ROUTER_ID, $override));
        }

        $this->setup->entry(static::ROUTER_ID)->lazy(function (ContainerInterface $container) {
            return new Router($this->routing($container), $this->baseUri(), $this->notFoundResponse());
        });
    }

    protected function notFoundResponse()
    {
        return new NotFoundResponse();
    }

    protected function baseUri()
    {
        return new Uri();
    }

    protected function registerShutdown()
    {
        if (!ob_get_level()) { ob_start(); }
        if (getenv(static::DEV_ENVIRONMENT) !== false) { return; }
        register_shutdown_function(function () {
            $error = error_get_last();
            if ($error === null) { return; }
            header_remove();
            http_response_code(503);
            ob_end_clean();
        });
    }

    abstract protected function routing(ContainerInterface $c): Route;

    private function process(MiddlewareInterface $middleware, ServerRequestInterface $request)
    {
        return $middleware->process($request, $this);
    }
}
