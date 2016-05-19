<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
class CRM_Contribute_Form_Task_PDFLetterCommonTest extends CiviUnitTestCase {

  /**
   * Assume empty database with just civicrm_data.
   */
  protected $_individualId;

  /**
   * Clean up after each test.
   */
  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
    CRM_Utils_Hook::singleton()->reset();
  }

  /**
   * Test the buildContributionArray function.
   */
  public function testBuildContributionArray() {
    $this->_individualId = $this->individualCreate();
    $params = array('contact_id' => $this->_individualId, 'total_amount' => 6, 'financial_type_id' => 'Donation');
    $contributionIDs = $returnProperties = $messageToken = array();
    $result = $this->callAPISuccess('Contribution', 'create', $params);
    $contributionIDs[] = $result['id'];
    $result = $this->callAPISuccess('Contribution', 'create', $params);
    $contributionIDs[] = $result['id'];
    $this->hookClass->setHook('civicrm_tokenValues', array($this, 'hookTokenValues'));

    list($contributions, $contacts) = CRM_Contribute_Form_Task_PDFLetterCommon::buildContributionArray('contact_id', $contributionIDs, $returnProperties, TRUE, TRUE, $messageToken, 'test', '**', FALSE);

    $this->assertEquals('Anthony', $contacts[$this->_individualId]['first_name']);
    $this->assertEquals('emo', $contacts[$this->_individualId]['favourite_emoticon']);
    $this->assertEquals('Donation', $contributions[$result['id']]['financial_type']);
  }

  /**
   * Implement token values hook.
   *
   * @param array $details
   * @param array $contactIDs
   * @param int $jobID
   * @param array $tokens
   * @param string $className
   */
  public function hookTokenValues(&$details, $contactIDs, $jobID, $tokens, $className) {
    foreach ($details as $index => $detail) {
      $details[$index]['favourite_emoticon'] = 'emo';
    }
  }

}
