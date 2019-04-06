<?php

/**
 * Class CRM_Extension_Container_StaticTest
 * @group headless
 */
class CRM_Extension_Container_StaticTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }

  public function testGetKeysEmpty() {
    $c = new CRM_Extension_Container_Static(array());
    $this->assertEquals($c->getKeys(), array());
  }

  public function testGetKeys() {
    $c = $this->_createContainer();
    $this->assertEquals($c->getKeys(), array('test.foo', 'test.foo.bar'));
  }

  public function testGetPath() {
    $c = $this->_createContainer();
    try {
      $c->getPath('un.kno.wn');
    }
    catch (CRM_Extension_Exception $e) {
      $exc = $e;
    }
    $this->assertTrue(is_object($exc), 'Expected exception');

    $this->assertEquals("/path/to/foo", $c->getPath('test.foo'));
    $this->assertEquals("/path/to/bar", $c->getPath('test.foo.bar'));
  }

  public function testGetResUrl() {
    $c = $this->_createContainer();
    try {
      $c->getResUrl('un.kno.wn');
    }
    catch (CRM_Extension_Exception $e) {
      $exc = $e;
    }
    $this->assertTrue(is_object($exc), 'Expected exception');

    $this->assertEquals('http://foo', $c->getResUrl('test.foo'));
    $this->assertEquals('http://foobar', $c->getResUrl('test.foo.bar'));
  }

  /**
   * @return CRM_Extension_Container_Static
   */
  public function _createContainer() {
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
