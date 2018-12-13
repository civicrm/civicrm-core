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
 * Class CRM_Contribute_Page_AjaxTest
 * @group headless
 */
class CRM_Contribute_Page_AjaxTest extends CiviUnitTestCase {

  protected $_params = array();

  public function setUp() {
    parent::setUp();

    $this->_fields = array('amount', 'sct_label');

    $this->_params = array(
      'page' => 1,
      'rp' => 50,
      'offset' => 0,
      'rowCount' => 50,
      'sort' => NULL,
      'is_unit_test' => TRUE,
    );
    $softContactParams = array(
      'first_name' => 'soft',
      'last_name' => 'Contact',
    );
    $this->_softContactId = $this->individualCreate($softContactParams);

    //create three sample contacts
    foreach (array(0, 1, 2) as $seq) {
      $this->_primaryContacts[] = $this->individualCreate(array(), $seq);
    }
  }

  /**
   * Test retrieve Soft Contribution through AJAX
   */
  public function testGetSoftContributionSelector() {
    $softTypes = array(3, 2, 5);
    $amounts = array('100', '600', '150');

    // create sample soft contribution for contact
    foreach ($this->_primaryContacts as $seq => $contactId) {
      $this->callAPISuccess('Contribution', 'create', array(
        'contact_id' => $contactId,
        'receive_date' => date('Ymd'),
        'total_amount' => $amounts[$seq],
        'financial_type_id' => 1,
        'non_deductible_amount' => '10',
        'contribution_status_id' => 1,
        'soft_credit' => array(
          '1' => array(
            'contact_id' => $this->_softContactId,
            'amount' => $amounts[$seq],
            'soft_credit_type_id' => $softTypes[$seq],
          ),
        ),
      ));
    }

    $_GET = array_merge($this->_params,
      array(
        'cid' => $this->_softContactId,
        'context' => 'contribution',
      )
    );
    $softCreditList = CRM_Contribute_Page_AJAX::getSoftContributionRows();

    foreach ($this->_fields as $columnName) {
      $_GET['columns'][] = array(
        'data' => $columnName,
      );
    }
    // get the results in descending order
    $_GET['order'] = array(
      '0' => array(
        'column' => 0,
        'dir' => 'desc',
      ),
    );
    $amountSortedList = CRM_Contribute_Page_AJAX::getSoftContributionRows();

    $this->assertEquals(3, $softCreditList['recordsTotal']);
    $this->assertEquals(3, $amountSortedList['recordsTotal']);
    rsort($amounts);
    foreach ($amounts as $key => $amount) {
      $amount = CRM_Utils_Money::format($amount, 'USD');
      $this->assertEquals($amount, $amountSortedList['data'][$key]['amount']);
    }

    // sort with soft credit types
    $_GET['order'][0]['column'] = 1;
    foreach ($softTypes as $id) {
      $softLabels[] = CRM_Core_PseudoConstant::getLabel('CRM_Contribute_BAO_ContributionSoft', 'soft_credit_type_id', $id);
    }
    rsort($softLabels);
    $softTypeSortedList = CRM_Contribute_Page_AJAX::getSoftContributionRows();
    foreach ($softLabels as $key => $labels) {
      $this->assertEquals($labels, $softTypeSortedList['data'][$key]['sct_label']);
    }
  }

  /**
   * Test retrieve Soft Contribution For Membership
   */
  public function testGetSoftContributionForMembership() {
    //Check soft credit for membership
    $memParams = array(
      'contribution_contact_id' => $this->_primaryContacts[0],
      'contact_id' => $this->_softContactId,
      'contribution_status_id' => 1,
      'financial_type_id' => 2,
      'status_id' => 1,
      'total_amount' => 100,
      'soft_credit' => array(
        'soft_credit_type_id' => 11,
        'contact_id' => $this->_softContactId,
      ),
    );
    $_GET = array_merge($this->_params,
      array(
        'cid' => $this->_softContactId,
        'context' => 'membership',
        'entityID' => $this->contactMembershipCreate($memParams),
      )
    );

    $softCreditList = CRM_Contribute_Page_AJAX::getSoftContributionRows();
    $this->assertEquals(1, $softCreditList['recordsTotal']);
    $this->assertEquals('Gift', $softCreditList['data'][0]['sct_label']);
    $this->assertEquals('$ 100.00', $softCreditList['data'][0]['amount']);
    $this->assertEquals('Member Dues', $softCreditList['data'][0]['financial_type']);
  }

}
