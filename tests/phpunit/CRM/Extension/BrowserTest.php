<?php

/**
 * Class CRM_Extension_BrowserTest
 * @group headless
 */
class CRM_Extension_BrowserTest extends CiviUnitTestCase {

  use \Civi\Test\GuzzleTestTrait;

  /**
   * @var CRM_Extension_Browser
   */
  protected $browser;

  /**
   * Get the expected response from browser extension.
   *
   * @return array
   */
  protected function getExpectedResponse() {
    return [
      '{"test.crm.extension.browsertest.a":"<extension key=\'test.crm.extension.browsertest.a\' type=\'report\'>\n  <file>main<\/file>\n  <name>test_crm_extension_browsertest_a<\/name>\n  <description>Brought to you by the letter \"A\"<\/description>\n  <version>0.1<\/version>\n  <downloadUrl>http:\/\/example.com\/test.crm.extension.browsertest.a-0.1.zip<\/downloadUrl>\n  <typeInfo>\n    <reportUrl>test\/extension\/browsertest\/a<\/reportUrl>\n    <component>CiviContribute<\/component>\n  <\/typeInfo>\n<\/extension>\n","test.crm.extension.browsertest.b":"<extension key=\'test.crm.extension.browsertest.b\' type=\'module\'>\n  <file>moduletest<\/file>\n  <name>test_crm_extension_browsertest_b<\/name>\n  <version>1.2<\/version>\n  <downloadUrl>http:\/\/example.com\/test.crm.extension.browsertest.b-1.2.zip<\/downloadUrl>\n  <description>Brought to you by the letter \"B\"<\/description>\n<\/extension>\n"}',
    ];
  }

  /**
   * Add a mock handler to the extension browser for testing.
   *
   */
  protected function setupMockHandler() {
    $responses = $this->getExpectedResponse();
    $this->createMockHandler($responses);
    $this->setUpClientWithHistoryContainer();
    $this->browser->setGuzzleClient($this->getGuzzleClient());
  }

  public function testDisabled() {
    $this->browser = new CRM_Extension_Browser(FALSE, '/index.html', 'file:///itd/oesn/tmat/ter');
    $this->assertEquals(FALSE, $this->browser->isEnabled());
    $this->assertEquals([], $this->browser->checkRequirements());
    $this->assertEquals([], $this->browser->getExtensions());
  }

  public function testCheckRequirements_BadCachedir_false() {
    $this->browser = new CRM_Extension_Browser('file://' . dirname(__FILE__) . '/dataset/good-repository', NULL, FALSE);
    $this->assertEquals(TRUE, $this->browser->isEnabled());
    $reqs = $this->browser->checkRequirements();
    $this->assertEquals(1, count($reqs));
  }

  public function testCheckRequirements_BadCachedir_nonexistent() {
    $this->browser = new CRM_Extension_Browser('file://' . dirname(__FILE__) . '/dataset/good-repository', NULL, '/tot/all/yin/v/alid');
    $this->assertEquals(TRUE, $this->browser->isEnabled());
    $reqs = $this->browser->checkRequirements();
    $this->assertEquals(1, count($reqs));
  }

  public function testGetExtensions_good() {
    $this->browser = new CRM_Extension_Browser('file://' . dirname(__FILE__) . '/dataset/good-repository', NULL, $this->createTempDir('ext-cache-'));
    $this->assertEquals(TRUE, $this->browser->isEnabled());
    $this->assertEquals([], $this->browser->checkRequirements());
    $this->setupMockHandler();
    $exts = $this->browser->getExtensions();
    $keys = array_keys($exts);
    sort($keys);
    $this->assertEquals(['test.crm.extension.browsertest.a', 'test.crm.extension.browsertest.b'], $keys);
    $this->assertEquals('report', $exts['test.crm.extension.browsertest.a']->type);
    $this->assertEquals('module', $exts['test.crm.extension.browsertest.b']->type);
    $this->assertEquals('http://example.com/test.crm.extension.browsertest.a-0.1.zip', $exts['test.crm.extension.browsertest.a']->downloadUrl);
    $this->assertEquals('http://example.com/test.crm.extension.browsertest.b-1.2.zip', $exts['test.crm.extension.browsertest.b']->downloadUrl);
  }

  public function testGetExtension_good() {
    $this->browser = new CRM_Extension_Browser('file://' . dirname(__FILE__) . '/dataset/good-repository', NULL, $this->createTempDir('ext-cache-'));
    $this->assertEquals(TRUE, $this->browser->isEnabled());
    $this->assertEquals([], $this->browser->checkRequirements());
    $this->setupMockHandler();
    $info = $this->browser->getExtension('test.crm.extension.browsertest.b');
    $this->assertEquals('module', $info->type);
    $this->assertEquals('http://example.com/test.crm.extension.browsertest.b-1.2.zip', $info->downloadUrl);
  }

  public function testGetExtension_nonexistent() {
    $this->browser = new CRM_Extension_Browser('file://' . dirname(__FILE__) . '/dataset/good-repository', NULL, $this->createTempDir('ext-cache-'));
    $this->assertEquals(TRUE, $this->browser->isEnabled());
    $this->assertEquals([], $this->browser->checkRequirements());
    $this->setupMockHandler();
    $info = $this->browser->getExtension('test.crm.extension.browsertest.nonexistent');
    $this->assertEquals(NULL, $info);
  }

}
