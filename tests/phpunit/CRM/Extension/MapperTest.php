<?php

/**
 * Class CRM_Extension_MapperTest
 * @group headless
 */
class CRM_Extension_MapperTest extends CiviUnitTestCase {

  /**
   * @var string
   */
  protected $basedir;

  /**
   * @var string
   */
  protected $basedir2;

  /**
   * @var CRM_Extension_Container_Interface
   */
  protected $container;

  /**
   * @var CRM_Extension_Container_Interface
   */
  protected $containerWithSlash;

  /**
   * @var CRM_Extension_Mapper
   */
  protected $mapper;

  /**
   * @var CRM_Extension_Mapper
   */
  protected $mapperWithSlash;

  public function setUp(): void {
    parent::setUp();
    list ($this->basedir, $this->container) = $this->_createContainer();
    $this->mapper = new CRM_Extension_Mapper($this->container);

    list ($this->basedir2, $this->containerWithSlash) = $this->_createContainer(NULL, NULL, '/');
    $this->mapperWithSlash = new CRM_Extension_Mapper($this->containerWithSlash);
  }

  public function tearDown(): void {
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_extension WHERE full_name IN ('test.foo.bar', 'test.evil.escape')");
    parent::tearDown();
  }

  public function testClassToKey(): void {
    $this->assertSame("test.foo.bar", $this->mapper->classToKey('test_foo_bar'));
  }

  public function testIsExtensionKey(): void {
    $this->assertFalse($this->mapper->isExtensionKey('test_foo_bar'));
    $this->assertTrue($this->mapper->isExtensionKey('test.foo.bar'));
    $this->assertTrue($this->mapper->isExtensionKey('org.civicrm.afform-gui'));
    $this->assertFalse($this->mapper->isExtensionKey('CRM_Core_DAO'));
    $this->assertFalse($this->mapper->isExtensionKey('../../etc/passwd.php'));
    $this->assertFalse($this->mapper->isExtensionKey('../test.foo.bar'));
    $this->assertFalse($this->mapper->isExtensionKey('phar://evil.phar'));
    $this->assertFalse($this->mapper->isExtensionKey("test.foo.bar\n"));
  }

  public function testGetLegacyExtensionPath(): void {
    file_put_contents("{$this->basedir}/weird/foobar/oddball.php", "<?php\n");

    // Uninstalled extension on disk must not resolve.
    $this->assertNull($this->mapper->getLegacyExtensionPath('test.foo.bar', 'report'));

    // Path traversal / invalid keys must not resolve.
    $this->assertNull($this->mapper->getLegacyExtensionPath('../../etc/passwd', 'report'));
    $this->assertNull($this->mapper->getLegacyExtensionPath('../test.foo.bar', 'report'));

    // Disabled extension in civicrm_extension must not resolve.
    $dao = new CRM_Core_DAO_Extension();
    $dao->type = 'report';
    $dao->full_name = 'test.foo.bar';
    $dao->name = 'test.foo.bar';
    $dao->label = 'Test Foo Bar';
    $dao->file = 'oddball';
    $dao->is_active = 0;
    $dao->save();
    $this->assertNull($this->mapper->getLegacyExtensionPath('test.foo.bar', 'report'));

    // Enabled extension resolves only for its exact legacy type.
    $dao->is_active = 1;
    $dao->save();
    $this->assertNull($this->mapper->getLegacyExtensionPath('test.foo.bar', 'payment'));
    $this->assertNull($this->mapper->getLegacyExtensionPath('test.foo.bar', 'module'));
    $this->assertSame(
      "{$this->basedir}/weird/foobar/oddball.php",
      $this->mapper->getLegacyExtensionPath('test.foo.bar', 'report')
    );

    // Malformed <file> attempting path traversal must not resolve.
    mkdir("{$this->basedir}/weird/escape");
    file_put_contents("{$this->basedir}/weird/secret.php", "<?php\n");
    file_put_contents(
      "{$this->basedir}/weird/escape/info.xml",
      "<extension key='test.evil.escape' type='report'><file>../secret</file></extension>"
    );
    $this->container->refresh();
    $evilDao = new CRM_Core_DAO_Extension();
    $evilDao->type = 'report';
    $evilDao->full_name = 'test.evil.escape';
    $evilDao->name = 'test.evil.escape';
    $evilDao->label = 'Evil Escape';
    $evilDao->file = '../secret';
    $evilDao->is_active = 1;
    $evilDao->save();
    $this->assertNull($this->mapper->getLegacyExtensionPath('test.evil.escape', 'report'));
  }

