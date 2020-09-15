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

  /**
   * This test demonstrates how to (and how not to) compute a derivative path variable.
   */
  public function testAbsoluteRelativeConversions() {
    $gstack = \CRM_Utils_GlobalStack::singleton();
    $gstack->push(['_SERVER' => ['HTTP_HOST' => 'example.com']]);
    $cleanup = \CRM_Utils_AutoClean::with([$gstack, 'pop']);

    $paths = new Paths();
    $paths->register('test.base', function () {
      return [
        'path' => '/var/foo/',
        'url' => 'http://example.com/foo/',
      ];
    });
    $paths->register('test.goodsub', function () use ($paths) {
      // This is a stand-in for how [civicrm.bower], [civicrm.packages], [civicrm.vendor] currently work.
      return [
        'path' => $paths->getPath('[test.base]/good/'),
        'url' => $paths->getUrl('[test.base]/good/', 'absolute'),
      ];
    });
    $paths->register('test.badsub', function () use ($paths) {
      // This is a stand-in for how [civicrm.bower], [civicrm.packages], [civicrm.vendor] used to work (incorrectly).
      return [
        'path' => $paths->getPath('[test.base]/bad/'),
        // The following *looks* OK, but it's not. Note that `getUrl()` by default uses `$preferFormat==relative`.
        // Both registered URLs (`register()`, `$civicrm_paths`) and outputted URLs (`getUrl()`)
        // can be in relative form. However, they are relative to different bases: registrations are
        // relative to CMS root, and outputted URLs are relative to HTTP root. They are often the same, but...
        // on deployments where they differ, this example will misbehave.
        'url' => $paths->getUrl('[test.base]/bad/'),
      ];
    });

    // The test.base works as explicitly defined...
    $this->assertEquals('/var/foo', $paths->getPath('[test.base]/.'));
    $this->assertEquals('http://example.com/foo', $paths->getUrl('[test.base]/.', 'absolute'));
    $this->assertEquals('/foo', $paths->getUrl('[test.base]/.', 'relative'));

    // The test.goodsub works as expected...
    $this->assertEquals('/var/foo/good', $paths->getPath('[test.goodsub]/.'));
    $this->assertEquals('http://example.com/foo/good', $paths->getUrl('[test.goodsub]/.', 'absolute'));
    $this->assertEquals('/foo/good', $paths->getUrl('[test.goodsub]/.', 'relative'));

    // The test.badsub doesn't work as expected.
    $this->assertEquals('/var/foo/bad', $paths->getPath('[test.badsub]/.'));
    $this->assertNotEquals('http://example.com/foo/bad', $paths->getUrl('[test.badsub]/.', 'absolute'));
    $this->assertNotEquals('/foo/bad', $paths->getUrl('[test.badsub]/.', 'relative'));
  }

}
