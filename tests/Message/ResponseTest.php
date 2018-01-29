<?php

namespace Shudd3r\Http\Tests\Message;

use PHPUnit\Framework\TestCase;
use Shudd3r\Http\Src\Message\Response;
use Psr\Http\Message\ResponseInterface;
use Shudd3r\Http\Tests\Message\Doubles\DummyStream;
use InvalidArgumentException;


class ResponseTest extends TestCase
{
    private function response($status = 200, $reason = null) {
        return new Response($status, new DummyStream(), [], ['reason' => $reason]);
    }

    public function testInstantiation() {
        $this->assertInstanceOf(ResponseInterface::class, $this->response());
    }

    public function testStatusCodeIsReturned() {
        $fail = 'Status code should be set by constructor';
        $this->assertSame(201, $this->response(201)->getStatusCode(), $fail);

        $fail = 'Status code should be modified by withStatus() method';
        $this->assertSame(300, $this->response()->withStatus(300)->getStatusCode(), $fail);
    }

    public function testNewStatusCode_ReturnsNewObject() {
        $original = $this->response(404);
        $clone = $original->withStatus(201);
        $this->assertEquals($clone, $original->withStatus(201));
        $this->assertNotSame($original, $clone);
    }

    public function testReasonPhraseResolve() {
        $fail = 'Default status code (200) should resolve into default "OK" reason phrase if not specified';
        $this->assertSame('OK', $this->response()->getReasonPhrase(), $fail);

        $fail = 'Specified reason phrase should take precedence over default one';
        $reason = 'My Own Reason';
        $this->assertSame($reason, $this->response(200, $reason)->getReasonPhrase(), $fail);

        $fail = 'Unspecified reason phrase should be an empty string for non-standard code';
        $this->assertSame('', $this->response(599)->getReasonPhrase(), $fail);

        $fail = 'Unspecified reason phrase should be resolved into default for standard code';
        $this->assertSame('Created', $this->response(201)->getReasonPhrase(), $fail);

        $fail = 'withStatus(non_standard_code) should resolve reason phrase into empty string';
        $this->assertSame('', $this->response()->withStatus(599)->getReasonPhrase(), $fail);

        $fail = 'withStatus(standard_code) should resolve reason phrase into default';
        $this->assertSame('Created', $this->response()->withStatus(201)->getReasonPhrase(), $fail);

        $fail = 'withStatus(standard_code, reason) should return specified reason';
        $reason = 'Another reason';
        $this->assertSame($reason, $this->response(201)->withStatus(201, $reason)->getReasonPhrase(), $fail);
    }

    public function testConstructorWithInvalidStatusCode_ThrowsException() {
        $this->expectException(InvalidArgumentException::class);
        $this->response(900);
    }

    /**
     * @dataProvider invalidStatusCodes
     * @param $code
     */
    public function testWithStatusWithInvalidStatusCode_ThrowsException($code) {
        $this->expectException(InvalidArgumentException::class);
        $this->response()->withStatus($code);
    }

    public function invalidStatusCodes() {
        return [
            'null' => [null],
            'false' => [false],
            'string' => ['200'],
            'below min range' => [99],
            'above max range' => [600]
        ];
    }

    /**
     * @dataProvider invalidReasonPhrases
     * @param $reason
     */
    public function testConstructorWithInvalidReasonPhrase_ThrowsException($reason) {
        $this->expectException(InvalidArgumentException::class);
        $this->response(200, $reason);
    }

    /**
     * @dataProvider invalidReasonPhrases
     * @param $reason
     */
    public function testWithStatusWithInvalidReasonPhrase_ThrowsException($reason) {
        $this->expectException(InvalidArgumentException::class);
        $this->response()->withStatus(200, $reason);
    }

    public function invalidReasonPhrases() {
        return [
            'array' => [['Reason in array']],
            'false' => [false],
            'int' => [20]
        ];
    }
}
