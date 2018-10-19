<?php

/*
 * This file is part of Polymorphine/App package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\App;

use Polymorphine\Routing\Router;
use Polymorphine\Container\ContainerSetup;
use Polymorphine\Container\RecordSetup;
use Polymorphine\Container\Exception;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Container\ContainerInterface;


abstract class AppHandler implements RequestHandlerInterface
{
    public const ROUTER_ID       = 'app.router';
    public const DEV_ENVIRONMENT = 'APP_DEV';

    private $setup;
    private $container;
    private $middleware   = [];
    private $processQueue = [];

    /**
     * @param ContainerSetup $setup
     */
    public function __construct(ContainerSetup $setup = null)
    {
        $this->registerShutdown(getenv(static::DEV_ENVIRONMENT));
        $this->setup     = $setup ?? new ContainerSetup();
        $this->container = $this->setup->container();
        $this->environmentSetup();
    }

    final public function handle(ServerRequestInterface $request): ResponseInterface
    {
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

    abstract protected function routing(ContainerInterface $c): Router;

    protected function environmentSetup()
    {
        if ($this->setup->exists(static::ROUTER_ID)) {
            $message  = 'Reserved router key `%s` used as container entry (rename entry or %s ROUTER_ID constant)';
            $override = static::ROUTER_ID === self::ROUTER_ID ? 'override' : 'change';
            throw new Exception\InvalidIdException(sprintf($message, static::ROUTER_ID, $override));
        }

        $this->setup->entry(static::ROUTER_ID)->invoke(function () {
            return $this->routing($this->container);
        });
    }

    protected function registerShutdown(bool $devEnv)
    {
        if (!ob_get_level()) { ob_start(); }
        if ($devEnv) { return; }
        register_shutdown_function(function () {
            $error = error_get_last();
            if ($error === null) { return; }
            header_remove();
            http_response_code(503);
            ob_end_clean();
        });
    }

    private function process(MiddlewareInterface $middleware, ServerRequestInterface $request)
    {
        return $middleware->process($request, $this);
    }
}
