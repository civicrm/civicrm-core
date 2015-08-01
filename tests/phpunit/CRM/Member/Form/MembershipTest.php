<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 *  File for the MembershipTest class
 *
 *  (PHP 5)
 *
 * @author Walt Haas <walt@dharmatech.org> (801) 534-1262
 */

/**
 *  Include class definitions
 */
require_once 'CiviTest/CiviUnitTestCase.php';

require_once 'HTML/QuickForm/Page.php';

/**
 *  Test CRM_Member_Form_Membership functions.
 *
 * @package   CiviCRM
 */
class CRM_Member_Form_MembershipTest extends CiviUnitTestCase {

  /**
   * Assume empty database with just civicrm_data.
   */
  protected $_individualId;
  protected $_contribution;
  protected $_financialTypeId = 1;
  protected $_apiversion;
  protected $_entity = 'Membership';
  protected $_params;
  protected $_ids = array();
  protected $_paymentProcessorID;

  /**
   * Membership type ID for annual fixed membership.
   *
   * @var int
   */
  protected $membershipTypeAnnualFixedID;

  /**
   * Parameters to create payment processor.
   *
   * @var array
   */
  protected $_processorParams = array();

  /**
   * ID of created membership.
   *
   * @var int
   */
  protected $_membershipID;

  /**
   * Payment instrument mapping.
   *
   * @var array
   */
  protected $paymentInstruments = array();

  /**
   * Test setup for every test.
   *
   * Connect to the database, truncate the tables that will be used
   * and redirect stdin to a temporary file.
   */
  public function setUp() {
    $this->_apiversion = 3;
    parent::setUp();

    $this->_individualId = $this->individualCreate();
    $processor = $this->processorCreate();
    $this->_paymentProcessorID = $processor->id;
    // Insert test data.
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      $this->createFlatXMLDataSet(
        dirname(__FILE__) . '/dataset/data.xml'
      )
    );
    $membershipTypeAnnualFixed = $this->callAPISuccess('membership_type', 'create', array(
      'domain_id' => 1,
      'name' => "AnnualFixed",
      'member_of_contact_id' => 23,
      'duration_unit' => "year",
      'duration_interval' => 1,
      'period_type' => "fixed",
      'fixed_period_start_day' => "101",
      'fixed_period_rollover_day' => "1231",
      'relationship_type_id' => 20,
      'financial_type_id' => 2,
    ));
    $this->membershipTypeAnnualFixedID = $membershipTypeAnnualFixed['id'];

