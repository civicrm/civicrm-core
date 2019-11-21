<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
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

  public function testActivityOrderBy() {
    $sortVars = [
      1 => [
        'name' => 'activity_date_time',
        'sort' => 'activity_date_time',
        'direction' => 2,
        'title' => 'Date',
      ],
      2 => [
        'name' => 'activity_type_id',
        'sort' => 'activity_type_id',
        'direction' => 4,
        'title' => 'Type',
      ],
      3 => [
        'name' => 'activity_subject',
        'sort' => 'activity_subject',
        'direction' => 4,
        'title' => 'Subject',
      ],
      4 => [
        'name' => 'source_contact',
        'sort' => 'source_contact',
        'direction' => 4,
        'title' => 'Added By',
      ],
      5 => [
        'name' => 'activity_status',
        'sort' => 'activity_status',
        'direction' => 1,
        'title' => 'Status',
      ],
    ];
    $sort = new CRM_Utils_Sort($sortVars, '5_u');
    $searchSelector = new CRM_Activity_Selector_Search($queryParams, CRM_Core_Action::VIEW);
    $searchSelector->getRows(4, 0, 50, $sort);
  }

}
