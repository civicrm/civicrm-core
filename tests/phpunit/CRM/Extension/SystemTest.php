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

  public function testCacheIsSharedWhenOnlyUrlsDiffer(): void {
    $extensionsDir = $this->createTempDir('ext-cache-');
    $webParams = [
      'extensionsDir' => $extensionsDir,
      'extensionsURL' => 'https://example.org/ext',
      'resourceBase' => 'https://example.org/core',
      'userFrameworkBaseURL' => 'https://example.org/',
    ];
    $cliParams = [
      'extensionsDir' => $extensionsDir,
      'extensionsURL' => 'http://localhost/ext',
      'resourceBase' => 'http://localhost/core',
      'userFrameworkBaseURL' => 'http://localhost/',
    ];

    // Each new system stands in for a separate process, with no in-memory copy of the cache.
    (new CRM_Extension_System($webParams))->getCache()->set('probe', 'set by web');
    $this->assertEquals('set by web', (new CRM_Extension_System($cliParams))->getCache()->get('probe'));

    (new CRM_Extension_System($cliParams))->getCache()->flush();
    $this->assertNull((new CRM_Extension_System($webParams))->getCache()->get('probe'));
  }

  public function testCacheIsSeparateForDifferentExtensionsDirs(): void {
    $siteA = new CRM_Extension_System(['extensionsDir' => $this->createTempDir('ext-cache-a-')]);
    $siteB = new CRM_Extension_System(['extensionsDir' => $this->createTempDir('ext-cache-b-')]);

    $siteA->getCache()->set('probe', 'set by site A');
    $this->assertNull($siteB->getCache()->get('probe'));
  }

}
