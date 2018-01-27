<?php

namespace Shudd3r\Http\Src\Message;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;
use InvalidArgumentException;


class ServerRequestFactory
{
    private $server;
    private $get;
    private $post;
    private $cookie;
    private $files;

    public function __construct(array $params = []) {
        $this->server = $params['server'] ?? [];
        $this->get    = $params['get'] ?? [];
        $this->post   = $params['post'] ?? [];
        $this->cookie = $params['cookie'] ?? [];
        $this->files  = $params['files'] ?? [];
    }

    public function create(array $attributes = []): ServerRequestInterface {
        $method  = $this->server['REQUEST_METHOD'] ?? 'GET';
        $uri     = $this->resolveUri();
        $body    = Stream::fromResourceUri('php://input');
        $headers = $this->resolveHeaders();
        $params  = [
            'server'     => $this->server,
            'cookie'     => $this->cookie,
            'query'      => $this->get,
            'parsedBody' => $this->parsedBody(),
            'files'      => $this->normalizeFiles($this->files),
            'attributes' => $attributes,
            'version'    => $this->server['SERVER_PROTOCOL'] ?? '1.1'
        ];

        return new ServerRequest($method, $uri, $body, $headers, $params);
    }

    private function resolveUri(): UriInterface {
        $scheme = (empty($this->server['HTTPS']) || $this->server['HTTPS'] === 'off') ? 'http' : 'https';
        $host   = $this->server['HTTP_HOST'] ?? 'localhost';
        $port   = $this->server['SERVER_PORT'] ?? null;

        list($uri, $fragment) = explode('#', $this->server['REQUEST_URI'] ?? '/', 2) + ['', ''];
        list($path, $query) = explode('?', $uri, 2) + ['', ''];

        return new Uri([
            'scheme' => $scheme,
            'host' => $host,
            'port' => $port,
            'path' => $path,
            'query' => $query,
            'fragment' => $fragment
        ]);
    }

    private function resolveHeaders(): array {
        $headers = [];
        foreach ($this->server as $key => $value) {
            if (!$value || !$headerName = $this->headerName($key)) { continue; }
            $headers[$headerName] = $value;
        }

        if (!isset($headers['Authorization']) && $value = $this->authorizationHeader()) {
            $headers['Authorization'] = $value;
        }

        return $headers;
    }

    private function headerName($name) {
        if (strpos($name, 'HTTP_') === 0) {
            if ($name === 'HTTP_CONTENT_MD5') { return 'Content-MD5'; }
            return $this->normalizedHeaderName(substr($name, 5));
        }

        if (strpos($name, 'CONTENT_') === 0) {
            return $this->normalizedHeaderName($name);
        }

        return false;
    }

    private function normalizedHeaderName(string $name): string {
        return ucwords(strtolower(str_replace('_', '-', $name)), '-');
    }

    private function authorizationHeader() {
        if (!function_exists('apache_request_headers')) { return false; }
        $headers = apache_request_headers();
        return $headers['Authorization'] ?? $headers['authorization'] ?? false;
    }

    private function normalizeFiles(array $files): array {
        $normalizedFiles = [];
        foreach ($files as $key => $value) {
            $normalizedFiles[$key] = ($value instanceof UploadedFileInterface)
                ? $value
                : $this->resolveFileTree($value);
        }

        return $normalizedFiles;
    }

    private function resolveFileTree($value) {
        if (!is_array($value)) {
            throw new InvalidArgumentException('Invalid file data structure');
        }

        return isset($value['tmp_name']) ? $this->createUploadedFile($value) : $this->normalizeFiles($value);
    }

    private function createUploadedFile(array $file) {
        return is_array($file['tmp_name']) ? $this->transposeFileDataSet($file) : new UploadedFile($file);
    }

    private function transposeFileDataSet(array $files) {
        $normalizedFiles = [];
        foreach ($files as $spec_key => $values) {
            foreach ($values as $idx => $value) {
                $normalizedFiles[$idx][$spec_key] = $value;
            }
        }
        $createFile = function ($file) { return new UploadedFile($file); };

        return array_map($createFile, $normalizedFiles);
    }

    protected function parsedBody() {
        //TODO: parsed body use cases
        return $this->post;
    }

    public static function fromGlobals(array $override = []): ServerRequestInterface {
        $factory = new self([
            'server' => isset($override['server']) ? $override['server'] + $_SERVER : $_SERVER,
            'get'    => isset($override['get']) ? $override['get'] + $_GET : $_GET,
            'post'   => isset($override['post']) ? $override['post'] + $_POST : $_POST,
            'cookie' => isset($override['cookie']) ? $override['cookie'] + $_COOKIE : $_COOKIE,
            'files'  => isset($override['files']) ? $override['files'] + $_FILES : $_FILES
        ]);

        return $factory->create();
    }
}
