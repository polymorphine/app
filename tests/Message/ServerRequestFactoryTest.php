<?php

namespace Shudd3r\Http\Tests\Message;

use Psr\Http\Message\ServerRequestInterface;
use Shudd3r\Http\Src\Message\ServerRequestFactory;
use PHPUnit\Framework\TestCase;


class ServerRequestFactoryTest extends TestCase
{
    private function factory() {
        return new ServerRequestFactory();
    }

    public function testInstantiation() {
        $this->assertInstanceOf(ServerRequestFactory::class, $this->factory());
    }

    public function testBasicIntegration() {
        $data = $this->basicData();
        $factory = new ServerRequestFactory($data);
        $this->assertInstanceOf(ServerRequestFactory::class, $factory);

        $request = $factory->create(['attr name' => 'attr value']);
        $this->assertInstanceOf(ServerRequestInterface::class, $request);
        $this->assertSame($data['server'], $request->getServerParams());
        $this->assertSame($data['get'], $request->getQueryParams());
        $this->assertSame($data['post'], $request->getParsedBody());
        $this->assertSame($data['cookie'], $request->getCookieParams());
        $this->assertSame(['attr name' => 'attr value'], $request->getAttributes());
    }

    private function basicData() {
        return [
            'post' => ['post name' => 'post value'],
            'get' => ['get name' => 'get value'],
            'cookie' => ['cookie name' => 'cookie value'],
            'server' => ['SERVER_NAME' => 'server value'],
            'files' => []
        ];
    }

    //TODO: incomplete tests
}
