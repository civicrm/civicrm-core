<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * Class CRM_Extension_ManagerTest
 * @group headless
 */
class CRM_Extension_ManagerTest extends CiviUnitTestCase {
  const TESTING_TYPE = 'report';
  const OTHER_TESTING_TYPE = 'module';

  /**
   * @var string
   */
  protected $basedir;

  /**
   * @var CRM_Extension_Container_Basic
   */
  protected $container;

  /**
   * @var CRM_Extension_Mapper
   */
  protected $mapper;

  public function setUp(): void {
    parent::setUp();
    list($this->basedir, $this->container) = $this->createContainer();
    $this->mapper = new CRM_Extension_Mapper($this->container);
  }

  /**
   * Install an extension with an invalid type name.
   */
  public function testInstallInvalidType(): void {
    $this->expectException(CRM_Extension_Exception::class);
    $testingTypeManager = $this->getMockBuilder('CRM_Extension_Manager_Interface')->getMock();
    $testingTypeManager->expects($this->never())
      ->method('onPreInstall');
    $manager = $this->_createManager([
      self::OTHER_TESTING_TYPE => $testingTypeManager,
    ]);
    $manager->install(['test.foo.bar']);
  }

  /**
   * Install an extension with a valid type name.
   *
   * Note: We initially install two extensions but then toggle only
   * the second. This controls for bad SQL queries which hit either
   * "the first row" or "all rows".
   */
  public function testInstall_Disable_Uninstall(): void {
    $testingTypeManager = $this->getMockBuilder('CRM_Extension_Manager_Interface')->getMock();
    $manager = $this->_createManager([
      self::TESTING_TYPE => $testingTypeManager,
    ]);
    $this->assertEquals('uninstalled', $manager->getStatus('test.foo.bar'));
    $this->assertEquals('uninstalled', $manager->getStatus('test.whiz.bang'));

    $testingTypeManager
      ->expects($this->exactly(2))
      ->method('onPreInstall');
    $testingTypeManager
      ->expects($this->exactly(2))
      ->method('onPostInstall');
    $manager->install(['test.whiz.bang', 'test.foo.bar']);
    $this->assertEquals('installed', $manager->getStatus('test.foo.bar'));
    $this->assertEquals('installed', $manager->getStatus('test.whiz.bang'));

    $testingTypeManager
      ->expects($this->once())
      ->method('onPreDisable');
    $testingTypeManager
      ->expects($this->once())
      ->method('onPostDisable');
    $manager->disable(['test.foo.bar']);
    $this->assertEquals('disabled', $manager->getStatus('test.foo.bar'));
    // no side-effect
    $this->assertEquals('installed', $manager->getStatus('test.whiz.bang'));

    $testingTypeManager
      ->expects($this->once())
      ->method('onPreUninstall');
    $testingTypeManager
      ->expects($this->once())
      ->method('onPostUninstall');
    $manager->uninstall(['test.foo.bar']);
    $this->assertEquals('uninstalled', $manager->getStatus('test.foo.bar'));
    // no side-effect
    $this->assertEquals('installed', $manager->getStatus('test.whiz.bang'));
  }

