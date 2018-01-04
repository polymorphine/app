<?php

namespace Shudd3r\Http\Tests\Message;

use Shudd3r\Http\Src\Message\Uri;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;


class UriTest extends TestCase
{
    private function uri($uri = '') {
        return new Uri($uri);
    }

    public function testEmptyConstructorUri_ReturnsRootPathUriString() {
        $this->assertSame('/', (string) $this->uri());
    }

    public function testGettersContractForEmptyUri() {
        $uri = $this->uri();
        $this->assertSame('', $uri->getScheme());
        $this->assertSame('', $uri->getUserInfo());
        $this->assertSame('', $uri->getHost());
        $this->assertSame('', $uri->getAuthority());
        $this->assertSame(null, $uri->getPort());
        $this->assertSame('', $uri->getPath());
        $this->assertSame('', $uri->getQuery());
        $this->assertSame('', $uri->getPath());
    }

    public function testAllPropertiesAreSetWithinConstructor() {
        $uri = $this->uri('https://user:pass@example.com:9001/foo/bar?foo=bar&baz=qux#foo');
        $this->assertSame('https', $uri->getScheme());
        $this->assertSame('user:pass', $uri->getUserInfo());
        $this->assertSame('example.com', $uri->getHost());
        $this->assertSame(9001, $uri->getPort());
        $this->assertSame('user:pass@example.com:9001', $uri->getAuthority());
        $this->assertSame('/foo/bar', $uri->getPath());
        $this->assertSame('foo=bar&baz=qux', $uri->getQuery());
        $this->assertSame('foo', $uri->getFragment());
    }

    public function testImmutability_ModifiersShouldReturnNewInstances() {
        $uri = $this->uri();
        $this->assertNotSame($uri->withScheme('http'), $uri->withScheme('http'));
        $this->assertNotSame($uri->withUserInfo('user'), $uri->withUserInfo('user'));
        $this->assertNotSame($uri->withUserInfo('user', 'password'), $uri->withUserInfo('user', 'password'));
        $this->assertNotSame($uri->withHost('example.com'), $uri->withHost('example.com'));
        $this->assertNotSame($uri->withPort(9001), $uri->withPort(9001));
        $this->assertNotSame($uri->withPort("9001"), $uri->withPort("9001"));
        $this->assertNotSame($uri->withPort(null), $uri->withPort(null));
        $this->assertNotSame($uri->withPath('/foo/bar'), $uri->withPath('/foo/bar'));
        $this->assertNotSame($uri->withQuery('?foo=bar&baz=qux'), $uri->withQuery('?foo=bar&baz=qux'));
        $this->assertNotSame($uri->withFragment('foo'), $uri->withFragment('foo'));
    }

    public function testModifierParametersAndGetterResponseEquivalence() {
        $uri = $this->uri();
        $this->assertSame('http', $uri->withScheme('http')->getScheme());
        $this->assertSame('user', $uri->withUserInfo('user')->getUserInfo());
        $this->assertSame('user:password', $uri->withUserInfo('user', 'password')->getUserInfo());
        $this->assertSame('example.com', $uri->withHost('example.com')->getHost());
        $this->assertSame(9001, $uri->withPort(9001)->getPort());
        $this->assertSame(9001, $uri->withPort("9001")->getPort());
        $this->assertSame(null, $uri->withPort(9001)->withPort(null)->getPort());
        $this->assertSame('user:pass@example.com:500', $uri->withHost('example.com')->withUserInfo('user', 'pass')->withPort(500)->getAuthority());
        $this->assertSame('/foo/bar', $uri->withPath('/foo/bar')->getPath());
        $this->assertSame('foo=bar&baz=qux', $uri->withQuery('foo=bar&baz=qux')->getQuery());
        $this->assertSame('foo', $uri->withFragment('foo')->getFragment());
    }

    public function testInstantiationWithUnsupportedScheme_ThrowsInvalidArgumentException() {
        $this->expectException(InvalidArgumentException::class);
        $this->uri('xttp://example.com');
    }

    public function testModifyingToUnsupportedScheme_ThrowsInvalidArgumentException() {
        $this->expectException(InvalidArgumentException::class);
        $this->uri()->withScheme('httpx');
    }

    public function testSchemeIsNormalizedToLowercase() {
        $this->assertSame('http', $this->uri()->withScheme('Http')->getScheme());
        $this->assertSame('http', $this->uri('hTTP:\\www.example.com')->getScheme());
    }

    public function testDefaultSchemePortLogic() {
        $this->assertNull($this->uri('www.example.com')->getPort(), 'No port specified');
        $this->assertSame(80, $this->uri('www.example.com:80')->getPort(), 'Default port for http, but scheme yet unknown');
        $this->assertNull($this->uri('http://www.example.com:80')->getPort(), 'Default should be omitted when scheme present');

        $this->assertSame(80, $this->uri('http://example.com:80')->withScheme('https')->getPort(), 'Scheme has changed and SPECIFIED port no longer default');
        $this->assertNull($this->uri('https://example.com:80')->withScheme('http')->getPort(), 'Scheme has changed and SPECIFIED port became default');

        $uri = $this->uri('http:foo.bar:500');
        $this->assertNull($uri->getPort(), 'This is relative path with scheme - port not specified');
        $uri = $uri->withPort(443); //SET Port
        $this->assertSame(443, $uri->getPort(), 'No host was given but port was set with modifier');
        $this->assertNull($this->uri((string) $uri)->getPort(), 'Without host port will not be part of uri string even if specified');
        $uri = $uri->withHost('example.com'); //SET Host
        $this->assertSame(443, $this->uri((string) $uri)->getPort(), 'Port included in uri string when host became present');
        $this->assertNull($this->uri((string) $uri->withScheme('https'))->getPort(), 'Changed scheme match its default port - not present in uri string');
    }
}
