<?php

namespace Shudd3r\Http\Src\Container\Factory;

use Shudd3r\Http\Src\Container\Factory;
use Psr\Container\ContainerInterface;
use Shudd3r\Http\Src\Container\Container;
use Shudd3r\Http\Src\Container\Record;
use Shudd3r\Http\Src\Container\Exception;
use Closure;


class ContainerFactory implements Factory
{
    private $records;

    public function __construct(array $records = []) {
        $this->records = $this->validRecords($records);
    }

    public function container(): ContainerInterface {
        return new Container($this->records);
    }

    public function value($name, $value) {
        $id = $this->validId($name);
        $this->records[$id] = new Record\DirectRecord($value);
    }

    public function lazy($name, Closure $closure) {
        $id = $this->validId($name);
        $this->records[$id] = new Record\LazyRecord($closure);
    }

    public function record($name, Record $record) {
        $id = $this->validId($name);
        $this->records[$id] = $record;
    }

    private function validId($id) {
        if (empty($id) || is_numeric($id)) {
            throw new Exception\InvalidIdException('Numeric id tokens are not supported');
        }

        if (array_key_exists($id, $this->records)) {
            throw new Exception\InvalidIdException(sprintf('Record already exists - cannot overwrite existing `%s` id', $id));
        }

        return $id;
    }

    private function validRecords(array $records): array {
        foreach ($records as &$record) {
            if (!$record instanceof Record) {
                $record = new Record\DirectRecord($record);
            }
        }

        return $records;
    }
}
