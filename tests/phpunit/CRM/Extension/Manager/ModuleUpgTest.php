<?php

/**
 * Class CRM_Extension_Manager_ModuleUpgTest
 * @group headless
 */
class CRM_Extension_Manager_ModuleUpgTest extends CiviUnitTestCase {

  /**
   * @var \CRM_Extension_System
   */
  protected $system;

  public function setUp():void {
    parent::setUp();
    // $query = "INSERT INTO civicrm_domain ( name, version ) VALUES ( 'domain', 3 )";
    // $result = CRM_Core_DAO::executeQuery($query);
    global $_test_extension_manager_moduleupgtest_counts;
    $_test_extension_manager_moduleupgtest_counts = [];
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

  /**
   * Install an extension with a valid type name.
   */
  public function testInstallDisableUninstall() {
    $manager = $this->system->getManager();
    $this->assertModuleActiveByName(FALSE, 'moduleupgtest');

    $manager->install(['test.extension.manager.moduleupgtest']);
    $this->assertHookCounts('moduleupgtest', [
      'install' => 1,
      'postInstall' => 1,
      'enable' => 1,
      'disable' => 0,
      'uninstall' => 0,
    ]);
    $this->assertModuleActiveByName(TRUE, 'moduleupgtest');
    $this->assertModuleActiveByKey(TRUE, 'test.extension.manager.moduleupgtest');

    $manager->disable(['test.extension.manager.moduleupgtest']);
    $this->assertHookCounts('moduleupgtest', [
      'install' => 1,
      'postInstall' => 1,
      'enable' => 1,
      'disable' => 1,
      'uninstall' => 0,
    ]);
    $this->assertModuleActiveByName(FALSE, 'moduleupgtest');
    $this->assertModuleActiveByKey(FALSE, 'test.extension.manager.moduleupgtest');

    $manager->uninstall(['test.extension.manager.moduleupgtest']);
    $this->assertHookCounts('moduleupgtest', [
      'install' => 1,
      'postInstall' => 1,
      'enable' => 1,
      'disable' => 1,
      'uninstall' => 1,
    ]);
    $this->assertModuleActiveByName(FALSE, 'moduleupgtest');
    $this->assertModuleActiveByKey(FALSE, 'test.extension.manager.moduleupgtest');
  }

  /**
   * Install an extension with a valid type name.
   */
  public function testInstallDisableEnable() {
    $manager = $this->system->getManager();
    $this->assertModuleActiveByName(FALSE, 'moduleupgtest');
    $this->assertModuleActiveByKey(FALSE, 'test.extension.manager.moduleupgtest');

    $manager->install(['test.extension.manager.moduleupgtest']);
    $this->assertHookCounts('moduleupgtest', [
      'install' => 1,
      'enable' => 1,
      'disable' => 0,
      'uninstall' => 0,
    ]);
    $this->assertModuleActiveByName(TRUE, 'moduleupgtest');
    $this->assertModuleActiveByKey(TRUE, 'test.extension.manager.moduleupgtest');

    $manager->disable(['test.extension.manager.moduleupgtest']);
    $this->assertHookCounts('moduleupgtest', [
      'install' => 1,
      'enable' => 1,
      'disable' => 1,
      'uninstall' => 0,
    ]);
    $this->assertModuleActiveByName(FALSE, 'moduleupgtest');
    $this->assertModuleActiveByKey(FALSE, 'test.extension.manager.moduleupgtest');

    $manager->enable(['test.extension.manager.moduleupgtest']);
    $this->assertHookCounts('moduleupgtest', [
      'install' => 1,
      'enable' => 2,
      'disable' => 1,
      'uninstall' => 0,
    ]);
    $this->assertModuleActiveByName(TRUE, 'moduleupgtest');
    $this->assertModuleActiveByKey(TRUE, 'test.extension.manager.moduleupgtest');
  }

  /**
   * Install an extension then forcibly remove the code and cleanup DB afterwards.
   */
  public function testInstall_DirtyRemove_Disable_Uninstall() {
    // create temporary extension (which can dirtily remove later)
    $this->_createExtension('test.extension.manager.moduleupg.auto1', 'module', 'test_extension_manager_moduleupg_auto1');
    $mainfile = $this->basedir . '/test.extension.manager.moduleupg.auto1/test_extension_manager_moduleupg_auto1.php';
    $this->assertTrue(file_exists($mainfile));
    $manager = $this->system->getManager();
    $this->assertModuleActiveByName(FALSE, 'test_extension_manager_moduleupg_auto1');
    $this->assertModuleActiveByKey(FALSE, 'test.extension.manager.moduleupg.auto1');

    // install it
    $manager->install(['test.extension.manager.moduleupg.auto1']);
    $this->assertEquals('installed', $manager->getStatus('test.extension.manager.moduleupg.auto1'));
    $this->assertHookCounts('test_extension_manager_moduleupg_auto1', [
      'install' => 1,
      'enable' => 1,
      'disable' => 0,
      'uninstall' => 0,
    ]);
    $this->assertModuleActiveByName(TRUE, 'test_extension_manager_moduleupg_auto1');
    $this->assertModuleActiveByKey(TRUE, 'test.extension.manager.moduleupg.auto1');

    // dirty removal
    CRM_Utils_File::cleanDir($this->basedir . '/test.extension.manager.moduleupg.auto1', TRUE, FALSE);
    $manager->refresh();
    $this->assertEquals('installed-missing', $manager->getStatus('test.extension.manager.moduleupg.auto1'));

    // disable while missing
    $manager->disable(['test.extension.manager.moduleupg.auto1']);
    $this->assertEquals('disabled-missing', $manager->getStatus('test.extension.manager.moduleupg.auto1'));
    $this->assertHookCounts('test_extension_manager_moduleupg_auto1', [
      'install' => 1,
      'enable' => 1,
      // normally called -- but not for missing modules!
      'disable' => 0,
      'uninstall' => 0,
    ]);
    $this->assertModuleActiveByName(FALSE, 'test_extension_manager_moduleupg_auto1');
    $this->assertModuleActiveByKey(FALSE, 'test.extension.manager.moduleupgtest');

    $manager->uninstall(['test.extension.manager.moduleupg.auto1']);
    $this->assertHookCounts('test_extension_manager_moduleupg_auto1', [
      'install' => 1,
      'enable' => 1,
      // normally called -- but not for missing modules!
      'disable' => 0,
      // normally called -- but not for missing modules!
      'uninstall' => 0,
    ]);
    $this->assertEquals('unknown', $manager->getStatus('test.extension.manager.moduleupg.auto1'));
    $this->assertModuleActiveByName(FALSE, 'test_extension_manager_moduleupg_auto1');
    $this->assertModuleActiveByKey(FALSE, 'test.extension.manager.moduleupg.auto1');
  }

  /**
   * Install an extension then forcibly remove the code and cleanup DB afterwards.
   */
  public function testInstall_DirtyRemove_Disable_Restore() {
    // create temporary extension (which can dirtily remove later)
    $this->_createExtension('test.extension.manager.moduleupg.auto2', 'module', 'test_extension_manager_moduleupg_auto2');
    $mainfile = $this->basedir . '/test.extension.manager.moduleupg.auto2/test_extension_manager_moduleupg_auto2.php';
    $this->assertTrue(file_exists($mainfile));
    $manager = $this->system->getManager();
    $this->assertModuleActiveByName(FALSE, 'test_extension_manager_moduleupg_auto2');
    $this->assertModuleActiveByKey(FALSE, 'test.extension.manager.moduleupg.auto2');

    // install it
    $manager->install(['test.extension.manager.moduleupg.auto2']);
    $this->assertEquals('installed', $manager->getStatus('test.extension.manager.moduleupg.auto2'));
    $this->assertHookCounts('test_extension_manager_moduleupg_auto2', [
      'install' => 1,
      'enable' => 1,
      'disable' => 0,
      'uninstall' => 0,
    ]);
    $this->assertModuleActiveByName(TRUE, 'test_extension_manager_moduleupg_auto2');
    $this->assertModuleActiveByKey(TRUE, 'test.extension.manager.moduleupg.auto2');

    // dirty removal
    CRM_Utils_File::cleanDir($this->basedir . '/test.extension.manager.moduleupg.auto2', TRUE, FALSE);
    $manager->refresh();
    $this->assertEquals('installed-missing', $manager->getStatus('test.extension.manager.moduleupg.auto2'));

    // disable while missing
    $manager->disable(['test.extension.manager.moduleupg.auto2']);
    $this->assertEquals('disabled-missing', $manager->getStatus('test.extension.manager.moduleupg.auto2'));
    $this->assertHookCounts('test_extension_manager_moduleupg_auto2', [
      'install' => 1,
      'enable' => 1,
      // normally called -- but not for missing modules!
      'disable' => 0,
      'uninstall' => 0,
    ]);
    $this->assertModuleActiveByName(FALSE, 'test_extension_manager_moduleupg_auto2');
    $this->assertModuleActiveByKey(FALSE, 'test.extension.manager.moduleupgtest');

    // restore the code
    $this->_createExtension('test.extension.manager.moduleupg.auto2', 'module', 'test_extension_manager_moduleupg_auto2');
    $manager->refresh();
    $this->assertHookCounts('test_extension_manager_moduleupg_auto2', [
      'install' => 1,
      'enable' => 1,
      'disable' => 0,
      'uninstall' => 0,
    ]);
    $this->assertEquals('disabled', $manager->getStatus('test.extension.manager.moduleupg.auto2'));
    $this->assertModuleActiveByName(FALSE, 'test_extension_manager_moduleupg_auto2');
    $this->assertModuleActiveByKey(FALSE, 'test.extension.manager.moduleupg.auto2');
  }

  /**
   * @param $module
   * @param array $counts
   *   Expected hook invocation counts ($hookName => $count).
   */
  public function assertHookCounts($module, $counts) {
    global $_test_extension_manager_moduleupgtest_counts;
    foreach ($counts as $key => $expected) {
      $actual = $_test_extension_manager_moduleupgtest_counts[$module][$key] ?? 0;
      $this->assertSame($expected, $actual,
        sprintf('Expected %d call(s) to hook_civicrm_%s -- found %d', $expected, $key, $actual)
      );
    }
  }

  /**
   * @param $expectedIsActive
   * @param $prefix
   */
  public function assertModuleActiveByName($expectedIsActive, $prefix) {
    // FIXME
    $activeModules = CRM_Core_PseudoConstant::getModuleExtensions(TRUE);
    foreach ($activeModules as $activeModule) {
      if ($activeModule['prefix'] == $prefix) {
        $this->assertEquals($expectedIsActive, TRUE);
        return;
      }
    }
    $this->assertEquals($expectedIsActive, FALSE);
  }

  /**
   * @param $expectedIsActive
   * @param $key
   */
  public function assertModuleActiveByKey($expectedIsActive, $key) {
    foreach (CRM_Core_Module::getAll() as $module) {
      if ($module->name == $key) {
        $this->assertEquals((bool) $expectedIsActive, (bool) $module->is_active);
        return;
      }
    }
    $this->assertEquals($expectedIsActive, FALSE);
  }

  /**
   * @param $key
   * @param $type
   * @param $file
   * @param string $template
   */
  public function _createExtension($key, $type, $file, $template = self::MODULE_TEMPLATE) {
    $basedir = $this->basedir;
    mkdir("$basedir/$key");
    $upgClass = 'CRM_' . preg_replace('/[^a-zA-Z0-9]/', '', $key) . '_Upgrader';
    $infoXmlStr = "<extension key='$key' type='$type'><file>$file</file><upgrader>$upgClass</upgrader></extension>";
    $modulePhpStr = strtr($template, ['_FILE_' => $file, '_TEST_' => __CLASS__, '_UPGRADER_' => $upgClass]);
    file_put_contents("$basedir/$key/info.xml", $infoXmlStr);
    file_put_contents("$basedir/$key/$file.php", $modulePhpStr);
    $system = CRM_Extension_System::singleton();
    $system->getCache()->flush();
    $system->getManager()->refresh();
    $this->system->getCache()->flush();
    $this->system->getManager()->refresh();
    CRM_Extension_System::setSingleton($this->system);
  }

  /**
   * @param $module
   * @param string $name
   */
  public static function incHookCount($module, $name) {
    global $_test_extension_manager_moduleupgtest_counts;
    if (!isset($_test_extension_manager_moduleupgtest_counts[$module][$name])) {
      $_test_extension_manager_moduleupgtest_counts[$module][$name] = 0;
    }
    $_test_extension_manager_moduleupgtest_counts[$module][$name] = 1 + (int) $_test_extension_manager_moduleupgtest_counts[$module][$name];
  }

  const MODULE_TEMPLATE = "<?php
class _UPGRADER_ extends CRM_Extension_Upgrader_Base {
  public function install() {
    _TEST_::incHookCount('_FILE_', 'install');
  }

  public function postInstall() {
    _TEST_::incHookCount('_FILE_', 'postInstall');
  }

  public function uninstall() {
    _TEST_::incHookCount('_FILE_', 'uninstall');
  }

  public function enable() {
    _TEST_::incHookCount('_FILE_', 'enable');
  }

  public function disable() {
    _TEST_::incHookCount('_FILE_', 'disable');
  }
}
";

}
