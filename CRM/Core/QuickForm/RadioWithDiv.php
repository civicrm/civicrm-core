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
 * @package CRM
 */

require_once 'HTML/QuickForm/radio.php';

/**
 * Class CRM_Core_QuickForm_RadioWithDiv
 */
class CRM_Core_QuickForm_RadioWithDiv extends HTML_QuickForm_radio {

  /**
   * Returns the radio element in HTML
   *
   * @since     1.0
   * @access    public
   * @return    string
   */
  public function toHtml(): string {
    $html = parent::toHtml();
    if (is_numeric($this->getAttribute('options_per_line'))) {
      return '<div class="crm-option-label-pair" >' . $html . '</div>';
    }
    return $html;
  }

}
