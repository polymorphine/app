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

        $request = $factory->create(['attr' => 'attr value']);
        $this->assertInstanceOf(ServerRequestInterface::class, $request);
        $this->assertSame($data['server'], $request->getServerParams());
        $this->assertSame($data['get'], $request->getQueryParams());
        $this->assertSame($data['post'], $request->getParsedBody());
        $this->assertSame($data['cookie'], $request->getCookieParams());
        $this->assertSame(['attr' => 'attr value'], $request->getAttributes());
    }

    private function basicData() {
        return [
            'post' => ['name' => 'post value'],
            'get' => ['name' => 'get value'],
            'cookie' => ['cookie' => 'cookie value'],
            'server' => ['SERVER_NAME' => 'server value'],
            'files' => []
        ];
    }

    public function testOverridingSuperglobals() {
        $_POST = ['name' => 'overwritten value', 'original' => 'original value'];
        $_GET = ['name' => 'overwritten value'];
        $_COOKIE = ['cookie' => 'original cookie'];
        $data = $this->basicData();
        $request = ServerRequestFactory::fromGlobals($data);

        $this->assertInstanceOf(ServerRequestInterface::class, $request);
        $this->assertSame($data['server'] + $_SERVER, $request->getServerParams());
        $this->assertSame($data['get'], $request->getQueryParams());
        $this->assertSame($data['post'] + $_POST, $request->getParsedBody());
        $this->assertSame($data['cookie'], $request->getCookieParams());
        $this->assertSame([], $request->getAttributes());
    }

    //TODO: incomplete tests
}
