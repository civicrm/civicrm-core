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
