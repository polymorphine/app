<?php

namespace Shudd3r\Http\Src\Container;

use Psr\Container\ContainerInterface;
use Shudd3r\Http\Src\Container\Records\Record;
use Shudd3r\Http\Src\Container\Records\RegistryInput;
use Shudd3r\Http\Src\Container\Exception\EntryNotFoundException;
use Shudd3r\Http\Src\Container\Exception\InvalidIdException;


class TreeRegistry implements Registry
{
    const PATH_SEPARATOR = '.';

    private $entries = [];
    private $container;

    public function __construct(array $entries = []) {
        $this->entries = $entries;
        $this->container = new Container($this);
    }

    public function get($id) {
        $entry = $this->entries;
        foreach ($this->path($id) as $key) {
            if (!is_array($entry)) { $entry = $this->scanArrayRecord($entry); }
            if (!array_key_exists($key, $entry)) {
                throw new EntryNotFoundException(sprintf('Path `%s` not defined', $id));
            }
            $entry = $entry[$key];
        }

        return $this->extractLeafNodes($entry);
    }

    public function has($id): bool {
        $entry = $this->entries;
        foreach ($this->path($id) as $key) {
            if (!is_array($entry)) { $entry = $this->scanArrayRecord($entry); }
            if (!array_key_exists($key, $entry)) { return false; }
            $entry = $entry[$key];
        }
        return true;
    }

    public function set(string $id, Record $value) {
        $entry = &$this->entries;
        foreach ($this->path($id) as $key) {
            if (isset($entry[$key]) && !is_array($entry[$key])) {
                throw new InvalidIdException(sprintf('Container path `%s` overrides leaf node in `%s` segment', $id, $key));
            }
            if (!isset($entry[$key])) { $entry[$key] = null; }
            $entry =& $entry[$key];
        }
        if (!empty($entry)) { throw new InvalidIdException(sprintf('Container already defined for `%s` path', $id)); }
        $entry = $value;
    }

    public function container(): ContainerInterface {
        return $this->container;
    }

    public function entry(string $id): RegistryInput {
        return new RegistryInput($id, $this);
    }

    private function path($path): array {
        if (empty($path) || !is_string($path)) {
            throw new InvalidIdException();
        }

        return explode(self::PATH_SEPARATOR, $path);
    }

    private function scanArrayRecord($entry): array {
        if (!$entry instanceof Record) { return []; }
        $value = $entry->value();
        return is_array($value) ? $value : [];
    }

    /**
     * @param $entry array|Record
     * @return mixed
     */
    private function extractLeafNodes($entry) {
        if (!is_array($entry)) {
            return ($entry instanceof Record) ? $entry->value() : $entry;
        }

        foreach ($entry as &$item) {
            $item = $this->extractLeafNodes($item);
        }

        return $entry;
    }
}
