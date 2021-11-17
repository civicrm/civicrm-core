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

namespace api\v4\Entity;

use api\v4\UnitTestCase;
use Civi\Api4\OptionGroup;
use Civi\Api4\OptionValue;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class OptionValueTest extends UnitTestCase implements TransactionalInterface {

  public function testNullDefault() {
    OptionGroup::create(FALSE)
      ->addValue('name', 'myTestGroup')
      ->addValue('title', 'myTestGroup')
      ->execute();

    $defaultId = OptionValue::create()
      ->addValue('option_group_id.name', 'myTestGroup')
      ->addValue('label', 'One')
      ->addValue('value', 1)
      ->addValue('is_default', TRUE)
      ->execute()->first()['id'];

    $this->assertTrue(OptionValue::get(FALSE)->addWhere('id', '=', $defaultId)->execute()->first()['is_default']);

    // Now create a second option with is_default set to null.
    // This should not interfere with the default setting in option one
    OptionValue::create()
      ->addValue('option_group_id.name', 'myTestGroup')
      ->addValue('label', 'Two')
      ->addValue('value', 2)
      ->addValue('is_default', NULL)
      ->execute();

    $this->assertTrue(OptionValue::get(FALSE)->addWhere('id', '=', $defaultId)->execute()->first()['is_default']);
  }

}
