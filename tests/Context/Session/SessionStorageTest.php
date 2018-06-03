<?php

/*
 * This file is part of Polymorphine/Http package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Http\Tests\Context\Session;

use PHPUnit\Framework\TestCase;
use Polymorphine\Http\Context\Session;
use Polymorphine\Http\Context\SessionManager;
use Polymorphine\Http\Context\Session\SessionStorage;
use Polymorphine\Http\Tests\Doubles\FakeSessionManager;


class SessionStorageTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(SessionStorage::class, $session = $this->storage());
        $this->assertInstanceOf(Session::class, $session);
    }

    public function testGetData()
    {
        $storage = $this->storage(['foo' => 'bar']);
        $this->assertSame('bar', $storage->get('foo'));
    }

    public function testSetData()
    {
        $storage = $this->storage();
        $this->assertFalse($storage->has('foo'));
        $storage->set('foo', 'bar');
        $this->assertTrue($storage->has('foo'));
        $this->assertSame('bar', $storage->get('foo'));
    }

    public function testSetOverwritesData()
    {
        $storage = $this->storage(['foo' => 'bar']);
        $storage->set('foo', 'baz');
        $this->assertSame('baz', $storage->get('foo'));
    }

    public function testRemoveData()
    {
        $storage = $this->storage(['foo' => 'bar', 'baz' => true]);
        $storage->remove('foo');
        $this->assertNull($storage->get('foo'));
    }

    public function testClearData()
    {
        $storage = new SessionStorage($manager = new FakeSessionManager(), ['foo' => 'bar', 'baz' => true]);
        $storage->clear();
        $storage->commit();
        $this->assertSame([], $manager->data);
    }

    public function testDefaultForMissingValues()
    {
        $storage = $this->storage();
        $this->assertSame('default', $storage->get('foo', 'default'));
    }

    public function testCommitSession()
    {
        $data = [
            'foo' => 'bar',
            'bar' => 'baz'
        ];

        $storage = new SessionStorage($manager = new FakeSessionManager(), $data);

        $data['fizz'] = 'buzz';
        $storage->set('fizz', 'buzz');

        $storage->commit();
        $this->assertSame($data, $manager->data);
    }

    public function testSettingNullDoesNotRemoveData()
    {
        $storage = new SessionStorage($manager = new FakeSessionManager(), ['foo' => 500]);
        $this->assertTrue($storage->has('foo'));
        $storage->set('foo', null);
        $this->assertTrue($storage->has('foo'));
        $storage->commit();
        $this->assertTrue(array_key_exists('foo', $manager->data));
    }

    private function storage(array $data = [], SessionManager $manager = null): Session
    {
        return new SessionStorage($manager ?? new FakeSessionManager(), $data);
    }
}
