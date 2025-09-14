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
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class generates form components for Component.
 */
class CRM_Admin_Form_Setting_Component extends CRM_Admin_Form_Setting {

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->addFormRule(['CRM_Admin_Form_Setting_Component', 'formRule'], $this);
    parent::buildQuickForm();
  }

  /**
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param array $options
   *   Additional user data.
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function formRule($fields, $files, $options) {
    $errors = [];

    if (array_key_exists('enable_components', $fields) && is_array($fields['enable_components'])) {
      if (!empty($fields['enable_components']['CiviPledge']) &&
        empty($fields['enable_components']['CiviContribute'])
      ) {
        $errors['enable_components'] = ts('You need to enable CiviContribute before enabling CiviPledge.');
      }
    }

    return $errors;
  }

}
