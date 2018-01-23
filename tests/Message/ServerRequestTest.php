<?php

namespace Shudd3r\Http\Tests\Message;

use Psr\Http\Message\ServerRequestInterface;
use Shudd3r\Http\Src\Message\ServerRequest;
use PHPUnit\Framework\TestCase;
use Shudd3r\Http\Tests\Doubles\DummyStream;
use Shudd3r\Http\Tests\Doubles\FakeUri;


class ServerRequestTest extends TestCase
{
    private function request(array $params = []) {
        return new ServerRequest('GET', new FakeUri(), new DummyStream(), [], $params);
    }

    public function testInstantiation() {
        $this->assertInstanceOf(ServerRequestInterface::class, $this->request());
    }
}
