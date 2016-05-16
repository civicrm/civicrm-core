<<<<<<< HEAD
<?php

/**
 *  File for the MembershipTest class
 *
 *  (PHP 5)
 *
 *   @author Walt Haas <walt@dharmatech.org> (801) 534-1262
 *   @copyright Copyright CiviCRM LLC (C) 2009
 *   @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html
 *              GNU Affero General Public License version 3
 *   @package   CiviCRM
 *
 *   This file is part of CiviCRM
 *
 *   CiviCRM is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Affero General Public License
 *   as published by the Free Software Foundation; either version 3 of
 *   the License, or (at your option) any later version.
 *
 *   CiviCRM is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU Affero General Public License for more details.
 *
 *   You should have received a copy of the GNU Affero General Public
 *   License along with this program.  If not, see
 *   <http://www.gnu.org/licenses/>.
 */

/**
 *  Include class definitions
 */
require_once 'CiviTest/CiviUnitTestCase.php';

require_once 'HTML/QuickForm/Page.php';

/**
 *  Test APIv2 civicrm_activity_* functions
 *
 *  @package   CiviCRM
 */
class CRM_Member_Form_MembershipTest extends CiviUnitTestCase {

  /**
   *  Test setup for every test
   *
   *  Connect to the database, truncate the tables that will be used
   *  and redirect stdin to a temporary file
   */
  public function setUp() {
    //  Connect to the database
    parent::setUp();

    $this->quickCleanup(
      array(
        'civicrm_address_format',
        'civicrm_currency',
        'civicrm_domain',
        'civicrm_file',
        'civicrm_financial_account',
        'civicrm_financial_trxn',
        'civicrm_job',
        'civicrm_job_log',
        'civicrm_location_type',
        'civicrm_mail_settings',
        'civicrm_mapping',
        'civicrm_navigation',
        'civicrm_option_group',
        'civicrm_payment_processor',
        'civicrm_payment_processor_type',
        'civicrm_preferences_date',
        'civicrm_worldregion',
        'civicrm_component',
        'civicrm_persistent',
        'civicrm_prevnext_cache',
        'civicrm_action_mapping',
        'civicrm_acl',
        'civicrm_acl_entity_role',
        'civicrm_contact',
        'civicrm_acl_contact_cache',
        'civicrm_relationship_type',
        'civicrm_saved_search',
        'civicrm_contact_type',
        'civicrm_mailing_component',
        'civicrm_mailing_bounce_type',
        'civicrm_mailing_bounce_pattern',
        'civicrm_financial_type',
        'civicrm_premiums',
        'civicrm_product',
        'civicrm_premiums_product',
        'civicrm_sms_provider',
        'civicrm_membership_status',
        'civicrm_campaign',
        'civicrm_campaign_group',
        'civicrm_survey',
        'civicrm_participant_status_type',
        'civicrm_event_carts',
        'civicrm_dedupe_rule_group',
        'civicrm_dedupe_rule',
        'civicrm_dedupe_exception',
        'civicrm_case',
        'civicrm_case_contact',
        'civicrm_grant',
        'civicrm_tell_friend',
        'civicrm_pledge_block',
        'civicrm_queue_item',
        'civicrm_report_instance',
        'civicrm_price_set',
        'civicrm_price_set_entity',
        'civicrm_pcp',
        'civicrm_batch',
        'civicrm_cache',
        'civicrm_country',
        'civicrm_custom_group',
        'civicrm_custom_field',
        'civicrm_dashboard',
        'civicrm_email',
        'civicrm_entity_batch',
        'civicrm_entity_file',
        'civicrm_entity_financial_trxn',
        'civicrm_im',
        'civicrm_log',
        'civicrm_mapping_field',
        'civicrm_menu',
        'civicrm_note',
        'civicrm_option_value',
        'civicrm_phone',
        'civicrm_state_province',
        'civicrm_tag',
        'civicrm_uf_match',
        'civicrm_timezone',
        'civicrm_openid',
        'civicrm_discount',
        'civicrm_website',
        'civicrm_setting',
        'civicrm_acl_cache',
        'civicrm_dashboard_contact',
        'civicrm_group',
        'civicrm_subscription_history',
        'civicrm_group_contact_cache',
        'civicrm_group_nesting',
        'civicrm_group_organization',
        'civicrm_relationship',
        'civicrm_mailing_event_subscribe',
        'civicrm_mailing_event_confirm',
        'civicrm_contribution_recur',
        'civicrm_contribution_page',
        'civicrm_contribution_widget',
        'civicrm_activity',
        'civicrm_activity_contact',
        'civicrm_case_activity',
        'civicrm_pledge',
        'civicrm_price_field',
        'civicrm_county',
        'civicrm_entity_tag',
        'civicrm_msg_template',
        'civicrm_uf_group',
        'civicrm_uf_field',
        'civicrm_uf_join',
        'civicrm_action_schedule',
        'civicrm_action_log',
        'civicrm_mailing',
        'civicrm_mailing_group',
        'civicrm_mailing_trackable_url',
        'civicrm_mailing_job',
        'civicrm_mailing_recipients',
        'civicrm_mailing_spool',
        'civicrm_mailing_event_queue',
        'civicrm_mailing_event_bounce',
        'civicrm_mailing_event_delivered',
        'civicrm_mailing_event_forward',
        'civicrm_mailing_event_opened',
        'civicrm_mailing_event_reply',
        'civicrm_mailing_event_trackable_url_open',
        'civicrm_mailing_event_unsubscribe',
        'civicrm_membership_type',
        'civicrm_membership',
        'civicrm_membership_block',
        'civicrm_membership_log',
        'civicrm_price_field_value',
        'civicrm_line_item',
        'civicrm_pcp_block',
        'civicrm_address',
        'civicrm_loc_block',
        'civicrm_group_contact',
        'civicrm_contribution',
        'civicrm_contribution_product',
        'civicrm_contribution_soft',
        'civicrm_membership_payment',
        'civicrm_event',
        'civicrm_participant',
        'civicrm_participant_payment',
        'civicrm_events_in_carts',
        'civicrm_pledge_payment',
      )
    );

    //  Insert test data
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      $this->createFlatXMLDataSet(
        dirname(__FILE__) . '/dataset/data.xml'
      )
    );
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
  function testFormRuleEmptyContact() {
    $params = array(
      'contact_select_id' => 0,
      'membership_type_id' => array(),
    );
    $files = array();
    $obj   = new CRM_Member_Form_Membership;
    $rc    = $obj->formRule($params, $files, $obj);
    $this->assertType('array', $rc,
      'In line ' . __LINE__
    );
    $this->assertTrue(array_key_exists('membership_type_id', $rc),
      'In line ' . __LINE__
    );

    $params['membership_type_id'] = array(1 => 3);
    $rc = $obj->formRule($params, $files, $obj);
    $this->assertType('array', $rc,
      'In line ' . __LINE__
    );
    $this->assertTrue(array_key_exists('join_date', $rc),
      'In line ' . __LINE__
    );
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a parameter
   *  that has an start date before the join date and a rolling
   *  membership type
   */
  function testFormRuleRollingEarlyStart() {
    $unixNow       = time();
    $ymdNow        = date('m/d/Y', $unixNow);
    $unixYesterday = $unixNow - (24 * 60 * 60);
    $ymdYesterday  = date('m/d/Y', $unixYesterday);
    $params        = array(
      'join_date' => $ymdNow,
      'start_date' => $ymdYesterday,
      'end_date' => '',
      'membership_type_id' => array('23', '3'),
    );
    $files = array();
    $obj   = new CRM_Member_Form_Membership;
    $rc    = call_user_func(array('CRM_Member_Form_Membership', 'formRule'),
      $params, $files, $obj
    );
    $this->assertType('array', $rc,
      'In line ' . __LINE__
    );
    $this->assertTrue(array_key_exists('start_date', $rc),
      'In line ' . __LINE__
    );
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a parameter
   *  that has an end date before the start date and a rolling
   *  membership type
   */
  function testFormRuleRollingEarlyEnd() {
    $unixNow       = time();
    $ymdNow        = date('m/d/Y', $unixNow);
    $unixYesterday = $unixNow - (24 * 60 * 60);
    $ymdYesterday  = date('m/d/Y', $unixYesterday);
    $params        = array(
      'join_date' => $ymdNow,
      'start_date' => $ymdNow,
      'end_date' => $ymdYesterday,
      'membership_type_id' => array('23', '3'),
    );
    $files = array();
    $obj   = new CRM_Member_Form_Membership;
    $rc    = $obj->formRule($params, $files, $obj);
    $this->assertType('array', $rc,
      'In line ' . __LINE__
    );
    $this->assertTrue(array_key_exists('end_date', $rc),
      'In line ' . __LINE__
    );
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a parameter
   *  that has an end date but no start date and a rolling
   *  membership type
   */
  function testFormRuleRollingEndNoStart() {
    $unixNow         = time();
    $ymdNow          = date('m/d/Y', $unixNow);
    $unixYearFromNow = $unixNow + (365 * 24 * 60 * 60);
    $ymdYearFromNow  = date('m/d/Y', $unixYearFromNow);
    $params          = array(
      'join_date' => $ymdNow,
      'start_date' => '',
      'end_date' => $ymdYearFromNow,
      'membership_type_id' => array('23', '3'),
    );
    $files = array();
    $obj   = new CRM_Member_Form_Membership;
    $rc    = $obj->formRule($params, $files, $obj);
    $this->assertType('array', $rc,
      'In line ' . __LINE__
    );
    $this->assertTrue(array_key_exists('start_date', $rc),
      'In line ' . __LINE__
    );
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a parameter
   *  that has an end date and a lifetime membership type
   */
  function testFormRuleRollingLifetimeEnd() {
    $unixNow         = time();
    $unixYearFromNow = $unixNow + (365 * 24 * 60 * 60);
    $params          = array(
      'join_date' => date('m/d/Y', $unixNow),
      'start_date' => date('m/d/Y', $unixNow),
      'end_date' => date('m/d/Y',
        $unixYearFromNow
      ),
      'membership_type_id' => array('23', '13'),
    );
    $files = array();
    $obj   = new CRM_Member_Form_Membership;
    $rc    = $obj->formRule($params, $files, $obj);
    $this->assertType('array', $rc);
    $this->assertTrue(array_key_exists('status_id', $rc));
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a parameter
   *  that has an override and no status
   */
  function testFormRuleOverrideNoStatus() {
    $unixNow         = time();
    $unixYearFromNow = $unixNow + (365 * 24 * 60 * 60);
    $params          = array('join_date' => date('m/d/Y', $unixNow),
      'membership_type_id' => array('23', '13'),
      'is_override' => TRUE,
    );
    $files = array();
    $obj   = new CRM_Member_Form_Membership;
    $rc    = $obj->formRule($params, $files, $obj);
    $this->assertType('array', $rc,
      'In line ' . __LINE__
    );
    $this->assertTrue(array_key_exists('status_id', $rc),
      'In line ' . __LINE__
    );
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a join date
   *  of one month from now and a rolling membership type
   */
  function testFormRuleRollingJoin1MonthFromNow() {
    $unixNow     = time();
    $unix1MFmNow = $unixNow + (31 * 24 * 60 * 60);
    $params      = array('join_date' => date('m/d/Y', $unix1MFmNow),
      'start_date' => '',
      'end_date' => '',
      'membership_type_id' => array('23', '3'),
    );
    $files = array();
    $obj   = new CRM_Member_Form_Membership;
    $rc    = $obj->formRule($params, $files, $obj);

    //  Should have found no valid membership status
    $this->assertType('array', $rc,
      'In line ' . __LINE__
    );
    $this->assertTrue(array_key_exists('_qf_default', $rc),
      'In line ' . __LINE__
    );
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a join date
   *  of today and a rolling membership type
   */
  function testFormRuleRollingJoinToday() {
    $unixNow = time();
    $params = array('join_date' => date('m/d/Y', $unixNow),
      'start_date' => '',
      'end_date' => '',
      'membership_type_id' => array('23', '3'),
    );
    $files = array();
    $obj   = new CRM_Member_Form_Membership;
    $rc    = $obj->formRule($params, $files, $obj);

    //  Should have found New membership status
    $this->assertTrue($rc, 'In line ' . __LINE__);
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a join date
   *  of one month ago and a rolling membership type
   */
  function testFormRuleRollingJoin1MonthAgo() {
    $unixNow   = time();
    $unix1MAgo = $unixNow - (31 * 24 * 60 * 60);
    $params    = array('join_date' => date('m/d/Y', $unix1MAgo),
      'start_date' => '',
      'end_date' => '',
      'membership_type_id' => array('23', '3'),
    );
    $files = array();
    $obj   = new CRM_Member_Form_Membership;
    $rc    = $obj->formRule($params, $files, $obj);

    //  Should have found New membership status
    $this->assertTrue($rc, 'In line ' . __LINE__);
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a join date
   *  of six months ago and a rolling membership type
   */
  function testFormRuleRollingJoin6MonthsAgo() {
    $unixNow   = time();
    $unix6MAgo = $unixNow - (180 * 24 * 60 * 60);
    $params    = array('join_date' => date('m/d/Y', $unix6MAgo),
      'start_date' => '',
      'end_date' => '',
      'membership_type_id' => array('23', '3'),
    );
    $files = array();
    $obj   = new CRM_Member_Form_Membership;
    $rc    = $obj->formRule($params, $files, $obj);

    //  Should have found Current membership status
    $this->assertTrue($rc, 'In line ' . __LINE__);
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a join date
   *  of one year+ ago and a rolling membership type
   */
  function testFormRuleRollingJoin1YearAgo() {
    $unixNow   = time();
    $unix1YAgo = $unixNow - (370 * 24 * 60 * 60);
    $params    = array('join_date' => date('m/d/Y', $unix1YAgo),
      'start_date' => '',
      'end_date' => '',
      'membership_type_id' => array('23', '3'),
    );
    $files = array();
    $obj   = new CRM_Member_Form_Membership;
    $rc    = $obj->formRule($params, $files, $obj);

    //  Should have found Grace membership status
    $this->assertTrue($rc, 'In line ' . __LINE__);
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a join date
   *  of two years ago and a rolling membership type
   */
  function testFormRuleRollingJoin2YearsAgo() {
    $unixNow   = time();
    $unix2YAgo = $unixNow - (2 * 365 * 24 * 60 * 60);
    $params    = array('join_date' => date('m/d/Y', $unix2YAgo),
      'start_date' => '',
      'end_date' => '',
      'membership_type_id' => array('23', '3'),
    );
    $files = array();
    $obj   = new CRM_Member_Form_Membership;
    $rc    = $obj->formRule($params, $files, $obj);

    //  Should have found Expired membership status
    $this->assertTrue($rc, 'In line ' . __LINE__);
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a join date
   *  of six months ago and a fixed membership type
   */
  function testFormRuleFixedJoin6MonthsAgo() {
    $unixNow   = time();
    $unix6MAgo = $unixNow - (180 * 24 * 60 * 60);
    $params    = array('join_date' => date('m/d/Y', $unix6MAgo),
      'start_date' => '',
      'end_date' => '',
      'membership_type_id' => array('23', '7'),
    );
    $files = array();
    $obj   = new CRM_Member_Form_Membership;
    $rc    = $obj->formRule($params, $files, $obj);

    //  Should have found Current membership status
    $this->assertTrue($rc, 'In line ' . __LINE__);
  }
}
// class CRM_Member_Form_MembershipTest

=======
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
 *  File for the MembershipTest class
 *
 *  (PHP 5)
 *
 * @author Walt Haas <walt@dharmatech.org> (801) 534-1262
 */

/**
 *  Test CRM_Member_Form_Membership functions.
 *
 * @package   CiviCRM
 * @group headless
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
   * @var CiviMailUtils
   */
  protected $mut;

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
    $this->_paymentProcessorID = $this->processorCreate();
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
    $this->mut = new CiviMailUtils($this, TRUE);
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
      'send_receipt' => TRUE,
      'receipt_text' => 'Receipt text',
    );
    $form->_contactID = $this->_individualId;
    $form->testSubmit($params);
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
    $this->mut->checkMailLog(array(
      '50',
      'Receipt text',
    ));
    $this->mut->stop();
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
    $params = $this->getBaseSubmitParams();
    $form->_mode = 'test';
    $form->_contactID = $this->_individualId;
    $form->testSubmit($params);
    $membership = $this->callAPISuccessGetSingle('Membership', array('contact_id' => $this->_individualId));
    $this->callAPISuccessGetCount('ContributionRecur', array('contact_id' => $this->_individualId), 1);

    $contribution = $this->callAPISuccess('Contribution', 'get', array(
      'contact_id' => $this->_individualId,
      'is_test' => TRUE,
    ));

    // CRM-16992.
    $this->callAPISuccessGetCount('LineItem', array(
      'entity_id' => $membership['id'],
      'entity_table' => 'civicrm_membership',
      'contribution_id' => $contribution['id'],
    ), 1);
  }

  /**
   * Test the submit function of the membership form.
   */
  public function testSubmitPayLaterWithBilling() {
    $form = $this->getForm(NULL);
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
      'num_terms' => '2',
      'source' => '',
      'total_amount' => '50.00',
      //Member dues, see data.xml
      'financial_type_id' => '2',
      'soft_credit_type_id' => '',
      'soft_credit_contact_id' => '',
      'payment_instrument_id' => 4,
      'from_email_address' => '"Demonstrators Anonymous" <info@example.org>',
      'receipt_text_signup' => 'Thank you text',
      'payment_processor_id' => $this->_paymentProcessorID,
      'record_contribution' => TRUE,
      'trxn_id' => 777,
      'contribution_status_id' => 2,
      'billing_first_name' => 'Test',
      'billing_middlename' => 'Last',
      'billing_street_address-5' => '10 Test St',
      'billing_city-5' => 'Test',
      'billing_state_province_id-5' => '1003',
      'billing_postal_code-5' => '90210',
      'billing_country_id-5' => '1228',
    );
    $form->_contactID = $this->_individualId;

    $form->testSubmit($params);
    $membership = $this->callAPISuccessGetSingle('Membership', array('contact_id' => $this->_individualId));
    $contribution = $this->callAPISuccessGetSingle('Contribution', array(
      'contact_id' => $this->_individualId,
      'contribution_status_id' => 2,
    ));
    $this->assertEquals($contribution['trxn_id'], 777);

    $this->callAPISuccessGetCount('LineItem', array(
      'entity_id' => $membership['id'],
      'entity_table' => 'civicrm_membership',
      'contribution_id' => $contribution['id'],
    ), 1);
    $this->callAPISuccessGetSingle('address', array(
      'contact_id' => $this->_individualId,
      'street_address' => '10 Test St',
      'postal_code' => 90210,
    ));
  }

  /**
   * Test the submit function of the membership form.
   */
  public function testSubmitRecurCompleteInstant() {
    $form = $this->getForm();
    $mut = new CiviMailUtils($this, TRUE);
    $processor = Civi\Payment\System::singleton()->getById($this->_paymentProcessorID);
    $processor->setDoDirectPaymentResult(array(
      'payment_status_id' => 1,
      'trxn_id' => 'kettles boil water',
      'fee_amount' => .14,
    ));
    $this->callAPISuccess('MembershipType', 'create', array(
      'id' => $this->membershipTypeAnnualFixedID,
      'duration_unit' => 'month',
      'duration_interval' => 1,
      'auto_renew' => TRUE,
    ));
    $form->preProcess();
    $this->createLoggedInUser();
    $params = $this->getBaseSubmitParams();
    $form->_mode = 'test';
    $form->_contactID = $this->_individualId;
    $form->testSubmit($params);
    $membership = $this->callAPISuccessGetSingle('Membership', array('contact_id' => $this->_individualId));
    $this->callAPISuccessGetCount('ContributionRecur', array('contact_id' => $this->_individualId), 1);

    $contribution = $this->callAPISuccess('Contribution', 'getsingle', array(
      'contact_id' => $this->_individualId,
      'is_test' => TRUE,
    ));

    $this->assertEquals(.14, $contribution['fee_amount']);
    $this->assertEquals('kettles boil water', $contribution['trxn_id']);

    $this->callAPISuccessGetCount('LineItem', array(
      'entity_id' => $membership['id'],
      'entity_table' => 'civicrm_membership',
      'contribution_id' => $contribution['id'],
    ), 1);
    $mut->checkMailLog(array(
        '===========================================================
Billing Name and Address
===========================================================
Test
10 Test St
Test, AR 90210
US',
        '===========================================================
Membership Information
===========================================================
Membership Type: AnnualFixed
Membership Start Date: ',
        '===========================================================
Credit Card Information
===========================================================
Visa
************1111
Expires: ',
      )
    );
    $mut->stop();

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

  /**
   * @return array
   */
  protected function getBaseSubmitParams() {
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
      'send_receipt' => 1,
    );
    return $params;
  }

}
>>>>>>> refs/remotes/civicrm/master
