<?php

namespace Shudd3r\Http\Src\Container;

use Psr\Container\ContainerInterface;
use Shudd3r\Http\Src\Container\Exception;
use Closure;


class FlatContainer implements ContainerInterface
{
    private $values;
    private $callbacks;

    public function __construct(array $values, array $callbacks) {
        $this->values    = $this->checkKeys($values);
        $this->callbacks = $this->typeCheck($callbacks);
    }

    public function get($id) {
        if (!$this->has($id)) {
            throw new Exception\EntryNotFoundException();
        }

        return array_key_exists($id, $this->values)
            ? $this->values[$id]
            : $this->values[$id] = $this->invoke($this->callbacks[$id]);
    }

    public function has($id): bool {
        if (empty($id) || is_numeric($id)) {
            throw new Exception\InvalidIdException('Only non numeric string tokens accepted');
        }

        return (array_key_exists($id, $this->values) || array_key_exists($id, $this->callbacks));
    }

    private function checkKeys(array $values) {
        if (!$this->isAssoc($values)) {
            throw new Exception\InvalidStateException();
        }
        return $values;
    }

    private function typeCheck(array $callbacks): array {
        if (!$this->isAssoc($callbacks) || !$this->hasClosures($callbacks)) {
            throw new Exception\InvalidStateException();
        }
        return $callbacks;
    }

    private function invoke(Closure $callback) {
        return $callback->bindTo($this)->__invoke();
    }

    private function isAssoc(array $values) {
        if (empty($values)) { return true; }
        return (count(array_filter(array_keys($values), 'is_numeric')) === 0);
    }

    private function hasClosures(array $values) {
        if (empty($values)) { return true; }
        $callback = function ($value) { return (!$value instanceof Closure); };
        return (count(array_filter($values, $callback)) === 0);
    }
}
