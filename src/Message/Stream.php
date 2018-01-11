<?php

namespace Shudd3r\Http\Src\Message;

use Psr\Http\Message\StreamInterface;
use InvalidArgumentException;


class Stream implements StreamInterface
{
    protected $resource;

    private $metaData;
    private $readable;
    private $writable;
    private $seekable;

    public function __construct($resource) {
        if (!is_resource($resource)) {
            throw new InvalidArgumentException('Invalid stream resource');
        }

        $this->resource = $resource;
    }

    public static function fromResourceUri(string $stream, $mode = 'r') {
        $error = function () { throw new InvalidArgumentException('Invalid stream reference'); };
        set_error_handler($error, E_WARNING);
        $resource = fopen($stream, $mode);
        restore_error_handler();

        return new self($resource);
    }

    public function __toString() {}
    public function close() {}
    public function detach() {}
    public function getSize() {}
    public function tell() {}
    public function eof() {}

    public function isSeekable() {
        if (isset($this->seekable)) { return $this->seekable; }
        return $this->seekable = $this->getMetadata('seekable');
    }

    public function seek($offset, $whence = SEEK_SET) {}
    public function rewind() {}

    public function isWritable() {
        if (isset($this->writable)) { return $this->writable; }
        $mode = $this->getMetadata('mode');
        $writable = ['w' => true, 'a' => true, 'x' => true, 'c' => true];
        return $this->writable = (isset($writable[$mode[0]]) || strstr($mode, '+'));
    }

    public function write($string) {}

    public function isReadable() {
        if (isset($this->readable)) { return $this->readable; }
        $mode = $this->getMetadata('mode');
        return $this->readable = ($mode[0] === 'r' || strstr($mode, '+'));
    }

    public function read($length) {}
    public function getContents() {}

    public function getMetadata($key = null) {
        isset($this->metaData) or $this->metaData = stream_get_meta_data($this->resource);
        if ($key === null) { return $this->metaData; }

        return isset($this->metaData[$key]) ? $this->metaData[$key] : null;
    }
}
