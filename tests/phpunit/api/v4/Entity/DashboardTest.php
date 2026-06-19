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

namespace Civi\tests\phpunit\api\v4\Entity;

use api\v4\Api4TestBase;
use Civi\Api4\Dashboard;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class DashboardTest extends Api4TestBase implements TransactionalInterface {

  /**
   * Test row-level access controls for Dashboard entity via APIv4
   */
  public function testApiRowLevelAccess(): void {
    // With checkPermissions = TRUE, only permitted dashboards should return
    $dashboards = Dashboard::get(TRUE)
      ->addSelect('name')
      ->execute()
      ->column('name');

    $this->assertTrue(in_array('blog', $dashboards));
    $this->assertFalse(in_array('myCases', $dashboards));

    // With checkPermissions = FALSE, all dashboards should return
    $allDashboards = Dashboard::get(FALSE)
      ->addSelect('name')
      ->execute()
      ->column('name');

    $this->assertTrue(in_array('blog', $allDashboards));
    $this->assertTrue(in_array('myCases', $allDashboards));
  }

}
