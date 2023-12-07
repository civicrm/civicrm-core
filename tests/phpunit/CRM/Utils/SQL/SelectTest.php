<?php

/**
 * Class CRM_Utils_SQL_SelectTest
 * @group headless
 */
class CRM_Utils_SQL_SelectTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();
  }

  public function testGetDefault(): void {
    $select = CRM_Utils_SQL_Select::from('foo bar');
    $this->assertLike('SELECT * FROM foo bar', $select->toSQL());
  }

  public function testExecute_OK_fetch(): void {
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

  public function testExecute_OK_fetchValue(): void {
    $select = CRM_Utils_SQL_Select::from('civicrm_contact')->select('count(*) as cnt');
    $this->assertLike('SELECT count(*) as cnt FROM civicrm_contact', $select->toSQL());
    $this->assertTrue(is_numeric($select->execute()->fetchValue()));
  }

  public function testExecute_OK_fetchAll(): void {
    $select = CRM_Utils_SQL_Select::from('civicrm_contact')->select('count(*) as cnt');
    $this->assertLike('SELECT count(*) as cnt FROM civicrm_contact', $select->toSQL());
    $records = $select->execute()->fetchAll();
    $this->assertTrue(is_numeric($records[0]['cnt']));
  }

  public function testExecute_Error(): void {
    $select = CRM_Utils_SQL_Select::from('civicrm_contact')->select('snarb;barg');

    try {
      $select->execute();
      $this->fail('Expected an exception');
    }
    catch (PEAR_Exception $e) {
      $this->assertTrue(TRUE, "Received expected exception");
    }
  }

  public function testGetFields(): void {
    $select = CRM_Utils_SQL_Select::from('foo')
      ->select('bar')
      ->select(['whiz', 'bang']);
    $this->assertLike('SELECT bar, whiz, bang FROM foo', $select->toSQL());
  }

  public function testWherePlain(): void {
    $select = CRM_Utils_SQL_Select::from('foo')
      ->where('foo = bar')
      ->where(['whiz = bang', 'frob > nicate']);
    $this->assertLike('SELECT * FROM foo WHERE (foo = bar) AND (whiz = bang) AND (frob > nicate)', $select->toSQL());
  }

  public function testWhereArg(): void {
    $select = CRM_Utils_SQL_Select::from('foo')
      ->where('foo = @value', ['@value' => 'not"valid'])
      ->where(['whiz > @base', 'frob != @base'], ['@base' => 'in"valid']);
    $this->assertLike('SELECT * FROM foo WHERE (foo = "not\\"valid") AND (whiz > "in\\"valid") AND (frob != "in\\"valid")', $select->toSQL());
  }

  public function testWhereNullArg(): void {
    $select = CRM_Utils_SQL_Select::from('foo')
      ->where('foo IS @value', ['@value' => NULL])
      ->where('nonexistent IS @nonexistent', [])
      ->where('morenonexistent IS @nonexistent', NULL)
      ->where('bar IS @value', ['@value' => 'null']);
    $this->assertLike('SELECT * FROM foo WHERE (foo IS NULL) AND (nonexistent IS @nonexistent) AND (morenonexistent IS @nonexistent) AND (bar IS "null")', $select->toSQL());
  }

  public function testGroupByPlain(): void {
    $select = CRM_Utils_SQL_Select::from('foo')
      ->groupBy("bar_id")
      ->groupBy(['whiz_id*2', 'lower(bang)']);
    $this->assertLike('SELECT * FROM foo GROUP BY bar_id, whiz_id*2, lower(bang)', $select->toSQL());
  }

  public function testHavingPlain(): void {
    $select = CRM_Utils_SQL_Select::from('foo')
      ->groupBy("bar_id")
      ->having('count(id) > 2')
      ->having(['sum(id) > 10', 'avg(id) < 200']);
    $this->assertLike('SELECT * FROM foo GROUP BY bar_id HAVING (count(id) > 2) AND (sum(id) > 10) AND (avg(id) < 200)', $select->toSQL());
  }

  public function testHavingArg(): void {
    $select = CRM_Utils_SQL_Select::from('foo')
      ->groupBy("bar_id")
      ->having('count(id) > #mincnt', ['#mincnt' => 2])
      ->having(['sum(id) > #whiz', 'avg(id) < #whiz'], ['#whiz' => 10]);
    $this->assertLike('SELECT * FROM foo GROUP BY bar_id HAVING (count(id) > 2) AND (sum(id) > 10) AND (avg(id) < 10)', $select->toSQL());
  }

  public function testOrderByPlain(): void {
    $select = CRM_Utils_SQL_Select::from('foo bar')
      ->orderBy('first asc')
      ->orderBy(['second desc', 'third']);
    $this->assertLike('SELECT * FROM foo bar ORDER BY first asc, second desc, third', $select->toSQL());
  }

  public function testLimit_defaultOffset(): void {
    $select = CRM_Utils_SQL_Select::from('foo bar')
      ->limit(20);
    $this->assertLike('SELECT * FROM foo bar LIMIT 20 OFFSET 0', $select->toSQL());
  }

  public function testLimit_withOffset(): void {
    $select = CRM_Utils_SQL_Select::from('foo bar')
      ->limit(20, 60);
    $this->assertLike('SELECT * FROM foo bar LIMIT 20 OFFSET 60', $select->toSQL());
  }

  public function testLimit_disable(): void {
    $select = CRM_Utils_SQL_Select::from('foo bar')
      ->limit(20, 60)
      ->limit(NULL, NULL);
    $this->assertLike('SELECT * FROM foo bar', $select->toSQL());
  }

  public function testModeOutput(): void {
    $select = CRM_Utils_SQL_Select::from('foo', ['mode' => 'out'])
      ->where('foo = @value')
      ->where([
        'whiz > @base',
        'frob != @base',
      ])
      ->param('@value', 'not"valid')
      ->param([
        '@base' => 'in"valid',
      ]);
    $this->assertLike('SELECT * FROM foo WHERE (foo = "not\\"valid") AND (whiz > "in\\"valid") AND (frob != "in\\"valid")', $select->toSQL());

    try {
      CRM_Utils_SQL_Select::from('foo', ['mode' => 'out'])
        ->where('foo = @value', ['@value' => 'not"valid']);
      $this->fail('In output mode, we should reject requests to interpolate inputs.');
    }
    catch (Exception $e) {
      $this->assertMatchesRegularExpression("/Cannot mix interpolation modes/", $e->getMessage());
    }

    $outputModeFragment = CRM_Utils_SQL_Select::fragment()
      ->param('value', 'whatever');
    $inputModeFragment = CRM_Utils_SQL_Select::fragment()
      ->where('foo = @value', ['@value' => 'not"valid']);
    try {
      $outputModeFragment->merge($inputModeFragment);
      $this->fail('In output-mode, we should reject requests to merge from input-mode.');
    }
    catch (Exception $e) {
      $this->assertMatchesRegularExpression("/Cannot merge queries that use different interpolation modes/", $e->getMessage());
    }
  }

  public function testBig(): void {
    $select = CRM_Utils_SQL_Select::from('foo')
      ->select('foo.id')
      ->join('rel1', 'INNER JOIN rel1_table rel1 ON foo.id = rel1.foo_id')
      ->join('rel2', 'LEFT JOIN rel2_table rel2 ON foo.id = rel2.foo_id')
      ->where('foo.type = @theType', ['@theType' => 'mytype'])
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
  public function testNoIterativeInterpolation(): void {
    $select = CRM_Utils_SQL_Select::from('foo')
      ->where('a = @a and b = @b and c = @c', [
        'a' => '@b',
        'b' => '@c',
        'c' => '@a',
      ]);
    $this->assertLike('SELECT * FROM foo WHERE (a = "@b" and b = "@c" and c = "@a")', $select->toSQL());
  }

  public function testInterpolate(): void {
    $actual = CRM_Utils_SQL_Select::from('ignore')->interpolate(
      '@escaped !unescaped #validated',
      [
        '@escaped' => 'foo"bar',
        '!unescaped' => 'concat(foo,bar)',
        '#validated' => 15.2,
      ]
    );
    $this->assertLike('"foo\"bar" concat(foo,bar) 15.2', $actual);
  }

  public function testInterpolateWildcard(): void {
    $actual = CRM_Utils_SQL_Select::from('ignore')->interpolate(
      'escaped @escaped unescaped !unescaped validated #validated',
      [
        'escaped' => 'foo"bar',
        'unescaped' => 'concat(foo,bar)',
        'validated' => 15.2,
      ]
    );
    $this->assertLike('escaped "foo\"bar" unescaped concat(foo,bar) validated 15.2', $actual);
  }

  public function testInterpolateUnknown(): void {
    $actual = CRM_Utils_SQL_Select::from('ignore')->interpolate(
      'escaped @escaped unescaped !unescaped validated #validated',
      [
        'irrelevant' => 'foo',
      ]
    );
    $this->assertLike('escaped @escaped unescaped !unescaped validated #validated', $actual);
  }

  public function testInterpolateUnknownStrict(): void {
    try {
      CRM_Utils_SQL_Select::from('ignore')
        ->strict()
        ->interpolate('@johnMcClane',
          [
            'irrelevant' => 'foo',
          ]
        );
      $this->fail('Unknown variables should throw errors in strict mode.');
    }
    catch (Exception $e) {
      $this->assertMatchesRegularExpression('/Cannot build query. Variable "@johnMcClane" is unknown./', $e->getMessage());
    }
  }

  public function testInterpolateArray(): void {
    $actual = CRM_Utils_SQL_Select::from('ignore')->interpolate(
      '(@escaped) (!unescaped) (#validated)',
      [
        '@escaped' => ['foo"bar', "whiz", "null", NULL, "bang"],
        '!unescaped' => ['foo"bar', 'bar'],
        '#validated' => [1, 10, NULL, 100.1],
      ]
    );
    $this->assertLike('("foo\\"bar", "whiz", "null", NULL, "bang") (foo"bar, bar) (1, 10, NULL, 100.1)', $actual);
  }

  public function testInterpolateBadNumber(): void {
    try {
      $result = CRM_Utils_SQL_Select::from('ignore')->interpolate('#num', [
        '#num' => '5not-a-number5',
      ]);
      $this->fail('Expected exception; got: ' . var_export($result, TRUE));
    }
    catch (CRM_Core_Exception $e) {
      $this->assertTrue(TRUE, "Caught expected exception");
    }

    try {
      $result = CRM_Utils_SQL_Select::from('ignore')->interpolate('#num', [
        '#num' => [1, '5not-a-number5', 2],
      ]);
      $this->fail('Expected exception; got: ' . var_export($result, TRUE));
    }
    catch (CRM_Core_Exception $e) {
      $this->assertTrue(TRUE, "Caught expected exception");
    }
  }

  public function testMerge(): void {
    $fragmentOutMode = CRM_Utils_SQL_Select::fragment()
      ->select(['a', 'b'])
      ->where('a = #two')
      ->param('two', 2);
    $fragmentAutoMode = CRM_Utils_SQL_Select::fragment()
      ->select('e')
      ->where('whipit()');
    $query = CRM_Utils_SQL_Select::from('foo')
      ->select(['c', 'd'])
      ->where('c = @four')
      ->param('four', 4)
      ->merge($fragmentOutMode)
      ->merge($fragmentAutoMode);
    $this->assertLike('SELECT c, d, a, b, e FROM foo WHERE (c = "4") AND (a = 2) AND (whipit())', $query->toSQL());
  }

  public function testUnion(): void {
    $a = CRM_Utils_SQL_Select::from('a')->select('a_name')->where('a1 = !num')->param('num', 100);
    $b = CRM_Utils_SQL_Select::from('b')->select('b_name')->where('b2 = @val')->param('val', "ab cd");
    $u = CRM_Utils_SQL_Select::fromSet()->union('ALL', [$a, $b])->limit(100)->orderBy('a_name');
    $expectA = 'SELECT a_name FROM a WHERE (a1 = 100) ';
    $expectB = 'SELECT b_name FROM b WHERE (b2 = "ab cd") ';
    $expectUnion = "SELECT * FROM (($expectA) UNION ALL ($expectB)) _sql_set ORDER BY a_name LIMIT 100 OFFSET 0";
    $this->assertLike($expectUnion, $u->toSQL());
  }

  public function testUnionIntersect(): void {
    $a = CRM_Utils_SQL_Select::from('a')->select('a_name')->where('a1 = !num')->param('num', 100);
    $b = CRM_Utils_SQL_Select::from('b')->select('b_name')->where('b2 = @val')->param('val', "bb bb");
    $c = CRM_Utils_SQL_Select::from('c')->select('c_name')->where('c3 = @val')->param('val', "cc cc");
    $u = CRM_Utils_SQL_Select::fromSet()
      ->union('ALL', [$a, $b])
      ->union('DISTINCT', $c)
      ->limit(100)
      ->orderBy('a_name');
    $expectA = 'SELECT a_name FROM a WHERE (a1 = 100) ';
    $expectB = 'SELECT b_name FROM b WHERE (b2 = "bb bb") ';
    $expectC = 'SELECT c_name FROM c WHERE (c3 = "cc cc") ';
    $expectUnion = "SELECT * FROM (($expectA) UNION ALL ($expectB) UNION DISTINCT ($expectC)) _sql_set ORDER BY a_name LIMIT 100 OFFSET 0";
    $this->assertLike($expectUnion, $u->toSQL());
  }

  public function testArrayGet(): void {
    $select = CRM_Utils_SQL_Select::from("foo")
      ->param('hello', 'world');
    $this->assertEquals('world', $select['hello']);
  }

  public function testInsertInto_WithDupes(): void {
    $select = CRM_Utils_SQL_Select::from('foo')
      ->insertInto('bar', ['first', 'second', 'third', 'fourth'])
      ->select('fid')
      ->select('1')
      ->select('fid')
      ->select('1')
      ->where('!field = #value', ['field' => 'zoo', 'value' => 3])
      ->where('!field = #value', ['field' => 'aviary', 'value' => 3])
      ->where('!field = #value', ['field' => 'zoo', 'value' => 3])
      ->groupBy('!colName', ['colName' => 'noodle'])
      ->groupBy('!colName', ['colName' => 'sauce'])
      ->groupBy('!colName', ['colName' => 'noodle']);
    $this->assertLike('INSERT INTO bar (first, second, third, fourth) SELECT fid, 1, fid, 1 FROM foo WHERE (zoo = 3) AND (aviary = 3) GROUP BY noodle, sauce', $select->toSQL());
  }

  public function testInsertInto_OnDuplicateUpdate(): void {
    $select = CRM_Utils_SQL_Select::from('foo')
      ->insertInto('bar', ['first', 'second', 'third'])
      ->select(['foo.one', 'foo.two', 'foo.three'])
      ->onDuplicate('second = twiddle(foo.two)')
      ->onDuplicate('third = twiddle(foo.three)');
    $this->assertLike('INSERT INTO bar (first, second, third) SELECT foo.one, foo.two, foo.three FROM foo ON DUPLICATE KEY UPDATE second = twiddle(foo.two), third = twiddle(foo.three)', $select->toSQL());
  }

  public function testSyncInto_EarlyInterpolate(): void {
    $select = CRM_Utils_SQL_Select::from('foo')
      ->where('foo.whiz = 100')
      ->syncInto('bar', ['name'], [
        'name' => 'foo.name',
        'first' => 'concat(foo.one, @suffix)',
        'second' => 'foo.two',
      ], [
        '@suffix' => ' and more',
      ]);
    $this->assertLike('INSERT INTO bar (name, first, second) SELECT foo.name, concat(foo.one, " and more"), foo.two FROM foo WHERE (foo.whiz = 100) ON DUPLICATE KEY UPDATE first = concat(foo.one, " and more"), second = foo.two', $select->toSQL());
  }

  public function testSyncInto_LateInterpolate(): void {
    $select = CRM_Utils_SQL_Select::from('foo')
      ->where('foo.whiz = 100')
      ->syncInto('bar', ['name'], [
        'name' => 'foo.name',
        'first' => 'concat(foo.one, @suffix)',
        'second' => 'foo.two',
      ])
      ->param(['@suffix' => ' and on and on']);
    $this->assertLike('INSERT INTO bar (name, first, second) SELECT foo.name, concat(foo.one, " and on and on"), foo.two FROM foo WHERE (foo.whiz = 100) ON DUPLICATE KEY UPDATE first = concat(foo.one, " and on and on"), second = foo.two', $select->toSQL());
  }

}
