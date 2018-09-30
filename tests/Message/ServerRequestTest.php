<?php

/*
 * This file is part of Polymorphine/App package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Tests\Message;

use PHPUnit\Framework\TestCase;
use Polymorphine\Http\Tests\Doubles;
use Polymorphine\Http\Message\ServerRequest;
use Polymorphine\Http\Message\Uri;
use Psr\Http\Message\ServerRequestInterface;
use InvalidArgumentException;


class ServerRequestTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(ServerRequestInterface::class, $this->request());
    }

    public function testGetServerParams_ReturnsInstanceServerParamsArray()
    {
        $params = ['key' => 'value'];
        $this->assertSame($params, $this->request(['server' => $params])->getServerParams());
    }

    /**
     * @dataProvider instanceProperties
     *
     * @param $method
     * @param $key
     * @param $params
     */
    public function testGetters_ReturnConstructorProperties($method, $params, $key)
    {
        $this->assertSame($params, $this->request([$key => $params])->{$method}());
    }

    public function instanceProperties()
    {
        return [
            'cookie' => ['getCookieParams', ['key' => 'value'], 'cookie'],
            'query'  => ['getQueryParams', ['key' => 'value'], 'query'],
            'attrib' => ['getAttributes', ['key' => 'value'], 'attributes'],
            'pBody'  => ['getParsedBody', ['key' => 'value'], 'parsedBody'],
            'files'  => ['getUploadedFiles', ['key' => new Doubles\FakeUploadedFile()], 'files']
        ];
    }

    public function testGetAttribute_ReturnsSpecifiedAttributeValue()
    {
        $request = $this->request(['attributes' => ['name' => 'value']]);
        $this->assertSame('value', $request->getAttribute('name', 'default'));
        $request = $this->request(['attributes' => ['name' => null]]);
        $this->assertSame(null, $request->getAttribute('name', 'default'));
    }

    public function testGetAttribute_ReturnsDefaultValueIfAttributeNotPresent()
    {
        $request = $this->request(['attributes' => ['unknownName' => 'value']]);
        $this->assertSame('default', $request->getAttribute('name', 'default'));
        $this->assertSame(null, $request->getAttribute('name'));
    }

    /**
     * @dataProvider mutatorMethods
     *
     * @param $method
     * @param $params
     */
    public function testMutatorMethods_ReturnNewInstance($method, $params)
    {
        $original = $this->request();
        $derived1 = $original->{$method}($params);
        $derived2 = $original->{$method}($params);
        $this->assertEquals($derived1, $derived2);
        $this->assertNotSame($derived1, $derived2);
    }

    public function mutatorMethods()
    {
        return [
            'cookie' => ['withCookieParams', ['key' => 'value']],
            'query'  => ['withQueryParams', ['key' => 'value']],
            'pBody'  => ['withParsedBody', ['key' => 'value']],
            'files'  => ['withUploadedFiles', ['key' => new Doubles\FakeUploadedFile()]]
        ];
    }

    public function testAttributeMutation_ReturnsNewInstance()
    {
        $original = $this->request();
        [$name, $value] = ['name', 'value'];
        $derived1 = $original->withAttribute($name, $value);
        $derived2 = $original->withAttribute($name, $value);
        $this->assertEquals($derived1, $derived2);
        $this->assertNotSame($derived1, $derived2);

        $original = $derived1;
        $derived1 = $original->withoutAttribute($name);
        $derived2 = $original->withoutAttribute($name);
        $this->assertEquals($derived1, $derived2);
        $this->assertNotSame($derived1, $derived2);
    }

    public function testGetParsedBodyForRequestWithoutBody_returnsNull()
    {
        $this->assertNull($this->request()->getParsedBody());
        $request = $this->request(['body' => ['key' => 'value']]);
        $this->assertNull($request->withParsedBody(null)->getParsedBody());
        $this->assertNull($request->withParsedBody([])->getParsedBody());
    }

    public function testUploadedFilesInvalidStructure_ThrowsInvalidArgumentException()
    {
        $this->expectException(InvalidArgumentException::class);
        $files = [
            'first'  => new Doubles\FakeUploadedFile(),
            'second' => 'oops im not a file'
        ];
        $this->request(['files' => $files]);
    }

    public function testResolveUnspecifiedParsedBodyIntoSuperglobalPOST()
    {
        $_POST = ['test' => 'value'];

        $fail    = 'POST x-www-form-urlencoded should resolve into $_POST superglobal';
        $request = $this->request([], 'POST', ['Content-Type' => 'application/x-www-form-urlencoded']);
        $this->assertSame($_POST, $request->getParsedBody(), $fail);

        $fail    = 'POST multipart/form-data should resolve into $_POST superglobal';
        $request = $this->request([], 'POST', ['Content-Type' => 'multipart/form-data; boundary=...etc']);
        $this->assertSame($_POST, $request->getParsedBody(), $fail);

        $fail    = 'POST method with non-form data type should remain empty';
        $request = $this->request([], 'POST', ['Content-Type' => 'other-data-type']);
        $this->assertNull($request->getParsedBody(), $fail);

        $fail    = 'GET method is not assumed form content type - should remain empty';
        $request = $this->request([], 'GET', ['Content-Type' => 'multipart/form-data; boundary=...etc']);
        $this->assertNull($request->getParsedBody(), $fail);
    }

    public function testUploadedFileNestedStructureIsValid()
    {
        $files = [
            'first' => new Doubles\FakeUploadedFile(),
            'second' => [
                'subcategory1' => new Doubles\FakeUploadedFile(),
                'subcategory2' => new Doubles\FakeUploadedFile()
            ]
        ];
        $request = $this->request(['files' => $files]);
        $this->assertSame($files, $request->getUploadedFiles());
    }

    private function request(array $params = [], $method = 'GET', $headers = [])
    {
        return new ServerRequest($method, Uri::fromString(), new Doubles\FakeStream(), $headers, $params);
    }
}
