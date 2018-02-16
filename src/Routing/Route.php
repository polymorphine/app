<?php

namespace Polymorphine\Http\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use InvalidArgumentException;


abstract class Route
{
    const PATH_SEPARATOR = '.';

    /**
     * Forward $request and handle it from matching endpoint Route or Routes
     * Return null if no matching Route is found
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface|null
     */
    public abstract function forward(ServerRequestInterface $request);

    /**
     * Get subsequent Route by its $path
     *
     * If routing is organized in nested hierarchical structure
     * provided $path should be relative to current position in Routing tree
     *
     * If Route specified with $path cannot be found
     * GatewayCallException should be thrown
     *
     * @param string $path
     * @return Route
     * @throws Exception\GatewayCallException
     */
    public function gateway(string $path): Route {
        throw new Exception\GatewayCallException(sprintf('Gateway not found for path `%s`', $path));
    }

    /**
     * Get endpoint call Uri
     *
     * If Route is not an endpoint for any ServerRequestInterface
     * EndpointCallException must be thrown
     *
     * If Uri cannot be built with given $params method should throw
     * InvalidArgumentException
     *
     * Uri itself used for ServerRequest does not guarantee reaching
     * current endpoint - other conditions might reject the request
     * on its way here: http method, authorization, application state... etc.
     *
     * @param array $params
     * @param UriInterface $prototype
     * @return UriInterface
     * @throws Exception\EndpointCallException|InvalidArgumentException
     */
    public function uri(array $params = [], UriInterface $prototype = null): UriInterface {
        throw new Exception\EndpointCallException('Uri not defined in gateway route');
    }
}
