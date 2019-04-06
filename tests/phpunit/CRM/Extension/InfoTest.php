<?php

/**
 * Class CRM_Extension_InfoTest
 * @group headless
 */
class CRM_Extension_InfoTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
    $this->file = NULL;
  }

  public function tearDown() {
    if ($this->file) {
      unlink($this->file);
    }
    parent::tearDown();
  }

  public function testGood_file() {
    $this->file = tempnam(sys_get_temp_dir(), 'infoxml-');
    file_put_contents($this->file, "<extension key='test.foo' type='module'><file>foo</file><typeInfo><extra>zamboni</extra></typeInfo></extension>");

    $info = CRM_Extension_Info::loadFromFile($this->file);
    $this->assertEquals('test.foo', $info->key);
    $this->assertEquals('foo', $info->file);
    $this->assertEquals('zamboni', $info->typeInfo['extra']);
  }

  public function testBad_file() {
    // <file> vs file>
    $this->file = tempnam(sys_get_temp_dir(), 'infoxml-');
    file_put_contents($this->file, "<extension key='test.foo' type='module'>file>foo</file></extension>");

    $exc = NULL;
    try {
      $info = CRM_Extension_Info::loadFromFile($this->file);
    }
    catch (CRM_Extension_Exception $e) {
      $exc = $e;
    }
    $this->assertTrue(is_object($exc));
  }

  public function testGood_string() {
    $data = "<extension key='test.foo' type='module'><file>foo</file><typeInfo><extra>zamboni</extra></typeInfo></extension>";

    $info = CRM_Extension_Info::loadFromString($data);
    $this->assertEquals('test.foo', $info->key);
    $this->assertEquals('foo', $info->file);
    $this->assertEquals('zamboni', $info->typeInfo['extra']);
    $this->assertEquals(array(), $info->requires);
  }

  public function testGood_string_extras() {
    $data = "<extension key='test.bar' type='module'><file>testbar</file>
      <classloader><psr4 prefix=\"Civi\\\" path=\"Civi\"/></classloader>
      <requires><ext>org.civicrm.a</ext><ext>org.civicrm.b</ext></requires>
    </extension>
    ";

    $info = CRM_Extension_Info::loadFromString($data);
    $this->assertEquals('test.bar', $info->key);
    $this->assertEquals('testbar', $info->file);
    $this->assertEquals('Civi\\', $info->classloader[0]['prefix']);
    $this->assertEquals('Civi', $info->classloader[0]['path']);
    $this->assertEquals(array('org.civicrm.a', 'org.civicrm.b'), $info->requires);
  }

  public function testBad_string() {
    // <file> vs file>
    $data = "<extension key='test.foo' type='module'>file>foo</file></extension>";

    $exc = NULL;
    try {
      $info = CRM_Extension_Info::loadFromString($data);
    }
    catch (CRM_Extension_Exception $e) {
      $exc = $e;
    }
    $this->assertTrue(is_object($exc));
  }

  public function test_requirements() {
    // Quicksearch requirement should get filtered out per extension-compatibility.json
    $data = "<extension key='test.foo' type='module'><file>foo</file><requires><ext>example.test</ext><ext>com.ixiam.modules.quicksearch</ext></requires></extension>";

    $info = CRM_Extension_Info::loadFromString($data);
    $this->assertEquals(['example.test'], $info->requires);
  }

}
