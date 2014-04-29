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
      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(
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

