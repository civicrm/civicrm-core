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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This class generates form components for Search Parameters
 *
 */
class CRM_Admin_Form_Setting_Search extends CRM_Admin_Form_Setting {

  protected $_settings = array(
    'search_autocomplete_count' => CRM_Core_BAO_Setting::SEARCH_PREFERENCES_NAME,
  );

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Settings - Search Preferences'));

    $this->addYesNo('includeWildCardInName', ts('Automatic Wildcard'));
    $this->addYesNo('includeEmailInName', ts('Include Email'));
    $this->addYesNo('includeNickNameInName', ts('Include Nickname'));

    $this->addYesNo('includeAlphabeticalPager', ts('Include Alphabetical Pager'));
    $this->addYesNo('includeOrderByClause', ts('Include Order By Clause'));

    $this->addElement('text', 'smartGroupCacheTimeout', ts('Smart group cache timeout'),
      array('size' => 3, 'maxlength' => 5)
    );

    $types = array('Contact', 'Individual', 'Organization', 'Household');
    $profiles = CRM_Core_BAO_UFGroup::getProfiles($types);

    $this->add('select', 'defaultSearchProfileID', ts('Default Contact Search Profile'),
      array(
        '' => ts('- select -')) + $profiles
    );

    // Autocomplete for Contact Search (quick search etc.)
    $options = array(
      ts('Contact Name') => 1) + array_flip(CRM_Core_OptionGroup::values('contact_autocomplete_options',
        FALSE, FALSE, TRUE
      ));
    $this->addCheckBox('autocompleteContactSearch', ts('Autocomplete Contact Search'), $options,
      NULL, NULL, NULL, NULL, array('&nbsp;&nbsp;')
    );
    $element = $this->getElement('autocompleteContactSearch');
    $element->_elements[0]->_flagFrozen = TRUE;

    // Autocomplete for Contact Reference (custom fields)
    $optionsCR = array(
      ts('Contact Name') => 1) + array_flip(CRM_Core_OptionGroup::values('contact_reference_options',
        FALSE, FALSE, TRUE
      ));
    $this->addCheckBox('autocompleteContactReference', ts('Contact Reference Options'), $optionsCR,
      NULL, NULL, NULL, NULL, array('&nbsp;&nbsp;')
    );
    $element = $this->getElement('autocompleteContactReference');
    $element->_elements[0]->_flagFrozen = TRUE;

    parent::buildQuickForm();
  }
}

