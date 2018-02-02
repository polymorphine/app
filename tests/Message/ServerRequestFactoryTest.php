<?php

namespace Shudd3r\Http\Tests\Message;

use PHPUnit\Framework\TestCase;
use Shudd3r\Http\Src\Message\ServerRequestFactory;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Shudd3r\Http\Tests\Message\Doubles\FakeUploadedFile;
use InvalidArgumentException;


class ServerRequestFactoryTest extends TestCase
{
    public static $nativeCallResult;

    private function factory(array $data = []) {
        return new ServerRequestFactory($data);
    }

    public function tearDown() {
        self::$nativeCallResult = null;
    }

    public function testInstantiation() {
        $this->assertInstanceOf(ServerRequestFactory::class, $this->factory());
    }

    public function testBasicIntegration() {
        $data = $this->basicData();
        $factory = $this->factory($data);
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

    /**
     * @dataProvider normalizeHeaderNames
     * @param $serverKey
     * @param $headerName
     */
    public function testNormalizedHeadrNamesFromServerArray($serverKey, $headerName) {
        $data['server'] = [$serverKey => 'value'];
        $this->assertTrue($this->factory($data)->create()->hasHeader($headerName));
    }

    public function normalizeHeaderNames() {
        return [
            ['HTTP_ACCEPT', 'Accept'],
            ['HTTP_ACCEPT_ENCODING', 'Accept-Encoding'],
            ['HTTP_CONTENT_MD5', 'Content-MD5'],
            ['CONTENT_TYPE', 'Content-Type']
        ];
    }

    public function testResolvingAuthorizationHeader() {
        $this->assertFalse($this->factory()->create()->hasHeader('Authorization'));
        $data['server'] = ['HTTP_AUTHORIZATION' => 'value'];
        $this->assertTrue($this->factory($data)->create()->hasHeader('Authorization'));
        self::$nativeCallResult = ['Authorization' => 'value'];
        $this->assertTrue($this->factory()->create()->hasHeader('Authorization'));
        self::$nativeCallResult = ['authorization' => 'value'];
        $this->assertTrue($this->factory()->create()->hasHeader('Authorization'));
        self::$nativeCallResult = ['AUTHORIZATION' => 'value'];
        $this->assertFalse($this->factory()->create()->hasHeader('Authorization'));
    }

    public function testUploadedFileSuperglobalParameterStructure() {
        $files['test'] = [
            'tmp_name' => 'phpFOOBAR',
            'name' => 'avatar.png',
            'size' => 10240,
            'type' => 'image/jpeg',
            'error' => 0
        ];
        $request = $this->factory(['files' => $files])->create();
        $this->assertInstanceOf(UploadedFileInterface::class, $request->getUploadedFiles()['test']);
    }

    public function testUploadedFileNestedStructureParameter() {
        $files = [
            'first' => new FakeUploadedFile(),
            'second' => ['subcategory' => new FakeUploadedFile()]
        ];
        $request = $this->factory(['files' => $files])->create();
        $this->assertSame($files, $request->getUploadedFiles());
    }

    public function testSingleUploadedFileSuperglobalStructure() {
        $files['test'] = $this->fileData('test.txt');
        $request = $this->factory(['files' => $files])->create();
        $file = $request->getUploadedFiles();
        $this->assertInstanceOf(UploadedFileInterface::class, $file['test']);
        $this->assertSame('test.txt', $file['test']->getClientFilename());
    }

    public function testMultipleUploadedFileSuperglobalStructure() {
        $files['test'] = $this->fileData(['testA.txt', 'testB.txt']);
        //var_dump($files); exit;

        $request = $this->factory(['files' => $files])->create();
        $file = $request->getUploadedFiles();
        $this->assertInstanceOf(UploadedFileInterface::class, $file['test'][0]);
        $this->assertSame('testB.txt', $file['test'][1]->getClientFilename());
    }

    public function testMixedStructureUploadedFiles() {
        $files = [
            'test' => ['multiple' => $this->fileData(['testA.txt', 'testB.txt'])],
            'multipleC' => [new FakeUploadedFile(), new FakeUploadedFile()],
            'singleD' => $this->fileData('testD.txt')
        ];

        $request = $this->factory(['files' => $files])->create();
        $file = $request->getUploadedFiles();
        $this->assertInstanceOf(UploadedFileInterface::class, $file['test']['multiple'][0]);
        $this->assertInstanceOf(UploadedFileInterface::class, $file['multipleC'][1]);
        $this->assertSame('testD.txt', $file['singleD']->getClientFilename());
    }

    private function fileData($name) {
        $multi = is_array($name);
        $fill = function ($value) use ($name) { return array_fill(0, count($name), $value); };
        return [
            'tmp_name' => $multi ? $fill('phpFOOBAR') : 'phpFOOBAR',
            'name' => $name,
            'size' => $multi ? $fill(10240) : 10240,
            'type' => $multi ? $fill('text/plain') : 'text/plain',
            'error' => $multi ? $fill(0) : 0
        ];
    }

    public function testInvalidFileDataStructure_ThrowsException() {
        $this->expectException(InvalidArgumentException::class);
        $this->factory(['files' => ['field' => 'filename.txt']])->create();
    }

    //TODO: parsed body use cases
}

namespace Shudd3r\Http\Src\Message;

use Shudd3r\Http\Tests\Message\ServerRequestFactoryTest as Factory;


function apache_request_headers() {
    return Factory::$nativeCallResult ?? [];
}

function function_exists($name) {
    return Factory::$nativeCallResult ? true : \function_exists($name);
}

