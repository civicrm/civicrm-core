<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 | at info'AT'civicrm'DOT'org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/


require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'CiviTest/Contact.php';
require_once 'CiviTest/ContributionPage.php';
require_once 'CiviTest/Custom.php';
require_once 'CiviTest/PaypalPro.php';
class CRM_Contribute_BAO_ContributionPageTest extends CiviUnitTestCase {
  function get_info() {
    return array(
      'name' => 'Contribution BAOs',
      'description' => 'Test all Contribute_BAO_ContributionPage methods.',
      'group' => 'CiviCRM BAO Tests',
    );
  }

  function setUp() {
    parent::setUp();
    $this->_financialTypeID = 1;
  }

  function tearDown() {
  }

  /**
   * create() method (create Contribution Page)
   */
  function testCreate() {

    $params = array(
      'qfkey' => '9a3ef3c08879ad4c8c109b21c583400e',
      'title' => 'Test Contribution Page',
      'financial_type_id' => $this->_financialTypeID,
      'intro_text' => '',
      'footer_text' => 'Thanks',
      'is_for_organization' => 0,
      'for_organization' => ' I am contributing on behalf of an organization',
      'goal_amount' => '400',
      'is_active' => 1,
      'honor_block_title' => '',
      'honor_block_text' => '',
      'start_date' => '20091022105900',
      'start_date_time' => '10:59AM',
      'end_date' => '19700101000000',
      'end_date_time' => '',
      'is_credit_card_only' => '',
    );


    $contributionpage = CRM_Contribute_BAO_ContributionPage::create($params);

    $this->assertNotNull($contributionpage->id);
    $this->assertType('int', $contributionpage->id);
    ContributionPage::delete($contributionpage->id);
  }

  /**
   *  test setIsActive() method
   */
  function testsetIsActive() {

    $params = array(
      'title' => 'Test Contribution Page',
      'financial_type_id' => $this->_financialTypeID,
      'is_active' => 1,
    );

    $contributionpage = CRM_Contribute_BAO_ContributionPage::create($params);
    $id               = $contributionpage->id;
    $is_active        = 1;
    $pageActive       = CRM_Contribute_BAO_ContributionPage::setIsActive($id, $is_active);
    $this->assertEquals($pageActive, true, 'Verify financial types record deletion.');
    ContributionPage::delete($contributionpage->id);
  }

  /**
   * test setValues() method
   */
  function testSetValues() {

    $params = array(
      'title' => 'Test Contribution Page',
      'financial_type_id' => $this->_financialTypeID,
      'is_active' => 1,
    );

    $contributionpage = CRM_Contribute_BAO_ContributionPage::create($params);

    $id        = $contributionpage->id;
    $values    = array();
    $setValues = CRM_Contribute_BAO_ContributionPage::setValues($id, $values);

    $this->assertEquals($params['title'], $values['title'], 'Verify contribution title.');
    $this->assertEquals($this->_financialTypeID, $values['financial_type_id'], 'Verify financial types id.');
    $this->assertEquals(1, $values['is_active'], 'Verify contribution is_active value.');
    ContributionPage::delete($contributionpage->id);
  }

  /**
   * test copy() method
   */
  function testcopy() {
    $params = array(
      'qfkey' => '9a3ef3c08879ad4c8c109b21c583400e',
      'title' => 'Test Contribution Page',
      'financial_type_id' => $this->_financialTypeID,
      'intro_text' => '',
      'footer_text' => 'Thanks',
      'is_for_organization' => 0,
      'for_organization' => ' I am contributing on behalf of an organization',
      'goal_amount' => '400',
      'is_active' => 1,
      'honor_block_title' => '',
      'honor_block_text' => '',
      'start_date' => '20091022105900',
      'start_date_time' => '10:59AM',
      'end_date' => '19700101000000',
      'end_date_time' => '',
      'is_credit_card_only' => '',
    );


    $contributionpage = CRM_Contribute_BAO_ContributionPage::create($params);
    $copycontributionpage = CRM_Contribute_BAO_ContributionPage::copy($contributionpage->id);
    $this->assertEquals($copycontributionpage->financial_type_id, $this->_financialTypeID, 'Check for Financial type id.');
    $this->assertEquals($copycontributionpage->goal_amount, 400, 'Check for goal amount.');
    ContributionPage::delete($contributionpage->id);
    ContributionPage::delete($copycontributionpage->id);
  }

  /**
   * test checkRecurPaymentProcessor() method
   */
  function testcheckRecurPaymentProcessor() {
    //@todo paypalpro create seems to fail silently without causing this class to fail
    // $this->paymentProcessorCreate may be a better option
    $paymentProcessor = PaypalPro::create();
    $params = array(
      'title' => 'Test Contribution Page',
      'financial_type_id' => $this->_financialTypeID,
      'is_active' => 1,
      'payment_processor_id' => $paymentProcessor,
    );

    $contributionpage = CRM_Contribute_BAO_ContributionPage::create($params);
    $id               = $contributionpage->id;
    $checkRecurring   = CRM_Contribute_BAO_ContributionPage::checkRecurPaymentProcessor($id);
    $this->assertEquals($checkRecurring, FALSE, 'Check for false return.');
    ContributionPage::delete($contributionpage->id);
  }
}


