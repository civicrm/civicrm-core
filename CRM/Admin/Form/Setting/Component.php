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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * This class generates form components for Component.
 */
class CRM_Admin_Form_Setting_Component extends CRM_Admin_Form_Setting {
  protected $_components;

  protected $_settings = [
    'enable_components' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
  ];

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
      if (!empty($fields['enable_components']['CiviCase']) &&
        !CRM_Core_DAO::checkTriggerViewPermission(TRUE, FALSE)
      ) {
        $errors['enable_components'] = ts('CiviCase requires CREATE VIEW and DROP VIEW permissions for the database.');
      }
    }

    return $errors;
  }

}
