<?php

namespace Shudd3r\Http\Tests;

use PHPUnit\Framework\TestCase;
use Shudd3r\Http\Src\App;
use Psr\Http\Message\ResponseInterface;
use Shudd3r\Http\Src\Container\Records\RegistryInput;
use Shudd3r\Http\Src\Container\FlatRegistry;
use Shudd3r\Http\Tests\Doubles\DummyRequest;


class AppTest extends TestCase
{
    private function app(FlatRegistry $registry = null) {
        return $registry ? new App($registry) : new App();
    }

    public function testExecute_ReturnsResponse() {
        $app = $this->app();
        $this->assertInstanceOf(ResponseInterface::class, $app->execute(new DummyRequest()));
    }

    public function testConfig_ReturnsRegistryInput() {
        $app = $this->app();
        $this->assertInstanceOf(RegistryInput::class, $app->config('test'));
    }
}
