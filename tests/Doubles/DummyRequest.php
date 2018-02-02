<?php

namespace Shudd3r\Http\Tests\Doubles;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Shudd3r\Http\Tests\Message\Doubles\FakeUri;


class DummyRequest implements ServerRequestInterface
{
    public $uri;
    public $method;

    public function getMethod() { return $this->method ?: 'GET'; }
    public function getUri() { return $this->uri ?: new FakeUri('example.com', '/foo/bar'); }
    public function getRequestTarget() {
        $query = $this->getUri()->getquery();
        $path = $this->getUri()->getPath();
        return $query ? $path . '?' . $query : $path;
    }

    public function getProtocolVersion() {}
    public function withProtocolVersion($version) {}
    public function getHeaders() {}
    public function hasHeader($name) {}
    public function getHeader($name) {}
    public function getHeaderLine($name) {}
    public function withHeader($name, $value) {}
    public function withAddedHeader($name, $value) {}
    public function withoutHeader($name) {}
    public function getBody() {}
    public function withBody(StreamInterface $body) {}
    public function withRequestTarget($requestTarget) {}
    public function withMethod($method) {}
    public function withUri(UriInterface $uri, $preserveHost = false) {}
    public function getServerParams() {}
    public function getCookieParams() {}
    public function withCookieParams(array $cookies) {}
    public function getQueryParams() {}
    public function withQueryParams(array $query) {}
    public function getUploadedFiles() {}
    public function withUploadedFiles(array $uploadedFiles) {}
    public function getParsedBody() {}
    public function withParsedBody($data) {}
    public function getAttributes() {}
    public function getAttribute($name, $default = null) {}
    public function withAttribute($name, $value) {}
    public function withoutAttribute($name) {}
}
