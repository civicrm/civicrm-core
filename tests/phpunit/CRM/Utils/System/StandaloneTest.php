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

  public function testRestrictPrivateDirWritesHtaccessInsideDir(): void {
    $dir = rtrim($this->createTempDir('private-'), '/');

    CRM_Utils_System_Standalone::restrictPrivateDir($dir);

    $this->assertStringContainsString('Require all denied', file_get_contents("$dir/.htaccess"));
    $this->assertFileDoesNotExist("$dir.htaccess");
  }

  public function testRestrictPrivateDirKeepsExistingHtaccess(): void {
    $dir = rtrim($this->createTempDir('private-'), '/');
    file_put_contents("$dir/.htaccess", 'custom');

    CRM_Utils_System_Standalone::restrictPrivateDir($dir);

    $this->assertSame('custom', file_get_contents("$dir/.htaccess"));
  }

  public function testRestrictPrivateDirSkipsReadOnlyDir(): void {
    $dir = rtrim($this->createTempDir('private-'), '/');
    chmod($dir, 0555);
    if (is_writable($dir)) {
      chmod($dir, 0755);
      $this->markTestSkipped('Permissions are not enforced for this user.');
    }

    CRM_Utils_System_Standalone::restrictPrivateDir($dir);
    chmod($dir, 0755);

    $this->assertFileDoesNotExist("$dir/.htaccess");
  }

}
