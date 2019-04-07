<?php

/**
 * Class CRM_Extension_Container_BasicTest
 * @group headless
 */
class CRM_Extension_Container_BasicTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }

  public function testGetKeysEmpty() {
    $basedir = $this->createTempDir('ext-empty-');
    $c = new CRM_Extension_Container_Basic($basedir, 'http://example/basedir', NULL, NULL);
    $this->assertEquals($c->getKeys(), array());
  }

  public function testGetKeys() {
    list($basedir, $c) = $this->_createContainer();
    $this->assertEquals($c->getKeys(), array('test.foo', 'test.foo.bar'));
  }

  public function testGetPath() {
    list($basedir, $c) = $this->_createContainer();
    try {
      $c->getPath('un.kno.wn');
    }
    catch (CRM_Extension_Exception $e) {
      $exc = $e;
    }
    $this->assertTrue(is_object($exc), 'Expected exception');

    $this->assertEquals("$basedir/foo", $c->getPath('test.foo'));
    $this->assertEquals("$basedir/foo/bar", $c->getPath('test.foo.bar'));
  }

  public function testGetPath_extraSlashFromConfig() {
    list($basedir, $c) = $this->_createContainer(NULL, NULL, '/');
    try {
      $c->getPath('un.kno.wn');
    }
    catch (CRM_Extension_Exception $e) {
      $exc = $e;
    }
    $this->assertTrue(is_object($exc), 'Expected exception');

    $this->assertEquals("$basedir/foo", $c->getPath('test.foo'));
    $this->assertEquals("$basedir/foo/bar", $c->getPath('test.foo.bar'));
  }

  public function testGetResUrl() {
    list($basedir, $c) = $this->_createContainer();
    try {
      $c->getResUrl('un.kno.wn');
    }
    catch (CRM_Extension_Exception $e) {
      $exc = $e;
    }
    $this->assertTrue(is_object($exc), 'Expected exception');

    $this->assertEquals('http://example/basedir/foo', $c->getResUrl('test.foo'));
    $this->assertEquals('http://example/basedir/foo/bar', $c->getResUrl('test.foo.bar'));
  }

  public function testGetResUrl_extraSlashFromConfig() {
    list($basedir, $c) = $this->_createContainer(NULL, NULL, '/');
    try {
      $c->getResUrl('un.kno.wn');
    }
    catch (CRM_Extension_Exception $e) {
      $exc = $e;
    }
    $this->assertTrue(is_object($exc), 'Expected exception');

    $this->assertEquals('http://example/basedir/foo', $c->getResUrl('test.foo'));
    $this->assertEquals('http://example/basedir/foo/bar', $c->getResUrl('test.foo.bar'));
  }

  public function testCaching() {
    $cache = new CRM_Utils_Cache_Arraycache(array());
    $this->assertTrue(!is_array($cache->get('basic-scan')));
    list($basedir, $c) = $this->_createContainer($cache, 'basic-scan');
    $this->assertEquals('http://example/basedir/foo', $c->getResUrl('test.foo'));
    $this->assertTrue(is_array($cache->get('basic-scan')));

    $cacheData = $cache->get('basic-scan');
    $this->assertEquals('/foo/bar', $cacheData['test.foo.bar']);
  }

  /**
   * @param CRM_Utils_Cache_Interface $cache
   * @param null $cacheKey
   * @param string $appendPathGarbage
   *
   * @return array
   */
  public function _createContainer(CRM_Utils_Cache_Interface $cache = NULL, $cacheKey = NULL, $appendPathGarbage = '') {
    $basedir = rtrim($this->createTempDir('ext-'), '/');
    mkdir("$basedir/foo");
    mkdir("$basedir/foo/bar");
    file_put_contents("$basedir/foo/info.xml", "<extension key='test.foo' type='module'><file>foo</file></extension>");
    // not needed for now // file_put_contents("$basedir/foo/foo.php", "<?php\n");
    file_put_contents("$basedir/foo/bar/info.xml", "<extension key='test.foo.bar' type='report'><file>oddball</file></extension>");
    // not needed for now // file_put_contents("$basedir/foo/bar/oddball.php", "<?php\n");
    $c = new CRM_Extension_Container_Basic($basedir . $appendPathGarbage, 'http://example/basedir' . $appendPathGarbage, $cache, $cacheKey);
    return array($basedir, $c);
  }

  public function testConvertPathsToUrls() {
    $relPaths = array(
      'foo.bar' => 'foo\bar',
      'whiz.bang' => 'tests\extensions\whiz\bang',
    );
    $expectedRelUrls = array(
      'foo.bar' => 'foo/bar',
      'whiz.bang' => 'tests/extensions/whiz/bang',
    );
    $actualRelUrls = CRM_Extension_Container_Basic::convertPathsToUrls('\\', $relPaths);
    $this->assertEquals($expectedRelUrls, $actualRelUrls);
  }

}
