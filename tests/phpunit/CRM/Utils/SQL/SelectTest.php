<?php
require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * Class CRM_Utils_SQL_SelectTest
 */
class CRM_Utils_SQL_SelectTest extends CiviUnitTestCase {
  public function testGetDefault() {
    $select = CRM_Utils_SQL_Select::from('foo bar');
    $this->assertLike('SELECT * FROM foo bar', $select->toSQL());
  }

  public function testGetFields() {
    $select = CRM_Utils_SQL_Select::from('foo')
      ->select('bar')
      ->select(array('whiz', 'bang'));
    $this->assertLike('SELECT bar, whiz, bang FROM foo', $select->toSQL());
  }

  public function testWherePlain() {
    $select = CRM_Utils_SQL_Select::from('foo')
      ->where('foo = bar')
      ->where(array('whiz = bang', 'frob > nicate'));
    $this->assertLike('SELECT * FROM foo WHERE (foo = bar) AND (whiz = bang) AND (frob > nicate)', $select->toSQL());
  }

  public function testWhereArg() {
    $select = CRM_Utils_SQL_Select::from('foo')
      ->where('foo = @value', array('@value' => 'not"valid'))
      ->where(array('whiz > @base', 'frob != @base'), array('@base' => 'in"valid'));
    $this->assertLike('SELECT * FROM foo WHERE (foo = "not\\"valid") AND (whiz > "in\\"valid") AND (frob != "in\\"valid")', $select->toSQL());
  }

  public function testGroupByPlain() {
    $select = CRM_Utils_SQL_Select::from('foo')
      ->groupBy("bar_id")
      ->groupBy(array('whiz_id*2', 'lower(bang)'));
    $this->assertLike('SELECT * FROM foo GROUP BY bar_id, whiz_id*2, lower(bang)', $select->toSQL());
  }

  public function testHavingPlain() {
    $select = CRM_Utils_SQL_Select::from('foo')
      ->groupBy("bar_id")
      ->having('count(id) > 2')
      ->having(array('sum(id) > 10', 'avg(id) < 200'));
    $this->assertLike('SELECT * FROM foo GROUP BY bar_id HAVING (count(id) > 2) AND (sum(id) > 10) AND (avg(id) < 200)', $select->toSQL());
  }

  public function testHavingArg() {
    $select = CRM_Utils_SQL_Select::from('foo')
      ->groupBy("bar_id")
      ->having('count(id) > #mincnt', array('#mincnt' => 2))
      ->having(array('sum(id) > #whiz', 'avg(id) < #whiz'), array('#whiz' => 10));
    $this->assertLike('SELECT * FROM foo GROUP BY bar_id HAVING (count(id) > 2) AND (sum(id) > 10) AND (avg(id) < 10)', $select->toSQL());
  }

  public function testOrderByPlain() {
    $select = CRM_Utils_SQL_Select::from('foo bar')
      ->orderBy('first asc')
      ->orderBy(array('second desc', 'third'));
    $this->assertLike('SELECT * FROM foo bar ORDER BY first asc, second desc, third', $select->toSQL());
  }

  public function testLimit_defaultOffset() {
    $select = CRM_Utils_SQL_Select::from('foo bar')
      ->limit(20);
    $this->assertLike('SELECT * FROM foo bar LIMIT 20 OFFSET 0', $select->toSQL());
  }

  public function testLimit_withOffset() {
    $select = CRM_Utils_SQL_Select::from('foo bar')
      ->limit(20, 60);
    $this->assertLike('SELECT * FROM foo bar LIMIT 20 OFFSET 60', $select->toSQL());
  }

  public function testLimit_disable() {
    $select = CRM_Utils_SQL_Select::from('foo bar')
      ->limit(20, 60)
      ->limit(NULL, NULL);
    $this->assertLike('SELECT * FROM foo bar', $select->toSQL());
  }

  public function testBig() {
    $select = CRM_Utils_SQL_Select::from('foo')
      ->select('foo.id')
      ->join('rel1', 'INNER JOIN rel1_table rel1 ON foo.id = rel1.foo_id')
      ->join('rel2', 'LEFT JOIN rel2_table rel2 ON foo.id = rel2.foo_id')
      ->where('foo.type = @type', array('@type' => 'mytype'))
      ->groupBy("foo.id")
      ->having('sum(rel1.stat) > 10')
      ->orderBy('rel2.whiz')
      ->limit(100, 300);
    $this->assertLike(
      "SELECT foo.id FROM foo"
      . " INNER JOIN rel1_table rel1 ON foo.id = rel1.foo_id"
      . " LEFT JOIN rel2_table rel2 ON foo.id = rel2.foo_id "
      . " WHERE (foo.type = \"mytype\")"
      . " GROUP BY foo.id"
      . " HAVING (sum(rel1.stat) > 10)"
      . " ORDER BY rel2.whiz"
      . " LIMIT 100 OFFSET 300",
      $select->toSQL()
    );
  }

  public function testInterpolate() {
    $actual = CRM_Utils_SQL_Select::from('ignore')->interpolate(
      '@escaped !unescaped #validated',
      array(
        '@escaped' => 'foo"bar',
        '!unescaped' => 'concat(foo,bar)',
        '#validated' => 15.2,
      )
    );
    $this->assertLike('"foo\"bar" concat(foo,bar) 15.2', $actual);
  }

  public function testInterpolateArray() {
    $actual = CRM_Utils_SQL_Select::from('ignore')->interpolate(
      '(@escaped) (!unescaped) (#validated)',
      array(
        '@escaped' => array('foo"bar', "whiz", "null", NULL, "bang"),
        '!unescaped' => array('foo"bar', 'bar'),
        '#validated' => array(1, 10, NULL, 100.1),
      )
    );
    $this->assertLike('("foo\\"bar", "whiz", "null", NULL, "bang") (foo"bar, bar) (1, 10, NULL, 100.1)', $actual);
  }

  public function testInterpolateBadNumber() {
    try {
      $result = CRM_Utils_SQL_Select::from('ignore')->interpolate('#num', array(
        '#num' => '5not-a-number5',
      ));
      $this->fail('Expected exception; got: ' . var_export($result, TRUE));
    }
    catch (CRM_Core_Exception $e) {
      $this->assertTrue(TRUE, "Caught expected exception");
    }

    try {
      $result = CRM_Utils_SQL_Select::from('ignore')->interpolate('#num', array(
        '#num' => array(1, '5not-a-number5', 2),
      ));
      $this->fail('Expected exception; got: ' . var_export($result, TRUE));
    }
    catch (CRM_Core_Exception $e) {
      $this->assertTrue(TRUE, "Caught expected exception");
    }
  }

  public function testInterpolateBadKey() {
    try {
      $result = CRM_Utils_SQL_Select::from('ignore')->interpolate('this is a {var}', array(
        '{var}' => 'not a well-formed variable name',
      ));
      $this->fail('Expected exception; got: ' . var_export($result, TRUE));
    }
    catch (CRM_Core_Exception $e) {
      $this->assertTrue(TRUE, "Caught expected exception");
    }
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
