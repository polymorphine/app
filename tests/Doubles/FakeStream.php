<?php declare(strict_types=1);

/*
 * This file is part of Polymorphine/App package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\App\Tests\Doubles;

use Psr\Http\Message\StreamInterface;


class FakeStream implements StreamInterface
{
    public bool $seekable = true;

    private string $body;
    private string $stream;

    public function __construct(string $body = '')
    {
        $this->body   = $body;
        $this->stream = $body;
    }

    public function __toString(): string
    {
        return $this->body;
    }

    public function close(): void
    {
    }

    public function detach(): void
    {
    }

    public function getSize(): int
    {
        return strlen($this->stream);
    }

    public function tell(): int
    {
        return strlen($this->body) - strlen($this->stream);
    }

    public function eof(): bool
    {
        return empty($this->stream);
    }

    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
    }

    public function rewind(): void
    {
        $this->stream = $this->body;
    }

    public function isWritable(): bool
    {
        return true;
    }

    public function write($string): void
    {
        $this->body = $string;
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read($length)
    {
        $send = substr($this->stream, 0, $length);
        $this->stream = substr($this->stream, $length) ?: '';
        return $send;
    }

    public function getContents(): string
    {
        return $this->body;
    }

    public function getMetadata($key = null): array
    {
        return [];
    }
}
