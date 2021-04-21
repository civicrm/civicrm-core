<?php

/**
 * Class CRM_Extension_InfoTest
 * @group headless
 */
class CRM_Extension_InfoTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    $this->file = NULL;
  }

  public function tearDown(): void {
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
    $this->assertEquals(NULL, $info->upgrader);
    $this->assertEquals([], $info->requires);
  }

  public function testGood_string_extras() {
    $data = "<extension key='test.bar' type='module'><file>testbar</file>
      <classloader>
        <psr4 prefix=\"Civi\\\" path=\"Civi\"/>
        <psr0 prefix=\"CRM_\" path=\"\"/>
      </classloader>
      <upgrader>CRM_Foo_Upgrader</upgrader>
      <requires><ext>org.civicrm.a</ext><ext>org.civicrm.b</ext></requires>
    </extension>
    ";

    $info = CRM_Extension_Info::loadFromString($data);
    $this->assertEquals('test.bar', $info->key);
    $this->assertEquals('testbar', $info->file);
    $this->assertEquals('Civi\\', $info->classloader[0]['prefix']);
    $this->assertEquals('Civi', $info->classloader[0]['path']);
    $this->assertEquals('psr4', $info->classloader[0]['type']);
    $this->assertEquals('CRM_', $info->classloader[1]['prefix']);
    $this->assertEquals('', $info->classloader[1]['path']);
    $this->assertEquals('psr0', $info->classloader[1]['type']);
    $this->assertEquals('CRM_Foo_Upgrader', $info->upgrader);
    $this->assertEquals(['org.civicrm.a', 'org.civicrm.b'], $info->requires);
  }

  public function getExampleAuthors() {
    $authorAliceXml = '<author><name>Alice</name><email>alice@example.org</email><role>Maintainer</role></author>';
    $authorAliceArr = ['name' => 'Alice', 'email' => 'alice@example.org', 'role' => 'Maintainer'];
    $authorBobXml = ' <author><name>Bob</name><homepage>https://example.com/bob</homepage><role>Developer</role></author>';
    $authorBobArr = ['name' => 'Bob', 'homepage' => 'https://example.com/bob', 'role' => 'Developer'];

    $maintAliceXml = '<maintainer><author>Alice</author><email>alice@example.org</email></maintainer>';
    $maintAliceArr = ['author' => 'Alice', 'email' => 'alice@example.org'];

    $hdr = "<extension key='test.author' type='module'><file>testauthor</file>";
    $ftr = "</extension>";

    // Maintainers can be inputted via either <maintainer> or <authors> (with role).
    // Maintainers are outputted via both `$info->maintainer` and `$info->authors` (with role)

    $cases = [];
    $cases[] = ["{$hdr}{$maintAliceXml}{$ftr}", [$authorAliceArr], $maintAliceArr];
    $cases[] = ["{$hdr}<authors>{$authorAliceXml}</authors>{$ftr}", [$authorAliceArr], $maintAliceArr];
    $cases[] = ["{$hdr}<authors>{$authorAliceXml}{$authorBobXml}</authors>{$ftr}", [$authorAliceArr, $authorBobArr], $maintAliceArr];
    $cases[] = ["{$hdr}<authors>{$authorBobXml}</authors>{$ftr}", [$authorBobArr], NULL];
    return $cases;
  }

  /**
   * @dataProvider getExampleAuthors
   */
  public function testAuthors($xmlString, $expectAuthors, $expectMaintainer) {
    $info = CRM_Extension_Info::loadFromString($xmlString);
    $this->assertEquals($expectAuthors, $info->authors);
    $this->assertEquals($expectMaintainer, $info->maintainer);
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
