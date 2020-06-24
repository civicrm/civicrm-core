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
 * This class generates form components for Search Parameters
 *
 */
class CRM_Admin_Form_Setting_Search extends CRM_Admin_Form_Setting {

  protected $_settings = [
    'includeWildCardInName' => CRM_Core_BAO_Setting::SEARCH_PREFERENCES_NAME,
    'includeEmailInName' => CRM_Core_BAO_Setting::SEARCH_PREFERENCES_NAME,
    'searchPrimaryDetailsOnly' => CRM_Core_BAO_Setting::SEARCH_PREFERENCES_NAME,
    'includeNickNameInName' => CRM_Core_BAO_Setting::SEARCH_PREFERENCES_NAME,
    'includeAlphabeticalPager' => CRM_Core_BAO_Setting::SEARCH_PREFERENCES_NAME,
    'includeOrderByClause' => CRM_Core_BAO_Setting::SEARCH_PREFERENCES_NAME,
    'defaultSearchProfileID' => CRM_Core_BAO_Setting::SEARCH_PREFERENCES_NAME,
    'smartGroupCacheTimeout' => CRM_Core_BAO_Setting::SEARCH_PREFERENCES_NAME,
    'quicksearch_options' => CRM_Core_BAO_Setting::SEARCH_PREFERENCES_NAME,
    'contact_autocomplete_options' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'contact_reference_options' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
    'search_autocomplete_count' => CRM_Core_BAO_Setting::SEARCH_PREFERENCES_NAME,
    'enable_innodb_fts' => CRM_Core_BAO_Setting::SEARCH_PREFERENCES_NAME,
  ];

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Settings - Search Preferences'));

    parent::buildQuickForm();

    // Option 1 can't be unchecked. @see self::enableOptionOne
    $element = $this->getElement('contact_autocomplete_options');
    $element->_elements[0]->setAttribute('disabled', 'disabled');

    // Option 1 can't be unchecked. @see self::enableOptionOne
    $element = $this->getElement('contact_reference_options');
    $element->_elements[0]->setAttribute('disabled', 'disabled');
  }

  /**
   * @return array
   */
  public static function getContactAutocompleteOptions() {
    return [1 => ts('Contact Name')] + CRM_Core_OptionGroup::values('contact_autocomplete_options', FALSE, FALSE, TRUE);
  }

  /**
   * @return array
   */
  public static function getAvailableProfiles() {
    return ['' => ts('- none -')] + CRM_Core_BAO_UFGroup::getProfiles([
      'Contact',
      'Individual',
      'Organization',
      'Household',
    ]);
  }

  /**
   * @return array
   */
  public static function getContactReferenceOptions() {
    return [1 => ts('Contact Name')] + CRM_Core_OptionGroup::values('contact_reference_options', FALSE, FALSE, TRUE);
  }

  /**
   * Presave callback for contact_reference_options and contact_autocomplete_options.
   *
   * Ensures "1" is always contained in the array.
   *
   * @param $value
   * @return bool
   */
  public static function enableOptionOne(&$value) {
    $values = (array) CRM_Utils_Array::explodePadded($value);
    if (!in_array(1, $values)) {
      $value = CRM_Utils_Array::implodePadded(array_merge([1], $values));
    }
    return TRUE;
  }

}
