<?php

namespace Shudd3r\Http\Tests\Message;


use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Shudd3r\Http\Src\Message\Stream;
use RuntimeException;
use Shudd3r\Http\Tests\Doubles\StubStreamWrapper;

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

    /**
     * @var $streamWrapper string
     */
    protected $streamWrapper;

    private function stream($resource = null, $mode = null) {
        $resource = $resource ?? 'php://memory';
        return $this->stream = is_resource($resource) ? new Stream($resource) : Stream::fromResourceUri($resource, $mode);
    }

    private function fileStream($mode = null, string $contents = '') {
        $this->testFilename = tempnam(sys_get_temp_dir(), 'test');
        if ($contents) { file_put_contents($this->testFilename, $contents); }
        return $this->stream($this->testFilename, $mode);
    }

    private function customStream($name, $wrapperClass, $mode = null) {
        if (in_array($name, stream_get_wrappers())) { stream_wrapper_unregister($name); }
        stream_wrapper_register($name, $wrapperClass);
        $this->streamWrapper = $name;
        return $this->stream($name . '://stream', $mode);
    }

    private function streamWithPredefinedConditions($contents, $position) {
        $resource = fopen('php://memory', 'w+');
        fwrite($resource, $contents);
        fseek($resource, $position);
        return $this->stream($resource);
    }

    public function tearDown() {
        if (isset($this->streamWrapper)) {
            stream_wrapper_unregister($this->streamWrapper);
            unset($this->streamWrapper);
        }
        if ($this->testFilename) {
            $this->stream->close();
            if (file_exists($this->testFilename)) { unlink($this->testFilename); }
        }
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

    public function testReadDetachedStream_ThrowsException() {
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

    public function testGetContentsFromDetachedStream_ThrowsException() {
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

    public function testSeekDetachedStream_ThrowsException() {
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

    public function testRewindDetachedStream_ThrowsException() {
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

    public function testWriteIntoDetachedStream_ThrowsException() {
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

    public function testGetSize_ReturnsSizeOfStream() {
        $this->assertSame(11, $this->fileStream('r+', 'hello world')->getSize());
        $this->assertSame(0, $this->fileStream('w+', 'truncated mode')->getSize());
    }

    public function testTellOnCreatedStream_ReturnsInitialPointerPosition() {
        $this->assertSame(0, $this->fileStream('r', 'hello world')->tell());
    }

    public function testSeekMovesPointerPosition() {
        $stream = $this->fileStream('r', 'hello world');
        $stream->seek(5);
        $this->assertSame(5, $stream->tell());
        $this->expectExceptionMessage('Error:');
        $stream->seek(-1);
    }

    public function testWhenStreamWasReadToItsEnd_EofReturnsTrue() {
        $stream = $this->fileStream('r', 'hello world!')->detach();
        //eof is assumed false and modified by reading
        while (!feof($stream)) { fread($stream, 12); }
        $stream = $this->stream($stream);
        $this->assertTrue($stream->eof());
    }

    public function testTellError_ThrowsException() {
        $stream = $this->customStream('test', StubStreamWrapper::class);
        StubStreamWrapper::$tellReturns = -1;
        //cursor position will be assumed 0 until moved
        $stream->seek(1);
        $this->expectExceptionMessage('Error:');
        $stream->tell();
    }

    public function testWriteSendsDataToStream() {
        $stream = $this->customStream('test', StubStreamWrapper::class, 'w+b');
        $data = 'Hello World!';
        $stream->write($data);
        $this->assertSame($data, StubStreamWrapper::$writtenData);
    }

    public function testErrorOnWrite_ThrowsException() {
        $stream = $this->stream(null, 'w+b');
        error_reporting(0);
        $this->expectExceptionMessage('Error:');
        $stream->write(['invalid type']);
        error_reporting(E_ALL);
    }

    public function testReadGetsDataFromStream() {
        $string = 'Hello World!';
        $stream = $this->streamWithPredefinedConditions($string, 6);
        $this->assertSame('World', $stream->read(5));
    }

    public function testErrorOnRead_ThrowsException() {
        $stream = $this->stream(null, 'w+b');
        error_reporting(0);
        $this->expectExceptionMessage('Error:');
        $stream->read(['invalid type']);
        error_reporting(E_ALL);
    }

    public function testGetContents_ReturnsRemainingStreamContents() {
        $string = 'Hello World!';
        $stream = $this->streamWithPredefinedConditions($string, 6);
        $this->assertSame('World!', $stream->getContents());
    }

    public function testToString_ReturnsFullStreamContents() {
        $string = 'Hello World!';
        $stream = $this->streamWithPredefinedConditions($string, 6);
        $this->assertSame('Hello World!', (string) $stream);
    }

    public function testWrittenDataCorrespondsToReadData() {
        $string = 'Hello World!';
        $stream = $this->stream(null, 'w+');
        $stream->write($string);
        $stream->rewind();
        $this->assertSame($string, $stream->read(strlen($string)));
        $stream->rewind();
        $this->assertSame($string, $stream->getContents());
        $this->assertSame($string, (string)$stream);
    }

    public function testSeekWhenceBehavior() {
        $stream = $this->streamWithPredefinedConditions('Hello World!', 3);
        $stream->seek(6);
        $this->assertSame(6, $stream->tell(), 'SEEK_SET offset resolves into absolute position');
        $stream = $this->streamWithPredefinedConditions('Hello World!', 3);
        $stream->seek(6, SEEK_CUR);
        $this->assertSame(9, $stream->tell(), 'SEEK_CUR offset resolves into position relative to current');
        $stream = $this->streamWithPredefinedConditions('Hello World!', 3);
        $stream->seek(-3, SEEK_END);
        $this->assertSame(9, $stream->tell(), 'SEEK_END offset resolves into position relative to end of stream');
    }

    public function testToStringOnUnreadableStream_ReturnsEmptyString() {
        $stream = $this->fileStream('a', 'Hello World');
        $this->assertSame('', (string) $stream);
    }

    public function testToStringOnNotSeekableStream_ReturnsEmptyString() {
        $stream = $this->stream('php://output', 'a');
        $this->assertFalse($stream->isSeekable());
        $this->assertSame('', (string) $stream);
    }

    //TODO: Reproduce getContents() & __toString errors
}
