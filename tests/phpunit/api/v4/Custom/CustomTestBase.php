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

/**
 * @deprecated
 * Not needed if you use `$this->createTestRecord()` to make your custom groups,
 * as that will cleanup automatically.
 */
abstract class CustomTestBase extends Api4TestBase {

  /**
   * Delete all created custom groups.
   *
   * @throws \CRM_Core_Exception
   */
  public function tearDown(): void {
    CustomGroup::delete(FALSE)->addWhere('id', '>', 0)->execute();
    parent::tearDown();
  }

}
