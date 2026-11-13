<?php
namespace Civi\Core;

class SettingsManagerTest extends \CiviUnitTestCase {

  protected $domainDefaults;
  protected $contactDefaults;
  protected $mandates;
  protected $origSetting;

  /**
   * @var string|false
   */
  protected $origExtRepoUrlEnv;

  protected function setUp(): void {
    $this->origSetting = $GLOBALS['civicrm_setting'];
    $this->origExtRepoUrlEnv = getenv('CIVICRM_SETTING_EXT_REPO_URL');

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

  public function tearDown(): void {
    $GLOBALS['civicrm_setting'] = $this->origSetting;
    putenv($this->origExtRepoUrlEnv === FALSE ? 'CIVICRM_SETTING_EXT_REPO_URL' : "CIVICRM_SETTING_EXT_REPO_URL={$this->origExtRepoUrlEnv}");
    parent::tearDown();
  }

  /**
   * Test mingled reads/writes of settings for two different domains.
   */
  public function testTwoDomains(): void {
    $da = \CRM_Core_DAO::createTestObject('CRM_Core_DAO_Domain');
    $db = \CRM_Core_DAO::createTestObject('CRM_Core_DAO_Domain');

    $manager = $this->createManager()->bootComplete();

    $daSettings = $manager->getBagByDomain($da->id);
    $daSettings->set('d1', 'un');
    $this->assertSame('un', $daSettings->get('d1'));
    $this->assertSame('beta', $daSettings->get('d2'));
    $this->assertSame('GAMMA!', $daSettings->get('d3'));

    $dbSettings = $manager->getBagByDomain($db->id);
    $this->assertSame('alpha', $dbSettings->get('d1'));
    $this->assertSame('beta', $dbSettings->get('d2'));
    $this->assertSame('GAMMA!', $dbSettings->get('d3'));

    $managerRedux = $this->createManager()->bootComplete();

    $daSettingsRedux = $managerRedux->getBagByDomain($da->id);
    $this->assertSame('un', $daSettingsRedux->get('d1'));
    $this->assertSame('beta', $daSettingsRedux->get('d2'));
    $this->assertSame('GAMMA!', $daSettingsRedux->get('d3'));
  }

  /**
   * Test mingled reads/writes of settings for two different contacts.
   */
  public function testTwoContacts(): void {
    $domain = \CRM_Core_DAO::createTestObject('CRM_Core_DAO_Domain');
    $ca = \CRM_Core_DAO::createTestObject('CRM_Contact_DAO_Contact');
    $cb = \CRM_Core_DAO::createTestObject('CRM_Contact_DAO_Contact');

    $manager = $this->createManager()->bootComplete();

    $caSettings = $manager->getBagByContact($domain->id, $ca->id);
    $caSettings->set('c1', 'un');
    $this->assertSame('un', $caSettings->get('c1'));
    $this->assertSame('beta', $caSettings->get('c2'));
    $this->assertSame('GAMMA MAN!', $caSettings->get('c3'));

    $cbSettings = $manager->getBagByContact($domain->id, $cb->id);
    $this->assertSame('alpha', $cbSettings->get('c1'));
    $this->assertSame('beta', $cbSettings->get('c2'));
    $this->assertSame('GAMMA MAN!', $cbSettings->get('c3'));

    // Read settings from freshly initialized objects.
    $manager = $this->createManager()->bootComplete();

    $caSettingsRedux = $manager->getBagByContact($domain->id, $ca->id);
    $this->assertSame('un', $caSettingsRedux->get('c1'));
    $this->assertSame('beta', $caSettingsRedux->get('c2'));
    $this->assertSame('GAMMA MAN!', $caSettingsRedux->get('c3'));
  }

  public function testCrossOver(): void {
    $domain = \CRM_Core_DAO::createTestObject('CRM_Core_DAO_Domain');
    $contact = \CRM_Core_DAO::createTestObject('CRM_Contact_DAO_Contact');

    $manager = $this->createManager()->bootComplete();

    // Store different values for the 'monkeywrench' setting on domain and contact

    $domainSettings = $manager->getBagByDomain($domain->id);
    $domainSettings->set('monkeywrench', 'from domain');
    $this->assertSame('from domain', $domainSettings->get('monkeywrench'));

    $contactSettings = $manager->getBagByContact($domain->id, $contact->id);
    $contactSettings->set('monkeywrench', 'from contact');
    $this->assertSame('from contact', $contactSettings->get('monkeywrench'));

    // Read settings from freshly initialized objects.
    $manager = $this->createManager()->bootComplete();

    $domainSettings = $manager->getBagByDomain($domain->id);
    $this->assertSame('from domain', $domainSettings->get('monkeywrench'));

    $contactSettings = $manager->getBagByContact($domain->id, $contact->id);
    $this->assertSame('from contact', $contactSettings->get('monkeywrench'));
  }

  /**
   * @return SettingsManager
   */
  protected function createManager() {
    $cache = new \CRM_Utils_Cache_ArrayCache([]);
    $cache->set('phase2_defaults_domain', $this->domainDefaults);
    $cache->set('phase2_defaults_contact', $this->contactDefaults);
    foreach ($this->mandates as $entity => $keyValues) {
      foreach ($keyValues as $k => $v) {
        $GLOBALS['civicrm_setting'][$entity][$k] = $v;
      }
    }
    $manager = new SettingsManager($cache);
    return $manager;
  }

  /**
   * Test passing in an array to Civi::settings facade
   */
  public function testArraySetting(): void {
    $domain = \CRM_Core_DAO::createTestObject('CRM_Core_DAO_Domain');

    $manager = $this->createManager()->bootComplete();
    $dSettings = $manager->getBagByDomain($domain->id);
    $dSettings->set('test_setting', ['hello' => 'World']);
    $test_setting = $dSettings->get('test_setting');
    $this->assertEquals(['hello' => 'World'], $test_setting);
  }

  public function testExtRepoUrlFromEnv(): void {
    unset($GLOBALS['civicrm_setting']['domain']['ext_repo_url']);
    putenv('CIVICRM_SETTING_EXT_REPO_URL');
    $domain = \CRM_Core_DAO::createTestObject('CRM_Core_DAO_Domain');
    $this->assertNotEquals('https://example.org/feed', $this->createManager()->bootComplete()->getBagByDomain($domain->id)->get('ext_repo_url'));

    putenv('CIVICRM_SETTING_EXT_REPO_URL=https://example.org/feed');
    $this->assertEquals('https://example.org/feed', $this->createManager()->bootComplete()->getBagByDomain($domain->id)->get('ext_repo_url'));
  }

  public function testSetConstantNamesEnvironmentVariable(): void {
    $this->expectException(\CRM_Core_Exception::class);
    $this->expectExceptionMessage('ext_repo_url is a system constant. It can only be set in civicrm.settings.php or with the environment variable CIVICRM_SETTING_EXT_REPO_URL.');
    \Civi::settings()->set('ext_repo_url', 'x');
  }

  /**
   * A constant without an environment variable, as an extension may declare it.
   */
  public function testSetConstantWithoutEnvironmentVariable(): void {
    $bag = new SettingsBag(1, NULL);
    $bag->updateConstant('editor_id', 'x');
    $this->expectException(\CRM_Core_Exception::class);
    $this->expectExceptionMessage('editor_id is a system constant. It can only be set in civicrm.settings.php.');
    $bag->set('editor_id', 'y');
  }

}
