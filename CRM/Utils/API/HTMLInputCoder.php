<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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
      $this->skipFields = [
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
        // This is needed for FROM Email Address configuration. dgg
        'label',
        // This is needed for navigation items urls
        'url',
        'details',
        // message templates’ text versions
        'msg_text',
        // (send an) email to contact’s and CiviMail’s text version
        'text_message',
        // data i/p of persistent table
        'data',
        // CRM-6673
        'sqlQuery',
        'pcp_title',
        'pcp_intro_text',
        // The 'new' text in word replacements
        'new',
        // e.g. '"Full Name" <user@example.org>'
        'replyto_email',
        'operator',
        // CRM-20468
        'content',
        // CiviCampaign Goal Details
        'goal_general',
        // https://lab.civicrm.org/dev/core/issues/1286
        'header',
        // https://lab.civicrm.org/dev/core/issues/1286
        'footer',
      ];
      $custom = CRM_Core_DAO::executeQuery('SELECT id FROM civicrm_custom_field WHERE html_type = "RichTextEditor"');
      while ($custom->fetch()) {
        $this->skipFields[] = 'custom_' . $custom->id;
      }
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
      $values = $this->encodeValue($values);
    }
  }

  public function encodeValue($value) {
    return str_replace(['<', '>'], ['&lt;', '&gt;'], $value);
  }

  /**
   * Perform in-place decode on strings (in a list of records).
   *
   * @param array $rows
   *   Ex in: $rows[0] = ['first_name' => 'A&W'].
   *   Ex out: $rows[0] = ['first_name' => 'A&amp;W'].
   */
  public function encodeRows(&$rows) {
    foreach ($rows as $rid => $row) {
      $this->encodeRow($rows[$rid]);
    }
  }

  /**
   * Perform in-place encode on strings (in a single record).
   *
   * @param array $row
   *   Ex in: ['first_name' => 'A&W'].
   *   Ex out: ['first_name' => 'A&amp;W'].
   */
  public function encodeRow(&$row) {
    foreach ($row as $k => $v) {
      if (is_string($v) && !$this->isSkippedField($k)) {
        $row[$k] = $this->encodeValue($v);
      }
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
      $values = $this->decodeValue($values);
    }
  }

  public function decodeValue($value) {
    return str_replace(['&lt;', '&gt;'], ['<', '>'], $value);
  }

  /**
   * Perform in-place decode on strings (in a list of records).
   *
   * @param array $rows
   *   Ex in: $rows[0] = ['first_name' => 'A&amp;W'].
   *   Ex out: $rows[0] = ['first_name' => 'A&W'].
   */
  public function decodeRows(&$rows) {
    foreach ($rows as $rid => $row) {
      $this->decodeRow($rows[$rid]);
    }
  }

  /**
   * Perform in-place decode on strings (in a single record).
   *
   * @param array $row
   *   Ex in: ['first_name' => 'A&amp;W'].
   *   Ex out: ['first_name' => 'A&W'].
   */
  public function decodeRow(&$row) {
    foreach ($row as $k => $v) {
      if (is_string($v) && !$this->isSkippedField($k)) {
        $row[$k] = $this->decodeValue($v);
      }
    }
  }

}
