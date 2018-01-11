<?php

namespace Shudd3r\Http\Tests\Message;


use Psr\Http\Message\StreamInterface;
use Shudd3r\Http\Src\Message\Stream;
use PHPUnit\Framework\TestCase;

class StreamTest extends TestCase
{
    private function stream($resource = null, $mode = null) {
        $resource = $resource ?? 'php://memory';
        return is_resource($resource) ? new Stream($resource) : Stream::fromResourceUri($resource, $mode);
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
}
