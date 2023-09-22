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


namespace api\v4\Custom;

use api\v4\Api4TestBase;
use Civi\Api4\CustomGroup;
use Civi\Api4\CustomField;

/**
 * Use this base class for any APIv4 tests which create custom groups/fields,
 * to ensure they get cleaned up properly.
 *
 * Note: The TransactionalInterface won't work with custom fields because of adding/dropping tables.
 * So these tests have to do their own cleanup of any contacts or other entities created.
 * The recommended way is to override the `tearDown` function and calling `parent::tearDown()`.
 */
abstract class CustomTestBase extends Api4TestBase {

  /**
   * Delete all created options groups.
   *
   * @throws \CRM_Core_Exception
   */
  public function tearDown(): void {
    $optgroups = CustomField::get(FALSE)->addSelect('option_group_id')->addWhere('option_group_id', 'IS NOT NULL')->execute();
    foreach ($optgroups as $optgroup) {
      \Civi\Api4\OptionGroup::delete(FALSE)->addWhere('id', '=', $optgroup['option_group_id'])->execute();
    }
    CustomField::delete(FALSE)->addWhere('id', '>', 0)->execute();
    CustomGroup::delete(FALSE)->addWhere('id', '>', 0)->execute();
    parent::tearDown();
  }

}
