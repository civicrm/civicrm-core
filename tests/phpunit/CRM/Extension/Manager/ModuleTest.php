<?php

/**
 * Class CRM_Extension_Manager_ModuleTest
 * @group headless
 */
class CRM_Extension_Manager_ModuleTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
    // $query = "INSERT INTO civicrm_domain ( name, version ) VALUES ( 'domain', 3 )";
    // $result = CRM_Core_DAO::executeQuery($query);
    global $_test_extension_manager_moduletest_counts;
    $_test_extension_manager_moduletest_counts = array();
    $this->basedir = $this->createTempDir('ext-');
    $this->system = new CRM_Extension_System(array(
      'extensionsDir' => $this->basedir,
      'extensionsURL' => 'http://testbase/',
    ));
    $this->setExtensionSystem($this->system);
  }

  public function tearDown() {
    parent::tearDown();
    $this->system = NULL;
  }

  /**
   * Install an extension with a valid type name.
   */
  public function testInstallDisableUninstall() {
    $manager = $this->system->getManager();
    $this->assertModuleActiveByName(FALSE, 'moduletest');

    $manager->install(array('test.extension.manager.moduletest'));
    $this->assertHookCounts('moduletest', array(
      'install' => 1,
      'postInstall' => 1,
      'enable' => 1,
      'disable' => 0,
      'uninstall' => 0,
    ));
    $this->assertModuleActiveByName(TRUE, 'moduletest');
    $this->assertModuleActiveByKey(TRUE, 'test.extension.manager.moduletest');

    $manager->disable(array('test.extension.manager.moduletest'));
    $this->assertHookCounts('moduletest', array(
      'install' => 1,
      'postInstall' => 1,
      'enable' => 1,
      'disable' => 1,
      'uninstall' => 0,
    ));
    $this->assertModuleActiveByName(FALSE, 'moduletest');
    $this->assertModuleActiveByKey(FALSE, 'test.extension.manager.moduletest');

    $manager->uninstall(array('test.extension.manager.moduletest'));
    $this->assertHookCounts('moduletest', array(
      'install' => 1,
      'postInstall' => 1,
      'enable' => 1,
      'disable' => 1,
      'uninstall' => 1,
    ));
    $this->assertModuleActiveByName(FALSE, 'moduletest');
    $this->assertModuleActiveByKey(FALSE, 'test.extension.manager.moduletest');
  }

  /**
   * Install an extension with a valid type name.
   */
  public function testInstallDisableEnable() {
    $manager = $this->system->getManager();
    $this->assertModuleActiveByName(FALSE, 'moduletest');
    $this->assertModuleActiveByKey(FALSE, 'test.extension.manager.moduletest');

    $manager->install(array('test.extension.manager.moduletest'));
    $this->assertHookCounts('moduletest', array(
      'install' => 1,
      'enable' => 1,
      'disable' => 0,
      'uninstall' => 0,
    ));
    $this->assertModuleActiveByName(TRUE, 'moduletest');
    $this->assertModuleActiveByKey(TRUE, 'test.extension.manager.moduletest');

    $manager->disable(array('test.extension.manager.moduletest'));
    $this->assertHookCounts('moduletest', array(
      'install' => 1,
      'enable' => 1,
      'disable' => 1,
      'uninstall' => 0,
    ));
    $this->assertModuleActiveByName(FALSE, 'moduletest');
    $this->assertModuleActiveByKey(FALSE, 'test.extension.manager.moduletest');

    $manager->enable(array('test.extension.manager.moduletest'));
    $this->assertHookCounts('moduletest', array(
      'install' => 1,
      'enable' => 2,
      'disable' => 1,
      'uninstall' => 0,
    ));
    $this->assertModuleActiveByName(TRUE, 'moduletest');
    $this->assertModuleActiveByKey(TRUE, 'test.extension.manager.moduletest');
  }

  /**
   * Install an extension then forcibly remove the code and cleanup DB afterwards.
   */
  public function testInstall_DirtyRemove_Disable_Uninstall() {
    // create temporary extension (which can dirtily remove later)
    $this->_createExtension('test.extension.manager.module.auto1', 'module', 'test_extension_manager_module_auto1');
    $mainfile = $this->basedir . '/test.extension.manager.module.auto1/test_extension_manager_module_auto1.php';
    $this->assertTrue(file_exists($mainfile));
    $manager = $this->system->getManager();
    $this->assertModuleActiveByName(FALSE, 'test_extension_manager_module_auto1');
    $this->assertModuleActiveByKey(FALSE, 'test.extension.manager.module.auto1');

    // install it
    $manager->install(array('test.extension.manager.module.auto1'));
    $this->assertEquals('installed', $manager->getStatus('test.extension.manager.module.auto1'));
    $this->assertHookCounts('test_extension_manager_module_auto1', array(
      'install' => 1,
      'enable' => 1,
      'disable' => 0,
      'uninstall' => 0,
    ));
    $this->assertModuleActiveByName(TRUE, 'test_extension_manager_module_auto1');
    $this->assertModuleActiveByKey(TRUE, 'test.extension.manager.module.auto1');

    // dirty removal
    CRM_Utils_File::cleanDir($this->basedir . '/test.extension.manager.module.auto1', TRUE, FALSE);
    $manager->refresh();
    $this->assertEquals('installed-missing', $manager->getStatus('test.extension.manager.module.auto1'));

    // disable while missing
    $manager->disable(array('test.extension.manager.module.auto1'));
    $this->assertEquals('disabled-missing', $manager->getStatus('test.extension.manager.module.auto1'));
    $this->assertHookCounts('test_extension_manager_module_auto1', array(
      'install' => 1,
      'enable' => 1,
      // normally called -- but not for missing modules!
      'disable' => 0,
      'uninstall' => 0,
    ));
    $this->assertModuleActiveByName(FALSE, 'test_extension_manager_module_auto1');
    $this->assertModuleActiveByKey(FALSE, 'test.extension.manager.moduletest');

    $manager->uninstall(array('test.extension.manager.module.auto1'));
    $this->assertHookCounts('test_extension_manager_module_auto1', array(
      'install' => 1,
      'enable' => 1,
      // normally called -- but not for missing modules!
      'disable' => 0,
      // normally called -- but not for missing modules!
      'uninstall' => 0,
    ));
    $this->assertEquals('unknown', $manager->getStatus('test.extension.manager.module.auto1'));
    $this->assertModuleActiveByName(FALSE, 'test_extension_manager_module_auto1');
    $this->assertModuleActiveByKey(FALSE, 'test.extension.manager.module.auto1');
  }

  /**
   * Install an extension then forcibly remove the code and cleanup DB afterwards.
   */
  public function testInstall_DirtyRemove_Disable_Restore() {
    // create temporary extension (which can dirtily remove later)
    $this->_createExtension('test.extension.manager.module.auto2', 'module', 'test_extension_manager_module_auto2');
    $mainfile = $this->basedir . '/test.extension.manager.module.auto2/test_extension_manager_module_auto2.php';
    $this->assertTrue(file_exists($mainfile));
    $manager = $this->system->getManager();
    $this->assertModuleActiveByName(FALSE, 'test_extension_manager_module_auto2');
    $this->assertModuleActiveByKey(FALSE, 'test.extension.manager.module.auto2');

    // install it
    $manager->install(array('test.extension.manager.module.auto2'));
    $this->assertEquals('installed', $manager->getStatus('test.extension.manager.module.auto2'));
    $this->assertHookCounts('test_extension_manager_module_auto2', array(
      'install' => 1,
      'enable' => 1,
      'disable' => 0,
      'uninstall' => 0,
    ));
    $this->assertModuleActiveByName(TRUE, 'test_extension_manager_module_auto2');
    $this->assertModuleActiveByKey(TRUE, 'test.extension.manager.module.auto2');

    // dirty removal
    CRM_Utils_File::cleanDir($this->basedir . '/test.extension.manager.module.auto2', TRUE, FALSE);
    $manager->refresh();
    $this->assertEquals('installed-missing', $manager->getStatus('test.extension.manager.module.auto2'));

    // disable while missing
    $manager->disable(array('test.extension.manager.module.auto2'));
    $this->assertEquals('disabled-missing', $manager->getStatus('test.extension.manager.module.auto2'));
    $this->assertHookCounts('test_extension_manager_module_auto2', array(
      'install' => 1,
      'enable' => 1,
      // normally called -- but not for missing modules!
      'disable' => 0,
      'uninstall' => 0,
    ));
    $this->assertModuleActiveByName(FALSE, 'test_extension_manager_module_auto2');
    $this->assertModuleActiveByKey(FALSE, 'test.extension.manager.moduletest');

    // restore the code
    $this->_createExtension('test.extension.manager.module.auto2', 'module', 'test_extension_manager_module_auto2');
    $manager->refresh();
    $this->assertHookCounts('test_extension_manager_module_auto2', array(
      'install' => 1,
      'enable' => 1,
      'disable' => 0,
      'uninstall' => 0,
    ));
    $this->assertEquals('disabled', $manager->getStatus('test.extension.manager.module.auto2'));
    $this->assertModuleActiveByName(FALSE, 'test_extension_manager_module_auto2');
    $this->assertModuleActiveByKey(FALSE, 'test.extension.manager.module.auto2');
  }

  /**
   * @param $module
   * @param array $counts
   *   Expected hook invocation counts ($hookName => $count).
   */
  public function assertHookCounts($module, $counts) {
    global $_test_extension_manager_moduletest_counts;
    foreach ($counts as $key => $expected) {
      $actual = @$_test_extension_manager_moduletest_counts[$module][$key];
      $this->assertEquals($expected, $actual,
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
    file_put_contents("$basedir/$key/info.xml", "<extension key='$key' type='$type'><file>$file</file></extension>");
    file_put_contents("$basedir/$key/$file.php", strtr($template, array('_FILE_' => $file)));
    $this->system->getCache()->flush();
    $this->system->getManager()->refresh();
  }

  /**
   * @param $module
   * @param string $name
   */
  public static function incHookCount($module, $name) {
    global $_test_extension_manager_moduletest_counts;
    if (!isset($_test_extension_manager_moduletest_counts[$module][$name])) {
      $_test_extension_manager_moduletest_counts[$module][$name] = 0;
    }
    $_test_extension_manager_moduletest_counts[$module][$name] = 1 + (int) $_test_extension_manager_moduletest_counts[$module][$name];
  }

  const MODULE_TEMPLATE = "<?php
function _FILE__civicrm_install() {
  CRM_Extension_Manager_ModuleTest::incHookCount('_FILE_', 'install');
}

function _FILE__civicrm_postInstall() {
  CRM_Extension_Manager_ModuleTest::incHookCount('_FILE_', 'postInstall');
}

function _FILE__civicrm_uninstall() {
  CRM_Extension_Manager_ModuleTest::incHookCount('_FILE_', 'uninstall');
}

function _FILE__civicrm_enable() {
  CRM_Extension_Manager_ModuleTest::incHookCount('_FILE_', 'enable');
}

function _FILE__civicrm_disable() {
  CRM_Extension_Manager_ModuleTest::incHookCount('_FILE_', 'disable');
}
";

}
