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
  public function testActivitySearchComponentPermission(): void {
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

  /**
   * Test for absence of fatal error on sort.
   */
  public function testActivityOrderBy(): void {
    $sort = new CRM_Utils_Sort([
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
        'title' => 'Added by',
      ],
      5 => [
        'name' => 'activity_status',
        'sort' => 'activity_status',
        'direction' => 1,
        'title' => 'Status',
      ],
    ], '5_u');
    $this->getSearchRows([], $sort);
  }

  /**
   * Get the result of the search.
   *
   * @param array $queryParams
   * @param \CRM_Utils_Sort|NULL $sort
   *
   * @return array
   */
  protected function getSearchRows(array $queryParams, ?CRM_Utils_Sort $sort): array {
    $searchSelector = new CRM_Activity_Selector_Search($queryParams, CRM_Core_Action::VIEW);
    return $searchSelector->getRows(4, 0, 50, $sort);
  }

}
