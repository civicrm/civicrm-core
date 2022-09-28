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
use Civi\Api4\CustomGroup;
use Civi\Api4\CustomField;

abstract class BaseCustomValueTest extends UnitTestCase {

  /**
   * Delete all created options groups.
   *
   * @throws \API_Exception
   */
  public function tearDown(): void {
    $optgroups = CustomField::get(FALSE)->addSelect('option_group_id')->addWhere('option_group_id', 'IS NOT NULL')->execute();
    foreach ($optgroups as $optgroup) {
      \Civi\Api4\OptionValue::delete(FALSE)->addWhere('option_group_id', '=', $optgroup['option_group_id'])->execute();
      \Civi\Api4\OptionGroup::delete(FALSE)->addWhere('id', '=', $optgroup['option_group_id'])->execute();
    }
    CustomField::delete(FALSE)->addWhere('id', '>', 0)->execute();
    CustomGroup::delete(FALSE)->addWhere('id', '>', 0)->execute();
    parent::tearDown();
  }

}
