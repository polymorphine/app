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
    private $lockDomain = false;
    private $path       = '/';
    private $lockPath   = false;
    private $secure     = false;
    private $httpOnly   = false;
    private $sameSite;

    public function __construct(string $name, ResponseHeaders $headers)
    {
        $this->name    = $name;
        $this->headers = $headers;
        $this->parsePrefix($name);
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
        if ($this->lockDomain) {
            throw new LogicException('Cannot set domain in cookies with `__Host-` name prefix');
        }

        $this->domain = $domain;
        return $this;
    }

    public function path(string $path): CookieSetup
    {
        if ($this->lockPath) {
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
        if ($this->sameSite) { return $this; }
        $this->sameSite = 'Strict';
        return $this;
    }

    public function sameSiteLax()
    {
        if ($this->sameSite) { return $this; }
        $this->sameSite = 'Lax';
        return $this;
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

    private function parsePrefix(string $name): void
    {
        $secure = (stripos($name, '__Secure-') === 0);
        $host   = (stripos($name, '__Host-') === 0);
        if (!$host && !$secure) { return; }

        $this->secure = true;
        if ($secure) { return; }

        $this->lockPath   = true;
        $this->lockDomain = true;
    }
}