  /**
   * This is the same as testInstall_Disable_Uninstall, but we also install and remove a dependency.
   *
   * @throws \CRM_Extension_Exception
   */
  public function test_InstallAuto_DisableDownstream_UninstallDownstream(): void {
    $testingTypeManager = $this->getMockBuilder('CRM_Extension_Manager_Interface')->getMock();
    $manager = $this->_createManager([
      self::TESTING_TYPE => $testingTypeManager,
    ]);
    $this->assertEquals('uninstalled', $manager->getStatus('test.foo.bar'));
    $this->assertEquals('uninstalled', $manager->getStatus('test.foo.downstream'));
    $this->assertEquals('uninstalled', $manager->getStatus('test.whiz.bang'));

    $testingTypeManager->expects($this->exactly(2))->method('onPreInstall');
    $testingTypeManager->expects($this->exactly(2))->method('onPostInstall');
    $this->assertEquals(['test.foo.bar', 'test.foo.downstream'],
      $manager->findInstallRequirements(['test.foo.downstream']));
    $manager->install(
      $manager->findInstallRequirements(['test.foo.downstream']));
    $this->assertEquals('installed', $manager->getStatus('test.foo.bar'));
    $this->assertEquals('installed', $manager->getStatus('test.foo.downstream'));
    $this->assertEquals('uninstalled', $manager->getStatus('test.whiz.bang'));

    $testingTypeManager->expects($this->once())->method('onPreDisable');
    $testingTypeManager->expects($this->once())->method('onPostDisable');
    $this->assertEquals(['test.foo.downstream'],
      $manager->findDisableRequirements(['test.foo.downstream']));
    $manager->disable(['test.foo.downstream']);
    $this->assertEquals('installed', $manager->getStatus('test.foo.bar'));
    $this->assertEquals('disabled', $manager->getStatus('test.foo.downstream'));
    $this->assertEquals('uninstalled', $manager->getStatus('test.whiz.bang'));

    $testingTypeManager->expects($this->once())->method('onPreUninstall');
    $testingTypeManager->expects($this->once())->method('onPostUninstall');
    $manager->uninstall(['test.foo.downstream']);
    $this->assertEquals('installed', $manager->getStatus('test.foo.bar'));
    $this->assertEquals('uninstalled', $manager->getStatus('test.foo.downstream'));
    $this->assertEquals('uninstalled', $manager->getStatus('test.whiz.bang'));
  }

  /**
   * This is the same as testInstallAuto_Twice
   *
   * @throws \CRM_Extension_Exception
   */
  public function testInstallAuto_Twice(): void {
    $testingTypeManager = $this->getMockBuilder('CRM_Extension_Manager_Interface')->getMock();
    $manager = $this->_createManager([
      self::TESTING_TYPE => $testingTypeManager,
    ]);
    $this->assertEquals('uninstalled', $manager->getStatus('test.foo.bar'));
    $this->assertEquals('uninstalled', $manager->getStatus('test.foo.downstream'));
    $this->assertEquals('uninstalled', $manager->getStatus('test.whiz.bang'));

    $testingTypeManager->expects($this->exactly(2))->method('onPreInstall');
    $testingTypeManager->expects($this->exactly(2))->method('onPostInstall');
    $this->assertEquals(['test.foo.bar', 'test.foo.downstream'],
      $manager->findInstallRequirements(['test.foo.downstream']));
    $manager->install(
      $manager->findInstallRequirements(['test.foo.downstream']));
    $this->assertEquals('installed', $manager->getStatus('test.foo.bar'));
    $this->assertEquals('installed', $manager->getStatus('test.foo.downstream'));
    $this->assertEquals('uninstalled', $manager->getStatus('test.whiz.bang'));

    // And install a second time...
    $testingTypeManager->expects($this->exactly(0))->method('onPreInstall');
    $testingTypeManager->expects($this->exactly(0))->method('onPostInstall');
    $manager->install(
      $manager->findInstallRequirements(['test.foo.downstream']));
    $this->assertEquals('installed', $manager->getStatus('test.foo.bar'));
    $this->assertEquals('installed', $manager->getStatus('test.foo.downstream'));
    $this->assertEquals('uninstalled', $manager->getStatus('test.whiz.bang'));
  }

