<?php

/**
 * Class CRM_Utils_SQL_DeleteTest
 * @group headless
 */
class CRM_Utils_SQL_DeleteTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();
  }

  public function testGetDefault(): void {
    $del = CRM_Utils_SQL_Delete::from('foo');
    $this->assertLike('DELETE FROM foo', $del->toSQL());
  }

  public function testWherePlain(): void {
    $del = CRM_Utils_SQL_Delete::from('foo')
      ->where('foo = bar')
      ->where(['whiz = bang', 'frob > nicate']);
    $this->assertLike('DELETE FROM foo WHERE (foo = bar) AND (whiz = bang) AND (frob > nicate)', $del->toSQL());
  }

  public function testWhereArg(): void {
    $del = CRM_Utils_SQL_Delete::from('foo')
      ->where('foo = @value', ['@value' => 'not"valid'])
      ->where(['whiz > @base', 'frob != @base'], ['@base' => 'in"valid']);
    $this->assertLike('DELETE FROM foo WHERE (foo = "not\\"valid") AND (whiz > "in\\"valid") AND (frob != "in\\"valid")', $del->toSQL());
  }

  public function testWhereNullArg(): void {
    $del = CRM_Utils_SQL_Delete::from('foo')
      ->where('foo IS @value', ['@value' => NULL])
      ->where('nonexistent IS @nonexistent', [])
      ->where('morenonexistent IS @nonexistent', NULL)
      ->where('bar IS @value', ['@value' => 'null']);
    $this->assertLike('DELETE FROM foo WHERE (foo IS NULL) AND (nonexistent IS @nonexistent) AND (morenonexistent IS @nonexistent) AND (bar IS "null")', $del->toSQL());
  }

}
