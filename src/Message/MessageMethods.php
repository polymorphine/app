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
        $value = $this->validHeaderValues($value);

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
        $name = $this->indexHeaderName($name);
        $this->headers[$name] = $this->validHeaderValues($value);
    }

    private function indexHeaderName($name) {
        if (!is_string($name)) {
            throw new InvalidArgumentException('Invalid header name argument type - expected string token');
        }

        $headerIndex = strtolower($name);

        if (!isset($this->headerNames[$headerIndex])) {
            $this->headerNames[$headerIndex] = $this->validTokenChars($name);
        }

        return $this->headerNames[$headerIndex];
    }

    private function validTokenChars($token) {
        if (!preg_match('/^[a-zA-Z0-9\'`#$%&*+.^_|~!-]+$/', $token)) {
            throw new InvalidArgumentException('Invalid characters in header name string');
        }

        return $token;
    }

    private function validHeaderValues($headerValues) {
        if (is_string($headerValues)) { $headerValues = [$headerValues]; }
        if (!is_array($headerValues) || !$this->legalHeaderStrings($headerValues)) {
            throw new InvalidArgumentException('Invalid HTTP header value argument - expected legal strings[] or string');
        }

        return array_values($headerValues);
    }

    private function legalHeaderStrings(array $headerValues) {
        foreach ($headerValues as $value) {
            if (!is_string($value) || $this->illegalHeaderChars($value)) {
                return false;
            }
        }
        return true;
    }

    private function illegalHeaderChars(string $header) {
        $illegalCharset   = preg_match("/[^\t\r\n\x20-\x7E\x80-\xFE]/", $header);
        $invalidLineBreak = preg_match("/(?:[^\r]\n|\r[^\n]|\n[^ \t])/", $header);

        return ($illegalCharset || $invalidLineBreak);
    }

    private function removeHeader($headerIndex) {
        if (!isset($this->headerNames[$headerIndex])) { return; }
        unset($this->headers[$this->headerNames[$headerIndex]]);
        unset($this->headerNames[$headerIndex]);
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
