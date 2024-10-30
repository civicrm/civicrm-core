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
 * This class generates form components for Mapping and Geocoding.
 */
class CRM_Admin_Form_Setting_Mapping extends CRM_Admin_Form_Setting {

  /**
   * Subset of settings on the page as defined using the legacy method.
   *
   * @var array
   *
   * @deprecated - do not add new settings here - the page to display
   * settings on should be defined in the setting metadata.
   */
  protected $_settings = [
    // @todo remove these, define any not yet defined in the setting metadata.
    'mapAPIKey' => CRM_Core_BAO_Setting::MAP_PREFERENCES_NAME,
    'mapProvider' => CRM_Core_BAO_Setting::MAP_PREFERENCES_NAME,
    'geoAPIKey' => CRM_Core_BAO_Setting::MAP_PREFERENCES_NAME,
    'geoProvider' => CRM_Core_BAO_Setting::MAP_PREFERENCES_NAME,
  ];

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->setTitle(ts('Settings - Mapping and Geocoding Providers'));
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

    if ($fields['mapProvider'] == 'OpenStreetMaps' && $fields['geoProvider'] == '') {
      $errors['geoProvider'] = ts('Please select a Geocoding Provider - Open Street Maps does not provide geocoding.');
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
