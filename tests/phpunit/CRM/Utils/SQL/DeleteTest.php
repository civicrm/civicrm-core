<?php

/**
 * Class CRM_Utils_SQL_DeleteTest
 * @group headless
 */
class CRM_Utils_SQL_DeleteTest extends CiviUnitTestCase {

  public function testGetDefault() {
    $del = CRM_Utils_SQL_Delete::from('foo');
    $this->assertLike('DELETE FROM foo', $del->toSQL());
  }

  public function testWherePlain() {
    $del = CRM_Utils_SQL_Delete::from('foo')
      ->where('foo = bar')
      ->where(['whiz = bang', 'frob > nicate']);
    $this->assertLike('DELETE FROM foo WHERE (foo = bar) AND (whiz = bang) AND (frob > nicate)', $del->toSQL());
  }

  public function testWhereArg() {
    $del = CRM_Utils_SQL_Delete::from('foo')
      ->where('foo = @value', ['@value' => 'not"valid'])
      ->where(['whiz > @base', 'frob != @base'], ['@base' => 'in"valid']);
    $this->assertLike('DELETE FROM foo WHERE (foo = "not\\"valid") AND (whiz > "in\\"valid") AND (frob != "in\\"valid")', $del->toSQL());
  }

  public function testWhereNullArg() {
    $del = CRM_Utils_SQL_Delete::from('foo')
      ->where('foo IS @value', ['@value' => NULL])
      ->where('nonexistent IS @nonexistent', [])
      ->where('morenonexistent IS @nonexistent', NULL)
      ->where('bar IS @value', ['@value' => 'null']);
    $this->assertLike('DELETE FROM foo WHERE (foo IS NULL) AND (nonexistent IS @nonexistent) AND (morenonexistent IS @nonexistent) AND (bar IS "null")', $del->toSQL());
  }

  /**
   * @param $expected
   * @param $actual
   * @param string $message
   */
  public function assertLike($expected, $actual, $message = '') {
    $expected = trim((preg_replace('/[ \r\n\t]+/', ' ', $expected)));
    $actual = trim((preg_replace('/[ \r\n\t]+/', ' ', $actual)));
    $this->assertEquals($expected, $actual, $message);
  }

}
