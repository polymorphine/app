<?php

namespace Shudd3r\Http\Src\Container\Factory;

use Shudd3r\Http\Src\Container\Factory;
use Psr\Container\ContainerInterface;
use Shudd3r\Http\Src\Container\TreeContainer;
use Shudd3r\Http\Src\Container\Exception;
use Closure;


class TreeContainerFactory implements Factory
{
    private $lazy  = [];
    private $value = [];

    public function __construct(array $config = []) {
        $this->lazy  = $config['lazy'] ?? [];
        $this->value = $config['value'] ?? [];
    }

    public function container(): ContainerInterface {
        return new TreeContainer($this->value, $this->lazy);
    }

    public function value($name, $value) {
        $this->treePush($name, $value, $this->value);
    }

    public function lazy($name, Closure $closure) {
        $this->treePush($name, $closure, $this->lazy);
        return true;
    }

    private function treePush(string $name, $value, &$registry) {
        $path = $this->path($name);
        foreach ($path as $key) {
            if (is_numeric($key)) {
                throw new Exception\InvalidIdException(sprintf('Invalid path `%s` - numeric id tokens are not supported', $name));
            }
            if (is_array($registry) && array_key_exists($key, $registry) && !is_array($registry[$key])) {
                throw new Exception\InvalidIdException(sprintf('Cannot overwrite existing id - `%s` already set for `%s` key', $name, $key));
            }

            isset($registry[$key]) or $registry[$key] = null;

            $registry = &$registry[$key];
        }

        $registry = $value;
    }

    private function path($path) {
        if (empty($path)) {
            throw new Exception\InvalidIdException('Empty id - Only non numeric id tokens are not supported');
        }

        return explode(TreeContainer::PATH_SEPARATOR, $path);
    }
}
