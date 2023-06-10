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
use Civi\Api4\Group;
use Civi\Api4\Tag;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class GetWithUnionTest extends Api4TestBase implements TransactionalInterface {

  public function testUnionGroupsWithTags() {
    $this->saveTestRecords('Group', [
      'records' => [
        ['title' => '1G', 'description' => 'Group 1'],
        ['title' => '2G', 'description' => 'Group 2'],
        ['title' => '3G'],
      ],
    ]);
    $this->saveTestRecords('Tag', [
      'records' => [
        ['name' => '3T', 'description' => 'Tag 3'],
        ['name' => '2T', 'description' => 'Tag 2'],
        ['name' => '1T', 'description' => 'Tag 1'],
      ],
    ]);
    $result = Group::get(FALSE)
      ->addSelect('title', 'description', '"group" AS thing')
      ->addWhere('title', 'IN', ['1G', '2G', '3G'])
      ->addUnion(Tag::get()
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

    // Try with a "HAVING" clause
    $result = Group::get(FALSE)
      ->addSelect('title', 'description', '"group" AS thing')
      ->addWhere('title', 'IN', ['1G', '2G', '3G'])
      ->addUnion(Tag::get()
        ->addSelect('name', 'description', '"tag" AS thing')
        ->addWhere('name', 'IN', ['1T', '2T', '3T'])
      )
      ->addOrderBy('title')
      ->addHaving('title', 'LIKE', '1%')
      ->execute();
    $this->assertCount(2, $result);
  }

}
