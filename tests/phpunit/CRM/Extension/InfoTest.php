<?php

require_once 'CiviTest/CiviUnitTestCase.php';

class CRM_Extension_InfoTest extends CiviUnitTestCase {
  function setUp() {
    parent::setUp();
    $this->file = NULL;
  }

  function tearDown() {
    if ($this->file) {
      unlink($this->file);
    }
    parent::tearDown();
  }

  function testGood_file() {
    $this->file = tempnam(sys_get_temp_dir(), 'infoxml-');
    file_put_contents($this->file, "<extension key='test.foo' type='module'><file>foo</file><typeInfo><extra>zamboni</extra></typeInfo></extension>");

    $info = CRM_Extension_Info::loadFromFile($this->file);
    $this->assertEquals('test.foo', $info->key);
    $this->assertEquals('foo', $info->file);
    $this->assertEquals('zamboni', $info->typeInfo['extra']);
  }

  function testBad_file() {
    // <file> vs file>
    $this->file = tempnam(sys_get_temp_dir(), 'infoxml-');
    file_put_contents($this->file, "<extension key='test.foo' type='module'>file>foo</file></extension>");

    $exc = NULL;
    try {
      $info = CRM_Extension_Info::loadFromFile($this->file);
    } catch (CRM_Extension_Exception $e) {
      $exc = $e;
    }
    $this->assertTrue(is_object($exc));
  }

  function testGood_string() {
    $data = "<extension key='test.foo' type='module'><file>foo</file><typeInfo><extra>zamboni</extra></typeInfo></extension>";

    $info = CRM_Extension_Info::loadFromString($data);
    $this->assertEquals('test.foo', $info->key);
    $this->assertEquals('foo', $info->file);
    $this->assertEquals('zamboni', $info->typeInfo['extra']);
  }

  function testBad_string() {
    // <file> vs file>
    $data = "<extension key='test.foo' type='module'>file>foo</file></extension>";

    $exc = NULL;
    try {
      $info = CRM_Extension_Info::loadFromString($data);
    } catch (CRM_Extension_Exception $e) {
      $exc = $e;
    }
    $this->assertTrue(is_object($exc));
  }
}
