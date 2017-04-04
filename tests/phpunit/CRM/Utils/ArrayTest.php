<?php

/**
 * Class CRM_Utils_ArrayTest
 * @group headless
 */
class CRM_Utils_ArrayTest extends CiviUnitTestCase {

  public function testIndexArray() {
    $inputs = array();
    $inputs[] = array(
      'lang' => 'en',
      'msgid' => 'greeting',
      'familiar' => FALSE,
      'value' => 'Hello',
    );
    $inputs[] = array(
      'lang' => 'en',
      'msgid' => 'parting',
      'value' => 'Goodbye',
    );
    $inputs[] = array(
      'lang' => 'fr',
      'msgid' => 'greeting',
      'value' => 'Bon jour',
    );
    $inputs[] = array(
      'lang' => 'fr',
      'msgid' => 'parting',
      'value' => 'Au revoir',
    );
    $inputs[] = array(
      'lang' => 'en',
      'msgid' => 'greeting',
      'familiar' => TRUE,
      'value' => 'Hey',
    );
    $inputs[] = array(
      'msgid' => 'greeting',
      'familiar' => TRUE,
      'value' => 'Universal greeting',
    );

    $byLangMsgid = CRM_Utils_Array::index(array('lang', 'msgid'), $inputs);
    $this->assertEquals($inputs[4], $byLangMsgid['en']['greeting']);
    $this->assertEquals($inputs[1], $byLangMsgid['en']['parting']);
    $this->assertEquals($inputs[2], $byLangMsgid['fr']['greeting']);
    $this->assertEquals($inputs[3], $byLangMsgid['fr']['parting']);
    $this->assertEquals($inputs[5], $byLangMsgid[NULL]['greeting']);
  }

  public function testCollect() {
    $arr = array(
      array('catWord' => 'cat', 'dogWord' => 'dog'),
      array('catWord' => 'chat', 'dogWord' => 'chien'),
      array('catWord' => 'gato'),
    );
    $expected = array('cat', 'chat', 'gato');
    $this->assertEquals($expected, CRM_Utils_Array::collect('catWord', $arr));

    $arr = array();
    $arr['en'] = (object) array('catWord' => 'cat', 'dogWord' => 'dog');
    $arr['fr'] = (object) array('catWord' => 'chat', 'dogWord' => 'chien');
    $arr['es'] = (object) array('catWord' => 'gato');
    $expected = array('en' => 'cat', 'fr' => 'chat', 'es' => 'gato');
    $this->assertEquals($expected, CRM_Utils_Array::collect('catWord', $arr));
  }

  public function testProduct0() {
    $actual = CRM_Utils_Array::product(
      array(),
      array('base data' => 1)
    );
    $this->assertEquals(array(
      array('base data' => 1),
    ), $actual);
  }

  public function testProduct1() {
    $actual = CRM_Utils_Array::product(
      array('dim1' => array('a', 'b')),
      array('base data' => 1)
    );
    $this->assertEquals(array(
      array('base data' => 1, 'dim1' => 'a'),
      array('base data' => 1, 'dim1' => 'b'),
    ), $actual);
  }

  public function testProduct3() {
    $actual = CRM_Utils_Array::product(
      array('dim1' => array('a', 'b'), 'dim2' => array('alpha', 'beta'), 'dim3' => array('one', 'two')),
      array('base data' => 1)
    );
    $this->assertEquals(array(
      array('base data' => 1, 'dim1' => 'a', 'dim2' => 'alpha', 'dim3' => 'one'),
      array('base data' => 1, 'dim1' => 'a', 'dim2' => 'alpha', 'dim3' => 'two'),
      array('base data' => 1, 'dim1' => 'a', 'dim2' => 'beta', 'dim3' => 'one'),
      array('base data' => 1, 'dim1' => 'a', 'dim2' => 'beta', 'dim3' => 'two'),
      array('base data' => 1, 'dim1' => 'b', 'dim2' => 'alpha', 'dim3' => 'one'),
      array('base data' => 1, 'dim1' => 'b', 'dim2' => 'alpha', 'dim3' => 'two'),
      array('base data' => 1, 'dim1' => 'b', 'dim2' => 'beta', 'dim3' => 'one'),
      array('base data' => 1, 'dim1' => 'b', 'dim2' => 'beta', 'dim3' => 'two'),
    ), $actual);
  }

  public function testIsSubset() {
    $this->assertTrue(CRM_Utils_Array::isSubset(array(), array()));
    $this->assertTrue(CRM_Utils_Array::isSubset(array('a'), array('a')));
    $this->assertTrue(CRM_Utils_Array::isSubset(array('a'), array('b', 'a', 'c')));
    $this->assertTrue(CRM_Utils_Array::isSubset(array('b', 'd'), array('a', 'b', 'c', 'd')));
    $this->assertFalse(CRM_Utils_Array::isSubset(array('a'), array()));
    $this->assertFalse(CRM_Utils_Array::isSubset(array('a'), array('b')));
    $this->assertFalse(CRM_Utils_Array::isSubset(array('a'), array('b', 'c', 'd')));
  }

  public function testRemove() {
    $data = array(
      'one' => 1,
      'two' => 2,
      'three' => 3,
      'four' => 4,
      'five' => 5,
      'six' => 6,
    );
    CRM_Utils_Array::remove($data, 'one', 'two', array('three', 'four'), 'five');
    $this->assertEquals($data, array('six' => 6));
  }

  public function testGetSetPathParts() {
    $arr = array(
      'one' => '1',
      'two' => array(
        'half' => 2,
      ),
    );
    $this->assertEquals('1', CRM_Utils_Array::pathGet($arr, array('one')));
    $this->assertEquals('2', CRM_Utils_Array::pathGet($arr, array('two', 'half')));
    $this->assertEquals(NULL, CRM_Utils_Array::pathGet($arr, array('zoo', 'half')));
    CRM_Utils_Array::pathSet($arr, array('zoo', 'half'), '3');
    $this->assertEquals(3, CRM_Utils_Array::pathGet($arr, array('zoo', 'half')));
    $this->assertEquals(3, $arr['zoo']['half']);
  }

  public function getSortExamples() {
    $red = array('label' => 'Red', 'id' => 1, 'weight' => '90');
    $orange = array('label' => 'Orange', 'id' => 2, 'weight' => '70');
    $yellow = array('label' => 'Yellow', 'id' => 3, 'weight' => '10');
    $green = array('label' => 'Green', 'id' => 4, 'weight' => '70');
    $blue = array('label' => 'Blue', 'id' => 5, 'weight' => '70');

    $examples = array();
    $examples[] = array(
      array(
        'r' => $red,
        'y' => $yellow,
        'g' => $green,
        'o' => $orange,
        'b' => $blue,
      ),
      'id',
      array(
        'r' => $red,
        'o' => $orange,
        'y' => $yellow,
        'g' => $green,
        'b' => $blue,
      ),
    );
    $examples[] = array(
      array(
        'r' => $red,
        'y' => $yellow,
        'g' => $green,
        'o' => $orange,
        'b' => $blue,
      ),
      'label',
      array(
        'b' => $blue,
        'g' => $green,
        'o' => $orange,
        'r' => $red,
        'y' => $yellow,
      ),
    );
    $examples[] = array(
      array(
        'r' => $red,
        'g' => $green,
        'y' => $yellow,
        'o' => $orange,
        'b' => $blue,
      ),
      array('weight', 'id'),
      array(
        'y' => $yellow,
        'o' => $orange,
        'g' => $green,
        'b' => $blue,
        'r' => $red,
      ),
    );

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

}