  public function test_InstallAuto_DisableUpstream(): void {
    $testingTypeManager = $this->getMockBuilder('CRM_Extension_Manager_Interface')->getMock();
    $manager = $this->_createManager([
      self::TESTING_TYPE => $testingTypeManager,
    ]);
    $this->assertEquals('uninstalled', $manager->getStatus('test.foo.bar'));
    $this->assertEquals('uninstalled', $manager->getStatus('test.foo.downstream'));
    $this->assertEquals('uninstalled', $manager->getStatus('test.whiz.bang'));

    $testingTypeManager->expects($this->exactly(2))->method('onPreInstall');
    $testingTypeManager->expects($this->exactly(2))->method('onPostInstall');
    $this->assertEquals(['test.foo.bar', 'test.foo.downstream'],
      $manager->findInstallRequirements(['test.foo.downstream']));
    $manager->install(
      $manager->findInstallRequirements(['test.foo.downstream']));
    $this->assertEquals('installed', $manager->getStatus('test.foo.bar'));
    $this->assertEquals('installed', $manager->getStatus('test.foo.downstream'));
    $this->assertEquals('uninstalled', $manager->getStatus('test.whiz.bang'));

    $testingTypeManager->expects($this->never())->method('onPreDisable');
    $testingTypeManager->expects($this->never())->method('onPostDisable');
    $this->assertEquals(['test.foo.downstream', 'test.foo.bar'],
      $manager->findDisableRequirements(['test.foo.bar']));

    try {
      $manager->disable(['test.foo.bar']);
      $this->fail('Expected disable to fail due to dependency');
    }
    catch (CRM_Extension_Exception $e) {
      $this->assertMatchesRegularExpression('/test.foo.downstream/', $e->getMessage());
    }

    // Status unchanged
    $this->assertEquals('installed', $manager->getStatus('test.foo.bar'));
    $this->assertEquals('installed', $manager->getStatus('test.foo.downstream'));
    $this->assertEquals('uninstalled', $manager->getStatus('test.whiz.bang'));
  }

  /**
   * Install an extension and then harshly remove the underlying source.
   * Subseuently disable and uninstall.
   */
  public function testInstall_DirtyRemove_Disable_Uninstall(): void {
    $testingTypeManager = $this->getMockBuilder('CRM_Extension_Manager_Interface')->getMock();
    $manager = $this->_createManager([
      self::TESTING_TYPE => $testingTypeManager,
    ]);
    $this->assertEquals('uninstalled', $manager->getStatus('test.foo.bar'));

    $manager->install(['test.foo.bar']);
    $this->assertEquals('installed', $manager->getStatus('test.foo.bar'));

    $this->assertTrue(file_exists("{$this->basedir}/weird/foobar/info.xml"));
    CRM_Utils_File::cleanDir("{$this->basedir}/weird/foobar", TRUE, FALSE);
    $this->assertFalse(file_exists("{$this->basedir}/weird/foobar/info.xml"));
    $manager->refresh();
    $this->assertEquals('installed-missing', $manager->getStatus('test.foo.bar'));

    $testingTypeManager
      ->expects($this->once())
      ->method('onPreDisable');
    $testingTypeManager
      ->expects($this->once())
      ->method('onPostDisable');
    $manager->disable(['test.foo.bar']);
    $this->assertEquals('disabled-missing', $manager->getStatus('test.foo.bar'));

    $testingTypeManager
      ->expects($this->once())
      ->method('onPreUninstall');
    $testingTypeManager
      ->expects($this->once())
      ->method('onPostUninstall');
    $manager->uninstall(['test.foo.bar']);
    $this->assertEquals('unknown', $manager->getStatus('test.foo.bar'));
  }

  /**
   * Install an extension with a valid type name.
   */
  public function testInstall_Disable_Enable(): void {
    $testingTypeManager = $this->getMockBuilder('CRM_Extension_Manager_Interface')->getMock();
    $manager = $this->_createManager([
      self::TESTING_TYPE => $testingTypeManager,
    ]);
    $this->assertEquals('uninstalled', $manager->getStatus('test.foo.bar'));
    $this->assertEquals('uninstalled', $manager->getStatus('test.whiz.bang'));

    $testingTypeManager
      ->expects($this->exactly(2))
      ->method('onPreInstall');
    $testingTypeManager
      ->expects($this->exactly(2))
      ->method('onPostInstall');
    $manager->install(['test.whiz.bang', 'test.foo.bar']);
    $this->assertEquals('installed', $manager->getStatus('test.foo.bar'));
    $this->assertEquals('installed', $manager->getStatus('test.whiz.bang'));

    $testingTypeManager
      ->expects($this->once())
      ->method('onPreDisable');
    $testingTypeManager
      ->expects($this->once())
      ->method('onPostDisable');
    $manager->disable(['test.foo.bar']);
    $this->assertEquals('disabled', $manager->getStatus('test.foo.bar'));
    $this->assertEquals('installed', $manager->getStatus('test.whiz.bang'));

    $testingTypeManager
      ->expects($this->once())
      ->method('onPreEnable');
    $testingTypeManager
      ->expects($this->once())
      ->method('onPostEnable');
    $manager->enable(['test.foo.bar']);
    $this->assertEquals('installed', $manager->getStatus('test.foo.bar'));
    $this->assertEquals('installed', $manager->getStatus('test.whiz.bang'));
  }

