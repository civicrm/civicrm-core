<?php

/*
 * This file is part of php-cache organization.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\IntegrationTests;

use PHPUnit_Framework_TestCase as TestCase;
use Psr\SimpleCache\CacheInterface;

abstract class LegacySimpleCacheTest extends TestCase
{
    /**
     * @type array with functionName => reason.
     */
    protected $skippedTests = [];

    /**
     * @type CacheInterface
     */
    protected $cache;

    /**
     * @return CacheInterface that is used in the tests
     */
    abstract public function createSimpleCache();

    protected function setUp()
    {
        $this->cache = $this->createSimpleCache();
    }

    protected function tearDown()
    {
        if ($this->cache !== null) {
            $this->cache->clear();
        }
    }

    /**
     * Data provider for invalid keys.
     *
     * @return array
     */
    public static function invalidKeys()
    {
        return [
            [''],
            [true],
            [false],
            [null],
            [2],
            [2.5],
            ['{str'],
            ['rand{'],
            ['rand{str'],
            ['rand}str'],
            ['rand(str'],
            ['rand)str'],
            ['rand/str'],
            ['rand\\str'],
            ['rand@str'],
            ['rand:str'],
            [new \stdClass()],
            [['array']],
        ];
    }

    /**
     * @return array
     */
    public static function invalidTtl()
    {
        return [
            [''],
            [true],
            [false],
            ['abc'],
            [2.5],
            [' 1'], // can be casted to a int
            ['12foo'], // can be casted to a int
            ['025'], // can be interpreted as hex
            [new \stdClass()],
            [['array']],
        ];
    }

    /**
     * Data provider for valid keys.
     *
     * @return array
     */
    public static function validKeys()
    {
        return [
            ['AbC19_.'],
            ['1234567890123456789012345678901234567890123456789012345678901234'],
        ];
    }

    /**
     * Data provider for valid data to store.
     *
     * @return array
     */
    public static function validData()
    {
        return [
            ['AbC19_.'],
            [4711],
            [47.11],
            [true],
            [null],
            [['key' => 'value']],
            [new \stdClass()],
        ];
    }

    public function testSet()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $result = $this->cache->set('key', 'value');
        $this->assertTrue($result, 'set() must return true if success');
        $this->assertEquals('value', $this->cache->get('key'));
    }

    public function testSetTtl()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $result = $this->cache->set('key1', 'value', 1);
        $this->assertTrue($result, 'set() must return true if success');
        $this->assertEquals('value', $this->cache->get('key1'));
        sleep(2);
        $this->assertNull($this->cache->get('key1'), 'Value must expire after ttl.');

        $this->cache->set('key2', 'value', new \DateInterval('PT1S'));
        $this->assertEquals('value', $this->cache->get('key2'));
        sleep(2);
        $this->assertNull($this->cache->get('key2'), 'Value must expire after ttl.');
    }

    public function testSetExpiredTtl()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->set('key0', 'value');
        $this->cache->set('key0', 'value', 0);
        $this->assertNull($this->cache->get('key0'));
        $this->assertFalse($this->cache->has('key0'));

        $this->cache->set('key1', 'value', -1);
        $this->assertNull($this->cache->get('key1'));
        $this->assertFalse($this->cache->has('key1'));
    }

    public function testGet()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertNull($this->cache->get('key'));
        $this->assertEquals('foo', $this->cache->get('key', 'foo'));

        $this->cache->set('key', 'value');
        $this->assertEquals('value', $this->cache->get('key', 'foo'));
    }

    public function testDelete()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertTrue($this->cache->delete('key'), 'Deleting a value that does not exist should return true');
        $this->cache->set('key', 'value');
        $this->assertTrue($this->cache->delete('key'), 'Delete must return true on success');
        $this->assertNull($this->cache->get('key'), 'Values must be deleted on delete()');
    }

    public function testClear()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertTrue($this->cache->clear(), 'Clearing an empty cache should return true');
        $this->cache->set('key', 'value');
        $this->assertTrue($this->cache->clear(), 'Delete must return true on success');
        $this->assertNull($this->cache->get('key'), 'Values must be deleted on clear()');
    }

    public function testSetMultiple()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $result = $this->cache->setMultiple(['key0' => 'value0', 'key1' => 'value1']);
        $this->assertTrue($result, 'setMultiple() must return true if success');
        $this->assertEquals('value0', $this->cache->get('key0'));
        $this->assertEquals('value1', $this->cache->get('key1'));
    }

    public function testSetMultipleWithIntegerArrayKey()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $result = $this->cache->setMultiple(['0' => 'value0']);
        $this->assertTrue($result, 'setMultiple() must return true if success');
        $this->assertEquals('value0', $this->cache->get('0'));
    }

    public function testSetMultipleTtl()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->setMultiple(['key2' => 'value2', 'key3' => 'value3'], 1);
        $this->assertEquals('value2', $this->cache->get('key2'));
        $this->assertEquals('value3', $this->cache->get('key3'));
        sleep(2);
        $this->assertNull($this->cache->get('key2'), 'Value must expire after ttl.');
        $this->assertNull($this->cache->get('key3'), 'Value must expire after ttl.');

        $this->cache->setMultiple(['key4' => 'value4'], new \DateInterval('PT1S'));
        $this->assertEquals('value4', $this->cache->get('key4'));
        sleep(2);
        $this->assertNull($this->cache->get('key4'), 'Value must expire after ttl.');
    }

    public function testSetMultipleExpiredTtl()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->setMultiple(['key0' => 'value0', 'key1' => 'value1'], 0);
        $this->assertNull($this->cache->get('key0'));
        $this->assertNull($this->cache->get('key1'));
    }

    public function testSetMultipleWithGenerator()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $gen = function () {
            yield 'key0' => 'value0';
            yield 'key1' => 'value1';
        };

        $this->cache->setMultiple($gen());
        $this->assertEquals('value0', $this->cache->get('key0'));
        $this->assertEquals('value1', $this->cache->get('key1'));
    }

    public function testGetMultiple()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $result = $this->cache->getMultiple(['key0', 'key1']);
        $keys   = [];
        foreach ($result as $i => $r) {
            $keys[] = $i;
            $this->assertNull($r);
        }
        sort($keys);
        $this->assertSame(['key0', 'key1'], $keys);

        $this->cache->set('key3', 'value');
        $result = $this->cache->getMultiple(['key2', 'key3', 'key4'], 'foo');
        $keys   = [];
        foreach ($result as $key => $r) {
            $keys[] = $key;
            if ($key === 'key3') {
                $this->assertEquals('value', $r);
            } else {
                $this->assertEquals('foo', $r);
            }
        }
        sort($keys);
        $this->assertSame(['key2', 'key3', 'key4'], $keys);
    }

    public function testGetMultipleWithGenerator()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $gen = function () {
            yield 1 => 'key0';
            yield 1 => 'key1';
        };

        $this->cache->set('key0', 'value0');
        $result = $this->cache->getMultiple($gen());
        $keys   = [];
        foreach ($result as $key => $r) {
            $keys[] = $key;
            if ($key === 'key0') {
                $this->assertEquals('value0', $r);
            } elseif ($key === 'key1') {
                $this->assertNull($r);
            } else {
                $this->assertFalse(true, 'This should not happend');
            }
        }
        sort($keys);
        $this->assertSame(['key0', 'key1'], $keys);
        $this->assertEquals('value0', $this->cache->get('key0'));
        $this->assertNull($this->cache->get('key1'));
    }

    public function testDeleteMultiple()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertTrue($this->cache->deleteMultiple([]), 'Deleting a empty array should return true');
        $this->assertTrue($this->cache->deleteMultiple(['key']), 'Deleting a value that does not exist should return true');

        $this->cache->set('key0', 'value0');
        $this->cache->set('key1', 'value1');
        $this->assertTrue($this->cache->deleteMultiple(['key0', 'key1']), 'Delete must return true on success');
        $this->assertNull($this->cache->get('key0'), 'Values must be deleted on deleteMultiple()');
        $this->assertNull($this->cache->get('key1'), 'Values must be deleted on deleteMultiple()');
    }

    public function testDeleteMultipleGenerator()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $gen = function () {
            yield 1 => 'key0';
            yield 1 => 'key1';
        };
        $this->cache->set('key0', 'value0');
        $this->assertTrue($this->cache->deleteMultiple($gen()), 'Deleting a generator should return true');

        $this->assertNull($this->cache->get('key0'), 'Values must be deleted on deleteMultiple()');
        $this->assertNull($this->cache->get('key1'), 'Values must be deleted on deleteMultiple()');
    }

    public function testHas()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertFalse($this->cache->has('key0'));
        $this->cache->set('key0', 'value0');
        $this->assertTrue($this->cache->has('key0'));
    }

    public function testBasicUsageWithLongKey()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $key = str_repeat('a', 300);

        $this->assertFalse($this->cache->has($key));
        $this->assertTrue($this->cache->set($key, 'value'));

        $this->assertTrue($this->cache->has($key));
        $this->assertSame('value', $this->cache->get($key));

        $this->assertTrue($this->cache->delete($key));

        $this->assertFalse($this->cache->has($key));
    }

    /**
     * @expectedException \Psr\SimpleCache\InvalidArgumentException
     * @dataProvider invalidKeys
     */
    public function testGetInvalidKeys($key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->get($key);
    }

    /**
     * @expectedException \Psr\SimpleCache\InvalidArgumentException
     * @dataProvider invalidKeys
     */
    public function testGetMultipleInvalidKeys($key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $result = $this->cache->getMultiple(['key1', $key, 'key2']);
    }

    /**
     * @expectedException \Psr\SimpleCache\InvalidArgumentException
     */
    public function testGetMultipleNoIterable()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $result = $this->cache->getMultiple('key');
    }

    /**
     * @expectedException \Psr\SimpleCache\InvalidArgumentException
     * @dataProvider invalidKeys
     */
    public function testSetInvalidKeys($key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->set($key, 'foobar');
    }

    /**
     * @expectedException \Psr\SimpleCache\InvalidArgumentException
     * @dataProvider invalidKeys
     */
    public function testSetMultipleInvalidKeys($key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        if (is_int($key)) {
            $this->markTestSkipped('As keys, strings are always casted to ints so they should be accepted');
        }

        $values = function () use ($key) {
            yield 'key1' => 'foo';
            yield $key => 'bar';
            yield 'key2' => 'baz';
        };
        $this->cache->setMultiple($values());
    }

    /**
     * @expectedException \Psr\SimpleCache\InvalidArgumentException
     */
    public function testSetMultipleNoIterable()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->setMultiple('key');
    }

    /**
     * @expectedException \Psr\SimpleCache\InvalidArgumentException
     * @dataProvider invalidKeys
     */
    public function testHasInvalidKeys($key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->has($key);
    }

    /**
     * @expectedException \Psr\SimpleCache\InvalidArgumentException
     * @dataProvider invalidKeys
     */
    public function testDeleteInvalidKeys($key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->delete($key);
    }

    /**
     * @expectedException \Psr\SimpleCache\InvalidArgumentException
     * @dataProvider invalidKeys
     */
    public function testDeleteMultipleInvalidKeys($key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->deleteMultiple(['key1', $key, 'key2']);
    }

    /**
     * @expectedException \Psr\SimpleCache\InvalidArgumentException
     */
    public function testDeleteMultipleNoIterable()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->deleteMultiple('key');
    }

    /**
     * @expectedException \Psr\SimpleCache\InvalidArgumentException
     * @dataProvider invalidTtl
     */
    public function testSetInvalidTtl($ttl)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->set('key', 'value', $ttl);
    }

    /**
     * @expectedException \Psr\SimpleCache\InvalidArgumentException
     * @dataProvider invalidTtl
     */
    public function testSetMultipleInvalidTtl($ttl)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->setMultiple(['key' => 'value'], $ttl);
    }

    public function testNullOverwrite()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->set('key', 5);
        $this->cache->set('key', null);

        $this->assertNull($this->cache->get('key'), 'Setting null to a key must overwrite previous value');
    }

    public function testDataTypeString()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->set('key', '5');
        $result = $this->cache->get('key');
        $this->assertTrue('5' === $result, 'Wrong data type. If we store a string we must get an string back.');
        $this->assertTrue(is_string($result), 'Wrong data type. If we store a string we must get an string back.');
    }

    public function testDataTypeInteger()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->set('key', 5);
        $result = $this->cache->get('key');
        $this->assertTrue(5 === $result, 'Wrong data type. If we store an int we must get an int back.');
        $this->assertTrue(is_int($result), 'Wrong data type. If we store an int we must get an int back.');
    }

    public function testDataTypeFloat()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $float = 1.23456789;
        $this->cache->set('key', $float);
        $result = $this->cache->get('key');
        $this->assertTrue(is_float($result), 'Wrong data type. If we store float we must get an float back.');
        $this->assertEquals($float, $result);
    }

    public function testDataTypeBoolean()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->set('key', false);
        $result = $this->cache->get('key');
        $this->assertTrue(is_bool($result), 'Wrong data type. If we store boolean we must get an boolean back.');
        $this->assertFalse($result);
        $this->assertTrue($this->cache->has('key'), 'has() should return true when true are stored. ');
    }

    public function testDataTypeArray()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $array = ['a' => 'foo', 2 => 'bar'];
        $this->cache->set('key', $array);
        $result = $this->cache->get('key');
        $this->assertTrue(is_array($result), 'Wrong data type. If we store array we must get an array back.');
        $this->assertEquals($array, $result);
    }

    public function testDataTypeObject()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $object    = new \stdClass();
        $object->a = 'foo';
        $this->cache->set('key', $object);
        $result = $this->cache->get('key');
        $this->assertTrue(is_object($result), 'Wrong data type. If we store object we must get an object back.');
        $this->assertEquals($object, $result);
    }

    public function testBinaryData()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $data = '';
        for ($i = 0; $i < 256; $i++) {
            $data .= chr($i);
        }

        $array = ['a' => 'foo', 2 => 'bar'];
        $this->cache->set('key', $data);
        $result = $this->cache->get('key');
        $this->assertTrue($data === $result, 'Binary data must survive a round trip.');
    }

    /**
     * @dataProvider validKeys
     */
    public function testSetValidKeys($key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->set($key, 'foobar');
        $this->assertEquals('foobar', $this->cache->get($key));
    }

    /**
     * @dataProvider validKeys
     */
    public function testSetMultipleValidKeys($key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->setMultiple([$key => 'foobar']);
        $result = $this->cache->getMultiple([$key]);
        $keys   = [];
        foreach ($result as $i => $r) {
            $keys[] = $i;
            $this->assertEquals($key, $i);
            $this->assertEquals('foobar', $r);
        }
        $this->assertSame([$key], $keys);
    }

    /**
     * @dataProvider validData
     */
    public function testSetValidData($data)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->set('key', $data);
        $this->assertEquals($data, $this->cache->get('key'));
    }

    /**
     * @dataProvider validData
     */
    public function testSetMultipleValidData($data)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->setMultiple(['key' => $data]);
        $result = $this->cache->getMultiple(['key']);
        $keys   = [];
        foreach ($result as $i => $r) {
            $keys[] = $i;
            $this->assertEquals($data, $r);
        }
        $this->assertSame(['key'], $keys);
    }

    public function testObjectAsDefaultValue()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $obj      = new \stdClass();
        $obj->foo = 'value';
        $this->assertEquals($obj, $this->cache->get('key', $obj));
    }

    public function testObjectDoesNotChangeInCache()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $obj      = new \stdClass();
        $obj->foo = 'value';
        $this->cache->set('key', $obj);
        $obj->foo = 'changed';

        $cacheObject = $this->cache->get('key');
        $this->assertEquals('value', $cacheObject->foo, 'Object in cache should not have their values changed.');
    }
}
