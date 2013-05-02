<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

require_once 'api/Wrapper.php';
class CRM_Core_HTMLInputCoder implements API_Wrapper {
  private static $skipFields = NULL;

  /**
   * @var CRM_Core_HTMLInputCoder
   */
  private static $_singleton = NULL;

  /**
   * @return CRM_Core_HTMLInputCoder
   */
  public static function singleton() {
    if (self::$_singleton === NULL) {
      self::$_singleton = new CRM_Core_HTMLInputCoder();
    }
    return self::$_singleton;
  }

  /**
   * @return array<string> list of field names
   */
  public static function getSkipFields() {
    if (self::$skipFields === NULL) {
      self::$skipFields = array(
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
        'pay_later_receipt',
        'label', // This is needed for FROM Email Address configuration. dgg
        'url',  // This is needed for navigation items urls
        'details',
        'msg_text', // message templates’ text versions
        'text_message', // (send an) email to contact’s and CiviMail’s text version
        'data', // data i/p of persistent table
        'sqlQuery', // CRM-6673
        'pcp_title',
        'pcp_intro_text',
        'new', // The 'new' text in word replacements
      );
    }
    return self::$skipFields;
  }

  /**
   * @param string $fldName
   * @return bool TRUE if encoding should be skipped for this field
   */
  public static function isSkippedField($fldName) {
    $skipFields = self::getSkipFields();

    // Field should be skipped
    if (in_array($fldName, $skipFields)) {
      return TRUE;
    }
    // Field is multilingual and after cutting off _xx_YY should be skipped (CRM-7230)…
    if ((preg_match('/_[a-z][a-z]_[A-Z][A-Z]$/', $fldName) && in_array(substr($fldName, 0, -6), $skipFields))) {
      return TRUE;
    }
    // Field can take multiple entries, eg. fieldName[1], fieldName[2], etc.
    // We remove the index and check again if the fieldName in the list of skipped fields.
    $matches = array();
    if (preg_match('/^(.*)\[\d+\]/', $fldName, $matches) && in_array($matches[1], $skipFields)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * This function is going to filter the
   * submitted values across XSS vulnerability.
   *
   * @param array|string $values
   * @param bool $castToString If TRUE, all scalars will be filtered (and therefore cast to strings)
   *    If FALSE, then non-string values will be preserved
   */
  public static function encodeInput(&$values, $castToString = TRUE) {
    if (is_array($values)) {
      foreach ($values as &$value) {
        self::encodeInput($value);
      }
    } elseif ($castToString || is_string($values)) {
      $values = str_replace(array('<', '>'), array('&lt;', '&gt;'), $values);
    }
  }

  public static function decodeOutput(&$values, $castToString = TRUE) {
    if (is_array($values)) {
      foreach ($values as &$value) {
        self::decodeOutput($value);
      }
    } elseif ($castToString || is_string($values)) {
      $values = str_replace(array('&lt;', '&gt;'), array('<', '>'), $values);
    }
  }

  /**
   * {@inheritDoc}
   */
  public function fromApiInput($apiRequest) {
    $lowerAction = strtolower($apiRequest['action']);
    if ($apiRequest['version'] == 3 && in_array($lowerAction, array('get', 'create'))) {
      // note: 'getsingle', 'replace', 'update', and chaining all build on top of 'get'/'create'
      foreach ($apiRequest['params'] as $key => $value) {
        // Don't apply escaping to API control parameters (e.g. 'api.foo' or 'options.foo')
        // and don't apply to other skippable fields
        if (!self::isApiControlField($key) && !self::isSkippedField($key)) {
          self::encodeInput($apiRequest['params'][$key], FALSE);
        }
      }
    } elseif ($apiRequest['version'] == 3 && $lowerAction == 'setvalue') {
      if (isset($apiRequest['params']['field']) && isset($apiRequest['params']['value'])) {
        if (!self::isSkippedField($apiRequest['params']['field'])) {
          self::encodeInput($apiRequest['params']['value'], FALSE);
        }
      }
    }
    return $apiRequest;
  }

  /**
   * {@inheritDoc}
   */
  public function toApiOutput($apiRequest, $result) {
    $lowerAction = strtolower($apiRequest['action']);
    if ($apiRequest['version'] == 3 && in_array($lowerAction, array('get', 'create', 'setvalue'))) {
      foreach ($result as $key => $value) {
        // Don't apply escaping to API control parameters (e.g. 'api.foo' or 'options.foo')
        // and don't apply to other skippable fields
        if (!self::isApiControlField($key) && !self::isSkippedField($key)) {
          self::decodeOutput($result[$key], FALSE);
        }
      }
    }
    // setvalue?
    return $result;
  }

  /**
   * @return bool
   */
  protected function isApiControlField($key) {
    return (FALSE !== strpos($key, '.'));
  }
}