  /**
   * Performing 'install' on a 'disabled' extension performs an 'enable'
   */
  public function testInstall_Disable_Install(): void {
    $testingTypeManager = $this->getMockBuilder('CRM_Extension_Manager_Interface')->getMock();
    $manager = $this->_createManager([
      self::TESTING_TYPE => $testingTypeManager,
    ]);
    $this->assertEquals('uninstalled', $manager->getStatus('test.foo.bar'));

    $testingTypeManager
      ->expects($this->once())
      ->method('onPreInstall');
    $testingTypeManager
      ->expects($this->once())
      ->method('onPostInstall');
    $manager->install(['test.foo.bar']);
    $this->assertEquals('installed', $manager->getStatus('test.foo.bar'));

    $testingTypeManager
      ->expects($this->once())
      ->method('onPreDisable');
    $testingTypeManager
      ->expects($this->once())
      ->method('onPostDisable');
    $manager->disable(['test.foo.bar']);
    $this->assertEquals('disabled', $manager->getStatus('test.foo.bar'));

    $testingTypeManager
      ->expects($this->once())
      ->method('onPreEnable');
    $testingTypeManager
      ->expects($this->once())
      ->method('onPostEnable');
    // install() instead of enable()
    $manager->install(['test.foo.bar']);
    $this->assertEquals('installed', $manager->getStatus('test.foo.bar'));
  }

  /**
   * Install an extension with a valid type name.
   */
  public function testEnableBare(): void {
    $testingTypeManager = $this->getMockBuilder('CRM_Extension_Manager_Interface')->getMock();
    $manager = $this->_createManager([
      self::TESTING_TYPE => $testingTypeManager,
    ]);
    $this->assertEquals('uninstalled', $manager->getStatus('test.foo.bar'));

    $testingTypeManager
      ->expects($this->once())
      ->method('onPreInstall');
    $testingTypeManager
      ->expects($this->once())
      ->method('onPostInstall');
    $testingTypeManager
      ->expects($this->never())
      ->method('onPreEnable');
    $testingTypeManager
      ->expects($this->never())
      ->method('onPostEnable');
    // enable not install
    $manager->enable(['test.foo.bar']);
    $this->assertEquals('installed', $manager->getStatus('test.foo.bar'));
  }

  /**
   * Get the status of an unknown extension.
   */
  public function testStatusUnknownKey(): void {
    $testingTypeManager = $this->getMockBuilder('CRM_Extension_Manager_Interface')->getMock();
    $testingTypeManager->expects($this->never())
      ->method('onPreInstall');
    $manager = $this->_createManager([
      self::TESTING_TYPE => $testingTypeManager,
    ]);
    $this->assertEquals('unknown', $manager->getStatus('test.foo.bar.whiz.bang'));
  }

  /**
   * Replace code for an extension that doesn't exist in the container
   */
  public function testReplace_Unknown(): void {
    $testingTypeManager = $this->getMockBuilder('CRM_Extension_Manager_Interface')->getMock();
    $manager = $this->_createManager([
      self::TESTING_TYPE => $testingTypeManager,
    ]);
    $this->assertEquals('unknown', $manager->getStatus('test.newextension'));

    $download = $this->createDownload('test.newextension', 'newextension');

    $testingTypeManager
    // no data to replace
      ->expects($this->never())
      ->method('onPreReplace');
    $testingTypeManager
    // no data to replace
      ->expects($this->never())
      ->method('onPostReplace');
    $manager->replace($download);
    $this->assertEquals('uninstalled', $manager->getStatus('test.newextension'));
    $this->assertTrue(file_exists("{$this->basedir}/test.newextension/info.xml"));
    $this->assertTrue(file_exists("{$this->basedir}/test.newextension/newextension.php"));
    $this->assertEquals(self::TESTING_TYPE, $this->mapper->keyToInfo('test.newextension')->type);
    $this->assertEquals('newextension', $this->mapper->keyToInfo('test.newextension')->file);
  }

