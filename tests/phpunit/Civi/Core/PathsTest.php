<?php

namespace Civi\Core;

/**
 * Class PathsTest
 * @package Civi\Core
 * @group headless
 */
class PathsTest extends \CiviUnitTestCase {

  public function getExamples() {
    $exs = [];

    // Ensure that various permutations of `$civicrm_paths`, `Civi::paths()->getPath()`
    // and `Civi::paths()->getUrl()` work as expected.

    // Trailing-slash configurations -- these all worked before current patch

    $exs[] = ['te.st', 'path', '/var/www/files/', '[te.st]/foo/bar', '/var/www/files/foo/bar'];
    $exs[] = ['te.st', 'path', '/var/www/files/', '[te.st]/foo/', '/var/www/files/foo/'];
    $exs[] = ['te.st', 'path', '/var/www/files/', '[te.st]/foo', '/var/www/files/foo'];
    $exs[] = ['te.st', 'path', '/var/www/files/', '[te.st]/.', '/var/www/files/'];

    $exs[] = ['te.st', 'url', 'http://example.com/files/', '[te.st]/foo/bar', 'http://example.com/files/foo/bar'];
    $exs[] = ['te.st', 'url', 'http://example.com/files/', '[te.st]/foo/', 'http://example.com/files/foo/'];
    $exs[] = ['te.st', 'url', 'http://example.com/files/', '[te.st]/foo', 'http://example.com/files/foo'];
    $exs[] = ['te.st', 'url', 'http://example.com/files/', '[te.st]/.', 'http://example.com/files/'];

    $exs[] = ['te.st', 'url', 'http://example.com:8080/', '[te.st]/foo/bar', 'http://example.com:8080/foo/bar'];
    $exs[] = ['te.st', 'url', 'http://example.com:8080/', '[te.st]/foo/', 'http://example.com:8080/foo/'];
    $exs[] = ['te.st', 'url', 'http://example.com:8080/', '[te.st]/foo', 'http://example.com:8080/foo'];
    $exs[] = ['te.st', 'url', 'http://example.com:8080/', '[te.st]/.', 'http://example.com:8080/'];

    // Trimmed-slash configurations -- some of these worked before, and some misbehaved. Now fixed.

    $exs[] = ['te.st', 'path', '/var/www/files', '[te.st]/foo/bar', '/var/www/files/foo/bar'];
    $exs[] = ['te.st', 'path', '/var/www/files', '[te.st]/foo/', '/var/www/files/foo/'];
    $exs[] = ['te.st', 'path', '/var/www/files', '[te.st]/foo', '/var/www/files/foo'];
    $exs[] = ['te.st', 'path', '/var/www/files', '[te.st]/.', '/var/www/files/'];

    $exs[] = ['te.st', 'url', 'http://example.com/files', '[te.st]/foo/bar', 'http://example.com/files/foo/bar'];
    $exs[] = ['te.st', 'url', 'http://example.com/files', '[te.st]/foo/', 'http://example.com/files/foo/'];
    $exs[] = ['te.st', 'url', 'http://example.com/files', '[te.st]/foo', 'http://example.com/files/foo'];
    $exs[] = ['te.st', 'url', 'http://example.com/files', '[te.st]/.', 'http://example.com/files/'];

    $exs[] = ['te.st', 'url', 'http://example.com:8080', '[te.st]/foo/bar', 'http://example.com:8080/foo/bar'];
    $exs[] = ['te.st', 'url', 'http://example.com:8080', '[te.st]/foo/', 'http://example.com:8080/foo/'];
    $exs[] = ['te.st', 'url', 'http://example.com:8080', '[te.st]/foo', 'http://example.com:8080/foo'];
    $exs[] = ['te.st', 'url', 'http://example.com:8080', '[te.st]/.', 'http://example.com:8080/'];

    return $exs;
  }

  /**
   * @param $varName
   * @param $varType
   * @param $varValue
   * @param $inputExpr
   * @param $expectValue
   * @dataProvider getExamples
   */
  public function testExamples($varName, $varType, $varValue, $inputExpr, $expectValue) {
    global $civicrm_paths;
    $civicrm_paths[$varName][$varType] = $varValue;
    $func = ($varType === 'url') ? 'getUrl' : 'getPath';

    $paths = new Paths();
    $paths->register($varName, function() {
      return ['path' => 'FIXME-PATH', 'url' => 'FIXME-URL'];
    });

    $actualValue = call_user_func([$paths, $func], $inputExpr);
    $this->assertEquals($expectValue, $actualValue);

    unset($civicrm_paths[$varName][$varType]);
  }

  public function testGetUrl_ImplicitBase() {
    $p = \Civi::paths();
    $cmsRoot = rtrim($p->getVariable('cms.root', 'url'), '/');

    $this->assertEquals("$cmsRoot/foo/bar", $p->getUrl('foo/bar'));
    $this->assertEquals("$cmsRoot/foo/", $p->getUrl('foo/'));
    $this->assertEquals("$cmsRoot/foo", $p->getUrl('foo'));
  }

}
