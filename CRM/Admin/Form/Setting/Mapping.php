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
 * This class generates form components for Mapping and Geocoding.
 */
class CRM_Admin_Form_Setting_Mapping extends CRM_Admin_Form_Setting {

  protected $_settings = [
    'mapAPIKey' => CRM_Core_BAO_Setting::MAP_PREFERENCES_NAME,
    'mapProvider' => CRM_Core_BAO_Setting::MAP_PREFERENCES_NAME,
    'geoAPIKey' => CRM_Core_BAO_Setting::MAP_PREFERENCES_NAME,
    'geoProvider' => CRM_Core_BAO_Setting::MAP_PREFERENCES_NAME,
  ];

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Settings - Mapping and Geocoding Providers'));
    parent::buildQuickForm();
  }

  /**
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function formRule($fields) {
    $errors = [];

    if (!CRM_Utils_System::checkPHPVersion(5, FALSE)) {
      $errors['_qf_default'] = ts('Mapping features require PHP version 5 or greater');
    }

    if (!$fields['mapAPIKey'] && ($fields['mapProvider'] != '' && $fields['mapProvider'] == 'Yahoo')) {
      $errors['mapAPIKey'] = "Map Provider key is a required field.";
    }

    if ($fields['mapProvider'] == 'OpenStreetMaps' && $fields['geoProvider'] == '') {
      $errors['geoProvider'] = "Please select a Geocoding Provider - Open Street Maps does not provide geocoding.";
    }

    return $errors;
  }

  /**
   * Add the rules (mainly global rules) for form.
   *
   * All local rules are added near the element
   */
  public function addRules() {
    $this->addFormRule(['CRM_Admin_Form_Setting_Mapping', 'formRule']);
  }

}
