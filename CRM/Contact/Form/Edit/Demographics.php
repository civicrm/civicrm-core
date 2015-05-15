<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */

/**
 * form helper class for an Demographics object
 */
class CRM_Contact_Form_Edit_Demographics {

  /**
   * Build the form object elements for Demographics object.
   *
   * @param CRM_Core_Form $form
   *   Reference to the form object.
   *
   * @return void
   */
  public static function buildQuickForm(&$form) {
    // radio button for gender
    $genderOptions = array();
    $gender = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'gender_id', array('localize' => TRUE));
    foreach ($gender as $key => $var) {
      $genderOptions[$key] = $form->createElement('radio', NULL,
        ts('Gender'), $var, $key,
        array('id' => "civicrm_gender_{$var}_{$key}")
      );
    }
    $form->addGroup($genderOptions, 'gender_id', ts('Gender'))->setAttribute('allowClear', TRUE);

    $form->addDate('birth_date', ts('Date of Birth'), FALSE, array('formatType' => 'birth'));

    $form->addElement('checkbox', 'is_deceased', NULL, ts('Contact is Deceased'), array('onclick' => "showDeceasedDate()"));
    $form->addDate('deceased_date', ts('Deceased Date'), FALSE, array('formatType' => 'birth'));
  }

  /**
   * Set default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   *
   * @param CRM_Core_Form $form
   * @param $defaults
   *
   * @return void
   */
  public static function setDefaultValues(&$form, &$defaults) {
  }

}
