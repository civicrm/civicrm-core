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


namespace api\v4\Utils;

use api\v4\UnitTestCase;
use Civi\Api4\CustomGroup;
use Civi\Api4\Utils\CoreUtil;

/**
 * @group headless
 */
class CoreUtilTest extends UnitTestCase {

  /**
   */
  public function testGetApiNameFromTableName() {
    $this->assertEquals('Contact', CoreUtil::getApiNameFromTableName('civicrm_contact'));
    $this->assertNull(CoreUtil::getApiNameFromTableName('civicrm_nothing'));

    $singleGroup = CustomGroup::create(FALSE)
      ->addValue('title', uniqid())
      ->addValue('extends', 'Contact')
      ->execute()->first();

    $this->assertNull(CoreUtil::getApiNameFromTableName($singleGroup['table_name']));

    $multiGroup = CustomGroup::create(FALSE)
      ->addValue('title', uniqid())
      ->addValue('extends', 'Contact')
      ->addValue('is_multiple', TRUE)
      ->execute()->first();

    $this->assertEquals('Custom_' . $multiGroup['name'], CoreUtil::getApiNameFromTableName($multiGroup['table_name']));
    $this->assertEquals($multiGroup['table_name'], CoreUtil::getTableName('Custom_' . $multiGroup['name']));
  }

  public function testGetApiClass() {
    $this->assertEquals('Civi\Api4\Contact', CoreUtil::getApiClass('Contact'));
    $this->assertEquals('Civi\Api4\CiviCase', CoreUtil::getApiClass('Case'));
    $this->assertNull(CoreUtil::getApiClass('NothingAtAll'));

    $singleGroup = CustomGroup::create(FALSE)
      ->addValue('title', uniqid())
      ->addValue('extends', 'Contact')
      ->execute()->first();

    $this->assertNull(CoreUtil::getApiClass($singleGroup['name']));

    $multiGroup = CustomGroup::create(FALSE)
      ->addValue('title', uniqid())
      ->addValue('extends', 'Contact')
      ->addValue('is_multiple', TRUE)
      ->execute()->first();

    $this->assertEquals('Civi\Api4\CustomValue', CoreUtil::getApiClass('Custom_' . $multiGroup['name']));

  }

}
