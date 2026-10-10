<?php
namespace Civi\Setup;

/**
 * Class StandaloneScaffoldTest
 * @package Civi\Setup
 * @group headless
 */
class StandaloneScaffoldTest extends \CiviUnitTestCase {

  /**
   * The private directory gets its own .htaccess that denies all web access.
   */
  public function testPrivateDirectoryIsDenied(): void {
    $dir = $this->createTempDir('scaffold-');
    StandaloneScaffold::create(['scaffold-dir' => $dir, 'scaffold-mode' => 'copy']);

    $this->assertFileExists("$dir/.htaccess");
    $this->assertFileExists("$dir/private/.htaccess");
    $this->assertStringContainsString('Require all denied', file_get_contents("$dir/private/.htaccess"));
  }

}
