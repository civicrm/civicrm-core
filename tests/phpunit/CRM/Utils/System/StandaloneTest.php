<?php

/**
 * @group headless
 */
class CRM_Utils_System_StandaloneTest extends CiviUnitTestCase {

  private $origAppRootPath;
  private $origCivicrmRoot;

  public function setUp(): void {
    parent::setUp();
    $this->origAppRootPath = $GLOBALS['appRootPath'] ?? NULL;
    $this->origCivicrmRoot = $GLOBALS['civicrm_root'];
  }

  public function tearDown(): void {
    $GLOBALS['appRootPath'] = $this->origAppRootPath;
    $GLOBALS['civicrm_root'] = $this->origCivicrmRoot;
    parent::tearDown();
  }

  public static function getLayouts(): array {
    return [
      'tarball' => ['core'],
      'composer' => ['vendor/civicrm/civicrm-core'],
    ];
  }

  /**
   * Without civicrm.standalone.php having run (e.g. cv with CIVICRM_SETTINGS),
   * cmsRootPath() must match the value the web entrypoint sets.
   *
   * @dataProvider getLayouts
   */
  public function testCmsRootPathWithoutBootFile(string $coreSubDir): void {
    $appRoot = realpath($this->createTempDir('standalone-root-'));
    mkdir("$appRoot/$coreSubDir", 0777, TRUE);
    file_put_contents("$appRoot/civicrm.standalone.php", "<?php\n");
    $userSystem = new CRM_Utils_System_Standalone();

    // Web: civicrm.standalone.php sets $appRootPath = __DIR__.
    $GLOBALS['appRootPath'] = $appRoot;
    $GLOBALS['civicrm_root'] = "$appRoot/$coreSubDir";
    $webValue = $userSystem->cmsRootPath();
    $this->assertEquals($appRoot, $webValue);

    // CLI: only civicrm.settings.php has run, so only $civicrm_root is known.
    $GLOBALS['appRootPath'] = NULL;
    $this->assertEquals($webValue, $userSystem->cmsRootPath());
  }

  public function testCmsRootPathWithoutBootFileOrMarker(): void {
    $GLOBALS['appRootPath'] = NULL;
    $GLOBALS['civicrm_root'] = $this->createTempDir('standalone-nomarker-') . '/core';
    $this->assertNull((new CRM_Utils_System_Standalone())->cmsRootPath());
  }

}
