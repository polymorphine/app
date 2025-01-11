<?php declare(strict_types=1);

/*
 * This file is part of Polymorphine/App package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\App;

use Polymorphine\Container\Setup;
use Polymorphine\Container\Setup\Build;
use Polymorphine\Container\Setup\Entry;
use Psr\Container\ContainerInterface as Container;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;


abstract class AppHandler implements Handler
{
    public const ROUTER_ID       = 'app.router';
    public const DEV_ENVIRONMENT = 'APP_DEV';

    private Setup     $setup;
    private Container $container;

    private array $middleware   = [];
    private array $processQueue = [];

    /**
     * @param Build|null $build
     */
    public function __construct(?Build $build = null)
    {
        $this->registerShutdown((bool) getenv(static::DEV_ENVIRONMENT));
        $this->setup = $this->environmentSetup($build ?? new Build());
    }

    final public function handle(Request $request): Response
    {
        $this->container ??= $this->setup->container();
        if ($middlewareId = array_shift($this->processQueue)) {
            return $this->process($this->container->get($middlewareId), $request);
        }

        $this->processQueue = $this->middleware;

        return $this->container->get(static::ROUTER_ID)->handle($request);
    }

    /**
     * @param string $id
     *
     * @return Entry
     */
    final public function config(string $id): Entry
    {
        return $this->setup->set($id);
    }

    /**
     * @param string $id
     *
     * @return Entry
     */
    final public function middleware(string $id): Entry
    {
        $this->middleware[]   = $id;
        $this->processQueue[] = $id;
        return $this->setup->set($id);
    }

    abstract protected function routing(Container $c): Handler;

    protected function environmentSetup(Build $build): Setup
    {
        if ($build->has(static::ROUTER_ID)) {
            $message  = 'Reserved router key `%s` used as container entry (rename entry or %s ROUTER_ID constant)';
            $override = static::ROUTER_ID === self::ROUTER_ID ? 'override' : 'change';
            throw new Setup\Exception\OverwriteRuleException(sprintf($message, static::ROUTER_ID, $override));
        }

        $setup = new Setup($build);
        $setup->set(self::ROUTER_ID)->callback(fn () => $this->routing($this->container));

        return $setup;
    }

    protected function registerShutdown(bool $devEnv)
    {
        if (!ob_get_level()) { ob_start(); }
        if ($devEnv) { return; }
        register_shutdown_function(fn () => $this->defaultShutdown());
    }

    private function process(Middleware $middleware, Request $request): Response
    {
        return $middleware->process($request, $this);
    }

    private function defaultShutdown(): void
    {
        $error = error_get_last();
        if ($error === null) { return; }
        header_remove();
        http_response_code(503);
        ob_end_clean();
    }
}
