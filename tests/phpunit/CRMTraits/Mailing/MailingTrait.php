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
 * Trait MailingTrait
 *
 * Trait for working with Mailing data in tests
 */
trait CRMTraits_Mailing_MailingTrait {

  /**
   * Load the data that used to be handled by the discontinued dbunit class.
   *
   * This could do with further tidy up - the initial priority is simply to get rid of
   * the dbunity package which is no longer supported.
   */
  protected function loadMailingDeliveryDataSet() {
    $xml = $this->getXml();
    foreach ($xml as $tableName => $element) {
      if (!empty($element)) {
        foreach ($element as $row) {
          $keys = $values = [];
          if (isset($row['@attributes'])) {
            foreach ($row['@attributes'] as $key => $value) {
              $keys[] = $key;
              $values[] = is_numeric($value) ? $value : "'{$value}'";
            }
          }
          else {
            // cos we copied it & it is inconsistent....
            foreach ($row as $key => $value) {
              $keys[] = $key;
              $values[] = is_numeric($value) ? $value : "'{$value}'";
            }
          }

          CRM_Core_DAO::executeQuery("
            INSERT INTO $tableName (" . implode(',', $keys) . ') VALUES(' . implode(',', $values) . ')'
          );
        }
      }
    }
  }

  /**
   * @return array
   */
  protected function getXml() {
    $xml = simplexml_load_string('<?xml version="1.0"?>
<dataset>
  <!--
  Mailing 14: First Mailing Events, 2011-05-25

  contact cid       bounce  open    click   reply
  bouncy  105       y       n       n       n
  test01  102       n       n       n       n
  test02  103       n       n       n       y
  test03  104       n       n       y[dc]   n
  test04  108       n       n       y[d]    y
  test05  109       n       y       n       n
  test06  110       n       y2      n       y
  test07  111       n       y       y[dc]   n
  test08  112       n       y       y[c2]   y

  Mailing 15: Second Test Mailing Events, 2011-05-26

  contact cid       bounce  open    click   reply
  test01  102       n       y       n       n
  test02  103       n       n       n       y
  test03  104       n       n       y[dc]   n
  test04  108       n       n       n       n
  test05  109       n       n       n       n
  test06  110       n       n       n       n
  test07  111       n       n       n       n
  test08  112       n       n       n       n
  -->
  <civicrm_contact id="102" contact_type="Individual" is_opt_out="0" display_name="Test One" sort_name="One, Test" first_name="Test" last_name="One"/>
  <civicrm_contact id="103" contact_type="Individual" is_opt_out="0" display_name="Test Two" sort_name="Two, Test" first_name="Test" last_name="Two"/>
  <civicrm_contact id="104" contact_type="Individual" is_opt_out="0" display_name="Test Three" sort_name="Three, Test" first_name="Test" last_name="Three"/>
  <civicrm_contact id="105" contact_type="Individual" is_opt_out="0" display_name="Test Bouncy" sort_name="Bouncy, Test" first_name="Test" last_name="Bouncy"/>
  <civicrm_contact id="108" contact_type="Individual" is_opt_out="0" display_name="Test Four" sort_name="Four, Test" first_name="Test" last_name="Four"/>
  <civicrm_contact id="109" contact_type="Individual" is_opt_out="0" display_name="Test Five" sort_name="Five, Test" first_name="Test" last_name="Five"/>
  <civicrm_contact id="110" contact_type="Individual" is_opt_out="0" display_name="Test Six" sort_name="Six, Test" first_name="Test" last_name="Six"/>
  <civicrm_contact id="111" contact_type="Individual" is_opt_out="0" display_name="Test Seven" sort_name="Seven, Test" first_name="Test" last_name="Seven"/>
  <civicrm_contact id="112" contact_type="Individual" is_opt_out="0" display_name="Test Eight" sort_name="Eight, Test" first_name="Test" last_name="Eight"/>
  <civicrm_contact id="113" contact_type="Individual" is_opt_out="0" display_name="Extra Neous" sort_name="Neous, Extra" first_name="Extra" last_name="Neous"/>
  <civicrm_email id="93" contact_id="102" location_type_id="1" email="test01@civicrm.org" is_primary="1" is_billing="0" on_hold="0" is_bulkmail="0" signature_text="" signature_html=""/>
  <civicrm_email id="94" contact_id="103" location_type_id="1" email="test02@civicrm.org" is_primary="1" is_billing="0" on_hold="0" is_bulkmail="0" signature_text="" signature_html=""/>
  <civicrm_email id="95" contact_id="104" location_type_id="1" email="test03@civicrm.org" is_primary="1" is_billing="0" on_hold="0" is_bulkmail="0" signature_text="" signature_html=""/>
  <civicrm_email id="96" contact_id="105" location_type_id="1" email="bouncy@bouncy.civicrm.org" is_primary="1" is_billing="0" on_hold="0" is_bulkmail="0" hold_date="2011-05-25 13:06:57" reset_date="2011-05-26 13:00:43" signature_text="" signature_html=""/>
  <civicrm_email id="99" contact_id="108" location_type_id="1" email="test04@civicrm.org" is_primary="1" is_billing="0" on_hold="0" is_bulkmail="0" signature_text="" signature_html=""/>
  <civicrm_email id="100" contact_id="109" location_type_id="1" email="test05@civicrm.org" is_primary="1" is_billing="0" on_hold="0" is_bulkmail="0" signature_text="" signature_html=""/>
  <civicrm_email id="101" contact_id="110" location_type_id="1" email="test06@civicrm.org" is_primary="1" is_billing="0" on_hold="0" is_bulkmail="0" signature_text="" signature_html=""/>
  <civicrm_email id="102" contact_id="111" location_type_id="1" email="test07@civicrm.org" is_primary="1" is_billing="0" on_hold="0" is_bulkmail="0" signature_text="" signature_html=""/>
  <civicrm_email id="103" contact_id="112" location_type_id="1" email="test08@civicrm.org" is_primary="1" is_billing="0" on_hold="0" is_bulkmail="0" signature_text="" signature_html=""/>
  <civicrm_mailing id="14" domain_id="1" header_id="1" footer_id="2" reply_id="8" unsubscribe_id="5" resubscribe_id="6" optout_id="7" name="First Mailing Events" from_name="FIXME" from_email="info@cividev.yook.civicrm.org" replyto_email="info@cividev.yook.civicrm.org" subject="Hello {contact.display_name}" body_text="" body_html="&lt;p&gt;&#13;&#10;&#9;Hello {contact.display_name},&lt;/p&gt;&#13;&#10;&lt;p&gt;&#13;&#10;&#9;You should check &lt;a href=&quot;http://drupal.org&quot;&gt;drupal.org&lt;/a&gt; and &lt;a href=&quot;http://civicrm.org&quot;&gt;civicrm.org&lt;/a&gt;.&lt;/p&gt;&#13;&#10;" url_tracking="1" forward_replies="0" auto_responder="0" open_tracking="1" is_completed="1" override_verp="0" created_id="102" created_date="2011-05-26 13:03:50" scheduled_id="102" scheduled_date="2011-05-25 13:06:08" approver_id="102" approval_date="2011-05-25 13:06:08" approval_status_id="1" approval_note="" is_archived="0" visibility="User and User Admin Only" />
  <civicrm_mailing id="15" domain_id="1" header_id="1" footer_id="2" reply_id="8" unsubscribe_id="5" resubscribe_id="6" optout_id="7" name="Second Test Mailing Events" from_name="FIXME" from_email="info@cividev.yook.civicrm.org" replyto_email="info@cividev.yook.civicrm.org" subject="Hello again, {contact.display_name}" body_text="" body_html="&lt;p&gt;&#13;&#10;&#9;Hello {contact.display_name},&lt;/p&gt;&#13;&#10;&lt;p&gt;&#13;&#10;&#9;You should check &lt;a href=&quot;http://drupal.org&quot;&gt;drupal.org&lt;/a&gt; and &lt;a href=&quot;http://civicrm.org&quot;&gt;civicrm.org&lt;/a&gt;.&lt;/p&gt;&#13;&#10;" url_tracking="1" forward_replies="0" auto_responder="0" open_tracking="1" is_completed="1" override_verp="0" created_id="102" created_date="2011-05-26 13:08:05" scheduled_id="102" scheduled_date="2011-05-26 13:08:46" approver_id="102" approval_date="2011-05-26 13:08:46" approval_status_id="1" approval_note="" is_archived="0" visibility="User and User Admin Only" />
  <civicrm_mailing_recipients id="1" mailing_id="14" email_id="96" contact_id="105"/>
  <civicrm_mailing_recipients id="2" mailing_id="14" email_id="93" contact_id="102"/>
  <civicrm_mailing_recipients id="3" mailing_id="14" email_id="94" contact_id="103"/>
  <civicrm_mailing_recipients id="4" mailing_id="14" email_id="95" contact_id="104"/>
  <civicrm_mailing_recipients id="5" mailing_id="14" email_id="99" contact_id="108"/>
  <civicrm_mailing_recipients id="6" mailing_id="14" email_id="100" contact_id="109"/>
  <civicrm_mailing_recipients id="7" mailing_id="14" email_id="101" contact_id="110"/>
  <civicrm_mailing_recipients id="8" mailing_id="14" email_id="102" contact_id="111"/>
  <civicrm_mailing_recipients id="9" mailing_id="14" email_id="103" contact_id="112"/>
  <civicrm_mailing_recipients id="10" mailing_id="15" email_id="93" contact_id="102"/>
  <civicrm_mailing_recipients id="11" mailing_id="15" email_id="94" contact_id="103"/>
  <civicrm_mailing_recipients id="12" mailing_id="15" email_id="95" contact_id="104"/>
  <civicrm_mailing_recipients id="13" mailing_id="15" email_id="99" contact_id="108"/>
  <civicrm_mailing_recipients id="14" mailing_id="15" email_id="100" contact_id="109"/>
  <civicrm_mailing_recipients id="15" mailing_id="15" email_id="101" contact_id="110"/>
  <civicrm_mailing_recipients id="16" mailing_id="15" email_id="102" contact_id="111"/>
  <civicrm_mailing_recipients id="17" mailing_id="15" email_id="103" contact_id="112"/>
  <civicrm_mailing_job id="23" mailing_id="14" scheduled_date="2011-05-25 13:06:08" start_date="2011-05-25 13:06:34" end_date="2011-05-25 13:06:35" status="Complete" is_test="0" job_type="" job_offset="0" job_limit="0"/>
  <civicrm_mailing_job id="24" mailing_id="14" scheduled_date="2011-05-25 13:06:08" start_date="2011-05-25 13:06:34" end_date="2011-05-25 13:06:35" status="Complete" is_test="0" job_type="child" parent_id="23" job_offset="0" job_limit="9"/>
  <civicrm_mailing_job id="25" mailing_id="15" scheduled_date="2011-05-26 13:08:46" start_date="2011-05-26 13:08:49" end_date="2011-05-26 13:08:51" status="Complete" is_test="0" job_type="" job_offset="0" job_limit="0"/>
  <civicrm_mailing_job id="26" mailing_id="15" scheduled_date="2011-05-26 13:08:46" start_date="2011-05-26 13:08:49" end_date="2011-05-26 13:08:51" status="Complete" is_test="0" job_type="child" parent_id="25" job_offset="0" job_limit="8"/>
  <civicrm_mailing_trackable_url id="12" url="http://drupal.org" mailing_id="14"/>
  <civicrm_mailing_trackable_url id="13" url="http://civicrm.org" mailing_id="14"/>
  <civicrm_mailing_trackable_url id="14" url="http://drupal.org" mailing_id="15"/>
  <civicrm_mailing_trackable_url id="15" url="http://civicrm.org" mailing_id="15"/>
  <civicrm_mailing_event_queue id="44" job_id="24" email_id="93" contact_id="102" hash="07705f3169d0fc84"/>
  <civicrm_mailing_event_queue id="45" job_id="24" email_id="94" contact_id="103" hash="8f6d859e31948f31"/>
  <civicrm_mailing_event_queue id="46" job_id="24" email_id="95" contact_id="104" hash="e9694c902fafa150"/>
  <civicrm_mailing_event_queue id="47" job_id="24" email_id="96" contact_id="105" hash="96a8f2de0d12ddf4"/>
  <civicrm_mailing_event_queue id="48" job_id="24" email_id="99" contact_id="108" hash="a3e9c35a0f8b8cf9"/>
  <civicrm_mailing_event_queue id="49" job_id="24" email_id="100" contact_id="109" hash="a32756fa40596d57"/>
  <civicrm_mailing_event_queue id="50" job_id="24" email_id="101" contact_id="110" hash="20d8df8676546473"/>
  <civicrm_mailing_event_queue id="51" job_id="24" email_id="102" contact_id="111" hash="2a2ea3816a403ccd"/>
  <civicrm_mailing_event_queue id="52" job_id="24" email_id="103" contact_id="112" hash="d429695899514370"/>
  <civicrm_mailing_event_queue id="53" job_id="26" email_id="93" contact_id="102" hash="98c5893b97ff170a"/>
  <civicrm_mailing_event_queue id="54" job_id="26" email_id="94" contact_id="103" hash="0ad8d95c73f56332"/>
  <civicrm_mailing_event_queue id="55" job_id="26" email_id="95" contact_id="104" hash="15035deafe89f4b0"/>
  <civicrm_mailing_event_queue id="56" job_id="26" email_id="99" contact_id="108" hash="c7df7cc740a7c105"/>
  <civicrm_mailing_event_queue id="57" job_id="26" email_id="100" contact_id="109" hash="c81c306117ca7fbd"/>
  <civicrm_mailing_event_queue id="58" job_id="26" email_id="101" contact_id="110" hash="a9ae5b99441f1dda"/>
  <civicrm_mailing_event_queue id="59" job_id="26" email_id="102" contact_id="111" hash="32fbff2a4814bb77"/>
  <civicrm_mailing_event_queue id="60" job_id="26" email_id="103" contact_id="112" hash="bcd3cfd309c8c117"/>
  <civicrm_mailing_event_bounce id="2" event_queue_id="47" bounce_type_id="6" bounce_reason="Unknown bounce type: Could not parse bounce email" time_stamp="2011-05-25 13:06:57"/>
  <civicrm_mailing_event_delivered id="44" event_queue_id="44" time_stamp="2011-05-25 13:06:34"/>
  <civicrm_mailing_event_delivered id="45" event_queue_id="45" time_stamp="2011-05-25 13:06:34"/>
  <civicrm_mailing_event_delivered id="46" event_queue_id="46" time_stamp="2011-05-25 13:06:34"/>
  <civicrm_mailing_event_delivered id="48" event_queue_id="48" time_stamp="2011-05-25 13:06:34"/>
  <civicrm_mailing_event_delivered id="49" event_queue_id="49" time_stamp="2011-05-25 13:06:35"/>
  <civicrm_mailing_event_delivered id="50" event_queue_id="50" time_stamp="2011-05-25 13:06:35"/>
  <civicrm_mailing_event_delivered id="51" event_queue_id="51" time_stamp="2011-05-25 13:06:35"/>
  <civicrm_mailing_event_delivered id="52" event_queue_id="52" time_stamp="2011-05-25 13:06:35"/>
  <civicrm_mailing_event_delivered id="53" event_queue_id="53" time_stamp="2011-05-26 13:08:50"/>
  <civicrm_mailing_event_delivered id="54" event_queue_id="54" time_stamp="2011-05-26 13:08:50"/>
  <civicrm_mailing_event_delivered id="55" event_queue_id="55" time_stamp="2011-05-26 13:08:50"/>
  <civicrm_mailing_event_delivered id="56" event_queue_id="56" time_stamp="2011-05-26 13:08:50"/>
  <civicrm_mailing_event_delivered id="57" event_queue_id="57" time_stamp="2011-05-26 13:08:50"/>
  <civicrm_mailing_event_delivered id="58" event_queue_id="58" time_stamp="2011-05-26 13:08:50"/>
  <civicrm_mailing_event_delivered id="59" event_queue_id="59" time_stamp="2011-05-26 13:08:51"/>
  <civicrm_mailing_event_delivered id="60" event_queue_id="60" time_stamp="2011-05-26 13:08:51"/>
  <civicrm_mailing_event_opened id="5" event_queue_id="50" time_stamp="2011-05-26 13:17:54"/>
  <civicrm_mailing_event_opened id="6" event_queue_id="50" time_stamp="2011-05-26 13:17:54"/>
  <civicrm_mailing_event_opened id="7" event_queue_id="50" time_stamp="2011-05-26 13:18:13"/>
  <civicrm_mailing_event_opened id="8" event_queue_id="50" time_stamp="2011-05-26 13:18:13"/>
  <civicrm_mailing_event_opened id="9" event_queue_id="49" time_stamp="2011-05-26 13:19:03"/>
  <civicrm_mailing_event_opened id="10" event_queue_id="49" time_stamp="2011-05-26 13:19:04"/>
  <civicrm_mailing_event_opened id="11" event_queue_id="52" time_stamp="2011-05-26 13:19:44"/>
  <civicrm_mailing_event_opened id="12" event_queue_id="52" time_stamp="2011-05-26 13:19:44"/>
  <civicrm_mailing_event_opened id="13" event_queue_id="52" time_stamp="2011-05-26 13:19:47"/>
  <civicrm_mailing_event_opened id="14" event_queue_id="52" time_stamp="2011-05-26 13:19:47"/>
  <civicrm_mailing_event_opened id="15" event_queue_id="51" time_stamp="2011-05-26 13:20:59"/>
  <civicrm_mailing_event_opened id="16" event_queue_id="51" time_stamp="2011-05-26 13:20:59"/>
  <civicrm_mailing_event_opened id="17" event_queue_id="53" time_stamp="2011-05-26 13:23:22"/>
  <civicrm_mailing_event_opened id="18" event_queue_id="53" time_stamp="2011-05-26 13:23:22"/>
  <civicrm_mailing_event_reply id="2" event_queue_id="45" time_stamp="2011-05-26 13:12:15"/>
  <civicrm_mailing_event_reply id="3" event_queue_id="48" time_stamp="2011-05-26 13:15:09"/>
  <civicrm_mailing_event_reply id="4" event_queue_id="50" time_stamp="2011-05-26 13:20:02"/>
  <civicrm_mailing_event_reply id="5" event_queue_id="52" time_stamp="2011-05-26 13:20:33"/>
  <civicrm_mailing_event_reply id="6" event_queue_id="54" time_stamp="2011-05-26 13:27:44"/>
  <civicrm_mailing_event_trackable_url_open id="5" event_queue_id="46" trackable_url_id="12" time_stamp="2011-05-26 13:16:03"/>
  <civicrm_mailing_event_trackable_url_open id="6" event_queue_id="46" trackable_url_id="13" time_stamp="2011-05-26 13:16:07"/>
  <civicrm_mailing_event_trackable_url_open id="4" event_queue_id="48" trackable_url_id="12" time_stamp="2011-05-26 13:15:36"/>
  <civicrm_mailing_event_trackable_url_open id="8" event_queue_id="51" trackable_url_id="12" time_stamp="2011-05-26 13:21:09"/>
  <civicrm_mailing_event_trackable_url_open id="9" event_queue_id="51" trackable_url_id="13" time_stamp="2011-05-26 13:21:13"/>
  <civicrm_mailing_event_trackable_url_open id="7" event_queue_id="52" trackable_url_id="13" time_stamp="2011-05-26 13:20:03"/>
  <civicrm_mailing_event_trackable_url_open id="12" event_queue_id="52" trackable_url_id="13" time_stamp="2011-05-27 13:20:03"/>
  <civicrm_mailing_event_trackable_url_open id="10" event_queue_id="55" trackable_url_id="14" time_stamp="2011-05-26 13:23:54"/>
  <civicrm_mailing_event_trackable_url_open id="11" event_queue_id="55" trackable_url_id="15" time_stamp="2011-05-26 13:23:58"/>
</dataset>');

    $json_string = json_encode($xml);
    return json_decode($json_string, TRUE);
  }

}
