<?php

namespace Shudd3r\Http\Src;


use PHPUnit\Framework\TestCase;
use Shudd3r\Http\Tests\Doubles\DummyRequest;
use Shudd3r\Http\Tests\Doubles\DummyResponse;

class AppTest extends TestCase
{
    public function testExecuteReturnsDummy() {
        $app = new App();
        $response = $app->execute(new DummyRequest());
        $this->assertEquals($response, new DummyResponse());
    }
}
