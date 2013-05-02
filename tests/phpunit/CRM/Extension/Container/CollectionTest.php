<?php

require_once 'CiviTest/CiviUnitTestCase.php';

class CRM_Extension_Container_CollectionTest extends CiviUnitTestCase {
  function setUp() {
    parent::setUp();
  }

  function tearDown() {
    parent::tearDown();
  }

  function testGetKeysEmpty() {
    $c = new CRM_Extension_Container_Collection(array());
    $this->assertEquals($c->getKeys(), array());
  }

  function testGetKeys() {
    $c = $this->_createContainer();
    $this->assertEquals(array('test.conflict', 'test.whiz', 'test.whizbang', 'test.foo', 'test.foo.bar'), $c->getKeys()); 
  }

  function testGetPath() {
    $c = $this->_createContainer();
    try {
      $c->getPath('un.kno.wn');
    } catch (CRM_Extension_Exception $e) {
      $exc = $e;
    }
    $this->assertTrue(is_object($exc), 'Expected exception');

    $this->assertEquals("/path/to/foo", $c->getPath('test.foo'));
    $this->assertEquals("/path/to/bar", $c->getPath('test.foo.bar'));
    $this->assertEquals("/path/to/whiz", $c->getPath('test.whiz'));
    $this->assertEquals("/path/to/whizbang", $c->getPath('test.whizbang'));
    $this->assertEquals("/path/to/conflict-b", $c->getPath('test.conflict'));
  }

  function testGetResUrl() {
    $c = $this->_createContainer();
    try {
      $c->getResUrl('un.kno.wn');
    } catch (CRM_Extension_Exception $e) {
      $exc = $e;
    }
    $this->assertTrue(is_object($exc), 'Expected exception');

    $this->assertEquals('http://foo', $c->getResUrl('test.foo'));
    $this->assertEquals('http://foobar', $c->getResUrl('test.foo.bar'));
    $this->assertEquals('http://whiz', $c->getResUrl('test.whiz'));
    $this->assertEquals('http://whizbang', $c->getResUrl('test.whizbang'));
    $this->assertEquals('http://conflict-b', $c->getResUrl('test.conflict'));
  }

  function testCaching() {
    $cache = new CRM_Utils_Cache_Arraycache(array());
    $this->assertTrue(!is_array($cache->get('ext-collection')));
    $c = $this->_createContainer($cache, 'ext-collection');
    $this->assertEquals('http://foo', $c->getResUrl('test.foo'));
    $this->assertTrue(is_array($cache->get('ext-collection')));

    $cacheData = $cache->get('ext-collection');
    $this->assertEquals('a', $cacheData['test.foo']); // 'test.foo' was defined in the 'a' container
    $this->assertEquals('b', $cacheData['test.whiz']); // 'test.whiz' was defined in the 'b' container
  }

  function _createContainer(CRM_Utils_Cache_Interface $cache = NULL, $cacheKey = NULL) {
    $containers = array();
    $containers['a'] = new CRM_Extension_Container_Static(array(
      'test.foo' => array(
        'path' => '/path/to/foo',
        'resUrl' => 'http://foo',
      ),
      'test.foo.bar' => array(
        'path' => '/path/to/bar',
        'resUrl' => 'http://foobar',
      ),
    ));
    $containers['b'] = new CRM_Extension_Container_Static(array(
      'test.whiz' => array(
        'path' => '/path/to/whiz',
        'resUrl' => 'http://whiz',
      ),
      'test.whizbang' => array(
        'path' => '/path/to/whizbang',
        'resUrl' => 'http://whizbang',
      ),
      'test.conflict' => array(
        'path' => '/path/to/conflict-b',
        'resUrl' => 'http://conflict-b',
      ),
    ));
    $containers['c'] = new CRM_Extension_Container_Static(array(
      'test.conflict' => array(
        'path' => '/path/to/conflict-c',
        'resUrl' => 'http://conflict-c',
      ),
    ));
    $c  = new CRM_Extension_Container_Collection($containers, $cache, $cacheKey);
    return $c;
  }
}
