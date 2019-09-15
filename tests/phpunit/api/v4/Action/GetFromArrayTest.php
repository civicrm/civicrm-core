<?php

namespace api\v4\Action;

use api\v4\UnitTestCase;
use Civi\Api4\MockArrayEntity;

/**
 * @group headless
 */
class GetFromArrayTest extends UnitTestCase {

  public function testArrayGetWithLimit() {
    $result = MockArrayEntity::get()
      ->setOffset(2)
      ->setLimit(2)
      ->execute();
    $this->assertEquals(3, $result[0]['field1']);
    $this->assertEquals(4, $result[1]['field1']);
    $this->assertEquals(2, count($result));
  }

  public function testArrayGetWithSort() {
    $result = MockArrayEntity::get()
      ->addOrderBy('field1', 'DESC')
      ->execute();
    $this->assertEquals([5, 4, 3, 2, 1], array_column((array) $result, 'field1'));

    $result = MockArrayEntity::get()
      ->addOrderBy('field5', 'DESC')
      ->addOrderBy('field2', 'ASC')
      ->execute();
    $this->assertEquals([3, 2, 5, 4, 1], array_column((array) $result, 'field1'));

    $result = MockArrayEntity::get()
      ->addOrderBy('field3', 'ASC')
      ->addOrderBy('field2', 'ASC')
      ->execute();
    $this->assertEquals([3, 1, 2, 5, 4], array_column((array) $result, 'field1'));
  }

  public function testArrayGetWithSelect() {
    $result = MockArrayEntity::get()
      ->addSelect('field1')
      ->addSelect('field3')
      ->setLimit(4)
      ->execute();
    $this->assertEquals([
      [
        'field1' => 1,
        'field3' => NULL,
      ],
      [
        'field1' => 2,
        'field3' => 0,
      ],
      [
        'field1' => 3,
      ],
      [
        'field1' => 4,
        'field3' => 1,
      ],
    ], (array) $result);
  }

  public function testArrayGetWithWhere() {
    $result = MockArrayEntity::get()
      ->addWhere('field2', '=', 'yack')
      ->execute();
    $this->assertEquals([2], array_column((array) $result, 'field1'));

    $result = MockArrayEntity::get()
      ->addWhere('field5', '!=', 'banana')
      ->addWhere('field3', 'IS NOT NULL')
      ->execute();
    $this->assertEquals([4, 5], array_column((array) $result, 'field1'));

    $result = MockArrayEntity::get()
      ->addWhere('field1', '>=', '4')
      ->execute();
    $this->assertEquals([4, 5], array_column((array) $result, 'field1'));

    $result = MockArrayEntity::get()
      ->addWhere('field1', '<', '2')
      ->execute();
    $this->assertEquals([1], array_column((array) $result, 'field1'));

    $result = MockArrayEntity::get()
      ->addWhere('field2', 'LIKE', '%ra%')
      ->execute();
    $this->assertEquals([1, 3], array_column((array) $result, 'field1'));

    $result = MockArrayEntity::get()
      ->addWhere('field3', 'IS NULL')
      ->execute();
    $this->assertEquals([1, 3], array_column((array) $result, 'field1'));

    $result = MockArrayEntity::get()
      ->addWhere('field3', '=', '0')
      ->execute();
    $this->assertEquals([2], array_column((array) $result, 'field1'));

    $result = MockArrayEntity::get()
      ->addWhere('field2', 'LIKE', '%ra')
      ->execute();
    $this->assertEquals([1], array_column((array) $result, 'field1'));

    $result = MockArrayEntity::get()
      ->addWhere('field2', 'LIKE', 'ra')
      ->execute();
    $this->assertEquals(0, count($result));

    $result = MockArrayEntity::get()
      ->addWhere('field2', 'NOT LIKE', '%ra%')
      ->execute();
    $this->assertEquals([2, 4, 5], array_column((array) $result, 'field1'));

    $result = MockArrayEntity::get()
      ->addWhere('field6', '=', '0')
      ->execute();
    $this->assertEquals([3, 4, 5], array_column((array) $result, 'field1'));

    $result = MockArrayEntity::get()
      ->addWhere('field6', '=', 0)
      ->execute();
    $this->assertEquals([3, 4, 5], array_column((array) $result, 'field1'));

    $result = MockArrayEntity::get()
      ->addWhere('field1', 'BETWEEN', [3, 5])
      ->execute();
    $this->assertEquals([3, 4, 5], array_column((array) $result, 'field1'));

    $result = MockArrayEntity::get()
      ->addWhere('field1', 'NOT BETWEEN', [3, 4])
      ->execute();
    $this->assertEquals([1, 2, 5], array_column((array) $result, 'field1'));
  }

  public function testArrayGetWithNestedWhereClauses() {
    $result = MockArrayEntity::get()
      ->addClause('OR', ['field2', 'LIKE', '%ra'], ['field2', 'LIKE', 'x ray'])
      ->execute();
    $this->assertEquals([1, 3], array_column((array) $result, 'field1'));

    $result = MockArrayEntity::get()
      ->addClause('OR', ['field2', '=', 'zebra'], ['field2', '=', 'yack'])
      ->addClause('OR', ['field5', '!=', 'apple'], ['field3', 'IS NULL'])
      ->execute();
    $this->assertEquals([1, 2], array_column((array) $result, 'field1'));

    $result = MockArrayEntity::get()
      ->addClause('NOT', ['field2', '!=', 'yack'])
      ->execute();
    $this->assertEquals([2], array_column((array) $result, 'field1'));

    $result = MockArrayEntity::get()
      ->addClause('OR', ['field1', '=', 2], ['AND', [['field5', '=', 'apple'], ['field3', '=', 1]]])
      ->execute();
    $this->assertEquals([2, 4, 5], array_column((array) $result, 'field1'));
  }

}