  public function testKeyToClass(): void {
    $this->assertSame("test_foo_bar", $this->mapper->keyToClass('test.foo.bar'));
  }

  public function testKeyToBasePath(): void {
    $this->assertSame("{$this->basedir}/weird/foobar", $this->mapper->keyToBasePath('test.foo.bar'));
    $this->assertSame("{$this->basedir2}/weird/foobar", $this->mapperWithSlash->keyToBasePath('test.foo.bar'));

    global $civicrm_root;
    $this->assertEquals(rtrim($civicrm_root, '/'), $this->mapper->keyToBasePath('civicrm'));
  }

  public function testKeyToUrl(): void {
    $this->assertSame("http://example/basedir/weird/foobar", $this->mapper->keyToUrl('test.foo.bar'));
    $this->assertSame("http://example/basedir/weird/foobar", $this->mapperWithSlash->keyToUrl('test.foo.bar'));

    $config = CRM_Core_Config::singleton();
    $this->assertEquals(rtrim($config->resourceBase, '/'), $this->mapper->keyToUrl('civicrm'));
    $this->assertEquals(rtrim($config->resourceBase, '/'), $this->mapperWithSlash->keyToUrl('civicrm'));
  }

  public function testGetKeysByPath(): void {
    $mappers = [
      $this->basedir => $this->mapper,
      $this->basedir2 => $this->mapperWithSlash,
    ];
    foreach ($mappers as $basedir => $mapper) {
      /** @var CRM_Extension_Mapper $mapper */
      $this->assertEquals([], $mapper->getKeysByPath($basedir));
      $this->assertEquals([], $mapper->getKeysByPath($basedir . '/weird'));
      $this->assertEquals([], $mapper->getKeysByPath($basedir . '/weird/'));
      $this->assertEquals([], $mapper->getKeysByPath($basedir . '/weird//'));
      $this->assertEquals(['test.foo.bar'], $mapper->getKeysByPath($basedir . '/*'));
      $this->assertEquals(['test.foo.bar'], $mapper->getKeysByPath($basedir . '//*'));
      $this->assertEquals(['test.foo.bar'], $mapper->getKeysByPath($basedir . '/weird/*'));
      $this->assertEquals(['test.foo.bar'], $mapper->getKeysByPath($basedir . '/weird/foobar'));
      $this->assertEquals(['test.foo.bar'], $mapper->getKeysByPath($basedir . '/weird/foobar/'));
      $this->assertEquals(['test.foo.bar'], $mapper->getKeysByPath($basedir . '/weird/foobar//'));
      $this->assertEquals(['test.foo.bar'], $mapper->getKeysByPath($basedir . '/weird/foobar/*'));
    }
  }

  public function testGetKeysByTag(): void {
    $this->assertEquals([], $this->mapper->getKeysByTag('big-rock-candy-mountain'));
    $this->assertEquals(['test.foo.bar'], $this->mapper->getKeysByTag('wakka'));
  }

  /**
   * @param CRM_Utils_Cache_Interface|null $cache
   * @param null $cacheKey
   * @param string $appendPathGarbage
   *
   * @return array
   */
  public function _createContainer(?CRM_Utils_Cache_Interface $cache = NULL, $cacheKey = NULL, $appendPathGarbage = '') {
    /*
    $container = new CRM_Extension_Container_Static(array(
    'test.foo.bar' => array(
    'path' => '/path/to/test.foo.bar',
    'resUrl' => 'http://resources/test.foo.bar',
    ),
    ));
     */
    $basedir = rtrim($this->createTempDir('ext-'), '/');
    mkdir("$basedir/weird");
    mkdir("$basedir/weird/foobar");
    file_put_contents("$basedir/weird/foobar/info.xml", "<extension key='test.foo.bar' type='report'><file>oddball</file><tags><tag>wakka</tag></tags></extension>");
    // not needed for now // file_put_contents("$basedir/weird/bar/oddball.php", "<?php\n");
    $c = new CRM_Extension_Container_Basic($basedir . $appendPathGarbage, 'http://example/basedir' . $appendPathGarbage, $cache, $cacheKey);
    return [$basedir, $c];
  }

}
