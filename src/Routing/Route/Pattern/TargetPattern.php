<?php

namespace Polymorphine\Http\Routing\Route\Pattern;

use Polymorphine\Http\Routing\Exception\UriParamsException;
use Polymorphine\Http\Routing\Route\Pattern;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;


class TargetPattern implements Pattern
{
    const PARAM_DELIM_LEFT = '{';
    const PARAM_DELIM_RIGHT = '}';

    const PARAM_TYPE_NUM = '#';
    const PARAM_TYPE_NAME = '%';
    const PARAM_TYPE_SLUG = '$';

    protected $paramTypeRegexp = [
        self::PARAM_TYPE_NUM => '[1-9][0-9]*',
        self::PARAM_TYPE_NAME => '[a-zA-Z0-9]+',
        self::PARAM_TYPE_SLUG => '[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9]'
    ];

    private $pattern;
    private $params;
    private $parsedPattern;

    public function __construct(string $pattern, array $params = [])
    {
        $this->pattern = $pattern;
        $this->params = $params;
    }

    public function matchedRequest(ServerRequestInterface $request): ?ServerRequestInterface
    {
        $this->parsedPattern or $this->parsedPattern = $this->parsePattern();

        $target = $request->getRequestTarget();

        //TODO: query handling
//        if ($pos = strpos($target, '?')) {
//            $target = substr($target, 0, $pos);
//        }

        $pattern = $this->pathPattern();
        if (!preg_match($pattern, $target, $attributes)) {
            return null;
        }

        foreach (array_intersect_key($attributes, $this->params) as $name => $param) {
            $request = $request->withAttribute($name, $param);
        }

        return $request;
    }

    public function uri(array $params = [], UriInterface $prototype): UriInterface
    {
        $this->parsedPattern or $this->parsedPattern = $this->parsePattern();
        $placeholders = $this->uriPlaceholders($params);
        $path = str_replace($placeholders, $params, $this->parsedPattern);

        return $prototype->withPath($path);
    }

    private function pathPattern()
    {
        $pattern = $this->parsedPattern;
        foreach ($this->params as $name => $regexp) {
            $placeholder = self::PARAM_DELIM_LEFT . $name . self::PARAM_DELIM_RIGHT;
            $replace = '(?P<' . $name . '>' . $regexp . ')';
            $pattern = str_replace($placeholder, $replace, $pattern);
        }

        return '#^' . $pattern . '$#';
    }

    private function parsePattern(): string
    {
        $types  = array_keys($this->paramTypeRegexp);
        $regexp = $this->typeMarkersRegexp($types);

        preg_match_all($regexp, $this->pattern, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $this->params[$match['id']] = $this->paramTypeRegexp[$match['type']];
        }

        $replace = array_map(function ($type) { return self::PARAM_DELIM_LEFT . $type; }, $types);

        return str_replace($replace, self::PARAM_DELIM_LEFT, $this->pattern);
    }

    private function typeMarkersRegexp(array $types): string
    {
        $regexpMarkers = array_map(function ($typeMarker) { return preg_quote($typeMarker, '/'); }, $types);
        $idPattern     = '(?P<type>' . implode('|', $regexpMarkers) . ')(?P<id>[a-zA-Z]+)';

        return '/' . self::PARAM_DELIM_LEFT . $idPattern . self::PARAM_DELIM_RIGHT . '/';
    }

    private function uriPlaceholders(array $params): array
    {
        if (count($params) !== count($this->params)) {
            $message = 'Route requires %s params for `%s` path - %s provided';
            throw new UriParamsException(sprintf($message, count($this->params), $this->parsedPattern, count($params)));
        }

        return array_map([$this, 'validPlaceholder'], array_keys($this->params), $this->params, $params);
    }

    private function validPlaceholder(string $name, string $type, &$value): string
    {
        $value = (string) $value;
        if (!preg_match('/^' . $type . '$/', $value)) {
            $message = 'Invalid param `%s` type for `%s` route path';

            throw new UriParamsException(sprintf($message, $name, $this->parsedPattern));
        }

        return self::PARAM_DELIM_LEFT . $name . self::PARAM_DELIM_RIGHT;
    }
}
