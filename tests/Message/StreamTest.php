<?php

namespace Shudd3r\Http\Tests\Message;


use Psr\Http\Message\StreamInterface;
use Shudd3r\Http\Src\Message\Stream;
use RuntimeException;
use PHPUnit\Framework\TestCase;

class StreamTest extends TestCase
{
    /**
     * @var $stream StreamInterface
     */
    protected $stream;

    /**
     * @var $testFilename string
     */
    protected $testFilename;

    private function stream($resource = null, $mode = null) {
        $resource = $resource ?? 'php://memory';
        return $this->stream = is_resource($resource) ? new Stream($resource) : Stream::fromResourceUri($resource, $mode);
    }

    private function fileStream($mode = null) {
        $this->testFilename = tempnam(sys_get_temp_dir(), 'test');
        return $this->stream($this->testFilename, $mode);
    }

    public function tearDown() {
        if (!$this->testFilename || !file_exists($this->testFilename)) { return; }
        $this->stream->close();
        unlink($this->testFilename);
    }

    public function testInstantiateWithStreamName() {
        $this->assertInstanceOf(StreamInterface::class, Stream::fromResourceUri('php://memory', null));
        $this->assertInstanceOf(StreamInterface::class, Stream::fromResourceUri('php://memory', 'w'));
    }

    /**
     * @dataProvider metaKeys
     * @param $key
     * @param $type
     */
    public function testGetMetaData_ReturnCorrectValueTypes($key, $type) {
        $meta = $this->stream('php://memory')->getMetadata();
        $this->assertSame($type, gettype($meta[$key]));
        $meta = $this->stream('php://memory')->getMetadata($key);
        $this->assertSame($type, gettype($meta));
    }

    public function metaKeys() {
        return [
            ['timed_out', 'boolean'],
            ['blocked', 'boolean'],
            ['eof', 'boolean'],
            ['unread_bytes', 'integer'],
            ['stream_type', 'string'],
            ['wrapper_type', 'string'],
            ['mode', 'string'],
            ['seekable', 'boolean'],
            ['uri', 'string']
        ];
    }

    public function testGetMetadataReturnsNullIfNoDataExistsForKey() {
        $this->assertNull($this->stream()->getMetadata('no_such_key'));
    }

    public function testDetachedStreamProperties() {
        $stream = $this->stream();
        $stream->detach();
        $this->assertFalse($stream->isReadable());
        $this->assertFalse($stream->isSeekable());
        $this->assertFalse($stream->isWritable());
    }

    public function testReadUnreadableStream_ThrowsException() {
        $stream = $this->fileStream('w');
        $this->assertFalse($stream->isReadable());
        $this->expectException(RuntimeException::class);
        $stream->read(1);
    }

    public function testReadDatechedStream_ThrowsException() {
        $stream = $this->stream();
        $stream->detach();
        $this->expectException(RuntimeException::class);
        $stream->read(1);
    }

    public function testGetContentsOnUnreadableStream_ThrowsException() {
        $stream = $this->fileStream('w');
        $this->assertFalse($stream->isReadable());
        $this->expectException(RuntimeException::class);
        $stream->getContents();
    }

    public function testGetContentsFromDatechedStream_ThrowsException() {
        $stream = $this->stream();
        $stream->detach();
        $this->expectException(RuntimeException::class);
        $stream->getContents();
    }

    public function testSeekNotSeekableStream_ThrowsException() {
        $stream = $this->stream('php://output', 'a');
        $this->assertFalse($stream->isSeekable());
        $this->expectException(RuntimeException::class);
        $stream->seek(1);
    }

    public function testSeekDetatchedStream_ThrowsException() {
        $stream = $this->stream();
        $stream->detach();
        $this->expectException(RuntimeException::class);
        $stream->seek(1);
    }

    public function testRewindNotSeekableStream_ThrowsException() {
        $stream = $this->stream('php://output', 'a');
        $this->assertFalse($stream->isSeekable());
        $this->expectException(RuntimeException::class);
        $stream->rewind();
    }

    public function testRewindDetatchedStream_ThrowsException() {
        $stream = $this->stream();
        $stream->detach();
        $this->expectException(RuntimeException::class);
        $stream->rewind();
    }

    public function testWriteNotWritableStream_ThrowsException() {
        $stream = $this->fileStream('r');
        $this->assertFalse($stream->isWritable());
        $this->expectException(RuntimeException::class);
        $stream->write('hello world!');
    }

    public function testWriteIntoDatachedStream_ThrowsException() {
        $stream = $this->stream();
        $stream->detach();
        $this->expectException(RuntimeException::class);
        $stream->write('hello world!');
    }

    public function testGetSizeOnDetachedResource_ReturnsNull() {
        $stream = $this->stream();
        $stream->detach();
        $this->assertNull($stream->getSize());
    }

    public function testEofOnDetachedStreamReturnsTrue() {
        $stream = $this->stream();
        $stream->detach();
        $this->assertTrue($stream->eof());
    }
}
