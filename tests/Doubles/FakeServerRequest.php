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

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;


class FakeServerRequest implements ServerRequestInterface
{
    public UriInterface $uri;
    public string       $method;
    public array        $attr    = [];
    public array        $cookies = [];
    public array        $parsed  = [];

    public function __construct(string $method = 'GET', UriInterface $uri = null)
    {
        $this->method = $method;
        $this->uri    = $uri ?? FakeUri::fromString('//example.com/foo/bar');
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function getRequestTarget(): string
    {
        $query = $this->getUri()->getquery();
        $path  = $this->getUri()->getPath();

        return $query ? $path . '?' . $query : $path;
    }

    public function getProtocolVersion(): string
    {
        return '1.1';
    }

    public function withProtocolVersion($version): self
    {
        return $this;
    }

    public function getHeaders(): array
    {
        return [];
    }

    public function hasHeader($name): bool
    {
        return false;
    }

    public function getHeader($name): array
    {
        return [];
    }

    public function getHeaderLine($name): string
    {
        return '';
    }

    public function withHeader($name, $value): self
    {
        return $this;
    }

    public function withAddedHeader($name, $value): self
    {
        return $this;
    }

    public function withoutHeader($name): self
    {
        return $this;
    }

    public function getBody(): StreamInterface
    {
        return new FakeStream();
    }

    public function withBody(StreamInterface $body): self
    {
        return $this;
    }

    public function withRequestTarget($requestTarget): self
    {
        return $this;
    }

    public function withMethod($method): self
    {
        return $this;
    }

    public function withUri(UriInterface $uri, $preserveHost = false): self
    {
        return $this;
    }

    public function getServerParams(): array
    {
        return [];
    }

    public function getCookieParams(): array
    {
        return $this->cookies;
    }

    public function withCookieParams(array $cookies): self
    {
        return $this;
    }

    public function getQueryParams(): array
    {
        return [];
    }

    public function withQueryParams(array $query): self
    {
        return $this;
    }

    public function getUploadedFiles(): array
    {
        return [];
    }

    public function withUploadedFiles(array $uploadedFiles): self
    {
        return $this;
    }

    public function getParsedBody(): array
    {
        return $this->parsed;
    }

    public function withParsedBody($data): self
    {
        $this->parsed = $data ?? [];
        return $this;
    }

    public function getAttributes(): array
    {
        return $this->attr;
    }

    public function getAttribute($name, $default = null)
    {
        return $this->attr[$name] ?? $default;
    }

    public function withAttribute($name, $value): self
    {
        $this->attr[$name] = $value;
        return $this;
    }

    public function withoutAttribute($name): self
    {
        return $this;
    }
}
