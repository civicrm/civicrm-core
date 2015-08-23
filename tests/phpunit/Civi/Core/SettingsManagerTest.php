<?php
namespace Civi\Core;

require_once 'CiviTest/CiviUnitTestCase.php';

class SettingsManagerTest extends \CiviUnitTestCase {

  protected $domainDefaults;
  protected $contactDefaults;
  protected $mandates;

  protected function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);

    $this->domainDefaults = array(
      'd1' => 'alpha',
      'd2' => 'beta',
      'd3' => 'gamma',
      'myrelpath' => 'foo',
      'myabspath' => '/tmp/bar',
      'myrelurl' => 'sites/foo',
      'myabsurl' => 'http://example.com/bar',
    );
    $this->contactDefaults = array(
      'c1' => 'alpha',
      'c2' => 'beta',
      'c3' => 'gamma',
    );
    $this->mandates = array(
      'foo' => array(
        'd3' => 'GAMMA!',
      ),
      'bar' => array(
        'c3' => 'GAMMA MAN!',
      ),
    );
  }

  /**
   * Test mingled reads/writes of settings for two different domains.
   */
  public function testTwoDomains() {
    $da = \CRM_Core_DAO::createTestObject('CRM_Core_DAO_Domain');
    $db = \CRM_Core_DAO::createTestObject('CRM_Core_DAO_Domain');

    $manager = $this->createManager();

    $daSettings = $manager->getBagByDomain($da->id);
    $daSettings->set('d1', 'un');
    $this->assertEquals('un', $daSettings->get('d1'));
    $this->assertEquals('beta', $daSettings->get('d2'));
    $this->assertEquals('GAMMA!', $daSettings->get('d3'));

    $dbSettings = $manager->getBagByDomain($db->id);
    $this->assertEquals('alpha', $dbSettings->get('d1'));
    $this->assertEquals('beta', $dbSettings->get('d2'));
    $this->assertEquals('GAMMA!', $dbSettings->get('d3'));

    $managerRedux = $this->createManager();

    $daSettingsRedux = $managerRedux->getBagByDomain($da->id);
    $this->assertEquals('un', $daSettingsRedux->get('d1'));
    $this->assertEquals('beta', $daSettingsRedux->get('d2'));
    $this->assertEquals('GAMMA!', $daSettingsRedux->get('d3'));
  }

  /**
   * Test mingled reads/writes of settings for two different contacts.
   */
  public function testTwoContacts() {
    $domain = \CRM_Core_DAO::createTestObject('CRM_Core_DAO_Domain');
    $ca = \CRM_Core_DAO::createTestObject('CRM_Contact_DAO_Contact');
    $cb = \CRM_Core_DAO::createTestObject('CRM_Contact_DAO_Contact');

    $manager = $this->createManager();

    $caSettings = $manager->getBagByContact($domain->id, $ca->id);
    $caSettings->set('c1', 'un');
    $this->assertEquals('un', $caSettings->get('c1'));
    $this->assertEquals('beta', $caSettings->get('c2'));
    $this->assertEquals('GAMMA MAN!', $caSettings->get('c3'));

    $cbSettings = $manager->getBagByContact($domain->id, $cb->id);
    $this->assertEquals('alpha', $cbSettings->get('c1'));
    $this->assertEquals('beta', $cbSettings->get('c2'));
    $this->assertEquals('GAMMA MAN!', $cbSettings->get('c3'));

    // Read settings from freshly initialized objects.
    $manager = $this->createManager();

    $caSettingsRedux = $manager->getBagByContact($domain->id, $ca->id);
    $this->assertEquals('un', $caSettingsRedux->get('c1'));
    $this->assertEquals('beta', $caSettingsRedux->get('c2'));
    $this->assertEquals('GAMMA MAN!', $caSettingsRedux->get('c3'));
  }

  public function testCrossOver() {
    $domain = \CRM_Core_DAO::createTestObject('CRM_Core_DAO_Domain');
    $contact = \CRM_Core_DAO::createTestObject('CRM_Contact_DAO_Contact');

    $manager = $this->createManager();

    // Store different values for the 'monkeywrench' setting on domain and contact

    $domainSettings = $manager->getBagByDomain($domain->id);
    $domainSettings->set('monkeywrench', 'from domain');
    $this->assertEquals('from domain', $domainSettings->get('monkeywrench'));

    $contactSettings = $manager->getBagByContact($domain->id, $contact->id);
    $contactSettings->set('monkeywrench', 'from contact');
    $this->assertEquals('from contact', $contactSettings->get('monkeywrench'));

    // Read settings from freshly initialized objects.
    $manager = $this->createManager();

    $domainSettings = $manager->getBagByDomain($domain->id);
    $this->assertEquals('from domain', $domainSettings->get('monkeywrench'));

    $contactSettings = $manager->getBagByContact($domain->id, $contact->id);
    $this->assertEquals('from contact', $contactSettings->get('monkeywrench'));
  }

  public function testPaths() {
    $domain = \CRM_Core_DAO::createTestObject('CRM_Core_DAO_Domain');
    $manager = $this->createManager();
    $settings = $manager->getBagByDomain($domain->id);

    $this->assertEquals('foo', $settings->get('myrelpath'));
    $this->assertRegExp(':/.+/foo$:', $settings->getPath('myrelpath'));
    $settings->setPath('myrelpath', 'foo/sub');
    $this->assertEquals('foo/sub', $settings->get('myrelpath'));
    $this->assertRegExp(':/.+/foo/sub$:', $settings->getPath('myrelpath'));

    $this->assertEquals('/tmp/bar', $settings->get('myabspath'));
    $this->assertEquals('/tmp/bar', $settings->getPath('myabspath'));
    $settings->setPath('myabspath', '/tmp/bar/whiz');
    $this->assertEquals('/tmp/bar/whiz', $settings->get('myabspath'));
  }

  public function testUrl() {
    $domain = \CRM_Core_DAO::createTestObject('CRM_Core_DAO_Domain');
    $manager = $this->createManager();
    $settings = $manager->getBagByDomain($domain->id);

    $this->assertEquals('sites/foo', $settings->get('myrelurl'));
    $this->assertRegExp(';^http.*sites/foo$;', $settings->getUrl('myrelurl', 'absolute'));
    $this->assertRegExp(';^https:.*sites/foo$;', $settings->getUrl('myrelurl', 'absolute', TRUE));
    //$this->assertEquals('/sites/foo', $settings->getUrl('myrelurl', 'relative'));
    $settings->setUrl('myrelurl', 'sites/foo/sub');
    $this->assertEquals('sites/foo/sub', $settings->get('myrelurl'));
    $this->assertRegExp(';^http.*sites/foo/sub$;', $settings->getUrl('myrelurl', 'absolute'));
    //$this->assertEquals('/sites/foo/sub', $settings->getUrl('myrelurl', 'relative'));

    $this->assertEquals('http://example.com/bar', $settings->get('myabsurl'));
    $this->assertEquals('http://example.com/bar', $settings->getUrl('myabsurl', 'absolute'));
    $settings->setUrl('myabsurl', 'http://example.com/whiz');
    $this->assertEquals('http://example.com/whiz', $settings->get('myabsurl'));
    $this->assertEquals('http://example.com/whiz', $settings->getUrl('myabsurl', 'absolute'));
  }

  /**
   * @return SettingsManager
   */
  protected function createManager() {
    $cache = new \CRM_Utils_Cache_Arraycache(array());
    $cache->set('defaults:domain', $this->domainDefaults);
    $cache->set('defaults:contact', $this->contactDefaults);
    $manager = new SettingsManager($cache, SettingsManager::parseMandatorySettings($this->mandates));
    return $manager;
  }

}
