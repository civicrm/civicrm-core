<?php

/**
 * Class CRM_Utils_ArrayTest
 * @group headless
 */
class CRM_Utils_ArrayTest extends CiviUnitTestCase {

  public function testIndexArray() {
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
    $this->assertEquals($inputs[5], $byLangMsgid[NULL]['greeting']);
  }

  public function testCollect() {
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

  public function testProduct0() {
    $actual = CRM_Utils_Array::product(
      [],
      ['base data' => 1]
    );
    $this->assertEquals([
      ['base data' => 1],
    ], $actual);
  }

  public function testProduct1() {
    $actual = CRM_Utils_Array::product(
      ['dim1' => ['a', 'b']],
      ['base data' => 1]
    );
    $this->assertEquals([
      ['base data' => 1, 'dim1' => 'a'],
      ['base data' => 1, 'dim1' => 'b'],
    ], $actual);
  }

  public function testProduct3() {
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

  public function testIsSubset() {
    $this->assertTrue(CRM_Utils_Array::isSubset([], []));
    $this->assertTrue(CRM_Utils_Array::isSubset(['a'], ['a']));
    $this->assertTrue(CRM_Utils_Array::isSubset(['a'], ['b', 'a', 'c']));
    $this->assertTrue(CRM_Utils_Array::isSubset(['b', 'd'], ['a', 'b', 'c', 'd']));
    $this->assertFalse(CRM_Utils_Array::isSubset(['a'], []));
    $this->assertFalse(CRM_Utils_Array::isSubset(['a'], ['b']));
    $this->assertFalse(CRM_Utils_Array::isSubset(['a'], ['b', 'c', 'd']));
  }

  public function testRemove() {
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

  public function testGetSetPathParts() {
    $arr = [
      'one' => '1',
      'two' => [
        'half' => 2,
      ],
    ];
    $this->assertEquals('1', CRM_Utils_Array::pathGet($arr, ['one']));
    $this->assertEquals('2', CRM_Utils_Array::pathGet($arr, ['two', 'half']));
    $this->assertEquals(NULL, CRM_Utils_Array::pathGet($arr, ['zoo', 'half']));
    CRM_Utils_Array::pathSet($arr, ['zoo', 'half'], '3');
    $this->assertEquals(3, CRM_Utils_Array::pathGet($arr, ['zoo', 'half']));
    $this->assertEquals(3, $arr['zoo']['half']);
  }

  public function getSortExamples() {
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

  public function getRecursiveIssetExamples() {
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

  public function getRecursiveValueExamples() {
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
  public function getBuildValueExamples() {
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
  public function testFlatten() {
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

}
