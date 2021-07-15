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
use api\v4\Traits\TableDropperTrait;
use Civi\Api4\CustomGroup;

abstract class BaseCustomValueTest extends UnitTestCase {

  use \api\v4\Traits\OptionCleanupTrait {
    setUp as setUpOptionCleanup;
  }
  use TableDropperTrait;

  /**
   * Set up baseline for testing
   */
  public function setUp(): void {
    $this->setUpOptionCleanup();
  }

  /**
   * Delete all created options groups.
   *
   * @throws \API_Exception
   */
  public function tearDown(): void {
    CustomGroup::delete(FALSE)->addWhere('id', '>', 0)->execute();
    parent::tearDown();
  }

}
