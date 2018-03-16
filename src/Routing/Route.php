<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;


abstract class Route
{
    const PATH_SEPARATOR = '.';

    /**
     * Forward $request and handle it from matching endpoint Route or Routes
     * Return null if no matching Route is found.
     *
     * @param ServerRequestInterface $request
     *
     * @return null|ResponseInterface
     */
    abstract public function forward(ServerRequestInterface $request): ?ResponseInterface;

    /**
     * Get subsequent Route by its $path.
     *
     * If routing is organized in nested hierarchical structure
     * provided $path should be relative to current position in Routing tree
     *
     * If Route specified with $path cannot be found
     * GatewayCallException should be thrown
     *
     * @param string $path
     *
     * @throws Exception\GatewayCallException
     *
     * @return Route
     */
    public function gateway(string $path): Route
    {
        throw new Exception\GatewayCallException(sprintf('Gateway not found for path `%s`', $path));
    }

    /**
     * Get endpoint call Uri.
     *
     * Uri itself used for incoming ServerRequest does not guarantee
     * reaching current endpoint as other conditions might reject it
     * (http method, authorization... etc.), but returned Uri parts
     * are required by this endpoint to pass.
     *
     * Returned Uri segments MUST match those compared in forward() method.
     * Other segments SHOULD NOT be added, and $prototype MUST NOT define
     * different segments than returned from current route instance.
     * If any Uri part defined in $prototype is overwritten with different
     * value UnreachableEndpointException SHOULD be thrown.
     *
     * If Route is not an endpoint for any ServerRequestInterface
     * EndpointCallException MUST be thrown
     *
     * Redundant $params SHOULD be ignored, but if Uri cannot be built with
     * given $params method MUST throw UriParamsException
     *
     * @param array        $params
     * @param UriInterface $prototype
     *
     * @throws Exception\EndpointCallException
     * @throws Exception\UriParamsException
     * @throws Exception\UnreachableEndpointException
     *
     * @return UriInterface
     */
    public function uri(array $params = [], UriInterface $prototype = null): UriInterface
    {
        throw new Exception\EndpointCallException('Uri not defined in gateway route');
    }
}
