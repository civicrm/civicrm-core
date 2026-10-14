<?php

/**
 * Class CRM_Extension_SystemTest
 * @group headless
 */
class CRM_Extension_SystemTest extends CiviUnitTestCase {

  public function testDefaultContainerHonoursMaxDepth(): void {
    $basedir = rtrim($this->createTempDir('ext-depth-'), '/');
    mkdir("$basedir/shallow");
    file_put_contents("$basedir/shallow/info.xml", "<extension key='test.shallow' type='module'><file>shallow</file></extension>");
    mkdir("$basedir/a/b/c/deep", 0777, TRUE);
    file_put_contents("$basedir/a/b/c/deep/info.xml", "<extension key='test.deep' type='module'><file>deep</file></extension>");

    $system = new CRM_Extension_System([
      'extensionsDir' => $basedir,
      'extensionsURL' => 'http://example/basedir',
      'maxDepth' => 2,
    ]);
    $keys = $system->getDefaultContainer()->getKeys();
    $this->assertContains('test.shallow', $keys);
    $this->assertNotContains('test.deep', $keys);
  }

}
