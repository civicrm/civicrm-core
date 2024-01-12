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

use api\v4\Api4TestBase;
use Civi\Api4\ContactType;
use Civi\Api4\EntitySet;
use Civi\Api4\Group;
use Civi\Api4\Relationship;
use Civi\Api4\Tag;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class EntitySetUnionTest extends Api4TestBase implements TransactionalInterface {

  public function testUnionGroupsWithTags(): void {
    $this->saveTestRecords('Group', [
      'records' => [
        ['title' => '1G', 'description' => 'Group 1'],
        ['title' => '2G', 'description' => 'Group 2'],
        ['title' => '3G', 'group_type:name' => ['Access Control', 'Mailing List']],
      ],
    ]);
    $this->saveTestRecords('Tag', [
      'records' => [
        ['name' => '3T', 'description' => 'Tag 3', 'used_for:name' => ['Contact', 'Activity']],
        ['name' => '2T', 'description' => 'Tag 2'],
        ['name' => '1T', 'description' => 'Tag 1'],
      ],
    ]);
    $result = EntitySet::get(FALSE)
      ->addSet('UNION ALL', Group::get()
        ->addSelect('title', 'description', '"group" AS thing')
        ->addWhere('title', 'IN', ['1G', '2G', '3G'])
      )
      ->addSet('UNION ALL', Tag::get()
        // The UNION will automatically alias Tag."name" to "title" because that's the column name in the 1st query
        ->addSelect('name', 'description', '"tag" AS thing')
        ->addWhere('name', 'IN', ['1T', '2T', '3T'])
      )
      ->addOrderBy('title')
      ->setLimit(5)
      ->execute();

    $this->assertCount(5, $result);
    $this->assertEquals(['title' => '1G', 'description' => 'Group 1', 'thing' => 'group'], $result[0]);
    $this->assertEquals(['title' => '1T', 'description' => 'Tag 1', 'thing' => 'tag'], $result[1]);
    $this->assertEquals(['title' => '2G', 'description' => 'Group 2', 'thing' => 'group'], $result[2]);
    $this->assertEquals(['title' => '2T', 'description' => 'Tag 2', 'thing' => 'tag'], $result[3]);
    $this->assertEquals(['title' => '3G', 'description' => NULL, 'thing' => 'group'], $result[4]);

    // Try with a "WHERE" clause
    $result = EntitySet::get(FALSE)
      ->addSet('UNION ALL', Group::get()
        ->addSelect('title', 'description', 'group_type:name AS type')
        ->addWhere('title', 'IN', ['1G', '2G', '3G'])
      )
      ->addSet('UNION ALL', Tag::get()
        ->addSelect('name', 'description', 'used_for:name')
        ->addWhere('name', 'IN', ['1T', '2T', '3T'])
      )
      ->addOrderBy('title')
      ->addWhere('title', 'LIKE', '3%')
      ->setDebug(TRUE)
      ->execute();
    $this->assertCount(2, $result);
    // Correct pseudoconstants should have been looked up for each row
    $this->assertEquals(['Access Control', 'Mailing List'], $result[0]['type']);
    $this->assertEquals(['Contact', 'Activity'], $result[1]['type']);
  }

  public function testGroupByUnionSet(): void {
    $contacts = $this->saveTestRecords('Contact', ['records' => 4])->column('id');
    $relationships = $this->saveTestRecords('Relationship', [
      'records' => [
        ['contact_id_a' => $contacts[0], 'contact_id_b' => $contacts[1]],
        ['contact_id_a' => $contacts[1], 'contact_id_b' => $contacts[2]],
        ['contact_id_a' => $contacts[2], 'contact_id_b' => $contacts[3]],
      ],
    ]);
    $result = EntitySet::get(FALSE)
      ->addSelect('COUNT(id) AS count', 'contact_id_a')
      ->addSet('UNION ALL', Relationship::get()
        ->addSelect('id', 'contact_id_a', 'contact_id_b', '"a_b" AS direction')
        ->addWhere('id', 'IN', $relationships->column('id'))
      )
      ->addSet('UNION ALL', Relationship::get()
        ->addSelect('id', 'contact_id_b', 'contact_id_a', '"b_a" AS direction')
        ->addWhere('id', 'IN', $relationships->column('id'))
      )
      ->addGroupBy('contact_id_a')
      ->addOrderBy('contact_id_a')
      ->execute();
    $this->assertCount(4, $result);
    $this->assertEquals(1, $result[0]['count']);
    $this->assertEquals(2, $result[1]['count']);
    $this->assertEquals(2, $result[2]['count']);
    $this->assertEquals(1, $result[3]['count']);
  }

  public function testUnionWithSelectAndOrderBy(): void {
    $contacts = $this->saveTestRecords('Contact', ['records' => 4])->column('id');
    $relationships = $this->saveTestRecords('Relationship', [
      'records' => [
        ['contact_id_a' => $contacts[0], 'contact_id_b' => $contacts[1]],
        ['contact_id_a' => $contacts[1], 'contact_id_b' => $contacts[2]],
        ['contact_id_a' => $contacts[1], 'contact_id_b' => $contacts[3]],
      ],
    ]);

    $result = EntitySet::get(FALSE)
      ->addSelect('contact_id_b', 'UPPER(direction) AS DIR')
      ->addSet('UNION ALL', Relationship::get()
        ->addSelect('id', 'contact_id_a', 'contact_id_b', '"a_b" AS direction')
        ->addWhere('id', 'IN', $relationships->column('id'))
      )
      ->addSet('UNION ALL', Relationship::get()
        ->addSelect('id', 'contact_id_b', 'contact_id_a', '"b_a" AS direction')
        ->addWhere('id', 'IN', $relationships->column('id'))
      )
      ->addWhere('contact_id_a', '=', $contacts[1])
      ->addOrderBy('direction')
      ->addOrderBy('id')
      ->execute();

    $this->assertCount(3, $result);
    $this->assertEquals('A_B', $result[0]['DIR']);
    $this->assertEquals('A_B', $result[1]['DIR']);
    $this->assertEquals('B_A', $result[2]['DIR']);
    $this->assertEquals($contacts[2], $result[0]['contact_id_b']);
    $this->assertEquals($contacts[3], $result[1]['contact_id_b']);
    $this->assertEquals($contacts[0], $result[2]['contact_id_b']);
  }

  public function testUnionWithSelectStar(): void {
    $subType = $this->createTestRecord('ContactType', [
      'parent_id:name' => 'Household',
      'name' => uniqid('HH1'),
    ]);
    $result = EntitySet::get(FALSE)
      ->addSelect('name', 'label', 'parent_id:name')
      ->addSet('UNION ALL', ContactType::get()
        ->addWhere('name', '=', 'Household')
      )
      ->addSet('UNION ALL', ContactType::get()
        ->addWhere('id', '=', $subType['id'])
      )
      ->addOrderBy('id')
      ->execute();
    $this->assertCount(2, $result);
    $this->assertEquals('Household', $result[1]['parent_id:name']);
    $this->assertEquals('Household', $result[0]['name']);

    $result = EntitySet::get(FALSE)
      ->addSelect('id', 'name', 'label', 'parent_id:name', 'is_parent')
      ->addSet('UNION ALL', ContactType::get()
        ->addSelect('*', 'TRUE AS is_parent')
        ->addWhere('name', '=', 'Household')
      )
      ->addSet('UNION ALL', ContactType::get()
        ->addSelect('*', 'FALSE AS is_parent')
        ->addWhere('id', '=', $subType['id'])
      )
      ->addOrderBy('is_parent')
      ->execute();

    $this->assertCount(2, $result);
    $this->assertEquals($subType['id'], $result[0]['id']);
    $this->assertIsInt($result[1]['id']);
    $this->assertFalse($result[0]['is_parent']);
    $this->assertEquals('Household', $result[0]['parent_id:name']);
    $this->assertEquals('Household', $result[1]['name']);
  }

}
