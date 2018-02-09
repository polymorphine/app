<?php

namespace Shudd3r\Http\Src\Container\Factory;

use Shudd3r\Http\Src\Container\Factory;
use Shudd3r\Http\Src\Container\Record;
use Closure;


/**
 * Write-only proxy that prevents configuration namespaces
 * gain access to already written values through created
 * Container.
 *
 * @see \Shudd3r\Http\Src\Container\Factory
 */
class ContainerRecordEntry
{
    private $name;
    private $factory;

    public function __construct(string $name, Factory $factory) {
        $this->name    = $name;
        $this->factory = $factory;
    }

    /**
     * Pushes value to Container's entry Record.
     * Unchanged value will be returned from Container when this
     * record is requested.
     *
     * @param $value
     */
    public function value($value) {
        $this->factory->value($this->name, $value);
    }

    /**
     * Pushes Closure to Container's entry Record.
     * Value returned from Container will be result of first
     * call to this Closure call and remain the same on
     * subsequent requests.
     *
     * Closure receives Container instance as parameter.
     *
     * @param Closure $closure
     */
    public function lazy(Closure $closure) {
        $this->factory->lazy($this->name, $closure);
    }

    /**
     * Pushes Record instance directly into Container's entry.
     *
     * @param Record $record
     */
    public function record(Record $record) {
        $this->factory->record($this->name, $record);
    }
}
