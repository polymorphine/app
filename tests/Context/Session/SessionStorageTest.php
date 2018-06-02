<?php


namespace Polymorphine\Http\Tests\Context\Session;

use Polymorphine\Http\Context\Session\SessionStorage;
use PHPUnit\Framework\TestCase;
use Polymorphine\Http\Tests\Doubles\FakeSessionManager;


class SessionStorageTest extends TestCase
{
    private function storage(array $data = [])
    {
        return new SessionStorage($data);
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf(SessionStorage::class, $this->storage());
    }

    public function testGetData()
    {
        $storage = $this->storage(['foo' => 'bar']);
        $this->assertSame('bar', $storage->get('foo'));
    }

    public function testSetData()
    {
        $storage = $this->storage();
        $this->assertFalse($storage->exists('foo'));
        $storage->set('foo', 'bar');
        $this->assertTrue($storage->exists('foo'));
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
        $storage = $this->storage(['foo' => 'bar', 'baz' => true]);
        $storage->clear();
        $storage->commit($manager = new FakeSessionManager());
        $this->assertSame([], $manager->data);
    }

    public function testDefaultForMissingValues()
    {
        $storage = $this->storage();
        $this->assertSame('default', $storage->get('foo', 'default'));
    }

    public function testGetAllData()
    {
        $data = [
            'foo' => 'bar',
            'bar' => 'baz'
        ];

        $storage = $this->storage($data);

        $data['fizz'] = 'buzz';
        $storage->set('fizz', 'buzz');

        $storage->commit($manager = new FakeSessionManager());
        $this->assertSame($data, $manager->data);
    }

    public function testSettingNullRemovesData()
    {
        $storage = $this->storage(['foo' => 500]);
        $this->assertTrue($storage->exists('foo'));
        $storage->set('foo', null);
        $this->assertFalse($storage->exists('foo'));
        $storage->commit($manager = new FakeSessionManager());
        $this->assertFalse(array_key_exists('foo', $manager->data));
    }
}
