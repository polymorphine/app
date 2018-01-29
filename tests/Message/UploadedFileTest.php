<?php

namespace Shudd3r\Http\Tests\Message;

use PHPUnit\Framework\TestCase;
use Shudd3r\Http\Src\Message\UploadedFile;
use Psr\Http\Message\StreamInterface;
use InvalidArgumentException;
use RuntimeException;


class UploadedFileTest extends TestCase
{
    private $testFilename;
    private $movedFilename;

    public static $forceNativeFunctionErrors = false;

    private function file($contents = '', array $data = []) {
        isset($this->testFilename) or $this->testFilename = tempnam(sys_get_temp_dir(), 'test');
        if ($contents) { file_put_contents($this->testFilename, $contents); }

        $fileData = [
            'tmp_name' => $this->testFilename,
            'size'     => strlen($contents),
            'error'    => UPLOAD_ERR_OK,
            'name'     => 'clientName.txt',
            'type'     => 'text/plain'
        ];

        $_FILES['test'] = $data + $fileData;

        return new UploadedFile($_FILES['test']);
    }

    private function targetPath($name = 'test.txt') {
        return $this->movedFilename = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $name;
    }

    public function tearDown() {
        if (file_exists($this->testFilename)) { unlink($this->testFilename); }
        if (file_exists($this->movedFilename)) { unlink($this->movedFilename); }
        $this->testFilename = null;
        $this->movedFilename = null;
        self::$forceNativeFunctionErrors = false;
    }

    public function testCreatingValidFile() {
        $file = $this->file('contents', ['name' => 'test.txt']);
        $this->assertSame(UPLOAD_ERR_OK, $file->getError());
        $this->assertSame('test.txt', $file->getClientFilename());
        $this->assertSame(8, $file->getSize());
        $this->assertSame('text/plain', $file->getClientMediaType());
    }

    /**
     * @dataProvider invalidConstructorParams
     * @param $file array
     */
    public function testInvalidConstructorParam_ThrowsException(array $file) {
        $this->expectException(InvalidArgumentException::class);
        $this->file('contents', $file);
    }

    public function invalidConstructorParams() {
        return [
            'name' => [['name' => false]],
            'size' => [['size' => '123']],
            'tmp_name' => [['tmp_name' => ['array.txt']]],
            'type' => [['type' => 123]],
            'error code string' => [['error' => 'UPLOAD_ERR_OK']],
            'error code unknown' => [['error' => 12]]
        ];
    }

    public function testFileIsMoved() {
        $file = $this->file('empty');
        $target = $this->targetPath();
        $this->assertFalse(file_exists($target));
        $file->moveTo($target);
        $this->assertTrue(file_exists($target));
    }

    public function testMoveFileWithUploadError_ThrowsException() {
        $file = $this->file('', ['error' => UPLOAD_ERR_EXTENSION]);
        $this->expectException(RuntimeException::class);
        $file->moveTo($this->targetPath());
    }

    public function testMoveAlreadyMovedFile_ThrowsException() {
        $file = $this->file();
        $file->moveTo($this->targetPath());
        $this->expectException(RuntimeException::class);
        $file->moveTo(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'file.txt');
    }

    public function testFileMoveError_ThrowsException() {
        $file = $this->file();
        self::$forceNativeFunctionErrors = true;
        $this->expectException(RuntimeException::class);
        $file->moveTo($this->targetPath());
    }

    public function testGetStream_ReturnsStreamInterfaceInstance() {
        $file = $this->file();
        $this->assertInstanceOf(StreamInterface::class, $file->getStream());
    }

    public function testGetSreamFromUploadedWithError_ThrowsException() {
        $file = $this->file('', ['error' => UPLOAD_ERR_EXTENSION]);
        $this->expectException(RuntimeException::class);
        $file->getStream();
    }

    public function testGetStreamFromMovedFile_ThrowsException() {
        $file = $this->file();
        $file->moveTo($this->targetPath());
        $this->expectException(RuntimeException::class);
        $file->getStream();
    }
}

namespace Shudd3r\Http\Src\Message;

use Shudd3r\Http\Tests\Message\UploadedFileTest as TestConfig;

function move_uploaded_file($filename, $destination) {
    if (TestConfig::$forceNativeFunctionErrors) { return false; }
    return copy($filename, $destination);
}