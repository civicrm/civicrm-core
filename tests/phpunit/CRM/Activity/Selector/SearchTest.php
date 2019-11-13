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
 *  Class CRM_Activity_Selector_SearchTest
 *
 * @package CiviCRM
 */
class CRM_Activity_Selector_SearchTest extends CiviUnitTestCase {

  /**
   * Test activity search applies a permission based component filter.
   */
  public function testActivitySearchComponentPermission() {
    $this->activityCreate(['activity_type_id' => 'Contribution']);
    $this->activityCreate(['activity_type_id' => 'Pledge Reminder']);
    $this->activityCreate(['activity_type_id' => 'Meeting']);
    $this->setPermissions(['access CiviCRM', 'edit all contacts', 'access CiviContribute']);
    $queryParams = [['activity_location', '=', 'Baker Street', '', '']];
    $searchSelector = new CRM_Activity_Selector_Search($queryParams, CRM_Core_Action::VIEW);
    $this->assertEquals(2, $searchSelector->getTotalCount(NULL));
    $queryObject = $searchSelector->getQuery();
    $this->assertEquals("civicrm_activity.location = 'Baker Street'", $queryObject->_where[''][0]);
  }

}
