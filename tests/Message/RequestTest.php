<?php

namespace Shudd3r\Http\Tests\Message;

use Psr\Http\Message\RequestInterface;
use Shudd3r\Http\Src\Message\Request;
use PHPUnit\Framework\TestCase;
use Shudd3r\Http\Tests\Doubles\DummyStream;
use Shudd3r\Http\Tests\Doubles\FakeUri;
use InvalidArgumentException;


class RequestTest extends TestCase
{
    private function request($method = 'GET', array $headers = [], $uri = null, $target = null) {
        if (!isset($uri)) { $uri = new FakeUri(); }
        if (!$target) { return new Request($method, $uri, new DummyStream(), $headers, []); }

        return new Request($method, $uri, new DummyStream(), $headers, ['target' => $target]);
    }

    public function testRequestInstantiation() {
        $this->assertInstanceOf(RequestInterface::class, $this->request());
    }

    /**
     * @dataProvider mutatorMethods
     * @param $method
     * @param $param
     */
    public function testMutatorMethod_ReturnsNewInstance($method, $param) {
        $original = $this->request();
        $clone1 = $original->$method($param);
        $clone2 = $original->$method($param);
        $this->assertNotSame($clone1, $clone2);
        $this->assertEquals($clone1, $clone2);
        $this->assertNotEquals($original, $clone1);
    }

    public function mutatorMethods() {
        return [
            'withRequestTarget' => ['withRequestTarget', '*'],
            'withUri' => ['withUri', new FakeUri('', '/some/path')],
            'withMethod' => ['withMethod', 'POST']
        ];
    }

    public function testGetMethod() {
        $this->assertSame('POST', $this->request('POST')->getMethod());
        $this->assertSame('DELETE', $this->request()->withMethod('DELETE')->getMethod());
    }

    public function testGetUri() {
        $uri = new FakeUri();
        $this->assertSame($uri, $this->request('GET', [], $uri)->getUri());
        $this->assertSame($uri, $this->request()->withUri($uri)->getUri());
    }

    public function testWithMethodForInvalidMethod_ThrowsException() {
        $this->expectException(InvalidArgumentException::class);
        $this->request()->withMethod('SPACE INSIDE');
    }

    public function testConstructorWithInvalidMethod_ThrowsException() {
        $this->expectException(InvalidArgumentException::class);
        $this->request('SPACE INSIDE');
    }

    public function testGetRequestTargetForNotSpecifiedTargetAndUri_ReturnsRootPath() {
        $this->assertSame('/', $this->request()->getRequestTarget());
    }

    public function testGetRequestTargetForInvalidTargetAndNoUri_ReturnsRootPath() {
        $this->assertSame('/', $this->request('GET', [], null, '//malformed:uri')->getRequestTarget());
        $this->assertSame('/', $this->request()->withRequestTarget(['not string'])->getRequestTarget());
    }
}

