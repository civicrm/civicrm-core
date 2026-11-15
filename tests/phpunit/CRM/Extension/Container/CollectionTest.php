<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * Class CRM_Extension_Container_CollectionTest
 * @group headless
 */
class CRM_Extension_Container_CollectionTest extends CiviUnitTestCase {

  public function testGetKeysEmpty(): void {
    $c = new CRM_Extension_Container_Collection([]);
    $this->assertEquals($c->getKeys(), []);
  }

  public function testGetKeys(): void {
    $c = $this->_createContainer();
    $this->assertEquals([
      'test.conflict',
      'test.whiz',
      'test.whizbang',
      'test.foo',
      'test.foo.bar',
    ], $c->getKeys());
  }

  public function testGetPath(): void {
    $c = $this->_createContainer();
    try {
      $c->getPath('un.kno.wn');
    }
    catch (CRM_Extension_Exception $e) {
      $exc = $e;
    }
    $this->assertTrue(is_object($exc), 'Expected exception');

    $this->assertSame("/path/to/foo", $c->getPath('test.foo'));
    $this->assertSame("/path/to/bar", $c->getPath('test.foo.bar'));
    $this->assertSame("/path/to/whiz", $c->getPath('test.whiz'));
    $this->assertSame("/path/to/whizbang", $c->getPath('test.whizbang'));
    $this->assertSame("/path/to/conflict-b", $c->getPath('test.conflict'));
  }

  public function testGetResUrl(): void {
    $c = $this->_createContainer();
    try {
      $c->getResUrl('un.kno.wn');
    }
    catch (CRM_Extension_Exception $e) {
      $exc = $e;
    }
    $this->assertTrue(is_object($exc), 'Expected exception');

    $this->assertSame('http://foo', $c->getResUrl('test.foo'));
    $this->assertSame('http://foobar', $c->getResUrl('test.foo.bar'));
    $this->assertSame('http://whiz', $c->getResUrl('test.whiz'));
    $this->assertSame('http://whizbang', $c->getResUrl('test.whizbang'));
    $this->assertSame('http://conflict-b', $c->getResUrl('test.conflict'));
  }

  public function testCaching(): void {
    $cache = new CRM_Utils_Cache_ArrayCache([]);
    $this->assertTrue(!is_array($cache->get('ext-collection')));
    $c = $this->_createContainer($cache, 'ext-collection');
    $this->assertSame('http://foo', $c->getResUrl('test.foo'));
    $this->assertTrue(is_array($cache->get('ext-collection')));

    $cacheData = $cache->get('ext-collection');
    // 'test.foo' was defined in the 'a' container
    $this->assertSame('a', $cacheData['test.foo']);
    // 'test.whiz' was defined in the 'b' container
    $this->assertSame('b', $cacheData['test.whiz']);
  }

  /**
   * @param CRM_Utils_Cache_Interface|null $cache
   * @param null $cacheKey
   *
   * @return CRM_Extension_Container_Collection
   */
  public function _createContainer(?CRM_Utils_Cache_Interface $cache = NULL, $cacheKey = NULL) {
    $containers = [];
    $containers['a'] = new CRM_Extension_Container_Static([
      'test.foo' => [
        'path' => '/path/to/foo',
        'resUrl' => 'http://foo',
      ],
      'test.foo.bar' => [
        'path' => '/path/to/bar',
        'resUrl' => 'http://foobar',
      ],
    ]);
    $containers['b'] = new CRM_Extension_Container_Static([
      'test.whiz' => [
        'path' => '/path/to/whiz',
        'resUrl' => 'http://whiz',
      ],
      'test.whizbang' => [
        'path' => '/path/to/whizbang',
        'resUrl' => 'http://whizbang',
      ],
      'test.conflict' => [
        'path' => '/path/to/conflict-b',
        'resUrl' => 'http://conflict-b',
      ],
    ]);
    $containers['c'] = new CRM_Extension_Container_Static([
      'test.conflict' => [
        'path' => '/path/to/conflict-c',
        'resUrl' => 'http://conflict-c',
      ],
    ]);
    $c = new CRM_Extension_Container_Collection($containers, $cache, $cacheKey);
    return $c;
  }

}
