<?php

/**
 * Class CRM_Extension_Manager_SubmoduleTest
 *
 * The test scenarios here involve multiple actions (install/disable/uninstall) and targets (parent/uncle/child).
 * It takes a lot of words to describe each sequence. For naming the test-functions, we follow a convention:
 *
 * - Actions: [I]nstall, [D]isable, [U]ninstall
 * - Targets: [P]arent, [C]hild, [U]ncle
 *
 * Ex: [I]nstall [P]arent + [I]nstall [U]ncle + [D]isable [P]arent = IP_IU_DU
 *
 * @group headless
 */
class CRM_Extension_Manager_SubmoduleTest extends CiviUnitTestCase {

  /**
   * @var string
   */
  protected $basedir;

  /**
   * @var \CRM_Extension_System
   */
  protected $system;

  public function setUp():void {
    parent::setUp();
    // $query = "INSERT INTO civicrm_domain ( name, version ) VALUES ( 'domain', 3 )";
    // $result = CRM_Core_DAO::executeQuery($query);
    global $_test_extension_manager_submodule_log;
    $_test_extension_manager_submodule_log = [];
    $this->basedir = $this->createTempDir('ext-');
    $this->system = new CRM_Extension_System([
      'extensionsDir' => $this->basedir,
      'extensionsURL' => 'http://testbase/',
    ]);
    $this->setExtensionSystem($this->system);
  }

  public function tearDown(): void {
    parent::tearDown();
    $this->system = NULL;
  }

  public function testIP_IU_DU_UU_DP_UP(): void {
    $manager = $this->system->getManager();
    $this->assertModules(['parenttest' => 'uninstalled', 'childtest' => 'uninstalled', 'uncletest' => 'uninstalled']);

    $manager->install(['test.extension.parenttest']);
    $this->assertModules(['parenttest' => 'installed', 'childtest' => 'uninstalled', 'uncletest' => 'uninstalled']);
    $this->assertHookLog(['parenttest_civicrm_install', 'parenttest_civicrm_enable']);

    $manager->install(['test.extension.uncletest']);
    $this->assertModules(['parenttest' => 'installed', 'childtest' => 'installed', 'uncletest' => 'installed']);
    $this->assertHookLog(['uncletest_civicrm_install', 'uncletest_civicrm_enable', 'childtest_civicrm_install', 'childtest_civicrm_enable']);

    $manager->disable(['test.extension.uncletest']);
    $this->assertModules(['parenttest' => 'installed', 'childtest' => 'disabled', 'uncletest' => 'disabled']);
    $this->assertHookLog(['childtest_civicrm_disable', 'uncletest_civicrm_disable']);

    $manager->uninstall(['test.extension.uncletest']);
    $this->assertModules(['parenttest' => 'installed', 'childtest' => 'disabled', 'uncletest' => 'uninstalled']);
    $this->assertHookLog(['uncletest_civicrm_uninstall']);

    $manager->disable(['test.extension.parenttest']);
    $this->assertModules(['parenttest' => 'disabled', 'childtest' => 'disabled', 'uncletest' => 'uninstalled']);
    $this->assertHookLog(['parenttest_civicrm_disable']);

    $manager->uninstall(['test.extension.parenttest']);
    $this->assertModules(['parenttest' => 'uninstalled', 'childtest' => 'uninstalled', 'uncletest' => 'uninstalled']);
    $this->assertHookLog(['childtest_civicrm_uninstall', 'parenttest_civicrm_uninstall']);
  }

  public function testIU_IP_DP_UP(): void {
    $manager = $this->system->getManager();
    $this->assertModules(['parenttest' => 'uninstalled', 'childtest' => 'uninstalled', 'uncletest' => 'uninstalled']);

    $manager->install(['test.extension.uncletest']);
    $this->assertModules(['parenttest' => 'uninstalled', 'childtest' => 'uninstalled', 'uncletest' => 'installed']);
    $this->assertHookLog(['uncletest_civicrm_install', 'uncletest_civicrm_enable']);

    $manager->install(['test.extension.parenttest']);
    $this->assertModules(['parenttest' => 'installed', 'childtest' => 'installed', 'uncletest' => 'installed']);
    $this->assertHookLog(['parenttest_civicrm_install', 'parenttest_civicrm_enable', 'childtest_civicrm_install', 'childtest_civicrm_enable']);

    $manager->disable(['test.extension.parenttest']);
    $this->assertModules(['parenttest' => 'disabled', 'childtest' => 'disabled', 'uncletest' => 'installed']);
    $this->assertHookLog(['childtest_civicrm_disable', 'parenttest_civicrm_disable']);

    $manager->uninstall(['test.extension.parenttest']);
    $this->assertModules(['parenttest' => 'uninstalled', 'childtest' => 'uninstalled', 'uncletest' => 'installed']);
    $this->assertHookLog(['childtest_civicrm_uninstall', 'parenttest_civicrm_uninstall']);
  }

  public function assertHookLog(array $expected): void {
    global $_test_extension_manager_submodule_log;
    $this->assertEquals($expected, $_test_extension_manager_submodule_log);
    $_test_extension_manager_submodule_log = [];
  }

  public function assertModules(array $expected): void {
    $manager = $this->system->getManager();
    foreach ($expected as $module => $expectStatus) {
      $key = "test.extension.{$module}";
      $this->assertEquals($expectStatus, $manager->getStatus($key), "Module $module should have status {$expectStatus}.");
    }
  }

  /**
   * @param string $name
   */
  public static function logHook(string $name) {
    global $_test_extension_manager_submodule_log;
    $_test_extension_manager_submodule_log[] = $name;
  }

}