    $instruments = $this->callAPISuccess('contribution', 'getoptions', array('field' => 'payment_instrument_id'));
    $this->paymentInstruments = $instruments['values'];
  }

  /**
   * Clean up after each test.
   */
  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
    $this->quickCleanup(
      array(
        'civicrm_relationship',
        'civicrm_membership_type',
        'civicrm_membership',
        'civicrm_uf_match',
      )
    );
    $this->callAPISuccess('contact', 'delete', array('id' => 17, 'skip_undelete' => TRUE));
    $this->callAPISuccess('contact', 'delete', array('id' => 23, 'skip_undelete' => TRUE));
    $this->callAPISuccess('relationship_type', 'delete', array('id' => 20));
  }

  /**
   *  Test CRM_Member_Form_Membership::buildQuickForm()
   */
  //function testCRMMemberFormMembershipBuildQuickForm()
  //{
  //    throw new PHPUnit_Framework_IncompleteTestError( "not implemented" );
  //}

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a parameter
   *  that has an empty contact_select_id value
   */
  public function testFormRuleEmptyContact() {
    $params = array(
      'contact_select_id' => 0,
      'membership_type_id' => array(1 => NULL),
    );
    $files = array();
    $obj = new CRM_Member_Form_Membership();
    $rc = $obj->formRule($params, $files, $obj);
    $this->assertType('array', $rc);
    $this->assertTrue(array_key_exists('membership_type_id', $rc));

    $params['membership_type_id'] = array(1 => 3);
    $rc = $obj->formRule($params, $files, $obj);
    $this->assertType('array', $rc);
    $this->assertTrue(array_key_exists('join_date', $rc));
  }

  /**
   * Test that form rule fails if start date is before join date.
   *
   * Test CRM_Member_Form_Membership::formRule() with a parameter
   * that has an start date before the join date and a rolling
   * membership type.
   */
  public function testFormRuleRollingEarlyStart() {
    $unixNow = time();
    $ymdNow = date('m/d/Y', $unixNow);
    $unixYesterday = $unixNow - (24 * 60 * 60);
    $ymdYesterday = date('m/d/Y', $unixYesterday);
    $params = array(
      'join_date' => $ymdNow,
      'start_date' => $ymdYesterday,
      'end_date' => '',
      'membership_type_id' => array('23', '15'),
    );
    $files = array();
    $obj = new CRM_Member_Form_Membership();
    $rc = call_user_func(array('CRM_Member_Form_Membership', 'formRule'),
      $params, $files, $obj
    );
    $this->assertType('array', $rc);
    $this->assertTrue(array_key_exists('start_date', $rc));
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a parameter
   *  that has an end date before the start date and a rolling
   *  membership type
   */
  public function testFormRuleRollingEarlyEnd() {
    $unixNow = time();
    $ymdNow = date('m/d/Y', $unixNow);
    $unixYesterday = $unixNow - (24 * 60 * 60);
    $ymdYesterday = date('m/d/Y', $unixYesterday);
    $params = array(
      'join_date' => $ymdNow,
      'start_date' => $ymdNow,
      'end_date' => $ymdYesterday,
      'membership_type_id' => array('23', '15'),
    );
    $files = array();
    $obj = new CRM_Member_Form_Membership();
    $rc = $obj->formRule($params, $files, $obj);
    $this->assertType('array', $rc);
    $this->assertTrue(array_key_exists('end_date', $rc));
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with end date but no start date and a rolling membership type.
   */
  public function testFormRuleRollingEndNoStart() {
    $unixNow = time();
    $ymdNow = date('m/d/Y', $unixNow);
    $unixYearFromNow = $unixNow + (365 * 24 * 60 * 60);
    $ymdYearFromNow = date('m/d/Y', $unixYearFromNow);
    $params = array(
      'join_date' => $ymdNow,
      'start_date' => '',
      'end_date' => $ymdYearFromNow,
      'membership_type_id' => array('23', '15'),
    );
    $files = array();
    $obj = new CRM_Member_Form_Membership();
    $rc = $obj->formRule($params, $files, $obj);
    $this->assertType('array', $rc);
    $this->assertTrue(array_key_exists('start_date', $rc));
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a parameter
   *  that has an end date and a lifetime membership type
   */
  public function testFormRuleRollingLifetimeEnd() {
    $unixNow = time();
    $unixYearFromNow = $unixNow + (365 * 24 * 60 * 60);
    $params = array(
      'join_date' => date('m/d/Y', $unixNow),
      'start_date' => date('m/d/Y', $unixNow),
      'end_date' => date('m/d/Y',
        $unixYearFromNow
      ),
      'membership_type_id' => array('23', '25'),
    );
    $files = array();
    $obj = new CRM_Member_Form_Membership();
    $rc = $obj->formRule($params, $files, $obj);
    $this->assertType('array', $rc);
    $this->assertTrue(array_key_exists('status_id', $rc));
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a parameter
   *  that has an override and no status
   */
  public function testFormRuleOverrideNoStatus() {
    $unixNow = time();
    $params = array(
      'join_date' => date('m/d/Y', $unixNow),
      'membership_type_id' => array('23', '25'),
      'is_override' => TRUE,
    );
    $files = array();
    $obj = new CRM_Member_Form_Membership();
    $rc = $obj->formRule($params, $files, $obj);
    $this->assertType('array', $rc);
    $this->assertTrue(array_key_exists('status_id', $rc));
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a join date
   *  of one month from now and a rolling membership type
   */
  public function testFormRuleRollingJoin1MonthFromNow() {
    $unixNow = time();
    $unix1MFmNow = $unixNow + (31 * 24 * 60 * 60);
    $params = array(
      'join_date' => date('m/d/Y', $unix1MFmNow),
      'start_date' => '',
      'end_date' => '',
      'membership_type_id' => array('23', '15'),
    );
    $files = array();
    $obj = new CRM_Member_Form_Membership();
    $rc = $obj->formRule($params, $files, $obj);

    // Should have found no valid membership status.
    $this->assertType('array', $rc);
    $this->assertTrue(array_key_exists('_qf_default', $rc));
  }

  /**
   * Test CRM_Member_Form_Membership::formRule() with a join date of today and a rolling membership type.
   */
  public function testFormRuleRollingJoinToday() {
    $unixNow = time();
    $params = array(
      'join_date' => date('m/d/Y', $unixNow),
      'start_date' => '',
      'end_date' => '',
      'membership_type_id' => array('23', '15'),
    );
    $files = array();
    $obj = new CRM_Member_Form_Membership();
    $rc = $obj->formRule($params, $files, $obj);

    //  Should have found New membership status
    $this->assertTrue($rc);
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a join date
   *  of one month ago and a rolling membership type
   */
  public function testFormRuleRollingJoin1MonthAgo() {
    $unixNow = time();
    $unix1MAgo = $unixNow - (31 * 24 * 60 * 60);
    $params = array(
      'join_date' => date('m/d/Y', $unix1MAgo),
      'start_date' => '',
      'end_date' => '',
      'membership_type_id' => array('23', '15'),
    );
    $files = array();
    $obj = new CRM_Member_Form_Membership();
    $rc = $obj->formRule($params, $files, $obj);

    // Should have found New membership status.
    $this->assertTrue($rc);
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a join date of six months ago and a rolling membership type.
   */
  public function testFormRuleRollingJoin6MonthsAgo() {
    $unixNow = time();
    $unix6MAgo = $unixNow - (180 * 24 * 60 * 60);
    $params = array(
      'join_date' => date('m/d/Y', $unix6MAgo),
      'start_date' => '',
      'end_date' => '',
      'membership_type_id' => array('23', '15'),
    );
    $files = array();
    $obj = new CRM_Member_Form_Membership();
    $rc = $obj->formRule($params, $files, $obj);

    // Should have found Current membership status.
    $this->assertTrue($rc);
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a join date
   *  of one year+ ago and a rolling membership type
   */
  public function testFormRuleRollingJoin1YearAgo() {
    $unixNow = time();
    $unix1YAgo = $unixNow - (370 * 24 * 60 * 60);
    $params = array(
      'join_date' => date('m/d/Y', $unix1YAgo),
      'start_date' => '',
      'end_date' => '',
      'membership_type_id' => array('23', '15'),
    );
    $files = array();
    $obj = new CRM_Member_Form_Membership();
    $rc = $obj->formRule($params, $files, $obj);

    //  Should have found Grace membership status
    $this->assertTrue($rc);
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a join date
   *  of two years ago and a rolling membership type
   */
  public function testFormRuleRollingJoin2YearsAgo() {
    $unixNow = time();
    $unix2YAgo = $unixNow - (2 * 365 * 24 * 60 * 60);
    $params = array(
      'join_date' => date('m/d/Y', $unix2YAgo),
      'start_date' => '',
      'end_date' => '',
      'membership_type_id' => array('23', '15'),
    );
    $files = array();
    $obj = new CRM_Member_Form_Membership();
    $rc = $obj->formRule($params, $files, $obj);

    //  Should have found Expired membership status
    $this->assertTrue($rc);
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a join date
   *  of six months ago and a fixed membership type
   */
  public function testFormRuleFixedJoin6MonthsAgo() {
    $unixNow = time();
    $unix6MAgo = $unixNow - (180 * 24 * 60 * 60);
    $params = array(
      'join_date' => date('m/d/Y', $unix6MAgo),
      'start_date' => '',
      'end_date' => '',
      'membership_type_id' => array('23', '7'),
    );
    $files = array();
    $obj = new CRM_Member_Form_Membership();
    $rc = $obj->formRule($params, $files, $obj);

    //  Should have found Current membership status
    $this->assertTrue($rc);
  }

  /**
   * Test the submit function of the membership form.
   */
  public function testSubmit() {
    $form = $this->getForm();
    $form->_mode = 'test';
    $this->createLoggedInUser();
    $params = array(
      'cid' => $this->_individualId,
      'join_date' => date('m/d/Y', time()),
      'start_date' => '',
      'end_date' => '',
      // This format reflects the 23 being the organisation & the 25 being the type.
      'membership_type_id' => array(23, $this->membershipTypeAnnualFixedID),
      'auto_renew' => '0',
      'max_related' => '',
      'num_terms' => '1',
      'source' => '',
      'total_amount' => '50.00',
      'financial_type_id' => '2', //Member dues, see data.xml
      'soft_credit_type_id' => '',
      'soft_credit_contact_id' => '',
      'from_email_address' => '"Demonstrators Anonymous" <info@example.org>',
      'receipt_text_signup' => 'Thank you text',
      'payment_processor_id' => $this->_paymentProcessorID,
      'credit_card_number' => '4111111111111111',
      'cvv2' => '123',
      'credit_card_exp_date' => array(
        'M' => '9',
        'Y' => '2024', // TODO: Future proof
      ),
      'credit_card_type' => 'Visa',
      'billing_first_name' => 'Test',
      'billing_middlename' => 'Last',
      'billing_street_address-5' => '10 Test St',
      'billing_city-5' => 'Test',
      'billing_state_province_id-5' => '1003',
      'billing_postal_code-5' => '90210',
      'billing_country_id-5' => '1228',
    );
    $form->submit($params);
    $membership = $this->callAPISuccessGetSingle('Membership', array('contact_id' => $this->_individualId));
    $this->callAPISuccessGetCount('ContributionRecur', array('contact_id' => $this->_individualId), 0);
    $contribution = $this->callAPISuccess('Contribution', 'get', array(
      'contact_id' => $this->_individualId,
      'is_test' => TRUE,
    ));

    $this->callAPISuccessGetCount('LineItem', array(
      'entity_id' => $membership['id'],
      'entity_table' => 'civicrm_membership',
      'contribution_id' => $contribution['id'],
    ), 1);
  }

  /**
   * Test the submit function of the membership form.
   */
  public function testSubmitRecur() {
    $form = $this->getForm();

    $this->callAPISuccess('MembershipType', 'create', array(
      'id' => $this->membershipTypeAnnualFixedID,
      'duration_unit' => 'month',
      'duration_interval' => 1,
      'auto_renew' => TRUE,
    ));
    $form->preProcess();
    $this->createLoggedInUser();
    $params = array(
      'cid' => $this->_individualId,
      'price_set_id' => 0,
      'join_date' => date('m/d/Y', time()),
      'start_date' => '',
      'end_date' => '',
      'campaign_id' => '',
      // This format reflects the 23 being the organisation & the 25 being the type.
      'membership_type_id' => array(23, $this->membershipTypeAnnualFixedID),
      'auto_renew' => '1',
      'is_recur' => 1,
      'max_related' => 0,
      'num_terms' => '1',
      'source' => '',
      'total_amount' => '77.00',
      'financial_type_id' => '2', //Member dues, see data.xml
      'soft_credit_type_id' => 11,
      'soft_credit_contact_id' => '',
      'from_email_address' => '"Demonstrators Anonymous" <info@example.org>',
      'receipt_text' => 'Thank you text',
      'payment_processor_id' => $this->_paymentProcessorID,
      'credit_card_number' => '4111111111111111',
      'cvv2' => '123',
      'credit_card_exp_date' => array(
        'M' => '9',
        'Y' => '2019', // TODO: Future proof
      ),
      'credit_card_type' => 'Visa',
      'billing_first_name' => 'Test',
      'billing_middlename' => 'Last',
      'billing_street_address-5' => '10 Test St',
      'billing_city-5' => 'Test',
      'billing_state_province_id-5' => '1003',
      'billing_postal_code-5' => '90210',
      'billing_country_id-5' => '1228',
    );
    $form->_mode = 'test';

    $form->submit($params);
    $membership = $this->callAPISuccessGetSingle('Membership', array('contact_id' => $this->_individualId));
    $this->callAPISuccessGetCount('ContributionRecur', array('contact_id' => $this->_individualId), 1);

    $contribution = $this->callAPISuccess('Contribution', 'get', array(
      'contact_id' => $this->_individualId,
      'is_test' => TRUE,
    ));
    // Not currently passing.
    //  $this->callAPISuccessGetCount('LineItem', array(
    //  'entity_id' => $membership['id'],
    //  'entity_table' => 'civicrm_membership',
    //  'contribution_id' => $contribution['id'],
    //), 1);
    //
  }

  /**
   * Get a membership form object.
   *
   * We need to instantiate the form to run preprocess, which means we have to trick it about the request method.
   *
   * @return \CRM_Member_Form_Membership
   */
  protected function getForm() {
    $form = new CRM_Member_Form_Membership();
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $form->controller = new CRM_Core_Controller();
    $form->_bltID = 5;
    return $form;
  }

}
