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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;


class FakeResponse implements ResponseInterface
{
    public string $body;
    public array  $headers = [];
    public string $protocol = '1.1';
    public int    $status   = 200;
    public string $reason   = 'OK';

    public function __construct($body = '')
    {
        $this->body = $body;
    }

    public function getProtocolVersion(): string
    {
        return $this->protocol;
    }

    public function withProtocolVersion($version): self
    {
        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader($name): bool
    {
        return isset($this->headers[$name]);
    }

    public function getHeader($name): array
    {
        return $this->headers[$name];
    }

    public function getHeaderLine($name): string
    {
        return implode(';', $this->headers[$name]);
    }

    public function withHeader($name, $value): self
    {
        $this->headers[$name] = [$value];
        return $this;
    }

    public function withAddedHeader($name, $value): self
    {
        $this->headers[$name][] = $value;
        return $this;
    }

    public function withoutHeader($name): self
    {
        unset($this->headers[$name]);
        return $this;
    }

    public function getBody(): StreamInterface
    {
        return new FakeStream($this->body);
    }

    public function withBody(StreamInterface $body): self
    {
        $this->body = (string) $body;
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->status;
    }

    public function withStatus($code, $reasonPhrase = ''): self
    {
        return $this;
    }

    public function getReasonPhrase(): string
    {
        return $this->reason;
    }
}
