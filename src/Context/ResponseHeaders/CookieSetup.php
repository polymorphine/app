<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Context\ResponseHeaders;

use Polymorphine\Http\Context\ResponseHeaders;
use LogicException;
use DateTime;


class CookieSetup
{
    private const MAX_TIME = 2628000;

    private $headers;
    private $name;

    private $minutes;
    private $domain;
    private $path     = '/';
    private $hostLock = false;
    private $secure   = false;
    private $httpOnly = false;
    private $sameSite;

    public function __construct(string $name, ResponseHeaders $headers, array $attributes = [])
    {
        $this->name    = $name;
        $this->headers = $headers;
        $this->parsePrefix($name);
        $this->setAttributes($attributes);
    }

    public function value(string $value): void
    {
        $this->headers->add('Set-Cookie', $this->header($value));
    }

    public function remove(): void
    {
        $this->minutes = -self::MAX_TIME;
        $this->headers->add('Set-Cookie', $this->header(null));
    }

    public function expires(int $minutes): CookieSetup
    {
        $this->minutes = $minutes;
        return $this;
    }

    public function permanent(): CookieSetup
    {
        $this->minutes = self::MAX_TIME;
        return $this;
    }

    public function domain(string $domain): CookieSetup
    {
        if ($this->hostLock) {
            throw new LogicException('Cannot set domain in cookies with `__Host-` name prefix');
        }

        $this->domain = $domain;
        return $this;
    }

    public function path(string $path): CookieSetup
    {
        if ($this->hostLock) {
            throw new LogicException('Cannot set path in cookies with `__Host-` name prefix');
        }

        $this->path = $path;
        return $this;
    }

    public function httpOnly(): CookieSetup
    {
        $this->httpOnly = true;
        return $this;
    }

    public function secure(): CookieSetup
    {
        $this->secure = true;
        return $this;
    }

    public function sameSiteStrict()
    {
        $this->setSameSiteDirective('Strict');
        return $this;
    }

    public function sameSiteLax()
    {
        $this->setSameSiteDirective('Lax');
        return $this;
    }

    private function setSameSiteDirective(string $value): void
    {
        if ($this->sameSite) {
            throw new LogicException('SameSite cookie directive already set and cannot be changed');
        }
        $this->sameSite = $value;
    }

    private function parsePrefix(string $name): void
    {
        $secure = (stripos($name, '__Secure-') === 0);
        $host   = (stripos($name, '__Host-') === 0);
        if (!$host && !$secure) { return; }

        $this->secure = true;
        if ($secure) { return; }

        $this->hostLock = true;
    }

    private function setAttributes(array $attr): void
    {
        if (isset($attr['domain'])) { $this->domain($attr['domain']); }
        if (isset($attr['path'])) { $this->path($attr['path']); }
        if (isset($attr['expires'])) {
            $attr['expires'] ? $this->expires($attr['expires']) : $this->permanent();
        }
        if (!empty($attr['httpOnly'])) { $this->httpOnly(); }
        if (!empty($attr['secure'])) { $this->secure(); }
        if (!empty($attr['sameSite'])) {
            $this->setSameSiteDirective($attr['sameSite'] === 'Strict' ? 'Strict' : 'Lax');
        }
    }

    private function header($value): string
    {
        $header = $this->name . '=' . $value;

        if ($this->domain) {
            $header .= '; Domain=' . (string) $this->domain;
        }

        if ($this->path) {
            $header .= '; Path=' . $this->path;
        }

        if ($this->minutes) {
            $seconds = $this->minutes * 60;
            $expire  = (new DateTime())->setTimestamp(time() + $seconds)->format(DateTime::COOKIE);

            $header .= '; Expires=' . $expire;
            $header .= '; MaxAge=' . $seconds;
        }

        if ($this->secure) {
            $header .= '; Secure';
        }

        if ($this->httpOnly) {
            $header .= '; HttpOnly';
        }

        if ($this->sameSite) {
            $header .= '; SameSite=' . $this->sameSite;
        }

        return $header;
    }
}
