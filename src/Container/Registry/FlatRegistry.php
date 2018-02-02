<?php

namespace Shudd3r\Http\Src\Container\Registry;

use Shudd3r\Http\Src\Container\Registry;
use Shudd3r\Http\Src\Container\Exception\EntryNotFoundException;
use Shudd3r\Http\Src\Container\Exception\InvalidIdException;


class FlatRegistry implements Registry
{
    private $entries = [];

    public function __construct(array $entries = []) {
        $this->entries = $this->loadEntries($entries);
    }

    public function get($id) {
        if (!$this->has($id)) { throw new EntryNotFoundException(); }
        return $this->entries[$id]->value();
    }

    public function has($id): bool {
        if (!is_string($id)) { throw new InvalidIdException(); }
        return isset($this->entries[$id]);
    }

    public function set(string $id, Record $value) {
        $this->entries[$id] = $value;
    }

    protected function loadEntries(array $entries) {
        foreach ($entries as $key => &$entry) {
            if (!is_string($key)) {
                throw new InvalidIdException('Registry key must be a string');
            }

            $entry = is_callable($entry)
                ? new Records\LazyRecord($entry, $this)
                : new Records\DirectRecord($entry);
        }

        return $entries;
    }
}
