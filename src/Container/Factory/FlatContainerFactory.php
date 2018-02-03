<?php

namespace Shudd3r\Http\Src\Container\Factory;

use Shudd3r\Http\Src\Container\Factory;
use Psr\Container\ContainerInterface;
use Shudd3r\Http\Src\Container\FlatContainer;
use Shudd3r\Http\Src\Container\Exception;
use Closure;


class FlatContainerFactory implements Factory
{
    private $lazy  = [];
    private $value = [];

    public function __construct(array $config = []) {
        $this->lazy  = $config['lazy'] ?? [];
        $this->value = $config['value'] ?? [];
    }

    public function container(): ContainerInterface {
        return new FlatContainer($this->value, $this->lazy);
    }

    public function value($name, $value) {
        $this->checkId($name);
        $this->value[$name] = $value;
    }

    public function lazy($name, Closure $closure) {
        $this->checkId($name);
        $this->lazy[$name] = $closure;
    }

    private function checkId($id) {
        if (empty($id) || is_numeric($id)) {
            throw new Exception\InvalidIdException('Only non numeric id tokens are not supported');
        }

        if (array_key_exists($id, $this->value) || array_key_exists($id, $this->lazy)) {
            throw new Exception\InvalidIdException(sprintf('Cannot overwrite existing id - `%s` already set', $id));
        }
    }
}
