<?php

require_once 'CiviTest/CiviUnitTestCase.php';

class CRM_Extension_Container_StaticTest extends CiviUnitTestCase {
  function setUp() {
    parent::setUp();
  }

  function tearDown() {
    parent::tearDown();
  }

  function testGetKeysEmpty() {
    $c = new CRM_Extension_Container_Static(array());
    $this->assertEquals($c->getKeys(), array());
  }

  function testGetKeys() {
    $c = $this->_createContainer();
    $this->assertEquals($c->getKeys(), array('test.foo', 'test.foo.bar'));
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
  }

  function _createContainer() {
    return new CRM_Extension_Container_Static(array(
      'test.foo' => array(
        'path' => '/path/to/foo',
        'resUrl' => 'http://foo',
      ),
      'test.foo.bar' => array(
        'path' => '/path/to/bar',
        'resUrl' => 'http://foobar',
      ),
    ));
  }
}
