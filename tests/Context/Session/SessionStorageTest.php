<?php


namespace Polymorphine\Http\Tests\Context\Session;

use PHPUnit\Framework\TestCase;
use Polymorphine\Http\Context\Session\SessionManager;
use Polymorphine\Http\Context\Session\SessionStorage;
use Polymorphine\Http\Tests\Doubles\FakeSessionManager;
use Psr\SimpleCache\CacheInterface;


class SessionStorageTest extends TestCase
{
    private function storage(array $data = [], SessionManager $manager = null): CacheInterface
    {
        return new SessionStorage($manager ?? new FakeSessionManager(), $data);
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf(SessionStorage::class, $this->storage());
        $this->assertInstanceOf(CacheInterface::class, $this->storage());
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
        $storage->delete('foo');
        $this->assertNull($storage->get('foo'));
    }

    public function testClearData()
    {
        $storage = $this->storage(['foo' => 'bar', 'baz' => true], $manager = new FakeSessionManager());
        $storage->clear();
        $storage->commit();
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

        $storage = new SessionStorage($manager = new FakeSessionManager(), $data);

        $data['fizz'] = 'buzz';
        $storage->set('fizz', 'buzz');

        $storage->commit();
        $this->assertSame($data, $manager->data);
    }

    public function testSettingNullRemovesData()
    {
        $storage = new SessionStorage($manager = new FakeSessionManager(), ['foo' => 500]);
        $this->assertTrue($storage->has('foo'));
        $storage->set('foo', null);
        $this->assertFalse($storage->has('foo'));
        $storage->commit();
        $this->assertFalse(array_key_exists('foo', $manager->data));
    }
}
