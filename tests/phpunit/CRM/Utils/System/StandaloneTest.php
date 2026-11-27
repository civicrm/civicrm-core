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
 * @group headless
 */
class CRM_Utils_System_StandaloneTest extends CiviUnitTestCase {

  private ?\Civi\Core\Paths $originalPaths = NULL;

  private ?array $originalPathGlobals = NULL;

  public function tearDown(): void {
    if ($this->originalPaths) {
      \Civi::$statics[\Civi\Core\Container::class]['boot']['paths'] = $this->originalPaths;
      $GLOBALS['civicrm_paths'] = $this->originalPathGlobals;
    }
    parent::tearDown();
  }

  public function testPostContainerBootRestrictsPrivateDir(): void {
    $dir = $this->usePrivateDir();

    (new CRM_Utils_System_Standalone())->postContainerBoot();

    $this->assertStringContainsString('Require all denied', file_get_contents("$dir/.htaccess"));
    $this->assertFileDoesNotExist("$dir.htaccess");
  }

  public function testPostContainerBootKeepsExistingHtaccess(): void {
    $dir = $this->usePrivateDir();
    file_put_contents("$dir/.htaccess", 'custom');

    (new CRM_Utils_System_Standalone())->postContainerBoot();

    $this->assertSame('custom', file_get_contents("$dir/.htaccess"));
  }

  public function testPostContainerBootSkipsReadOnlyPrivateDir(): void {
    $dir = $this->usePrivateDir();
    chmod($dir, 0555);
    try {
      $this->assertFalse(is_writable($dir), 'The test needs a user that honours directory permissions.');
      (new CRM_Utils_System_Standalone())->postContainerBoot();
    }
    finally {
      chmod($dir, 0755);
    }

    $this->assertFileDoesNotExist("$dir/.htaccess");
  }

  /**
   * Point [civicrm.private] at a fresh temp dir (without trailing slash).
   */
  private function usePrivateDir(): string {
    $dir = rtrim($this->createTempDir('private-'), '/');
    $this->originalPaths = \Civi::paths();
    $this->originalPathGlobals = $GLOBALS['civicrm_paths'] ?? NULL;
    $GLOBALS['civicrm_paths']['civicrm.private']['path'] = $dir;
    \Civi::$statics[\Civi\Core\Container::class]['boot']['paths'] = new \Civi\Core\Paths();
    $this->assertSame($dir, \Civi::paths()->getPath('[civicrm.private]/.'));
    return $dir;
  }

}
