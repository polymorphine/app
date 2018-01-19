<?php

namespace Shudd3r\Http\Tests\Message;

use Psr\Http\Message\StreamInterface;
use Shudd3r\Http\Src\Message\UploadedFile;
use PHPUnit\Framework\TestCase;


class UploadedFileTest extends TestCase
{
    private $testFilename;
    private $movedFilename;

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
    }

    public function testCreatingValidFile() {
        $file = $this->file('contents', ['name' => 'test.txt']);
        $this->assertSame(UPLOAD_ERR_OK, $file->getError());
        $this->assertSame('test.txt', $file->getClientFilename());
        $this->assertSame(8, $file->getSize());
        $this->assertSame('text/plain', $file->getClientMediaType());
    }

    public function testFileIsMoved() {
        $file = $this->file('empty');
        $target = $this->targetPath();
        $this->assertFalse(file_exists($target));
        $file->moveTo($target);
        $this->assertTrue(file_exists($target));
    }

    public function testGetStream_ReturnsStreamInterfaceInstance() {
        $file = $this->file();
        $this->assertInstanceOf(StreamInterface::class, $file->getStream());
    }
}

namespace Shudd3r\Http\Src\Message;

function move_uploaded_file($filename, $destination) {
    return copy($filename, $destination);
}
