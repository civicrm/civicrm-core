<?php

/**
 * Class CRM_Utils_SQL_SelectTest
 * @group headless
 */
class CRM_Utils_SQL_SelectTest extends CiviUnitTestCase {

  public function testGetDefault() {
    $select = CRM_Utils_SQL_Select::from('foo bar');
    $this->assertLike('SELECT * FROM foo bar', $select->toSQL());
  }

  public function testExecute_OK_fetch() {
    $select = CRM_Utils_SQL_Select::from('civicrm_contact')->select('count(*) as cnt');
    $this->assertLike('SELECT count(*) as cnt FROM civicrm_contact', $select->toSQL());

    $select = CRM_Utils_SQL_Select::from('civicrm_contact')
      ->select('count(*) as cnt');
    $rows = 0;
    $dao = $select->execute();
    while ($dao->fetch()) {
      $rows++;
      $this->assertTrue(is_numeric($dao->cnt), "Expect query to execute");
    }
    $this->assertEquals(1, $rows);
  }

  public function testExecute_OK_fetchValue() {
    $select = CRM_Utils_SQL_Select::from('civicrm_contact')->select('count(*) as cnt');
    $this->assertLike('SELECT count(*) as cnt FROM civicrm_contact', $select->toSQL());
    $this->assertTrue(is_numeric($select->execute()->fetchValue()));
  }

  public function testExecute_OK_fetchAll() {
    $select = CRM_Utils_SQL_Select::from('civicrm_contact')->select('count(*) as cnt');
    $this->assertLike('SELECT count(*) as cnt FROM civicrm_contact', $select->toSQL());
    $records = $select->execute()->fetchAll();
    $this->assertTrue(is_numeric($records[0]['cnt']));
  }

  public function testExecute_Error() {
    $select = CRM_Utils_SQL_Select::from('civicrm_contact')->select('snarb;barg');

    try {
      $select->execute();
      $this->fail('Expected an exception');
    }
    catch (PEAR_Exception $e) {
      $this->assertTrue(TRUE, "Received expected exception");
    }
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

  public function testModeOutput() {
    $select = CRM_Utils_SQL_Select::from('foo', array('mode' => 'out'))
      ->where('foo = @value')
      ->where(array(
        'whiz > @base',
        'frob != @base',
      ))
      ->param('@value', 'not"valid')
      ->param(array(
        '@base' => 'in"valid',
      ));
    $this->assertLike('SELECT * FROM foo WHERE (foo = "not\\"valid") AND (whiz > "in\\"valid") AND (frob != "in\\"valid")', $select->toSQL());

    try {
      CRM_Utils_SQL_Select::from('foo', array('mode' => 'out'))
        ->where('foo = @value', array('@value' => 'not"valid'));
      $this->fail('In output mode, we should reject requests to interpolate inputs.');
    }
    catch (Exception $e) {
      $this->assertRegExp("/Cannot mix interpolation modes/", $e->getMessage());
    }

    $outputModeFragment = CRM_Utils_SQL_Select::fragment()
      ->param('value', 'whatever');
    $inputModeFragment = CRM_Utils_SQL_Select::fragment()
      ->where('foo = @value', array('@value' => 'not"valid'));
    try {
      $outputModeFragment->merge($inputModeFragment);
      $this->fail('In output-mode, we should reject requests to merge from input-mode.');
    }
    catch (Exception $e) {
      $this->assertRegExp("/Cannot merge queries that use different interpolation modes/", $e->getMessage());
    }
  }

  public function testBig() {
    $select = CRM_Utils_SQL_Select::from('foo')
      ->select('foo.id')
      ->join('rel1', 'INNER JOIN rel1_table rel1 ON foo.id = rel1.foo_id')
      ->join('rel2', 'LEFT JOIN rel2_table rel2 ON foo.id = rel2.foo_id')
      ->where('foo.type = @theType', array('@theType' => 'mytype'))
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

  /**
   * Parameter-values could include control characters like
   * '"@" or "!", but they should never be evaluated.
   */
  public function testNoIterativeInterpolation() {
    $select = CRM_Utils_SQL_Select::from('foo')
      ->where('a = @a and b = @b and c = @c', array(
        'a' => '@b',
        'b' => '@c',
        'c' => '@a',
      ));
    $this->assertLike('SELECT * FROM foo WHERE (a = "@b" and b = "@c" and c = "@a")', $select->toSQL());
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

  public function testInterpolateWildcard() {
    $actual = CRM_Utils_SQL_Select::from('ignore')->interpolate(
      'escaped @escaped unescaped !unescaped validated #validated',
      array(
        'escaped' => 'foo"bar',
        'unescaped' => 'concat(foo,bar)',
        'validated' => 15.2,
      )
    );
    $this->assertLike('escaped "foo\"bar" unescaped concat(foo,bar) validated 15.2', $actual);
  }

  public function testInterpolateUnknown() {
    $actual = CRM_Utils_SQL_Select::from('ignore')->interpolate(
      'escaped @escaped unescaped !unescaped validated #validated',
      array(
        'irrelevant' => 'foo',
      )
    );
    $this->assertLike('escaped @escaped unescaped !unescaped validated #validated', $actual);
  }

  public function testInterpolateUnknownStrict() {
    try {
      CRM_Utils_SQL_Select::from('ignore')
        ->strict()
        ->interpolate('@johnMcClane',
          array(
            'irrelevant' => 'foo',
          )
        );
      $this->fail('Unknown variables should throw errors in strict mode.');
    }
    catch (Exception $e) {
      $this->assertRegExp('/Cannot build query. Variable "@johnMcClane" is unknown./', $e->getMessage());
    }
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

  public function testMerge() {
    $fragmentOutMode = CRM_Utils_SQL_Select::fragment()
      ->select(array('a', 'b'))
      ->where('a = #two')
      ->param('two', 2);
    $fragmentAutoMode = CRM_Utils_SQL_Select::fragment()
      ->select('e')
      ->where('whipit()');
    $query = CRM_Utils_SQL_Select::from('foo')
      ->select(array('c', 'd'))
      ->where('c = @four')
      ->param('four', 4)
      ->merge($fragmentOutMode)
      ->merge($fragmentAutoMode);
    $this->assertLike('SELECT c, d, a, b, e FROM foo WHERE (c = "4") AND (a = 2) AND (whipit())', $query->toSQL());
  }

  public function testArrayGet() {
    $select = CRM_Utils_SQL_Select::from("foo")
      ->param('hello', 'world');
    $this->assertEquals('world', $select['hello']);
  }

  public function testInsertInto_WithDupes() {
    $select = CRM_Utils_SQL_Select::from('foo')
      ->insertInto('bar', array('first', 'second', 'third', 'fourth'))
      ->select('fid')
      ->select('1')
      ->select('fid')
      ->select('1')
      ->where('!field = #value', array('field' => 'zoo', 'value' => 3))
      ->where('!field = #value', array('field' => 'aviary', 'value' => 3))
      ->where('!field = #value', array('field' => 'zoo', 'value' => 3))
      ->groupBy('!colName', array('colName' => 'noodle'))
      ->groupBy('!colName', array('colName' => 'sauce'))
      ->groupBy('!colName', array('colName' => 'noodle'));
    $this->assertLike('INSERT INTO bar (first, second, third, fourth) SELECT fid, 1, fid, 1 FROM foo WHERE (zoo = 3) AND (aviary = 3) GROUP BY noodle, sauce', $select->toSQL());
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
