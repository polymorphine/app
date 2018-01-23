<?php

namespace Shudd3r\Http\Src\Message;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;


class ServerRequest implements ServerRequestInterface
{
    use RequestMethods;

    private $server;
    private $cookie;
    private $query;
    private $attributes;
    private $parsedBody;
    private $files;

    public function __construct(
        string $method,
        UriInterface $uri,
        StreamInterface $body,
        array $headers = [],
        array $params = []
    ) {
        $this->method = $this->validMethod($method);
        $this->uri = $uri;
        $this->body = $body;
        $this->version = isset($params['version']) ? $this->validProtocolVersion($params['version']) : '1.1';
        $this->target = isset($params['target']) ? $this->validRequestTarget($params['target']) : null;
        $this->server = isset($params['server']) ? (array) $params['server'] : [];
        $this->cookie = isset($params['cookie']) ? (array) $params['cookie'] : [];
        $this->query = isset($params['query']) ? (array) $params['query'] : [];
        $this->attributes = isset($params['attributes']) ? (array) $params['attributes'] : [];
        $this->parsedBody = isset($params['parsedBody']) ? (array) $params['parsedBody'] : [];
        $this->files = isset($params['files']) ? (array) $params['files'] : [];
        $this->loadHeaders($headers);
        $this->resolveHostHeader();
    }

    public function getServerParams(): array {
        return $this->server;
    }

    public function getCookieParams() {
        return $this->cookie;
    }
    public function withCookieParams(array $cookies) {
        $clone = clone $this;
        $clone->cookie = $cookies;
        return $clone;
    }

    public function getQueryParams() {
        return $this->query;
    }

    public function withQueryParams(array $query) {
        $clone = clone $this;
        $clone->query = $query;
        return $clone;
    }

    public function getUploadedFiles() {
        return $this->files;
    }

    public function withUploadedFiles(array $uploadedFiles) {
        $clone = clone $this;
        $clone->files = $uploadedFiles;
        return $clone;
    }

    public function getParsedBody() {
        return $this->parsedBody;
    }

    public function withParsedBody($data) {
        $clone = clone $this;
        $clone->parsedBody = $data;
        return $clone;
    }

    public function getAttributes() {
        return $this->attributes;
    }

    public function getAttribute($name, $default = null) {
        return array_key_exists($name, $this->attributes) ? $this->attributes[$name] : $default;
    }

    public function withAttribute($name, $value) {
        $clone = clone $this;
        $clone->attributes[$name] = $value;
        return $clone;
    }

    public function withoutAttribute($name) {
        $clone = clone $this;
        unset($clone->attributes[$name]);
        return $clone;
    }
}
