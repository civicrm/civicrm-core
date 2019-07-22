<?php
namespace Civi\Core;

class SettingsManagerTest extends \CiviUnitTestCase {

  protected $domainDefaults;
  protected $contactDefaults;
  protected $mandates;
  protected $origSetting;

  protected function setUp() {
    $this->origSetting = $GLOBALS['civicrm_setting'];

    parent::setUp();
    $this->useTransaction(TRUE);

    $this->domainDefaults = [
      'd1' => 'alpha',
      'd2' => 'beta',
      'd3' => 'gamma',
      'myrelpath' => 'foo',
      'myabspath' => '/tmp/bar',
      'myrelurl' => 'sites/foo',
      'myabsurl' => 'http://example.com/bar',
    ];
    $this->contactDefaults = [
      'c1' => 'alpha',
      'c2' => 'beta',
      'c3' => 'gamma',
    ];
    $this->mandates = [
      'Mailing Preferences' => [
        'd3' => 'GAMMA!',
      ],
      'contact' => [
        'c3' => 'GAMMA MAN!',
      ],
    ];
  }

  public function tearDown() {
    $GLOBALS['civicrm_setting'] = $this->origSetting;
    parent::tearDown();
  }

  /**
   * Test mingled reads/writes of settings for two different domains.
   */
  public function testTwoDomains() {
    $da = \CRM_Core_DAO::createTestObject('CRM_Core_DAO_Domain');
    $db = \CRM_Core_DAO::createTestObject('CRM_Core_DAO_Domain');

    $manager = $this->createManager()->useDefaults();

    $daSettings = $manager->getBagByDomain($da->id);
    $daSettings->set('d1', 'un');
    $this->assertEquals('un', $daSettings->get('d1'));
    $this->assertEquals('beta', $daSettings->get('d2'));
    $this->assertEquals('GAMMA!', $daSettings->get('d3'));

    $dbSettings = $manager->getBagByDomain($db->id);
    $this->assertEquals('alpha', $dbSettings->get('d1'));
    $this->assertEquals('beta', $dbSettings->get('d2'));
    $this->assertEquals('GAMMA!', $dbSettings->get('d3'));

    $managerRedux = $this->createManager()->useDefaults();

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

    $manager = $this->createManager()->useDefaults();

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
    $manager = $this->createManager()->useDefaults();

    $caSettingsRedux = $manager->getBagByContact($domain->id, $ca->id);
    $this->assertEquals('un', $caSettingsRedux->get('c1'));
    $this->assertEquals('beta', $caSettingsRedux->get('c2'));
    $this->assertEquals('GAMMA MAN!', $caSettingsRedux->get('c3'));
  }

  public function testCrossOver() {
    $domain = \CRM_Core_DAO::createTestObject('CRM_Core_DAO_Domain');
    $contact = \CRM_Core_DAO::createTestObject('CRM_Contact_DAO_Contact');

    $manager = $this->createManager()->useDefaults();

    // Store different values for the 'monkeywrench' setting on domain and contact

    $domainSettings = $manager->getBagByDomain($domain->id);
    $domainSettings->set('monkeywrench', 'from domain');
    $this->assertEquals('from domain', $domainSettings->get('monkeywrench'));

    $contactSettings = $manager->getBagByContact($domain->id, $contact->id);
    $contactSettings->set('monkeywrench', 'from contact');
    $this->assertEquals('from contact', $contactSettings->get('monkeywrench'));

    // Read settings from freshly initialized objects.
    $manager = $this->createManager()->useDefaults();

    $domainSettings = $manager->getBagByDomain($domain->id);
    $this->assertEquals('from domain', $domainSettings->get('monkeywrench'));

    $contactSettings = $manager->getBagByContact($domain->id, $contact->id);
    $this->assertEquals('from contact', $contactSettings->get('monkeywrench'));
  }

  /**
   * @return SettingsManager
   */
  protected function createManager() {
    $cache = new \CRM_Utils_Cache_Arraycache([]);
    $cache->set('defaults_domain', $this->domainDefaults);
    $cache->set('defaults_contact', $this->contactDefaults);
    foreach ($this->mandates as $entity => $keyValues) {
      foreach ($keyValues as $k => $v) {
        $GLOBALS['civicrm_setting'][$entity][$k] = $v;
      }
    }
    $manager = new SettingsManager($cache);
    return $manager;
  }

}