  /**
   * Replace code for an extension that doesn't exist in the container
   */
  public function testReplace_Uninstalled(): void {
    $testingTypeManager = $this->getMockBuilder('CRM_Extension_Manager_Interface')->getMock();
    $manager = $this->_createManager([
      self::TESTING_TYPE => $testingTypeManager,
    ]);
    $this->assertEquals('uninstalled', $manager->getStatus('test.whiz.bang'));
    $this->assertEquals('oddball', $this->mapper->keyToInfo('test.whiz.bang')->file);

    $download = $this->createDownload('test.whiz.bang', 'newextension');

    $testingTypeManager
    // no data to replace
      ->expects($this->never())
      ->method('onPreReplace');
    $testingTypeManager
    // no data to replace
      ->expects($this->never())
      ->method('onPostReplace');
    $manager->replace($download);
    $this->assertEquals('uninstalled', $manager->getStatus('test.whiz.bang'));
    $this->assertTrue(file_exists("{$this->basedir}/weird/whizbang/info.xml"));
    $this->assertTrue(file_exists("{$this->basedir}/weird/whizbang/newextension.php"));
    $this->assertFalse(file_exists("{$this->basedir}/weird/whizbang/oddball.php"));
    $this->assertEquals(self::TESTING_TYPE, $this->mapper->keyToInfo('test.whiz.bang')->type);
    $this->assertEquals('newextension', $this->mapper->keyToInfo('test.whiz.bang')->file);
  }

  /**
   * Install a module and then replace it with new code.
   *
   * Note that some metadata changes between versions -- the original has
   * file="oddball", and the upgrade has file="newextension".
   */
  public function testReplace_Installed(): void {
    $testingTypeManager = $this->getMockBuilder('CRM_Extension_Manager_Interface')->getMock();
    $manager = $this->_createManager([
      self::TESTING_TYPE => $testingTypeManager,
    ]);
    $this->assertEquals('uninstalled', $manager->getStatus('test.whiz.bang'));
    $this->assertEquals('oddball', $this->mapper->keyToInfo('test.whiz.bang')->file);

    $manager->install(['test.whiz.bang']);
    $this->assertEquals('installed', $manager->getStatus('test.whiz.bang'));
    $this->assertEquals('oddball', $this->mapper->keyToInfo('test.whiz.bang')->file);
    $this->assertDBQuery('oddball', 'SELECT file FROM civicrm_extension WHERE full_name ="test.whiz.bang"');

    $download = $this->createDownload('test.whiz.bang', 'newextension');

    $testingTypeManager
      ->expects($this->once())
      ->method('onPreReplace');
    $testingTypeManager
      ->expects($this->once())
      ->method('onPostReplace');
    $manager->replace($download);
    $this->assertEquals('installed', $manager->getStatus('test.whiz.bang'));
    $this->assertTrue(file_exists("{$this->basedir}/weird/whizbang/info.xml"));
    $this->assertTrue(file_exists("{$this->basedir}/weird/whizbang/newextension.php"));
    $this->assertFalse(file_exists("{$this->basedir}/weird/whizbang/oddball.php"));
    $this->assertEquals('newextension', $this->mapper->keyToInfo('test.whiz.bang')->file);
    $this->assertDBQuery('newextension', 'SELECT file FROM civicrm_extension WHERE full_name ="test.whiz.bang"');
  }

  public function testComponentExtensionSync(): void {
    CRM_Core_BAO_ConfigSetting::enableComponent('CiviCampaign');
    $this->assertEquals(CRM_Extension_Manager::STATUS_INSTALLED, CRM_Extension_System::singleton()->getManager()->getStatus('civi_campaign'));
    CRM_Core_BAO_ConfigSetting::disableComponent('CiviCampaign');
    $this->assertEquals(CRM_Extension_Manager::STATUS_DISABLED, CRM_Extension_System::singleton()->getManager()->getStatus('civi_campaign'));
    $this->assertFalse(CRM_Core_Component::isEnabled('CiviCampaign'));
    CRM_Extension_System::singleton()->getManager()->install('civi_campaign');
    $this->assertTrue(CRM_Core_Component::isEnabled('CiviCampaign'));
    CRM_Extension_System::singleton()->getManager()->disable('civi_campaign');
    $this->assertFalse(CRM_Core_Component::isEnabled('CiviCampaign'));
  }

