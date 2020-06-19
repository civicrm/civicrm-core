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
 *  Test APIv3 civicrm_contribute_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contribution
 * @group headless
 */
class CRM_Campaign_Form_CampaignTest extends CiviUnitTestCase {

  /**
   * Test the submit function on the contribution page.
   *
   * @param string $thousandSeparator
   *
   * @dataProvider getThousandSeparators
   */
  public function testSubmit($thousandSeparator) {
    $this->setCurrencySeparators($thousandSeparator);
    $this->createLoggedInUser();
    $form = new CRM_Campaign_Form_Campaign();
    $form->_action = CRM_Core_Action::ADD;
    $result = CRM_Campaign_Form_Campaign::Submit([
      'goal_revenue' => '$10' . $thousandSeparator . '000',
      'is_active' => 1,
      'title' => 'Test Campaign',
      'start_date' => date('Y-m-d'),
      'includeGroups' => [],
      'custom' => [],
      'campaign_type_id' => 1,
    ], $form);
    $campaign = $this->callAPISuccess('campaign', 'get', ['id' => $result['id']]);
    $this->assertEquals('10000', $campaign['values'][$campaign['id']]['goal_revenue']);
  }

}
