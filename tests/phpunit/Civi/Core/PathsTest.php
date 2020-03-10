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

    // Trailing-slash configurations

    $exs['ap1'] = ['te.st', 'path', '/var/www/files/', '[te.st]/foo/bar', '/var/www/files/foo/bar'];
    $exs['ap2'] = ['te.st', 'path', '/var/www/files/', '[te.st]/foo/', '/var/www/files/foo/'];
    $exs['ap3'] = ['te.st', 'path', '/var/www/files/', '[te.st]/foo', '/var/www/files/foo'];
    $exs['ap4'] = ['te.st', 'path', '/var/www/files/', '[te.st]/.', '/var/www/files'];
    $exs['ap5'] = ['te.st', 'path', '/var/www/files/', '[te.st]/0', '/var/www/files/0'];
    $exs['ap6'] = ['te.st', 'path', '/var/www/files/', '[te.st]/', '/var/www/files/'];

    $exs['au1'] = ['te.st', 'url', 'http://example.com/files/', '[te.st]/foo/bar', 'http://example.com/files/foo/bar'];
    $exs['au2'] = ['te.st', 'url', 'http://example.com/files/', '[te.st]/foo/', 'http://example.com/files/foo/'];
    $exs['au3'] = ['te.st', 'url', 'http://example.com/files/', '[te.st]/foo', 'http://example.com/files/foo'];
    $exs['au4'] = ['te.st', 'url', 'http://example.com/files/', '[te.st]/.', 'http://example.com/files'];
    $exs['au5'] = ['te.st', 'url', 'http://example.com/files/', '[te.st]/0', 'http://example.com/files/0'];
    $exs['au6'] = ['te.st', 'url', 'http://example.com/files/', '[te.st]/', 'http://example.com/files/'];

    $exs['au18'] = ['te.st', 'url', 'http://example.com:8080/', '[te.st]/foo/bar', 'http://example.com:8080/foo/bar'];
    $exs['au28'] = ['te.st', 'url', 'http://example.com:8080/', '[te.st]/foo/', 'http://example.com:8080/foo/'];
    $exs['au38'] = ['te.st', 'url', 'http://example.com:8080/', '[te.st]/foo', 'http://example.com:8080/foo'];
    $exs['au48'] = ['te.st', 'url', 'http://example.com:8080/', '[te.st]/.', 'http://example.com:8080'];
    $exs['au58'] = ['te.st', 'url', 'http://example.com:8080/', '[te.st]/0', 'http://example.com:8080/0'];
    $exs['au68'] = ['te.st', 'url', 'http://example.com:8080/', '[te.st]/', 'http://example.com:8080/'];

    // Trimmed-slash configurations

    $exs['bp1'] = ['te.st', 'path', '/var/www/files', '[te.st]/foo/bar', '/var/www/files/foo/bar'];
    $exs['bp2'] = ['te.st', 'path', '/var/www/files', '[te.st]/foo/', '/var/www/files/foo/'];
    $exs['bp3'] = ['te.st', 'path', '/var/www/files', '[te.st]/foo', '/var/www/files/foo'];
    $exs['bp4'] = ['te.st', 'path', '/var/www/files', '[te.st]/.', '/var/www/files'];
    $exs['bp5'] = ['te.st', 'path', '/var/www/files', '[te.st]/0', '/var/www/files/0'];
    $exs['bp6'] = ['te.st', 'path', '/var/www/files', '[te.st]/', '/var/www/files/'];

    $exs['bu1'] = ['te.st', 'url', 'http://example.com/files', '[te.st]/foo/bar', 'http://example.com/files/foo/bar'];
    $exs['bu2'] = ['te.st', 'url', 'http://example.com/files', '[te.st]/foo/', 'http://example.com/files/foo/'];
    $exs['bu3'] = ['te.st', 'url', 'http://example.com/files', '[te.st]/foo', 'http://example.com/files/foo'];
    $exs['bu4'] = ['te.st', 'url', 'http://example.com/files', '[te.st]/.', 'http://example.com/files'];
    $exs['bu5'] = ['te.st', 'url', 'http://example.com/files', '[te.st]/0', 'http://example.com/files/0'];
    $exs['bu6'] = ['te.st', 'url', 'http://example.com/files', '[te.st]/', 'http://example.com/files/'];

    $exs['bu18'] = ['te.st', 'url', 'http://example.com:8080', '[te.st]/foo/bar', 'http://example.com:8080/foo/bar'];
    $exs['bu28'] = ['te.st', 'url', 'http://example.com:8080', '[te.st]/foo/', 'http://example.com:8080/foo/'];
    $exs['bu38'] = ['te.st', 'url', 'http://example.com:8080', '[te.st]/foo', 'http://example.com:8080/foo'];
    $exs['bu48'] = ['te.st', 'url', 'http://example.com:8080', '[te.st]/.', 'http://example.com:8080'];
    $exs['bu58'] = ['te.st', 'url', 'http://example.com:8080', '[te.st]/0', 'http://example.com:8080/0'];
    $exs['bu68'] = ['te.st', 'url', 'http://example.com:8080', '[te.st]/', 'http://example.com:8080/'];

    // Oddballs
    $exs['wp1'] = ['wp.ex1', 'url', 'http://example.com/wp-admin/admin.php', '[wp.ex1]/.', 'http://example.com/wp-admin/admin.php'];
    $exs['http'] = ['te.st', 'url', 'http://example.com/files', '[te.st]/httpIsBetterThanGopher', 'http://example.com/files/httpIsBetterThanGopher'];

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
    $this->assertEquals($expectValue, $actualValue, "Evaluate $func(\"$inputExpr\") given ([$varName] = \"$varValue\")");

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