  /**
   * Install a module and then delete (leaving stale DB info); restore
   * the module by downloading new code.
   *
   * Note that some metadata changes between versions -- the original has
   * file="oddball", and the upgrade has file="newextension".
   */
  public function testReplace_InstalledMissing(): void {
    $testingTypeManager = $this->getMockBuilder('CRM_Extension_Manager_Interface')->getMock();
    $manager = $this->_createManager([
      self::TESTING_TYPE => $testingTypeManager,
    ]);

    // initial installation
    $this->assertEquals('uninstalled', $manager->getStatus('test.whiz.bang'));
    $manager->install(['test.whiz.bang']);
    $this->assertEquals('installed', $manager->getStatus('test.whiz.bang'));

    // dirty remove
    $this->assertTrue(file_exists("{$this->basedir}/weird/whizbang/info.xml"));
    CRM_Utils_File::cleanDir("{$this->basedir}/weird/whizbang", TRUE, FALSE);
    $this->assertFalse(file_exists("{$this->basedir}/weird/whizbang/info.xml"));
    $manager->refresh();
    $this->assertEquals('installed-missing', $manager->getStatus('test.whiz.bang'));

    // download and reinstall
    $download = $this->createDownload('test.whiz.bang', 'newextension');

    $testingTypeManager
      ->expects($this->once())
      ->method('onPreReplace');
    $testingTypeManager
      ->expects($this->once())
      ->method('onPostReplace');
    $manager->replace($download);
    $this->assertEquals('installed', $manager->getStatus('test.whiz.bang'));
    $this->assertTrue(file_exists("{$this->basedir}/test.whiz.bang/info.xml"));
    $this->assertTrue(file_exists("{$this->basedir}/test.whiz.bang/newextension.php"));
    $this->assertEquals('newextension', $this->mapper->keyToInfo('test.whiz.bang')->file);
    $this->assertDBQuery('newextension', 'SELECT file FROM civicrm_extension WHERE full_name ="test.whiz.bang"');
  }

  /**
   * @param $typeManagers
   *
   * @return CRM_Extension_Manager
   */
  public function _createManager($typeManagers) {
    return new CRM_Extension_Manager($this->container, $this->container, $this->mapper, $typeManagers);
  }

  /**
   * @return array
   */
  private function createContainer() {
    $basedir = $this->createTempDir('ext-');
    mkdir("$basedir/weird");
    mkdir("$basedir/weird/foobar");
    file_put_contents("$basedir/weird/foobar/info.xml", "<extension key='test.foo.bar' type='" . self::TESTING_TYPE . "'><file>oddball</file></extension>");
    // not needed for now // file_put_contents("$basedir/weird/bar/oddball.php", "<?php\n");
    mkdir("$basedir/weird/whizbang");
    file_put_contents("$basedir/weird/whizbang/info.xml", "<extension key='test.whiz.bang' type='" . self::TESTING_TYPE . "'><file>oddball</file></extension>");
    // not needed for now // file_put_contents("$basedir/weird/whizbang/oddball.php", "<?php\n");
    mkdir("$basedir/weird/downstream");
    file_put_contents("$basedir/weird/downstream/info.xml", "<extension key='test.foo.downstream' type='" . self::TESTING_TYPE . "'><file>oddball</file><requires><ext>test.foo.bar</ext></requires></extension>");
    // not needed for now // file_put_contents("$basedir/weird/downstream/oddball.php", "<?php\n");
    $c = new CRM_Extension_Container_Basic($basedir, 'http://example/basedir', NULL, NULL);
    return [$basedir, $c];
  }

  /**
   * @param string $key
   * @param string $file
   *
   * @return string
   */
  private function createDownload($key, $file) {
    $basedir = $this->createTempDir('ext-dl-');
    file_put_contents("$basedir/info.xml", "<extension key='$key' type='" . self::TESTING_TYPE . "'><file>$file</file></extension>");
    file_put_contents("$basedir/$file.php", "<?php\n");
    return $basedir;
  }

}
