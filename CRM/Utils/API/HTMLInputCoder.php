<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * This class captures the encoding practices of CRM-5667 in a reusable
 * fashion.  In this design, all submitted values are partially HTML-encoded
 * before saving to the database.  If a DB reader needs to output in
 * non-HTML medium, then it should undo the partial HTML encoding.
 *
 * This class should be short-lived -- 4.3 should introduce an alternative
 * escaping scheme and consequently remove HTMLInputCoder.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 */
class CRM_Utils_API_HTMLInputCoder extends CRM_Utils_API_AbstractFieldCoder {
  private $skipFields = NULL;

  /**
   * @var CRM_Utils_API_HTMLInputCoder
   */
  private static $_singleton = NULL;

  /**
   * @return CRM_Utils_API_HTMLInputCoder
   */
  public static function singleton() {
    if (self::$_singleton === NULL) {
      self::$_singleton = new CRM_Utils_API_HTMLInputCoder();
    }
    return self::$_singleton;
  }

  /**
   * Get skipped fields.
   *
   * @return array<string>
   *   list of field names
   */
  public function getSkipFields() {
    if ($this->skipFields === NULL) {
      $this->skipFields = array(
        'widget_code',
        'html_message',
        'body_html',
        'msg_html',
        'description',
        'intro',
        'thankyou_text',
        'tf_thankyou_text',
        'intro_text',
        'page_text',
        'body_text',
        'footer_text',
        'thankyou_footer',
        'thankyou_footer_text',
        'new_text',
        'renewal_text',
        'help_pre',
        'help_post',
        'confirm_title',
        'confirm_text',
        'confirm_footer_text',
        'confirm_email_text',
        'event_full_text',
        'waitlist_text',
        'approval_req_text',
        'report_header',
        'report_footer',
        'cc_id',
        'bcc_id',
        'premiums_intro_text',
        'honor_block_text',
        'pay_later_text',
        'pay_later_receipt',
        'label', // This is needed for FROM Email Address configuration. dgg
        'url', // This is needed for navigation items urls
        'details',
        'msg_text', // message templates’ text versions
        'text_message', // (send an) email to contact’s and CiviMail’s text version
        'data', // data i/p of persistent table
        'sqlQuery', // CRM-6673
        'pcp_title',
        'pcp_intro_text',
        'new', // The 'new' text in word replacements
        'replyto_email', // e.g. '"Full Name" <user@example.org>'
        'operator',
        'content', // CRM-20468
      );
    }
    return $this->skipFields;
  }

  /**
   * going to filter the
   * submitted values across XSS vulnerability.
   *
   * @param array|string $values
   * @param bool $castToString
   *   If TRUE, all scalars will be filtered (and therefore cast to strings).
   *    If FALSE, then non-string values will be preserved
   */
  public function encodeInput(&$values, $castToString = FALSE) {
    if (is_array($values)) {
      foreach ($values as &$value) {
        $this->encodeInput($value, TRUE);
      }
    }
    elseif ($castToString || is_string($values)) {
      $values = str_replace(array('<', '>'), array('&lt;', '&gt;'), $values);
    }
  }

  /**
   * @param array $values
   * @param bool $castToString
   */
  public function decodeOutput(&$values, $castToString = FALSE) {
    if (is_array($values)) {
      foreach ($values as &$value) {
        $this->decodeOutput($value, TRUE);
      }
    }
    elseif ($castToString || is_string($values)) {
      $values = str_replace(array('&lt;', '&gt;'), array('<', '>'), $values);
    }
  }

}
