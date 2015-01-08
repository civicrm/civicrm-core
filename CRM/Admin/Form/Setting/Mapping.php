<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * This class generates form components for Mapping and Geocoding
 *
 */
class CRM_Admin_Form_Setting_Mapping extends CRM_Admin_Form_Setting {

  /**
   * Function to build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Settings - Mapping and Geocoding Providers'));

    $map = CRM_Core_SelectValues::mapProvider();
    $geo = CRM_Core_SelectValues::geoProvider();
    $this->addElement('select', 'mapProvider', ts('Mapping Provider'), array('' => '- select -') + $map, array('class' => 'crm-select2'));
    $this->add('text', 'mapAPIKey', ts('Map Provider Key'), NULL);
    $this->addElement('select', 'geoProvider', ts('Geocoding Provider'), array('' => '- select -') + $geo, array('class' => 'crm-select2'));
    $this->add('text', 'geoAPIKey', ts('Geo Provider Key'), NULL);

    parent::buildQuickForm();
  }

  /**
   * global form rule
   *
   * @param array $fields  the input form values
   *
   * @return true if no errors, else array of errors
   * @access public
   * @static
   */
  static function formRule($fields) {
    $errors = array();

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
   * This function is used to add the rules (mainly global rules) for form.
   * All local rules are added near the element
   *
   * @param null
   *
   * @return void
   * @access public
   */
  function addRules() {
    $this->addFormRule(array('CRM_Admin_Form_Setting_Mapping', 'formRule'));
  }
}

