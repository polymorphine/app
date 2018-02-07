<?php

namespace Shudd3r\Http\Tests\Message;

use PHPUnit\Framework\TestCase;
use Shudd3r\Http\Src\Message\Stream;
use Psr\Http\Message\StreamInterface;
use InvalidArgumentException;
use RuntimeException;


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
     * @var $overrideNativeFunctions bool Force error responses from native function calls
     */
    public static $overrideNativeFunctions;

    private function stream($resource = null, $mode = null) {
        $resource = $resource ?? 'php://memory';
        return $this->stream = is_resource($resource) ? new Stream($resource) : Stream::fromResourceUri($resource, $mode);
    }

    private function fileStream($mode = null, string $contents = '') {
        $this->testFilename = tempnam(sys_get_temp_dir(), 'test');
        if ($contents) { file_put_contents($this->testFilename, $contents); }
        return $this->stream($this->testFilename, $mode);
    }

    private function streamWithPredefinedConditions($contents, $position) {
        $resource = fopen('php://memory', 'w+');
        fwrite($resource, $contents);
        fseek($resource, $position);
        return $this->stream($resource);
    }

    public function setUp() {
        self::$overrideNativeFunctions = false;
    }

    public function tearDown() {
        if ($this->stream) { $this->stream->close(); }
        if (file_exists($this->testFilename)) { unlink($this->testFilename); }
    }

    public function testInstantiateWithStreamName() {
        $this->assertInstanceOf(StreamInterface::class, Stream::fromResourceUri('php://memory', 'a+b'));
        $this->assertInstanceOf(StreamInterface::class, Stream::fromResourceUri('php://memory', 'w'));
    }

    public function testInstantiateWithStreamResource() {
        $this->assertInstanceOf(StreamInterface::class, (new Stream(fopen('php://input', 'r+b'))));
    }

    public function testNonResourceConstructorArgument_ThrowsException() {
        $this->expectException(InvalidArgumentException::class);
        new Stream('http://example.com');
    }

    public function testNonStreamResourceConstructorArgument_ThrowsException() {
        $this->expectException(InvalidArgumentException::class);
        new Stream(imagecreate(100, 100));
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
        fclose($stream->detach());
        $this->assertFalse($stream->isReadable());
        $this->assertFalse($stream->isSeekable());
        $this->assertFalse($stream->isWritable());
    }

    public function testTell_ReturnsPointerPosition() {
        $this->assertSame(0, $this->stream(null, 'r')->tell());
        $this->assertSame(5, $this->streamWithPredefinedConditions('Hello World!', 5)->tell());
    }

    public function testTellDetachedStream_ThrowsException() {
        $stream = $this->stream();
        fclose($stream->detach());
        $this->expectException(RuntimeException::class);
        $stream->tell();
    }

    public function testTellError_ThrowsException() {
        $stream = $this->stream();
        StreamTest::$overrideNativeFunctions = true;
        $this->expectException(RuntimeException::class);
        $stream->tell();
    }

    public function testSeekMovesPointerPosition() {
        $stream = $this->streamWithPredefinedConditions('Hello World!', 0);
        $this->assertSame(0, $stream->tell());
        $stream->seek(5);
        $this->assertSame(5, $stream->tell());
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

    public function testSeekNotSeekableStream_ThrowsException() {
        $stream = $this->stream('php://output', 'a');
        $this->assertFalse($stream->isSeekable());
        $this->expectException(RuntimeException::class);
        $stream->seek(1);
    }

    public function testSeekDetachedStream_ThrowsException() {
        $stream = $this->stream();
        fclose($stream->detach());
        $this->expectException(RuntimeException::class);
        $stream->seek(1);
    }

    public function testSeekError_ThrowsException() {
        $stream = $this->stream();
        $this->expectException(RuntimeException::class);
        $stream->seek(-1);
    }

    public function testRewindMovesPointerToBeginningOfTheStream() {
        $stream = $this->streamWithPredefinedConditions('Hello World!', 4);
        $stream->rewind();
        $this->assertSame(0, $stream->tell());
    }

    public function testRewindNotSeekableStream_ThrowsException() {
        $stream = $this->stream('php://output', 'a');
        $this->assertFalse($stream->isSeekable());
        $this->expectException(RuntimeException::class);
        $stream->rewind();
    }

    public function testRewindDetachedStream_ThrowsException() {
        $stream = $this->stream();
        fclose($stream->detach());
        $this->expectException(RuntimeException::class);
        $stream->rewind();
    }

    public function testGetSize_ReturnsSizeOfStream() {
        $this->assertSame(12, $this->streamWithPredefinedConditions('Hello World!', 0)->getSize());
        $this->assertSame(0, $this->stream(null, 'w+')->getSize());
    }

    public function testGetSizeOnDetachedResource_ReturnsNull() {
        $stream = $this->stream();
        fclose($stream->detach());
        $this->assertNull($stream->getSize());
    }

    public function testReadGetsDataFromStream() {
        $string = 'Hello World!';
        $stream = $this->streamWithPredefinedConditions($string, 6);
        $this->assertSame('World', $stream->read(5));
    }

    public function testReadUnreadableStream_ThrowsException() {
        $stream = $this->fileStream('w');
        $this->assertFalse($stream->isReadable());
        $this->expectException(RuntimeException::class);
        $stream->read(1);
    }

    public function testReadDetachedStream_ThrowsException() {
        $stream = $this->stream();
        fclose($stream->detach());
        $this->expectException(RuntimeException::class);
        $stream->read(1);
    }

    public function testReadError_ThrowsException() {
        self::$overrideNativeFunctions = true;
        $stream = $this->stream(null, 'w+b');
        $this->expectException(RuntimeException::class);
        $stream->read(1);
    }

    public function testGetContents_ReturnsRemainingStreamContents() {
        $string = 'Hello World!';
        $stream = $this->streamWithPredefinedConditions($string, 6);
        $this->assertSame('World!', $stream->getContents());
    }

    public function testGetContentsOnUnreadableStream_ThrowsException() {
        $stream = $this->fileStream('w');
        $this->assertFalse($stream->isReadable());
        $this->expectException(RuntimeException::class);
        $stream->getContents();
    }

    public function testGetContentsFromDetachedStream_ThrowsException() {
        $stream = $this->stream();
        fclose($stream->detach());
        $this->expectException(RuntimeException::class);
        $stream->getContents();
    }

    public function testGetContentsError_ThrowsException() {
        $stream = $this->streamWithPredefinedConditions('Hello World!', 0);
        self::$overrideNativeFunctions = true;
        $this->expectException(RuntimeException::class);
        $stream->getContents();
    }

    public function testEofOnRead() {
        $stream = $this->streamWithPredefinedConditions('hello world!', 11);
        $stream->read(1);
        $this->assertFalse($stream->eof());
        $stream->read(1);
        $this->assertTrue($stream->eof());
        $stream->seek(6);
        $this->assertFalse($stream->eof());
        $stream->getContents();
        $this->assertTrue($stream->eof());
    }

    public function testEofOnDetachedStream_ReturnsTrue() {
        $stream = $this->stream();
        $stream->detach();
        $this->assertTrue($stream->eof());
    }

    public function testWriteSendsDataToStream() {
        $stream = $this->stream(null, 'w+b');
        $data = 'Hello World!';
        $stream->write($data);
        $this->assertSame($data, (string) $stream);
    }

    public function testWriteNotWritableStream_ThrowsException() {
        $stream = $this->stream();
        $this->assertFalse($stream->isWritable());
        $this->expectException(RuntimeException::class);
        $stream->write('hello world!');
    }

    public function testWriteIntoDetachedStream_ThrowsException() {
        $stream = $this->stream();
        fclose($stream->detach());
        $this->expectException(RuntimeException::class);
        $stream->write('hello world!');
    }

    public function testErrorOnWrite_ThrowsException() {
        StreamTest::$overrideNativeFunctions = true;
        $stream = $this->stream(null, 'w+b');
        $this->expectException(RuntimeException::class);
        $stream->write('Hello World!');
    }

    public function testWrittenDataIsEqualToReadData() {
        $string = 'Hello World!';
        $stream = $this->stream(null, 'w+');
        $stream->write($string);
        $stream->rewind();
        $this->assertSame($string, $stream->read(strlen($string)));
        $stream->rewind();
        $this->assertSame($string, $stream->getContents());
        $this->assertSame($string, (string)$stream);
    }

    public function testToString_ReturnsFullStreamContents() {
        $string = 'Hello World!';
        $stream = $this->streamWithPredefinedConditions($string, 6);
        $this->assertSame($string, (string) $stream);
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

    public function testWhenErrorOccurs_ToStringReturnsEmptyString() {
        $stream = $this->streamWithPredefinedConditions('Hello World!', 6);
        self::$overrideNativeFunctions = true;
        $this->assertSame('', (string) $stream);
    }

    public function testInstantiateWithStringBody() {
        $stream = Stream::fromBodyString('Hello World!');
        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertSame('Hello', $stream->read(5));
    }
}

namespace Shudd3r\Http\Src\Message;

use Shudd3r\Http\Tests\Message\StreamTest;

function fread($resource, $count) {
    return StreamTest::$overrideNativeFunctions ? false : \fread($resource, $count);
}

function fwrite($resource, $contents) {
    return StreamTest::$overrideNativeFunctions ? false : \fwrite($resource, $contents);
}

function ftell($resource) {
    return StreamTest::$overrideNativeFunctions ? false : \ftell($resource);
}

function stream_get_contents($resource) {
    return StreamTest::$overrideNativeFunctions ? false : \stream_get_contents($resource);
}
