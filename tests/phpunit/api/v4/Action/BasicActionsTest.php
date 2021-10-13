<?php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */


namespace api\v4\Action;

use api\v4\UnitTestCase;
use Civi\Api4\MockBasicEntity;
use Civi\Api4\Utils\CoreUtil;
use Civi\Core\Event\GenericHookEvent;
use Civi\Test\HookInterface;

/**
 * @group headless
 */
class BasicActionsTest extends UnitTestCase implements HookInterface {

  /**
   * Listens for civi.api4.entityTypes event to manually add this nonstandard entity
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  public function on_civi_api4_entityTypes(GenericHookEvent $e): void {
    $e->entities['MockBasicEntity'] = MockBasicEntity::getInfo();
  }

  public function setUpHeadless() {
    // Ensure MockBasicEntity gets added via above listener
    \Civi::cache('metadata')->clear();
    return parent::setUpHeadless();
  }

  private function replaceRecords(&$records) {
    MockBasicEntity::delete()->addWhere('identifier', '>', 0)->execute();
    foreach ($records as &$record) {
      $record['identifier'] = MockBasicEntity::create()->setValues($record)->execute()->first()['identifier'];
    }
  }

  public function testGetInfo() {
    $info = MockBasicEntity::getInfo();
    $this->assertEquals('MockBasicEntity', $info['name']);
    $this->assertEquals(['identifier'], $info['primary_key']);
    $this->assertEquals('identifier', CoreUtil::getIdFieldName('MockBasicEntity'));
  }

  public function testCrud() {
    MockBasicEntity::delete()->addWhere('identifier', '>', 0)->execute();

    $id1 = MockBasicEntity::create()->addValue('foo', 'one')->execute()->first()['identifier'];

    $result = MockBasicEntity::get()->execute();
    $this->assertCount(1, $result);

    $id2 = MockBasicEntity::create()
      ->addValue('foo', 'two')
      ->addValue('group:label', 'First')
      ->execute()->first()['identifier'];

    $result = MockBasicEntity::get()->selectRowCount()->execute();
    $this->assertEquals(2, $result->count());

    // Updating a single record should support identifier either in the values or the where clause
    // Test both styles of update
    MockBasicEntity::update()->addWhere('identifier', '=', $id2)->addValue('foo', 'new')->execute();
    MockBasicEntity::update()->addValue('identifier', $id2)->addValue('color', 'red')->execute();

    $result = MockBasicEntity::get()->addOrderBy('identifier', 'DESC')->setLimit(1)->execute();
    // The object's count() method will account for all results, ignoring limit, while the array results are limited
    $this->assertCount(2, $result);
    $this->assertCount(1, (array) $result);
    $this->assertEquals('new', $result->first()['foo']);
    $this->assertEquals('red', $result->first()['color']);
    $this->assertEquals('one', $result->first()['group']);

    $result = MockBasicEntity::save()
      ->addRecord(['identifier' => $id1, 'foo' => 'one updated', 'weight' => '5'])
      ->addRecord(['identifier' => $id2, 'group:label' => 'Second'])
      ->addRecord(['foo' => 'three'])
      ->addDefault('color', 'pink')
      ->setReload(TRUE)
      ->execute()
      ->indexBy('identifier');

    $this->assertTrue(5 === $result[$id1]['weight']);
    $this->assertEquals('new', $result[$id2]['foo']);
    $this->assertEquals('two', $result[$id2]['group']);
    $this->assertEquals('three', $result->last()['foo']);
    $this->assertCount(3, $result);
    foreach ($result as $item) {
      $this->assertEquals('pink', $item['color']);
    }

    $ent1 = MockBasicEntity::get()->addWhere('identifier', '=', $id1)->execute()->first();
    $this->assertEquals('one updated', $ent1['foo']);
    $this->assertFalse(isset($ent1['group:label']));

    $ent2 = MockBasicEntity::get()->addWhere('group:label', '=', 'Second')->addSelect('group:label', 'group')->execute()->first();
    $this->assertEquals('two', $ent2['group']);
    $this->assertEquals('Second', $ent2['group:label']);
    // We didn't select this
    $this->assertFalse(isset($ent2['group:name']));

    // With no SELECT, all fields should be returned but not suffixy stuff like group:name
    $ent2 = MockBasicEntity::get()->addWhere('group:label', '=', 'Second')->execute()->first();
    $this->assertEquals('two', $ent2['group']);
    $this->assertFalse(isset($ent2['group:name']));
    // This one wasn't selected but did get used by the WHERE clause; ensure it isn't returned
    $this->assertFalse(isset($ent2['group:label']));

    MockBasicEntity::delete()->addWhere('identifier', '=', $id2);
    $result = MockBasicEntity::get()->execute();
    $this->assertEquals('one updated', $result->first()['foo']);
  }

  public function testReplace() {
    $objects = [
      ['group' => 'one', 'color' => 'red'],
      ['group' => 'one', 'color' => 'blue'],
      ['group' => 'one', 'color' => 'green'],
      ['group' => 'two', 'color' => 'orange'],
    ];

    $this->replaceRecords($objects);

    // Keep red, change blue, delete green, and add yellow
    $replacements = [
      ['color' => 'red', 'identifier' => $objects[0]['identifier']],
      ['color' => 'not blue', 'identifier' => $objects[1]['identifier']],
      ['color' => 'yellow'],
    ];

    MockBasicEntity::replace()->addWhere('group', '=', 'one')->setRecords($replacements)->execute();

    $newObjects = MockBasicEntity::get()->addOrderBy('identifier', 'DESC')->execute()->indexBy('identifier');

    $this->assertCount(4, $newObjects);

    $this->assertEquals('yellow', $newObjects->first()['color']);

    $this->assertEquals('not blue', $newObjects[$objects[1]['identifier']]['color']);

    // Ensure group two hasn't been altered
    $this->assertEquals('orange', $newObjects[$objects[3]['identifier']]['color']);
    $this->assertEquals('two', $newObjects[$objects[3]['identifier']]['group']);
  }

  public function testBatchFrobnicate() {
    $objects = [
      ['group' => 'one', 'color' => 'red', 'number' => 10],
      ['group' => 'one', 'color' => 'blue', 'number' => 20],
      ['group' => 'one', 'color' => 'green', 'number' => 30],
      ['group' => 'two', 'color' => 'blue', 'number' => 40],
    ];
    $this->replaceRecords($objects);

    $result = MockBasicEntity::batchFrobnicate()->addWhere('color', '=', 'blue')->execute();
    $this->assertEquals(2, count($result));
    $this->assertEquals([400, 1600], \CRM_Utils_Array::collect('frobnication', (array) $result));
  }

  public function testGetFields() {
    $getFields = MockBasicEntity::getFields()->execute()->indexBy('name');

    $this->assertCount(8, $getFields);
    $this->assertEquals('Identifier', $getFields['identifier']['title']);
    // Ensure default data type is "String" when not specified
    $this->assertEquals('String', $getFields['color']['data_type']);

    // Getfields should default to loadOptions = false and reduce them to bool
    $this->assertTrue($getFields['group']['options']);
    $this->assertTrue($getFields['fruit']['options']);
    $this->assertFalse($getFields['identifier']['options']);

    // Getfields should figure out what suffixes are available based on option keys
    $this->assertEquals(['name', 'label'], $getFields['group']['suffixes']);
    $this->assertEquals(['name', 'label', 'color'], $getFields['fruit']['suffixes']);

    // Load simple options
    $getFields = MockBasicEntity::getFields()
      ->addWhere('name', 'IN', ['group', 'fruit'])
      ->setLoadOptions(TRUE)
      ->execute()->indexBy('name');

    $this->assertCount(2, $getFields);
    $this->assertArrayHasKey('one', $getFields['group']['options']);
    // Complex options should be reduced to simple array
    $this->assertArrayHasKey(1, $getFields['fruit']['options']);
    $this->assertEquals('Banana', $getFields['fruit']['options'][3]);

    // Load complex options
    $getFields = MockBasicEntity::getFields()
      ->addWhere('name', 'IN', ['group', 'fruit'])
      ->setLoadOptions(['id', 'name', 'label', 'color'])
      ->execute()->indexBy('name');

    // Simple options should be expanded to non-assoc array
    $this->assertCount(2, $getFields);
    $this->assertEquals('one', $getFields['group']['options'][0]['id']);
    $this->assertEquals('First', $getFields['group']['options'][0]['name']);
    $this->assertEquals('First', $getFields['group']['options'][0]['label']);
    $this->assertFalse(isset($getFields['group']['options'][0]['color']));
    // Complex options should give all requested properties
    $this->assertEquals('Banana', $getFields['fruit']['options'][2]['label']);
    $this->assertEquals('yellow', $getFields['fruit']['options'][2]['color']);
  }

  public function testItemsToGet() {
    $get = MockBasicEntity::get()
      ->addWhere('color', 'NOT IN', ['yellow'])
      ->addWhere('color', 'IN', ['red', 'blue'])
      ->addWhere('color', '!=', 'green')
      ->addWhere('group', '=', 'one')
      ->addWhere('size', 'LIKE', 'big')
      ->addWhere('shape', 'LIKE', '%a');

    $itemsToGet = new \ReflectionMethod($get, '_itemsToGet');
    $itemsToGet->setAccessible(TRUE);

    $this->assertEquals(['red', 'blue'], $itemsToGet->invoke($get, 'color'));
    $this->assertEquals(['one'], $itemsToGet->invoke($get, 'group'));
    $this->assertEquals(['big'], $itemsToGet->invoke($get, 'size'));
    $this->assertEmpty($itemsToGet->invoke($get, 'shape'));
    $this->assertEmpty($itemsToGet->invoke($get, 'weight'));
  }

  public function testFieldsToGet() {
    $get = MockBasicEntity::get()
      ->addWhere('color', '!=', 'green');

    $isFieldSelected = new \ReflectionMethod($get, '_isFieldSelected');
    $isFieldSelected->setAccessible(TRUE);

    // If no "select" is set, should always return true
    $this->assertTrue($isFieldSelected->invoke($get, 'color'));
    $this->assertTrue($isFieldSelected->invoke($get, 'shape'));
    $this->assertTrue($isFieldSelected->invoke($get, 'size', 'color', 'shape'));

    // With a non-empty "select" fieldsToSelect() will return fields needed to evaluate each clause.
    $get->addSelect('identifier');
    $this->assertTrue($isFieldSelected->invoke($get, 'color', 'shape', 'size'));
    $this->assertTrue($isFieldSelected->invoke($get, 'identifier'));
    $this->assertFalse($isFieldSelected->invoke($get, 'shape', 'size', 'weight'));
    $this->assertFalse($isFieldSelected->invoke($get, 'group'));

    $get->addClause('OR', ['shape', '=', 'round'], ['AND', [['size', '=', 'big'], ['weight', '!=', 'small']]]);
    $this->assertTrue($isFieldSelected->invoke($get, 'color'));
    $this->assertTrue($isFieldSelected->invoke($get, 'identifier'));
    $this->assertTrue($isFieldSelected->invoke($get, 'shape'));
    $this->assertTrue($isFieldSelected->invoke($get, 'size'));
    $this->assertTrue($isFieldSelected->invoke($get, 'group', 'weight'));
    $this->assertFalse($isFieldSelected->invoke($get, 'group'));

    $get->addOrderBy('group');
    $this->assertTrue($isFieldSelected->invoke($get, 'group'));
  }

  public function testWildcardSelect() {
    $records = [
      ['group' => 'one', 'color' => 'red', 'shape' => 'round', 'size' => 'med', 'weight' => 10],
      ['group' => 'two', 'color' => 'blue', 'shape' => 'round', 'size' => 'med', 'weight' => 20],
    ];
    $this->replaceRecords($records);

    foreach (MockBasicEntity::get()->addSelect('*')->execute() as $result) {
      ksort($result);
      $this->assertEquals(['color', 'group', 'identifier', 'shape', 'size', 'weight'], array_keys($result));
    }

    $result = MockBasicEntity::get()
      ->addSelect('s*e', 'weig*ht')
      ->execute()
      ->first();
    $this->assertEquals(['shape', 'size', 'weight'], array_keys($result));
  }

  public function testEmptyAndNullOperators() {
    $records = [
      ['id' => NULL],
      ['color' => '', 'weight' => 0],
      ['color' => 'yellow', 'weight' => 100000000000],
    ];
    $this->replaceRecords($records);

    $result = MockBasicEntity::get()
      ->addWhere('color', 'IS NULL')
      ->execute()->indexBy('identifier');
    $this->assertCount(1, $result);
    $this->assertArrayHasKey($records[0]['identifier'], (array) $result);

    $result = MockBasicEntity::get()
      ->addWhere('color', 'IS EMPTY')
      ->execute()->indexBy('identifier');
    $this->assertCount(2, $result);
    $this->assertArrayNotHasKey($records[2]['identifier'], (array) $result);

    $result = MockBasicEntity::get()
      ->addWhere('color', 'IS NOT EMPTY')
      ->execute()->indexBy('identifier');
    $this->assertCount(1, $result);
    $this->assertArrayHasKey($records[2]['identifier'], (array) $result);

    $result = MockBasicEntity::get()
      ->addWhere('weight', 'IS NULL')
      ->execute()->indexBy('identifier');
    $this->assertCount(1, $result);
    $this->assertArrayHasKey($records[0]['identifier'], (array) $result);

    $result = MockBasicEntity::get()
      ->addWhere('weight', 'IS EMPTY')
      ->execute()->indexBy('identifier');
    $this->assertCount(2, $result);
    $this->assertArrayNotHasKey($records[2]['identifier'], (array) $result);

    $result = MockBasicEntity::get()
      ->addWhere('weight', 'IS NOT EMPTY')
      ->execute()->indexBy('identifier');
    $this->assertCount(1, $result);
    $this->assertArrayHasKey($records[2]['identifier'], (array) $result);
  }

  public function testContainsOperator() {
    $records = [
      ['group' => 'one', 'fruit:name' => ['apple', 'pear'], 'weight' => 11],
      ['group' => 'two', 'fruit:name' => ['pear', 'banana'], 'weight' => 12],
    ];
    $this->replaceRecords($records);

    $result = MockBasicEntity::get()
      ->addWhere('fruit:name', 'CONTAINS', 'apple')
      ->execute();
    $this->assertCount(1, $result);
    $this->assertEquals('one', $result->first()['group']);

    $result = MockBasicEntity::get()
      ->addWhere('fruit:name', 'CONTAINS', 'pear')
      ->execute();
    $this->assertCount(2, $result);

    $result = MockBasicEntity::get()
      ->addWhere('group', 'CONTAINS', 'o')
      ->execute();
    $this->assertCount(2, $result);

    $result = MockBasicEntity::get()
      ->addWhere('weight', 'CONTAINS', 1)
      ->execute();
    $this->assertCount(2, $result);

    $result = MockBasicEntity::get()
      ->addWhere('fruit:label', 'CONTAINS', 'Banana')
      ->execute();
    $this->assertCount(1, $result);
    $this->assertEquals('two', $result->first()['group']);

    $result = MockBasicEntity::get()
      ->addWhere('weight', 'CONTAINS', 2)
      ->execute();
    $this->assertCount(1, $result);
    $this->assertEquals('two', $result->first()['group']);
  }

  public function testRegexpOperators() {
    $records = [
      ['color' => 'red'],
      ['color' => 'blue'],
      ['color' => 'brown'],
    ];
    $this->replaceRecords($records);

    $result = MockBasicEntity::get()
      ->addWhere('color', 'REGEXP', '^b')
      ->execute();
    $this->assertCount(2, $result);
    $this->assertEquals('blue', $result[0]['color']);
    $this->assertEquals('brown', $result[1]['color']);

    $result = MockBasicEntity::get()
      ->addWhere('color', 'NOT REGEXP', '^b')
      ->execute();
    $this->assertCount(1, $result);
    $this->assertEquals('red', $result[0]['color']);
  }

  public function testPseudoconstantMatch() {
    $records = [
      ['group:label' => 'First', 'shape' => 'round', 'fruit:name' => 'banana'],
      ['group:name' => 'Second', 'shape' => 'square', 'fruit:label' => 'Pear'],
    ];
    $this->replaceRecords($records);

    $results = MockBasicEntity::get()
      ->addSelect('*', 'group:label', 'group:name', 'fruit:name', 'fruit:color', 'fruit:label')
      ->addOrderBy('fruit:color', "DESC")
      ->execute();

    $this->assertEquals('round', $results[0]['shape']);
    $this->assertEquals('one', $results[0]['group']);
    $this->assertEquals('First', $results[0]['group:label']);
    $this->assertEquals('First', $results[0]['group:name']);
    $this->assertEquals(3, $results[0]['fruit']);
    $this->assertEquals('Banana', $results[0]['fruit:label']);
    $this->assertEquals('banana', $results[0]['fruit:name']);
    $this->assertEquals('yellow', $results[0]['fruit:color']);

    // Reverse order
    $results = MockBasicEntity::get()
      ->addOrderBy('fruit:color')
      ->execute();
    $this->assertEquals('two', $results[0]['group']);

    // Cannot match to a non-unique option property like :color on create
    try {
      MockBasicEntity::create()->addValue('fruit:color', 'yellow')->execute();
    }
    catch (\API_Exception $createError) {
    }
    $this->assertStringContainsString('Illegal expression', $createError->getMessage());
  }

}
