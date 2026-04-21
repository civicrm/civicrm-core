<?php

/**
 * Class CRM_Utils_ArrayTest
 * @group headless
 */
class CRM_Utils_ArrayTest extends CiviUnitTestCase {

  /**
   * Set up for tests.
   */
  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();
  }

  public function testAsColumns(): void {
    $rowsNum = [
      ['a' => 10, 'b' => 11],
      ['a' => 20, 'b' => 21],
      ['a' => 20, 'b' => 29],
    ];

    $rowsAssoc = [
      '!' => ['a' => 10, 'b' => 11],
      '@' => ['a' => 20, 'b' => 21],
      '#' => ['a' => 20, 'b' => 29],
    ];

    $this->assertEquals(
      ['a' => [10, 20, 20], 'b' => [11, 21, 29]],
      CRM_Utils_Array::asColumns($rowsNum)
    );
    $this->assertEquals(
      ['a' => [10, 20], 'b' => [11, 21, 29]],
      CRM_Utils_Array::asColumns($rowsNum, TRUE)
    );
    $this->assertEquals(
      ['a' => ['!' => 10, '@' => 20, '#' => 20], 'b' => ['!' => 11, '@' => 21, '#' => 29]],
      CRM_Utils_Array::asColumns($rowsAssoc)
    );
    $this->assertEquals(
      ['a' => [10, 20], 'b' => [11, 21, 29]],
      CRM_Utils_Array::asColumns($rowsAssoc, TRUE)
    );
  }

  public function testIndexArray(): void {
    $inputs = [];
    $inputs[] = [
      'lang' => 'en',
      'msgid' => 'greeting',
      'familiar' => FALSE,
      'value' => 'Hello',
    ];
    $inputs[] = [
      'lang' => 'en',
      'msgid' => 'parting',
      'value' => 'Goodbye',
    ];
    $inputs[] = [
      'lang' => 'fr',
      'msgid' => 'greeting',
      'value' => 'Bon jour',
    ];
    $inputs[] = [
      'lang' => 'fr',
      'msgid' => 'parting',
      'value' => 'Au revoir',
    ];
    $inputs[] = [
      'lang' => 'en',
      'msgid' => 'greeting',
      'familiar' => TRUE,
      'value' => 'Hey',
    ];
    $inputs[] = [
      'msgid' => 'greeting',
      'familiar' => TRUE,
      'value' => 'Universal greeting',
    ];

    $byLangMsgid = CRM_Utils_Array::index(['lang', 'msgid'], $inputs);
    $this->assertEquals($inputs[4], $byLangMsgid['en']['greeting']);
    $this->assertEquals($inputs[1], $byLangMsgid['en']['parting']);
    $this->assertEquals($inputs[2], $byLangMsgid['fr']['greeting']);
    $this->assertEquals($inputs[3], $byLangMsgid['fr']['parting']);
    $this->assertEquals($inputs[5], $byLangMsgid['']['greeting']);
  }

  public function testCollect(): void {
    $arr = [
      ['catWord' => 'cat', 'dogWord' => 'dog'],
      ['catWord' => 'chat', 'dogWord' => 'chien'],
      ['catWord' => 'gato'],
    ];
    $expected = ['cat', 'chat', 'gato'];
    $this->assertEquals($expected, CRM_Utils_Array::collect('catWord', $arr));

    $arr = [];
    $arr['en'] = (object) ['catWord' => 'cat', 'dogWord' => 'dog'];
    $arr['fr'] = (object) ['catWord' => 'chat', 'dogWord' => 'chien'];
    $arr['es'] = (object) ['catWord' => 'gato'];
    $expected = ['en' => 'cat', 'fr' => 'chat', 'es' => 'gato'];
    $this->assertEquals($expected, CRM_Utils_Array::collect('catWord', $arr));
  }

  public function testProduct0(): void {
    $actual = CRM_Utils_Array::product(
      [],
      ['base data' => 1]
    );
    $this->assertEquals([
      ['base data' => 1],
    ], $actual);
  }

  public function testProduct1(): void {
    $actual = CRM_Utils_Array::product(
      ['dim1' => ['a', 'b']],
      ['base data' => 1]
    );
    $this->assertEquals([
      ['base data' => 1, 'dim1' => 'a'],
      ['base data' => 1, 'dim1' => 'b'],
    ], $actual);
  }

  public function testProduct3(): void {
    $actual = CRM_Utils_Array::product(
      ['dim1' => ['a', 'b'], 'dim2' => ['alpha', 'beta'], 'dim3' => ['one', 'two']],
      ['base data' => 1]
    );
    $this->assertEquals([
      ['base data' => 1, 'dim1' => 'a', 'dim2' => 'alpha', 'dim3' => 'one'],
      ['base data' => 1, 'dim1' => 'a', 'dim2' => 'alpha', 'dim3' => 'two'],
      ['base data' => 1, 'dim1' => 'a', 'dim2' => 'beta', 'dim3' => 'one'],
      ['base data' => 1, 'dim1' => 'a', 'dim2' => 'beta', 'dim3' => 'two'],
      ['base data' => 1, 'dim1' => 'b', 'dim2' => 'alpha', 'dim3' => 'one'],
      ['base data' => 1, 'dim1' => 'b', 'dim2' => 'alpha', 'dim3' => 'two'],
      ['base data' => 1, 'dim1' => 'b', 'dim2' => 'beta', 'dim3' => 'one'],
      ['base data' => 1, 'dim1' => 'b', 'dim2' => 'beta', 'dim3' => 'two'],
    ], $actual);
  }

  public function testIsSubset(): void {
    $this->assertTrue(CRM_Utils_Array::isSubset([], []));
    $this->assertTrue(CRM_Utils_Array::isSubset(['a'], ['a']));
    $this->assertTrue(CRM_Utils_Array::isSubset(['a'], ['b', 'a', 'c']));
    $this->assertTrue(CRM_Utils_Array::isSubset(['b', 'd'], ['a', 'b', 'c', 'd']));
    $this->assertFalse(CRM_Utils_Array::isSubset(['a'], []));
    $this->assertFalse(CRM_Utils_Array::isSubset(['a'], ['b']));
    $this->assertFalse(CRM_Utils_Array::isSubset(['a'], ['b', 'c', 'd']));
  }

  public function testRemove(): void {
    $data = [
      'one' => 1,
      'two' => 2,
      'three' => 3,
      'four' => 4,
      'five' => 5,
      'six' => 6,
    ];
    CRM_Utils_Array::remove($data, 'one', 'two', ['three', 'four'], 'five');
    $this->assertEquals($data, ['six' => 6]);
  }

  public function testGetSetPathParts(): void {
    $arr = $arrOrig = [
      'one' => '1',
      'two' => [
        'half' => 2,
      ],
      'three' => [
        'first-third' => '1/3',
        'second-third' => '2/3',
      ],
    ];
    $this->assertEquals('1', CRM_Utils_Array::pathGet($arr, ['one']));
    $this->assertEquals('2', CRM_Utils_Array::pathGet($arr, ['two', 'half']));
    $this->assertEquals(NULL, CRM_Utils_Array::pathGet($arr, ['zoo', 'half']));
    CRM_Utils_Array::pathSet($arr, ['zoo', 'half'], '3');
    $this->assertEquals(3, CRM_Utils_Array::pathGet($arr, ['zoo', 'half']));
    $this->assertEquals(3, $arr['zoo']['half']);

    $arrCopy = $arr;
    $this->assertEquals(FALSE, CRM_Utils_Array::pathUnset($arr, ['does-not-exist']));
    $this->assertEquals($arrCopy, $arr);

    $this->assertEquals(TRUE, CRM_Utils_Array::pathUnset($arr, ['two', 'half'], FALSE));
    $this->assertEquals([], $arr['two']);
    $this->assertTrue(array_key_exists('two', $arr));

    CRM_Utils_Array::pathUnset($arr, ['three', 'first-third'], TRUE);
    $this->assertEquals(['second-third' => '2/3'], $arr['three']);
    CRM_Utils_Array::pathUnset($arr, ['three', 'second-third'], TRUE);
    $this->assertFalse(array_key_exists('three', $arr));

    // pathMove(): Change location of an item
    $arr = $arrOrig;
    $this->assertEquals(2, $arr['two']['half']);
    $this->assertTrue(!isset($arr['verb']['double']['half']));
    $this->assertEquals(1, CRM_Utils_Array::pathMove($arr, ['two'], ['verb', 'double']));
    $this->assertEquals(2, $arr['verb']['double']['half']);
    $this->assertTrue(!isset($arr['two']['half']));

    // pathMove(): If item doesn't exist, return 0.
    $arr = $arrOrig;
    $this->assertTrue(!isset($arr['not-a-src']));
    $this->assertTrue(!isset($arr['not-a-dest']));
    $this->assertEquals(0, CRM_Utils_Array::pathMove($arr, ['not-a-src'], ['not-a-dest']));
    $this->assertTrue(!isset($arr['not-a-src']));
    $this->assertTrue(!isset($arr['not-a-dest']));

  }

  public function testGetSet_EmptyPath(): void {
    $emptyPath = [];

    $x = 'hello';
    $this->assertEquals(TRUE, CRM_Utils_Array::pathIsset($x, $emptyPath));
    $this->assertEquals('hello', CRM_Utils_Array::pathGet($x, $emptyPath));
    $this->assertEquals('hello', $x);

    CRM_Utils_Array::pathSet($x, $emptyPath, 'bon jour');
    $this->assertEquals(TRUE, CRM_Utils_Array::pathIsset($x, $emptyPath));
    $this->assertEquals('bon jour', CRM_Utils_Array::pathGet($x, $emptyPath));
    $this->assertEquals('bon jour', $x);

    CRM_Utils_Array::pathUnset($x, $emptyPath);
    $this->assertEquals(FALSE, CRM_Utils_Array::pathIsset($x, $emptyPath));
    $this->assertEquals(NULL, CRM_Utils_Array::pathGet($x, $emptyPath));
    $this->assertEquals(NULL, $x);

    CRM_Utils_Array::pathSet($x, $emptyPath, 'buenos dias');
    $this->assertEquals(TRUE, CRM_Utils_Array::pathIsset($x, $emptyPath));
    $this->assertEquals('buenos dias', CRM_Utils_Array::pathGet($x, $emptyPath));
    $this->assertEquals('buenos dias', $x);
  }

  public static function getSortExamples() {
    $red = ['label' => 'Red', 'id' => 1, 'weight' => '90'];
    $orange = ['label' => 'Orange', 'id' => 2, 'weight' => '70'];
    $yellow = ['label' => 'Yellow', 'id' => 3, 'weight' => '10'];
    $green = ['label' => 'Green', 'id' => 4, 'weight' => '70'];
    $blue = ['label' => 'Blue', 'id' => 5, 'weight' => '70'];

    $examples = [];
    $examples[] = [
      [
        'r' => $red,
        'y' => $yellow,
        'g' => $green,
        'o' => $orange,
        'b' => $blue,
      ],
      'id',
      [
        'r' => $red,
        'o' => $orange,
        'y' => $yellow,
        'g' => $green,
        'b' => $blue,
      ],
    ];
    $examples[] = [
      [
        'r' => $red,
        'y' => $yellow,
        'g' => $green,
        'o' => $orange,
        'b' => $blue,
      ],
      'label',
      [
        'b' => $blue,
        'g' => $green,
        'o' => $orange,
        'r' => $red,
        'y' => $yellow,
      ],
    ];
    $examples[] = [
      [
        'r' => $red,
        'g' => $green,
        'y' => $yellow,
        'o' => $orange,
        'b' => $blue,
      ],
      ['weight', 'id'],
      [
        'y' => $yellow,
        'o' => $orange,
        'g' => $green,
        'b' => $blue,
        'r' => $red,
      ],
    ];

    return $examples;
  }

  /**
   * @param array $array
   * @param string|array $field
   * @param $expected
   * @dataProvider getSortExamples
   */
  public function testCrmArraySortByField($array, $field, $expected) {
    $actual = CRM_Utils_Array::crmArraySortByField($array, $field);

    // assertEquals() has nicer error output, but it's not precise about order.
    $this->assertEquals($expected, $actual);

    $aIter = new ArrayIterator($actual);
    $eIter = new ArrayIterator($expected);
    $this->assertEquals($eIter->count(), $aIter->count());
    $pos = 0;
    while ($aIter->valid()) {
      $this->assertEquals($eIter->key(), $aIter->key(), "Keys at offset $pos do not match");
      $this->assertEquals($eIter->current(), $aIter->current(), "Values at offset $pos do not match");
      $aIter->next();
      $eIter->next();
      $pos++;
    }
  }

  public static function getRecursiveIssetExamples() {
    return [
      [
        [[[], [0, 1, 2], []]], [0, 1, 2], TRUE,
      ],
      [
        [[[], [0, 1, 2], []]], [0, 1, 3], FALSE,
      ],
      [
        [], ['foo'], FALSE,
      ],
      [
        [NULL, ['wrong' => NULL, 'right' => ['foo' => 1, 'bar' => 2]]], [1, 'wrong'], FALSE,
      ],
      [
        [NULL, ['wrong' => NULL, 'right' => ['foo' => 1, 'bar' => 2]]], [1, 'right'], TRUE,
      ],
      [
        [NULL, ['wrong' => NULL, 'right' => ['foo' => 1, 'bar' => 2]]], [1, 'right', 'foo'], TRUE,
      ],
    ];
  }

  /**
   * @param $array
   * @param $path
   * @param $expected
   * @dataProvider getRecursiveIssetExamples
   */
  public function testRecursiveIsset($array, $path, $expected) {
    $result = CRM_Utils_Array::pathIsset($array, $path);
    $this->assertEquals($expected, $result);
  }

  public static function getRecursiveValueExamples() {
    return [
      [
        [[[], [0, 1, 2], []]], [0, 1, 2], NULL, 2,
      ],
      [
        [[[], [0, 1, 2], []]], [0, 1, 3], NULL, NULL,
      ],
      [
        [], ['foo'], FALSE, FALSE,
      ],
      [
        [NULL, ['wrong' => NULL, 'right' => ['foo' => 1, 'bar' => 2]]], [1, 'wrong'], 'nada', 'nada',
      ],
      [
        [NULL, ['wrong' => NULL, 'right' => ['foo' => 1, 'bar' => 2]]], [1, 'right'], NULL, ['foo' => 1, 'bar' => 2],
      ],
      [
        [NULL, ['wrong' => NULL, 'right' => ['foo' => 1, 'bar' => 2]]], [1, 'right', 'foo'], NULL, 1,
      ],
    ];
  }

  /**
   * @param $array
   * @param $path
   * @param $default
   * @param $expected
   * @dataProvider getRecursiveValueExamples
   */
  public function testRecursiveValue($array, $path, $default, $expected) {
    $result = CRM_Utils_Array::pathGet($array, $path, $default);
    $this->assertEquals($expected, $result);
  }

  /**
   * Get values for build test.
   */
  public static function getBuildValueExamples() {
    return [
      [
        [], [0, 'email', 2, 'location'], [0 => ['email' => [2 => ['location' => 'llama']]]],
      ],
      [
        ['foo', 'bar', [['donkey']]], [2, 0, 1], ['foo', 'bar', [['donkey', 'llama']]],
      ],
      [
        ['a' => [1, 2, 3], 'b' => ['x' => [], 'y' => ['a' => 'donkey', 'b' => 'bear'], 'z' => [4, 5, 6]]], ['b', 'y', 'b'], ['a' => [1, 2, 3], 'b' => ['x' => [], 'y' => ['a' => 'donkey', 'b' => 'llama'], 'z' => [4, 5, 6]]],
      ],
    ];
  }

  /**
   * Test the build recursive function.
   *
   * @param $source
   * @param $path
   * @param $expected
   *
   * @dataProvider getBuildValueExamples
   */
  public function testBuildRecursiveValue($source, $path, $expected) {
    CRM_Utils_Array::pathSet($source, $path, 'llama');
    $this->assertEquals($expected, $source);
  }

  /**
   * Test the flatten function
   */
  public function testFlatten(): void {
    $data = [
      'my_array' => [
        '0' => 'bar',
        '1' => 'baz',
        '2' => 'boz',
      ],
      'my_complex' => [
        'dog' => 'woof',
        'asdf' => [
          'my_zero' => 0,
          'my_int' => 1,
          'my_null' => NULL,
          'my_empty' => '',
        ],
      ],
      'my_simple' => 999,
    ];

    $expected = [
      'my_array.0' => 'bar',
      'my_array.1' => 'baz',
      'my_array.2' => 'boz',
      'my_complex.dog' => 'woof',
      'my_complex.asdf.my_zero' => 0,
      'my_complex.asdf.my_int' => 1,
      'my_complex.asdf.my_null' => NULL,
      'my_complex.asdf.my_empty' => '',
      'my_simple' => 999,
    ];

    $flat = [];
    CRM_Utils_Array::flatten($data, $flat);
    $this->assertEquals($flat, $expected);
  }

  public function testSingle(): void {
    $okExamples = [
      ['abc'],
      [123],
      [TRUE],
      [FALSE],
      [''],
      [[]],
      [[1, 2, 3]],
      ['a' => 'b'],
      (function () {
        yield 'abc';
      })(),
    ];
    $badExamples = [
      [],
      [1, 2],
      ['a' => 'b', 'c' => 'd'],
      [[], []],
      (function () {
        yield from [];
      })(),
      (function () {
        yield 1;
        yield 2;
      })(),
    ];

    $todoCount = count($okExamples) + count($badExamples);
    foreach ($okExamples as $i => $okExample) {
      $this->assertTrue(CRM_Utils_Array::single($okExample) !== NULL, "Expect to get a result from example ($i)");
      $todoCount--;
    }

    foreach ($badExamples as $i => $badExample) {
      try {
        CRM_Utils_Array::single($badExample);
        $this->fail("Expected exception for bad example ($i)");
      }
      catch (CRM_Core_Exception $e) {
        $todoCount--;
      }
    }

    $this->assertEquals(0, $todoCount);
  }

  public function testValue() {
    $list = ['a' => 'apple', 'b' => 'banana', 'c' => NULL];

    // array key exists; value is not null
    $this->assertEquals('apple', CRM_Utils_Array::value('a', $list, 'fruit'));

    // array key does not exist
    $this->assertEquals('fruit', CRM_Utils_Array::value(999, $list, 'fruit'));

    // array key exists; value is null
    // This is the one situation in which the function's behavior differs from
    // that of PHP's null-coalescing operator (??)
    $this->assertEquals(NULL, CRM_Utils_Array::value('c', $list, 'fruit'));
  }

  public function testDeepSort() {
    $unsorted = [
      '1-bbb' => '1-BBB',
      '1-aaa' => '1-AAA',
      '1-ddd' => [
        '2-bbb' => '2-BBB',
        '2-ccc' => '2-ccc',
        '2-aaa' => '2-AAA',
      ],
      '1-ccc' => '1-CCC',
    ];
    $expected = [
      '1-aaa' => '1-AAA',
      '1-bbb' => '1-BBB',
      '1-ccc' => '1-CCC',
      '1-ddd' => [
        '2-aaa' => '2-AAA',
        '2-bbb' => '2-BBB',
        '2-ccc' => '2-ccc',
      ],
    ];
    $sorted = $unsorted;
    CRM_Utils_Array::deepSort($sorted, fn(array &$a) => ksort($a));
    $this->assertEquals($expected, $sorted);
  }

  public function testFilter(): void {
    $data = [
      'alice' => ['name' => 'Alice', 'age' => 30, 'active' => TRUE],
      'bob' => ['name' => 'Bob', 'age' => 25, 'active' => FALSE],
      'charlie' => ['name' => 'Charlie', 'age' => 35, 'active' => TRUE],
      'david' => ['name' => 'David', 'age' => 28, 'active' => FALSE],
    ];

    $filtered = CRM_Utils_Array::filter($data, ['active' => TRUE]);
    $this->assertCount(2, $filtered);
    $this->assertArrayHasKey('alice', $filtered);
    $this->assertArrayHasKey('charlie', $filtered);

    $noResults = CRM_Utils_Array::filter($data, ['xyz' => TRUE]);
    $this->assertCount(0, $noResults);
  }

  public function testFind(): void {
    $data = [
      ['name' => 'Alice', 'age' => 30, 'active' => TRUE],
      ['name' => 'Bob', 'age' => 25, 'active' => FALSE],
      ['name' => 'Charlie', 'age' => 35, 'active' => TRUE],
      ['name' => 'David', 'age' => 28, 'active' => FALSE],
    ];
    $this->assertTrue((bool) CRM_Utils_Array::find($data, ['name' => 'Alice', 'age' => 30]));
    $this->assertNull(CRM_Utils_Array::find($data, ['name' => 'Alice', 'age' => 31]));

    $this->assertTrue((bool) CRM_Utils_Array::find($data, 'name'));
    $this->assertNull(CRM_Utils_Array::find($data, 'xyz'));
  }

  public function testFindAll(): void {
    $data = [
      ['name' => 'Alice', 'age' => 30, 'active' => TRUE],
      ['name' => 'Bob', 'age' => 25, 'active' => FALSE],
      ['name' => 'Charlie', 'age' => 35, 'active' => TRUE],
      ['name' => 'David', 'age' => 28, 'active' => FALSE, 'children' => []],
      [
        'name' => 'Eve',
        'age' => 25,
        'active' => FALSE,
        'children' => [
          ['name' => 'Billy', 'age' => 3, 'active' => TRUE],
          ['name' => 'Jilly', 'age' => 5, 'active' => FALSE],
        ],
        'parent' => ['name' => 'Walter', 'age' => 55, 'active' => TRUE],
      ],
    ];

    // Test filtering by active status
    $activeUsers = CRM_Utils_Array::findAll($data, ['active' => TRUE]);
    $this->assertCount(4, $activeUsers);
    $this->assertEquals('Alice', $activeUsers[0]['name']);
    $this->assertEquals('Charlie', $activeUsers[1]['name']);
    $this->assertEquals('Billy', $activeUsers[2]['name']);
    $this->assertEquals('Walter', $activeUsers[3]['name']);

    // Test filtering by age greater than 28
    $olderUsers = CRM_Utils_Array::findAll($data, fn($item) => ($item['age'] ?? 0) > 28);
    $this->assertCount(3, $olderUsers);
    $this->assertEquals('Alice', $olderUsers[0]['name']);
    $this->assertEquals('Charlie', $olderUsers[1]['name']);
    $this->assertEquals('Walter', $olderUsers[2]['name']);

    // Test filtering with no matches
    $noMatches = CRM_Utils_Array::findAll($data, fn($item) => ($item['age'] ?? 0) > 100);
    $this->assertCount(0, $noMatches);
    $this->assertEquals([], $noMatches);

    // Test filtering with all matches
    $allMatches = CRM_Utils_Array::findAll($data, 'name');
    $this->assertCount(5, $allMatches);

    // Test with empty array
    $emptyResult = CRM_Utils_Array::findAll([], 'name');
    $this->assertEquals([], $emptyResult);

    $childrenKeyExists = CRM_Utils_Array::findAll($data, 'children');
    $this->assertCount(2, $childrenKeyExists);
    $this->assertEquals('David', $childrenKeyExists[0]['name']);
    $this->assertEquals('Eve', $childrenKeyExists[1]['name']);
  }

  public function testRemoveRecursive(): void {
    $data = [
      ['name' => 'Alice', 'age' => 31, 'active' => TRUE],
      ['name' => 'Bob', 'age' => 25, 'active' => FALSE],
      ['name' => 'Charlie', 'age' => 35, 'active' => TRUE],
      ['name' => 'David', 'age' => 28, 'active' => FALSE],
      [
        'name' => 'Eve',
        'age' => 30,
        'active' => TRUE,
        'children' => [
          ['name' => 'Billy', 'age' => 3, 'active' => TRUE],
          ['name' => 'Jilly', 'age' => 5, 'active' => FALSE],
        ],
      ],
    ];

    // Test removing by active status
    $dataClone = $data;
    CRM_Utils_Array::removeRecursive($dataClone, ['active' => FALSE]);
    $this->assertCount(3, $dataClone);
    $this->assertEquals('Alice', $dataClone[0]['name']);
    $this->assertEquals('Charlie', $dataClone[2]['name']);
    $this->assertCount(1, $dataClone[4]['children']);

    // Test removing by age greater than 30
    $dataClone = $data;
    CRM_Utils_Array::removeRecursive($dataClone, fn($item) => $item['age'] > 30);
    $this->assertCount(3, $dataClone);
    $this->assertEquals('Bob', $dataClone[1]['name']);
    $this->assertEquals('David', $dataClone[3]['name']);
    $this->assertCount(2, $dataClone[4]['children']);

    // Test removing with no matches
    $dataClone = $data;
    CRM_Utils_Array::removeRecursive($dataClone, fn($item) => $item['age'] > 100);
    $this->assertCount(5, $dataClone);
    $this->assertEquals($data, $dataClone);

    // Test removing with all matches
    $dataClone = $data;
    CRM_Utils_Array::removeRecursive($dataClone, 'name');
    $this->assertCount(0, $dataClone);
    $this->assertEquals([], $dataClone);

    // Test with empty array
    $emptyData = [];
    CRM_Utils_Array::removeRecursive($emptyData, 'name');
    $this->assertEquals([], $emptyData);

    // Test removing by children key
    $dataClone = $data;
    CRM_Utils_Array::removeRecursive($dataClone, 'children');
    $this->assertCount(4, $dataClone);
    $this->assertEquals('Alice', $dataClone[0]['name']);
    $this->assertEquals('Bob', $dataClone[1]['name']);
    $this->assertEquals('Charlie', $dataClone[2]['name']);
    $this->assertEquals('David', $dataClone[3]['name']);
  }

}
