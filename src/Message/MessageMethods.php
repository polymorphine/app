<?php

namespace Shudd3r\Http\Src\Message;

use Psr\Http\Message\StreamInterface;
use InvalidArgumentException;


trait MessageMethods
{
    private $body;
    private $version;
    private $headers;

    private $supportedProtocolVersions = ['1.0', '1.1', '2'];
    private $headerNames = [];

    public function getProtocolVersion() {
        return $this->version;
    }

    public function withProtocolVersion($version) {
        $clone = clone $this;
        $clone->version = $this->validProtocolVersion($version);
        return $clone;
    }

    public function getHeaders() {
        return $this->headers;
    }

    public function hasHeader($name) {
        return is_string($name) && isset($this->headerNames[strtolower($name)]);
    }

    public function getHeader($name) {
        return $this->hasHeader($name) ? $this->headers[$this->headerNames[strtolower($name)]] : [];
    }

    public function getHeaderLine($name) {
        $header = $this->getHeader($name);
        return empty($header) ? '' : implode(', ', $header);
    }

    public function withHeader($name, $value) {
        $clone = clone $this;
        $clone->setHeader($name, $value);
        return $clone;
    }

    public function withAddedHeader($name, $value) {
        if (!$this->hasHeader($name)) { return $this->withHeader($name, $value); }

        $name  = $this->headerNames[strtolower($name)];
        $value = $this->headerValue($value);

        $clone = clone $this;
        $clone->headers[$name] = array_merge($clone->headers[$name], $value);
        return $clone;
    }

    public function withoutHeader($name) {
        $clone = clone $this;
        $clone->removeHeader(strtolower($name));
        return $clone;
    }

    public function getBody(): StreamInterface {
        return $this->body;
    }

    public function withBody(StreamInterface $body) {
        $clone = clone $this;
        $clone->body = $body;
        return $clone;
    }

    private function loadHeaders(array $headers) {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }
    }

    private function setHeader($name, $value) {
        $this->headers[$name] = $this->headerValue($value);
        $this->headerNames[strtolower($name)] = $name;
    }

    private function headerValue($value) {
        return is_string($value) ? [$value] : $value;
    }

    private function removeHeader($header_id) {
        if (!isset($this->headerNames[$header_id])) { return; }
        unset($this->headers[$this->headerNames[$header_id]]);
        unset($this->headerNames[$header_id]);
    }

    private function validProtocolVersion($version) {
        if (!is_string($version)) {
            throw new InvalidArgumentException('Invalid HTTP protocol version type - expected string');
        }

        if (!in_array($version, $this->supportedProtocolVersions)) {
            throw new InvalidArgumentException('Unsupported HTTP protocol version - expected <' . implode('|', $this->supportedProtocolVersions) . '> string.');
        }
        return $version;
    }
}
