<?php

namespace Shudd3r\Http\Src\Message;

use Psr\Http\Message\StreamInterface;
use InvalidArgumentException;
use RuntimeException;
use Shudd3r\Http\Src\Message\Exception\StreamResourceCallException;


class Stream implements StreamInterface
{
    protected $resource;

    private $metaData;
    private $readable;
    private $writable;
    private $seekable;

    public function __construct($resource) {
        if (!is_resource($resource) || get_resource_type($resource) !== 'stream') {
            throw new InvalidArgumentException('Invalid stream resource');
        }

        $this->resource = $resource;
    }

    public static function fromResourceUri(string $streamUri, $mode = 'r') {
        $error = function () { throw new InvalidArgumentException('Invalid stream reference'); };
        set_error_handler($error, E_WARNING);
        $resource = fopen($streamUri, $mode);
        restore_error_handler();

        return new self($resource);
    }

    public static function fromBodyString(string $body) {
        $stream = self::fromResourceUri('php://temp', 'w+b');
        $stream->write($body);
        $stream->rewind();
        return $stream;
    }

    public function __toString() {
        try {
            $this->rewind();
            return $this->getContents();
        } catch (RuntimeException $e) {
            return '';
        }
    }

    public function close() {
        $resource = $this->detach();
        if ($resource) { fclose($resource); }
    }

    public function detach() {
        if (!$this->resource) { return null; }

        $resource = $this->resource;
        $this->resource = null;
        $this->readable = false;
        $this->seekable = false;
        $this->writable = false;

        return $resource;
    }

    public function getSize() {
        return $this->resource ? fstat($this->resource)['size'] : null;
    }

    public function tell() {
        if (!$this->resource) {
            throw new RuntimeException('Pointer position not available in detached resource');
        }

        $position = ftell($this->resource);

        if ($position === false) {
            throw new StreamResourceCallException('Failed to tell pointer position');
        }

        return $position;
    }

    public function eof() {
        return $this->resource ? feof($this->resource) : true;
    }

    public function isSeekable() {
        if (isset($this->seekable)) { return $this->seekable; }
        return $this->seekable = $this->getMetadata('seekable');
    }

    public function seek($offset, $whence = SEEK_SET) {
        if (!$this->isSeekable()) {
            throw new RuntimeException('Stream is not seekable or detached');
        }

        $exitCode = fseek($this->resource, $offset, $whence);

        if ($exitCode === -1) {
            throw new StreamResourceCallException('Failed to seek the stream');
        }
    }

    public function rewind() {
        $this->seek(0);
    }

    public function isWritable() {
        if (isset($this->writable)) { return $this->writable; }
        $mode = $this->getMetadata('mode');
        $writable = ['w' => true, 'a' => true, 'x' => true, 'c' => true];
        return $this->writable = (isset($writable[$mode[0]]) || strstr($mode, '+'));
    }

    public function write($string) {
        if (!$this->resource) {
            throw new RuntimeException('No resource available; cannot write');
        }

        if (!$this->isWritable()) {
            throw new RuntimeException('Stream is not writable');
        }

        $bytesWritten = fwrite($this->resource, $string);

        if ($bytesWritten === false) {
            throw new StreamResourceCallException('Failed writing to stream');
        }

        return $bytesWritten;
    }

    public function isReadable() {
        if (isset($this->readable)) { return $this->readable; }
        $mode = $this->getMetadata('mode');
        return $this->readable = ($mode[0] === 'r' || strstr($mode, '+'));
    }

    public function read($length) {
        if (!$this->resource) {
            throw new RuntimeException('No resource available; cannot read');
        }

        if (!$this->isReadable()) {
            throw new RuntimeException('Stream is not readable');
        }

        $streamData = fread($this->resource, $length);

        if ($streamData === false) {
            throw new StreamResourceCallException('Failed reading from stream');
        }

        return $streamData;
    }

    public function getContents() {
        if (!$this->isReadable()) {
            throw new RuntimeException('Stream is not readable or detached');
        }

        $streamContents = stream_get_contents($this->resource);

        if ($streamContents === false) {
            throw new StreamResourceCallException('Failed to retrieve stream contents');
        }

        return $streamContents;
    }

    public function getMetadata($key = null) {
        isset($this->metaData) or $this->metaData = stream_get_meta_data($this->resource);
        if ($key === null) { return $this->metaData; }

        return isset($this->metaData[$key]) ? $this->metaData[$key] : null;
    }
}