<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * This class generates form components for Search Parameters
 *
 */
class CRM_Admin_Form_Setting_Search extends CRM_Admin_Form_Setting {

  protected $_settings = array(
    'contact_reference_options' => CRM_Core_BAO_Setting::SEARCH_PREFERENCES_NAME,
    'contact_autocomplete_options' => CRM_Core_BAO_Setting::SEARCH_PREFERENCES_NAME,
    'search_autocomplete_count' => CRM_Core_BAO_Setting::SEARCH_PREFERENCES_NAME,
    'enable_innodb_fts' => CRM_Core_BAO_Setting::SEARCH_PREFERENCES_NAME,
    'includeWildCardInName' => CRM_Core_BAO_Setting::SEARCH_PREFERENCES_NAME,
    'includeEmailInName' => CRM_Core_BAO_Setting::SEARCH_PREFERENCES_NAME,
    'includeNickNameInName' => CRM_Core_BAO_Setting::SEARCH_PREFERENCES_NAME,
    'includeAlphabeticalPager' => CRM_Core_BAO_Setting::SEARCH_PREFERENCES_NAME,
    'includeOrderByClause' => CRM_Core_BAO_Setting::SEARCH_PREFERENCES_NAME,
    'smartGroupCacheTimeout' => CRM_Core_BAO_Setting::SEARCH_PREFERENCES_NAME,
    'defaultSearchProfileID' => CRM_Core_BAO_Setting::SEARCH_PREFERENCES_NAME,
  );

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Settings - Search Preferences'));

    parent::buildQuickForm();

    // @todo remove the following adds in favour of setting via the settings array (above).

    // Autocomplete for Contact Search (quick search etc.)
    $element = $this->getElement('contact_autocomplete_options');
    $element->_elements[0]->_flagFrozen = TRUE;

    // Autocomplete for Contact Reference (custom fields)
    $element = $this->getElement('contact_reference_options');
    $element->_elements[0]->_flagFrozen = TRUE;
  }

  /**
   * @return array
   */
  public static function getContactAutocompleteOptions() {
    return array(
      ts('Contact Name') => 1,
    ) + array_flip(CRM_Core_OptionGroup::values('contact_autocomplete_options',
      FALSE, FALSE, TRUE
    ));
  }

  /**
   * @return array
   */
  public static function getAvailableProfiles() {
    return array('' => ts('- none -')) + CRM_Core_BAO_UFGroup::getProfiles(array(
      'Contact',
      'Individual',
      'Organization',
      'Household',
    ));
  }

  /**
   * @return array
   */
  public static function getContactReferenceOptions() {
    return array(
      ts('Contact Name') => 1,
    ) + array_flip(CRM_Core_OptionGroup::values('contact_reference_options',
      FALSE, FALSE, TRUE
    ));
  }

}
