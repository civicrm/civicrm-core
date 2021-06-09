<?php

/**
 * Class CRM_Utils_LazyArrayTest
 * @group headless
 */
class CRM_Utils_LazyArrayTest extends CiviUnitTestCase {

  public function testAssoc() {
    $l = $this->createFruitBasket();
    $this->assertFalse($l->isLoaded());

    $this->assertEquals('apple', $l['a']);
    $this->assertEquals('banana', $l['b']);
    $this->assertTrue($l->isLoaded());
    $this->assertEquals(3, count($l));
    $this->assertTrue(isset($l['c']));
    $this->assertFalse(isset($l['d']));

    $l['a'] = 'apricot';
    $this->assertEquals('apricot', $l['a']);
    $this->assertEquals(3, count($l));

    $l['d'] = 'date';
    $this->assertEquals('date', $l['d']);
    $this->assertEquals(4, count($l));

    $keys = [];
    foreach ($l as $key => $value) {
      $keys[] = $key;
    }
    $this->assertEquals(['a', 'b', 'c', 'd'], $keys);
    $this->assertEquals(['a', 'b', 'c', 'd'], array_keys(CRM_Utils_Array::cast($l)));
  }

  public function testNumeric() {
    $l = $this->createSeaRecords();
    $this->assertFalse($l->isLoaded());

    $this->assertEquals('aegean', $l[0]['name']);
    $this->assertEquals('caspian', $l[2]['name']);
    $this->assertTrue($l->isLoaded());
    $this->assertEquals(3, count($l));
    $this->assertTrue(isset($l[2]));
    $this->assertFalse(isset($l[3]));

    $l[2]['name'] = 'coral';
    $this->assertEquals(['name' => 'coral', 'area' => 371], $l['2']);
    $this->assertEquals(3, count($l));

    $l[] = ['name' => 'weddell', 'area' => 2800];
    $this->assertEquals('weddell', $l[3]['name']);
    $this->assertEquals(4, count($l));

    $keys = [];
    foreach ($l as $key => $value) {
      $keys[] = $key;
    }
    $this->assertEquals([0, 1, 2, 3], $keys);
    $this->assertEquals([0, 1, 2, 3], array_keys(CRM_Utils_Array::cast($l)));
  }

  public function testBasicInspections() {
    $l = $this->createFruitBasket();
    $this->assertFalse($l->isLoaded());

    $this->assertTrue($l !== NULL);
    $this->assertTrue($l instanceof CRM_Utils_LazyArray);
    $this->assertTrue(is_iterable($l));
    $this->assertTrue(!is_array($l));

    $this->assertFalse($l->isLoaded());

    $this->assertEquals(3, count($l));
    $this->assertTrue($l->isLoaded());
  }

  public function testCopy() {
    $l = $this->createFruitBasket();
    $copy = $l->getArrayCopy();
    $copy['d'] = 'date';

    $this->assertEquals(3, count($l));
    $this->assertEquals(4, count($copy));
  }

  /**
   * @return \CRM_Utils_LazyArray
   */
  private function createFruitBasket(): \CRM_Utils_LazyArray {
    return new CRM_Utils_LazyArray(function () {
      yield 'a' => 'apple';
      yield 'b' => 'banana';
      yield 'c' => 'cherry';
    });
  }

  /**
   * @return \CRM_Utils_LazyArray
   */
  private function createSeaRecords(): \CRM_Utils_LazyArray {
    return new CRM_Utils_LazyArray(function () {
      return [
        ['name' => 'aegean', 'area' => 214],
        ['name' => 'baltic', 'area' => 377],
        ['name' => 'caspian', 'area' => 371],
      ];
    });
  }

}
