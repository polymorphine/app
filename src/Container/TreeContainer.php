<?php

namespace Shudd3r\Http\Src\Container;

use Psr\Container\ContainerInterface;
use Shudd3r\Http\Src\Container\Exception;
use Closure;


class TreeContainer implements ContainerInterface
{
    const PATH_SEPARATOR = '.';

    private $values;
    private $callbacks;

    public function __construct(array $values, array $callbacks) {
        $this->values    = $this->checkKeys($values);
        $this->callbacks = $this->typeCheck($callbacks);
    }

    public function get($id) {
        $path = $this->path($id);

        if ($this->treeSearch($path, $this->callbacks)) {
            $this->saveInvokedValues($path);
        }

        if (!$this->treeSearch($path, $this->values)) {
            throw new Exception\EntryNotFoundException(sprintf('Path `%s` not defined', $id));
        }

        return $this->treeValue($path, $this->values);
    }

    public function has($id): bool {
        $path = $this->path($id);
        return ($this->treeSearch($path, $this->values) || $this->treeSearch($path, $this->callbacks));
    }

    private function treeSearch(array $path, array $value) {
        foreach ($path as $key) {
            if (is_numeric($key)) {
                throw new Exception\InvalidIdException('Only non numeric string tokens accepted');
            }
            if (!is_array($value) || !array_key_exists($key, $value)) { return false; }
            $value = $value[$key];
        }

        return true;
    }

    private function treeValue(array $path, array $value) {
        foreach ($path as $key) {
            $value = $value[$key];
        }

        return $value;
    }

    private function saveInvokedValues(array $path) {
        $values    = &$this->values;
        $callbacks = &$this->callbacks;
        foreach ($path as $key) {
            if (isset($values[$key]) && !is_array($values[$key])) {
                throw new Exception\InvalidStateException('Invoked value overwrite container entry');
            }

            if (!isset($values[$key])) {
                $values[$key] = $this->treeInvoke($callbacks[$key]);
                unset($callbacks[$key]);
                return;
            }

            $values    = &$values[$key];
            $callbacks = &$callbacks[$key];
        }

        $values = array_merge($values, $this->treeInvoke($callbacks));
        unset($callbacks);
    }

    private function typeCheck(array $callbacks): array {
        if (!$this->isAssoc($callbacks)) { throw new Exception\InvalidStateException(); }
        foreach ($callbacks as $callback) {
            if (is_array($callback)) { $this->typeCheck($callback); continue; }
            if (!$callback instanceof Closure) { throw new Exception\InvalidStateException(); }
        }

        return $callbacks;
    }

    private function checkKeys(array $values) {
        if (!$this->isAssoc($values)) { throw new Exception\InvalidStateException(); }
        return $values;
    }

    private function isAssoc(array $values) {
        if (empty($values)) { return true; }
        $check = function ($key) { return (is_numeric($key) || strpos($key, self::PATH_SEPARATOR)); };
        return (count(array_filter(array_keys($values), $check)) === 0);
    }

    private function treeInvoke($closures) {
        if ($closures instanceof Closure) { return $this->invoke($closures); }
        foreach ($closures as &$value) {
            $value = $this->treeInvoke($value);
        }
        return $closures;
    }

    private function invoke(Closure $callback) {
        return $callback->bindTo($this)->__invoke();
    }

    private function path($path): array {
        if (empty($path)) {
            throw new Exception\InvalidIdException('Empty path - only non numeric string tokens accepted');
        }

        return explode(self::PATH_SEPARATOR, $path);
    }
}
