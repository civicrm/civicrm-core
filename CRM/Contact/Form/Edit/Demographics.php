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
 * form helper class for an Demographics object
 */
class CRM_Contact_Form_Edit_Demographics {

  /**
   * build the form elements for Demographics object
   *
   * @param CRM_Core_Form $form       reference to the form object
   *
   * @return void
   * @access public
   * @static
   */
  static function buildQuickForm(&$form) {
    // radio button for gender
    $genderOptions = array();
    $gender = CRM_Core_PseudoConstant::gender(TRUE);
    foreach ($gender as $key => $var) {
      $genderOptions[$key] = $form->createElement('radio', NULL,
        ts('Gender'), $var, $key,
        array('id' => "civicrm_gender_{$var}_{$key}")
      );
    }
    $form->addGroup($genderOptions, 'gender_id', ts('Gender'));

    $form->addDate('birth_date', ts('Date of birth'), FALSE, array('formatType' => 'birth'));

    $form->addElement('checkbox', 'is_deceased', NULL, ts('Contact is deceased'), array('onclick' => "showDeceasedDate()"));
    $form->addDate('deceased_date', ts('Deceased date'), FALSE, array('formatType' => 'birth'));
  }

  /**
   * This function sets the default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  static function setDefaultValues(&$form, &$defaults) {}
}

