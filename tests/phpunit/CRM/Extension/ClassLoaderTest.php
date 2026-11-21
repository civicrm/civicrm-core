<?php

/**
 * Class CRM_Extension_ClassLoaderTest
 * @group headless
 */
class CRM_Extension_ClassLoaderTest extends CiviUnitTestCase {

  private ?CRM_Extension_ClassLoader $classLoader = NULL;

  public function tearDown(): void {
    $this->classLoader?->unregister();
    parent::tearDown();
  }

  public function testCacheFileIncludesExtensionAddedAfterScan(): void {
    $basedir = rtrim($this->createTempDir('ext-'), '/');
    $container = new CRM_Extension_Container_Basic($basedir, 'http://example/basedir', new CRM_Utils_Cache_ArrayCache([]), 'basic');
    // Prime the cache with a scan that predates the extension.
    $this->assertEquals([], $container->getKeys());

    mkdir("$basedir/foo/src", 0777, TRUE);
    file_put_contents("$basedir/foo/info.xml", '<extension key="test.foo" type="module"><file>foo</file><classloader><psr4 prefix="Civi\\TestFoo\\" path="src"/></classloader></extension>');

    $manager = $this->createMock(CRM_Extension_Manager::class);
    $manager->method('getStatuses')->willReturn(['test.foo' => CRM_Extension_Manager::STATUS_INSTALLED]);
    $cacheFile = $this->createTempDir('compile-') . '/CachedExtLoader.php';
    $this->classLoader = $this->getMockBuilder(CRM_Extension_ClassLoader::class)
      ->setConstructorArgs([new CRM_Extension_Mapper($container), $container, $manager])
      ->onlyMethods(['shouldUseCacheFile', 'getCacheFile'])
      ->getMock();
    $this->classLoader->method('shouldUseCacheFile')->willReturn(TRUE);
    $this->classLoader->method('getCacheFile')->willReturn($cacheFile);
    $this->classLoader->register();

    $cachedLoader = require $cacheFile;
    $this->assertEquals(["$basedir/foo/src"], $cachedLoader->getPrefixesPsr4()['Civi\\TestFoo\\']);
  }

}
