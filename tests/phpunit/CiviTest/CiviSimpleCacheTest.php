<?php

/**
 * This file has been pulled out of cache/integration-tests
 * ********************************************************
 * This file is part of php-cache organization.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is originally available under the MIT license, which is
 * compatible for bundling into AGPL. Terms originally delivered in
 * `cache/integration-tests@0.17.0:LICENSE` and now reproduced below:
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

use PHPUnit\Framework\TestCase;

abstract class CiviSimpleCacheTest extends TestCase {
  /**
   * @var array
   * with functionName => reason.
   */
  protected $skippedTests = [];

  /**
   * @var CiviSimpleCacheTest
   */
  protected $cache;

  /**
   * @return CiviSimpleCacheTest that is used in the tests
   */
  abstract public function createSimpleCache();

  /**
   * Advance time perceived by the cache for the purposes of testing TTL.
   *
   * The default implementation sleeps for the specified duration,
   * but subclasses are encouraged to override this,
   * adjusting a mocked time possibly set up in {@link createSimpleCache()},
   * to speed up the tests.
   *
   * @param int $seconds
   */
  public function advanceTime($seconds) {
    sleep($seconds);
  }

  /**
   * @before
   */
  public function setupService() {
    $this->cache = $this->createSimpleCache();
  }

  /**
   * @after
   */
  public function tearDownService() {
    if ($this->cache !== NULL) {
      $this->cache->clear();
    }
  }

  /**
   * Data provider for invalid cache keys.
   *
   * @return array
   */
  public static function invalidKeys() {
    return array_merge(
      self::invalidArrayKeys(),
      [
        [2],
      ]
    );
  }

  /**
   * Data provider for invalid array keys.
   *
   * @return array
   */
  public static function invalidArrayKeys() {
    return [
      [''],
      [TRUE],
      [FALSE],
      [NULL],
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
  public static function invalidTtl() {
    return [
      [''],
      [TRUE],
      [FALSE],
      ['abc'],
      [2.5],
      // can be casted to a int
      [' 1'],
      // can be casted to a int
      ['12foo'],
      // can be interpreted as hex
      ['025'],
      [new \stdClass()],
      [['array']],
    ];
  }

  /**
   * Data provider for valid keys.
   *
   * @return array
   */
  public static function validKeys() {
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
  public static function validData() {
    return [
      ['AbC19_.'],
      [4711],
      [47.11],
      [TRUE],
      [NULL],
      [['key' => 'value']],
      [new \stdClass()],
    ];
  }

  public function testSet() {
    if (isset($this->skippedTests[__FUNCTION__])) {
      $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
    }

    $result = $this->cache->set('key', 'value');
    $this->assertTrue($result, 'set() must return true if success');
    $this->assertEquals('value', $this->cache->get('key'));
  }

  /**
   * @medium
   */
  public function testSetTtl() {
    if (isset($this->skippedTests[__FUNCTION__])) {
      $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
    }

    $result = $this->cache->set('key1', 'value', 2);
    $this->assertTrue($result, 'set() must return true if success');
    $this->assertEquals('value', $this->cache->get('key1'));

    $this->cache->set('key2', 'value', new \DateInterval('PT2S'));
    $this->assertEquals('value', $this->cache->get('key2'));

    $this->advanceTime(3);

    $this->assertNull($this->cache->get('key1'), 'Value must expire after ttl.');
    $this->assertNull($this->cache->get('key2'), 'Value must expire after ttl.');
  }

  public function testSetExpiredTtl() {
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

  public function testGet() {
    if (isset($this->skippedTests[__FUNCTION__])) {
      $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
    }

    $this->assertNull($this->cache->get('key'));
    $this->assertEquals('foo', $this->cache->get('key', 'foo'));

    $this->cache->set('key', 'value');
    $this->assertEquals('value', $this->cache->get('key', 'foo'));
  }

  public function testDelete() {
    if (isset($this->skippedTests[__FUNCTION__])) {
      $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
    }

    $this->assertTrue($this->cache->delete('key'), 'Deleting a value that does not exist should return true');
    $this->cache->set('key', 'value');
    $this->assertTrue($this->cache->delete('key'), 'Delete must return true on success');
    $this->assertNull($this->cache->get('key'), 'Values must be deleted on delete()');
  }

  public function testClear() {
    if (isset($this->skippedTests[__FUNCTION__])) {
      $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
    }

    $this->assertTrue($this->cache->clear(), 'Clearing an empty cache should return true');
    $this->cache->set('key', 'value');
    $this->assertTrue($this->cache->clear(), 'Delete must return true on success');
    $this->assertNull($this->cache->get('key'), 'Values must be deleted on clear()');
  }

  public function testSetMultiple() {
    if (isset($this->skippedTests[__FUNCTION__])) {
      $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
    }

    $result = $this->cache->setMultiple(['key0' => 'value0', 'key1' => 'value1']);
    $this->assertTrue($result, 'setMultiple() must return true if success');
    $this->assertEquals('value0', $this->cache->get('key0'));
    $this->assertEquals('value1', $this->cache->get('key1'));
  }

  public function testSetMultipleWithIntegerArrayKey() {
    if (isset($this->skippedTests[__FUNCTION__])) {
      $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
    }

    $result = $this->cache->setMultiple(['0' => 'value0']);
    $this->assertTrue($result, 'setMultiple() must return true if success');
    $this->assertEquals('value0', $this->cache->get('0'));
  }

  /**
   * @medium
   */
  public function testSetMultipleTtl() {
    if (isset($this->skippedTests[__FUNCTION__])) {
      $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
    }

    $this->cache->setMultiple(['key2' => 'value2', 'key3' => 'value3'], 2);
    $this->assertEquals('value2', $this->cache->get('key2'));
    $this->assertEquals('value3', $this->cache->get('key3'));

    $this->cache->setMultiple(['key4' => 'value4'], new \DateInterval('PT2S'));
    $this->assertEquals('value4', $this->cache->get('key4'));

    $this->advanceTime(3);
    $this->assertNull($this->cache->get('key2'), 'Value must expire after ttl.');
    $this->assertNull($this->cache->get('key3'), 'Value must expire after ttl.');
    $this->assertNull($this->cache->get('key4'), 'Value must expire after ttl.');
  }

  public function testSetMultipleExpiredTtl() {
    if (isset($this->skippedTests[__FUNCTION__])) {
      $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
    }

    $this->cache->setMultiple(['key0' => 'value0', 'key1' => 'value1'], 0);
    $this->assertNull($this->cache->get('key0'));
    $this->assertNull($this->cache->get('key1'));
  }

  public function testSetMultipleWithGenerator() {
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

  public function testGetMultiple() {
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
      }
      else {
        $this->assertEquals('foo', $r);
      }
    }
    sort($keys);
    $this->assertSame(['key2', 'key3', 'key4'], $keys);
  }

  public function testGetMultipleWithGenerator() {
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
      }
      elseif ($key === 'key1') {
        $this->assertNull($r);
      }
      else {
        $this->assertFalse(TRUE, 'This should not happend');
      }
    }
    sort($keys);
    $this->assertSame(['key0', 'key1'], $keys);
    $this->assertEquals('value0', $this->cache->get('key0'));
    $this->assertNull($this->cache->get('key1'));
  }

  public function testDeleteMultiple() {
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

  public function testDeleteMultipleGenerator() {
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

  public function testHas() {
    if (isset($this->skippedTests[__FUNCTION__])) {
      $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
    }

    $this->assertFalse($this->cache->has('key0'));
    $this->cache->set('key0', 'value0');
    $this->assertTrue($this->cache->has('key0'));
  }

  public function testBasicUsageWithLongKey() {
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
   * @dataProvider invalidKeys
   */
  public function testGetInvalidKeys($key) {
    if (isset($this->skippedTests[__FUNCTION__])) {
      $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
    }

    $this->expectException('Psr\SimpleCache\InvalidArgumentException');
    $this->cache->get($key);
  }

  /**
   * @dataProvider invalidKeys
   */
  public function testGetMultipleInvalidKeys($key) {
    if (isset($this->skippedTests[__FUNCTION__])) {
      $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
    }

    $this->expectException('Psr\SimpleCache\InvalidArgumentException');
    $result = $this->cache->getMultiple(['key1', $key, 'key2']);
  }

  public function testGetMultipleNoIterable() {
    if (isset($this->skippedTests[__FUNCTION__])) {
      $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
    }

    $this->expectException('Psr\SimpleCache\InvalidArgumentException');
    $result = $this->cache->getMultiple('key');
  }

  /**
   * @dataProvider invalidKeys
   */
  public function testSetInvalidKeys($key) {
    if (isset($this->skippedTests[__FUNCTION__])) {
      $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
    }

    $this->expectException('Psr\SimpleCache\InvalidArgumentException');
    $this->cache->set($key, 'foobar');
  }

  /**
   * @dataProvider invalidArrayKeys
   */
  public function testSetMultipleInvalidKeys($key) {
    if (isset($this->skippedTests[__FUNCTION__])) {
      $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
    }

    $values = function () use ($key) {
      yield 'key1' => 'foo';
      yield $key => 'bar';
      yield 'key2' => 'baz';
    };
    $this->expectException('Psr\SimpleCache\InvalidArgumentException');
    $this->cache->setMultiple($values());
  }

  public function testSetMultipleNoIterable() {
    if (isset($this->skippedTests[__FUNCTION__])) {
      $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
    }

    $this->expectException('Psr\SimpleCache\InvalidArgumentException');
    $this->cache->setMultiple('key');
  }

  /**
   * @dataProvider invalidKeys
   */
  public function testHasInvalidKeys($key) {
    if (isset($this->skippedTests[__FUNCTION__])) {
      $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
    }

    $this->expectException('Psr\SimpleCache\InvalidArgumentException');
    $this->cache->has($key);
  }

  /**
   * @dataProvider invalidKeys
   */
  public function testDeleteInvalidKeys($key) {
    if (isset($this->skippedTests[__FUNCTION__])) {
      $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
    }

    $this->expectException('Psr\SimpleCache\InvalidArgumentException');
    $this->cache->delete($key);
  }

  /**
   * @dataProvider invalidKeys
   */
  public function testDeleteMultipleInvalidKeys($key) {
    if (isset($this->skippedTests[__FUNCTION__])) {
      $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
    }

    $this->expectException('Psr\SimpleCache\InvalidArgumentException');
    $this->cache->deleteMultiple(['key1', $key, 'key2']);
  }

  public function testDeleteMultipleNoIterable() {
    if (isset($this->skippedTests[__FUNCTION__])) {
      $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
    }

    $this->expectException('Psr\SimpleCache\InvalidArgumentException');
    $this->cache->deleteMultiple('key');
  }

  /**
   * @dataProvider invalidTtl
   */
  public function testSetInvalidTtl($ttl) {
    if (isset($this->skippedTests[__FUNCTION__])) {
      $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
    }

    $this->expectException('Psr\SimpleCache\InvalidArgumentException');
    $this->cache->set('key', 'value', $ttl);
  }

  /**
   * @dataProvider invalidTtl
   */
  public function testSetMultipleInvalidTtl($ttl) {
    if (isset($this->skippedTests[__FUNCTION__])) {
      $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
    }

    $this->expectException('Psr\SimpleCache\InvalidArgumentException');
    $this->cache->setMultiple(['key' => 'value'], $ttl);
  }

  public function testNullOverwrite() {
    if (isset($this->skippedTests[__FUNCTION__])) {
      $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
    }

    $this->cache->set('key', 5);
    $this->cache->set('key', NULL);

    $this->assertNull($this->cache->get('key'), 'Setting null to a key must overwrite previous value');
  }

  public function testDataTypeString() {
    if (isset($this->skippedTests[__FUNCTION__])) {
      $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
    }

    $this->cache->set('key', '5');
    $result = $this->cache->get('key');
    $this->assertTrue('5' === $result, 'Wrong data type. If we store a string we must get an string back.');
    $this->assertTrue(is_string($result), 'Wrong data type. If we store a string we must get an string back.');
  }

  public function testDataTypeInteger() {
    if (isset($this->skippedTests[__FUNCTION__])) {
      $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
    }

    $this->cache->set('key', 5);
    $result = $this->cache->get('key');
    $this->assertTrue(5 === $result, 'Wrong data type. If we store an int we must get an int back.');
    $this->assertTrue(is_int($result), 'Wrong data type. If we store an int we must get an int back.');
  }

  public function testDataTypeFloat() {
    if (isset($this->skippedTests[__FUNCTION__])) {
      $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
    }

    $float = 1.23456789;
    $this->cache->set('key', $float);
    $result = $this->cache->get('key');
    $this->assertTrue(is_float($result), 'Wrong data type. If we store float we must get an float back.');
    $this->assertEquals($float, $result);
  }

  public function testDataTypeBoolean() {
    if (isset($this->skippedTests[__FUNCTION__])) {
      $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
    }

    $this->cache->set('key', FALSE);
    $result = $this->cache->get('key');
    $this->assertTrue(is_bool($result), 'Wrong data type. If we store boolean we must get an boolean back.');
    $this->assertFalse($result);
    $this->assertTrue($this->cache->has('key'), 'has() should return true when true are stored. ');
  }

  public function testDataTypeArray() {
    if (isset($this->skippedTests[__FUNCTION__])) {
      $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
    }

    $array = ['a' => 'foo', 2 => 'bar'];
    $this->cache->set('key', $array);
    $result = $this->cache->get('key');
    $this->assertTrue(is_array($result), 'Wrong data type. If we store array we must get an array back.');
    $this->assertEquals($array, $result);
  }

  public function testDataTypeObject() {
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

  public function testBinaryData() {
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
  public function testSetValidKeys($key) {
    if (isset($this->skippedTests[__FUNCTION__])) {
      $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
    }

    $this->cache->set($key, 'foobar');
    $this->assertEquals('foobar', $this->cache->get($key));
  }

  /**
   * @dataProvider validKeys
   */
  public function testSetMultipleValidKeys($key) {
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
  public function testSetValidData($data) {
    if (isset($this->skippedTests[__FUNCTION__])) {
      $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
    }

    $this->cache->set('key', $data);
    $this->assertEquals($data, $this->cache->get('key'));
  }

  /**
   * @dataProvider validData
   */
  public function testSetMultipleValidData($data) {
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

  public function testObjectAsDefaultValue() {
    if (isset($this->skippedTests[__FUNCTION__])) {
      $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
    }

    $obj      = new \stdClass();
    $obj->foo = 'value';
    $this->assertEquals($obj, $this->cache->get('key', $obj));
  }

  public function testObjectDoesNotChangeInCache() {
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
