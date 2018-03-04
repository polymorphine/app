<?php

namespace Polymorphine\Http\Routing\Route;


use Polymorphine\Http\Routing\Route;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Closure;
use InvalidArgumentException;


class DynamicEndpoint extends Route
{
    const PARAM_DELIM_LEFT = '{$';
    const PARAM_DELIM_RIGHT = '}';

    const PARAM_TYPE_NUM = '#';
    const PARAM_TYPE_NAME = '%';
    const PARAM_TYPE_SLUG = '$';

    private $method;
    private $path;
    private $callback;
    private $params = [];

    protected $paramTypeRegexp = [
        self::PARAM_TYPE_NUM => '[1-9][0-9]*',
        self::PARAM_TYPE_NAME => '[a-zA-Z]*',
        self::PARAM_TYPE_SLUG => '[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9]'
    ];

    public function __construct(string $method, string $path, Closure $callback)
    {
        $this->method = $method;
        $this->path = $path;
        $this->callback = $callback;
    }

    public static function post(string $path, Closure $callback)
    {
        return new self('POST', $path, $callback);
    }

    public static function get(string $path, Closure $callback)
    {
        return new self('GET', $path, $callback);
    }


    public function forward(ServerRequestInterface $request): ?ResponseInterface
    {
        return ($this->methodMatch($request) && $this->targetMatch($request))
            ? $this->callback->__invoke($request)
            : null;
    }

    private function methodMatch(ServerRequestInterface $request): bool
    {
        return $this->method === $request->getMethod();
    }

    private function targetMatch(ServerRequestInterface &$request): bool
    {
        $target = $request->getRequestTarget();

//        if ($pos = strpos($target, '?')) {
//            $target = substr($target, 0, $pos);
//        }

        if (!preg_match($this->pathPattern(), $target, $attributes)) {
            return false;
        }

        foreach (array_intersect_key($attributes, $this->params) as $name => $param) {
            $request = $request->withAttribute($name, $param);
        }

        return true;
    }

    private function pathPattern() {
        $this->extractParams();
        $pattern = $this->path;
        foreach ($this->params as $name => $regexp) {
            $placeholder = self::PARAM_DELIM_LEFT . $name . self::PARAM_DELIM_RIGHT;
            $replace     = '(?P<' . $name . '>' . $this->paramTypeRegexp[$regexp] . ')';
            $pattern     = str_replace($placeholder, $replace, $pattern);
        }

        return '#^' . $pattern . '$#';
    }

    private function extractParams(): void
    {
        if ($this->params) { return; }

        preg_match_all('/{(?P<type>#|%|\$)(?P<id>[a-z]+)}/', $this->path, $matches, PREG_SET_ORDER);
        $this->path = str_replace(['{#', '{%'], '{$', $this->path);

        foreach ($matches as $match) {
            $this->params[$match['id']] = $match['type'];
        }
    }
}
