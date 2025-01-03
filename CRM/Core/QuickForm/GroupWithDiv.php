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
 *
 * @package CRM
 * @copyright U.S. PIRG Education Fund 2007
 *
 */

require_once 'HTML/QuickForm/group.php';

/**
 * Class CRM_Core_QuickForm_GroupWithDiv
 */
class CRM_Core_QuickForm_GroupWithDiv extends HTML_QuickForm_group {

  /**
   * Returns the group element in HTML
   *
   * @since     1.0
   * @access    public
   * @return    string
   */
  public function toHtml(): string {
    $html = parent::toHtml();
    if (is_numeric($this->getAttribute('options_per_line'))) {
      return '<div class="crm-multiple-checkbox-radio-options crm-options-per-line" style="--crm-opts-per-line:' . $this->getAttribute('options_per_line') . ';">' . $html . '</div>';
    }
    return $html;
  }

}
