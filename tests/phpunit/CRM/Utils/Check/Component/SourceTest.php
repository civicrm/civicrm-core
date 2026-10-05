<?php

/**
 * Class CRM_Utils_Check_Component_SourceTest
 * @package CiviCRM
 * @subpackage CRM_Utils_Check
 * @group headless
 */
class CRM_Utils_Check_Component_SourceTest extends CiviUnitTestCase {

  /**
   * Test that on a clean repository, checkOrphans returns no messages.
   */
  public function testCheckOrphansEmptyOnCleanRepo(): void {
    $check = new CRM_Utils_Check_Component_Source();
    $messages = $check->checkOrphans();
    $this->assertEmpty($messages);
  }

  /**
   * Test case sensitivity when detecting orphaned files and directories.
   */
  public function testFindOrphanedFilesCaseSensitivity(): void {
    $mockCheck = new class() extends CRM_Utils_Check_Component_Source {
      public array $mockFiles = [];

      public function getRemovedFiles() {
        return $this->mockFiles;
      }

    };

    $root = rtrim(Civi::paths()->getPath('[civicrm.root]/'), '/');

    // 1. A path that differs only by case in a directory segment should NOT match
    $mockCheck->mockFiles = [
      'ext/civiimport/Managed/*',
      'ext/civiimport/Managed/ImportSearches.mgd.php',
    ];
    $this->assertEmpty($mockCheck->findOrphanedFiles());

    // 2. An actual file with matching case should be detected
    $testRelFile = 'CRM/Utils/Check/test_orphan_dummy.php';
    $testAbsFile = $root . '/' . $testRelFile;
    file_put_contents($testAbsFile, '<?php // dummy');

    try {
      $mockCheck->mockFiles = [$testRelFile];
      $orphans = $mockCheck->findOrphanedFiles();
      $this->assertCount(1, $orphans);
      $this->assertEquals($testAbsFile, $orphans[0]['path']);
      $this->assertEquals($testRelFile, $orphans[0]['name']);

      // But if the casing is different (e.g. CRM/UTILS/...), it should NOT be detected
      $mockCheck->mockFiles = ['CRM/UTILS/Check/test_orphan_dummy.php'];
      $this->assertEmpty($mockCheck->findOrphanedFiles());
    }
    finally {
      if (file_exists($testAbsFile)) {
        unlink($testAbsFile);
      }
    }
  }

}
