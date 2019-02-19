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
 * This class provides the functionality to support Proximity Searches.
 */
class CRM_Contact_Form_Task_ProximityCommon {

  /**
   * The context that we are working on.
   *
   * @var string
   */
  protected $_context;

  /**
   * The groupId retrieved from the GET vars.
   *
   * @var int
   */
  protected $_id;

  /**
   * The title of the group.
   *
   * @var string
   */
  protected $_title;

  /**
   * Build the form object.
   *
   * @param CRM_Core_Form $form
   * @param int $proxSearch
   */
  static public function buildQuickForm($form, $proxSearch) {
    // is proximity search required (2) or optional (1)?
    $proxRequired = ($proxSearch == 2 ? TRUE : FALSE);
    $form->assign('proximity_search', TRUE);

    $form->add('text', 'prox_street_address', ts('Street Address'), NULL, FALSE);

    $form->add('text', 'prox_city', ts('City'), NULL, FALSE);

    $form->add('text', 'prox_postal_code', ts('Postal Code'), NULL, FALSE);

    $form->addChainSelect('prox_state_province_id', array('required' => $proxRequired));

    $country = array('' => ts('- select -')) + CRM_Core_PseudoConstant::country();
    $form->add('select', 'prox_country_id', ts('Country'), $country, $proxRequired);

    $form->add('text', 'prox_distance', ts('Distance'), NULL, $proxRequired);

    $proxUnits = array('km' => ts('km'), 'miles' => ts('miles'));
    $form->add('select', 'prox_distance_unit', ts('Units'), $proxUnits, $proxRequired);
    // prox_distance_unit

    $form->addFormRule(array('CRM_Contact_Form_Task_ProximityCommon', 'formRule'), $form);
  }

  /**
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param CRM_Core_Form $form
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function formRule($fields, $files, $form) {
    $errors = array();
    // If Distance is present, make sure state, country and city or postal code are populated.
    if (!empty($fields['prox_distance'])) {
      if (empty($fields['prox_state_province_id']) || empty($fields['prox_country_id'])) {
        $errors["prox_state_province_id"] = ts("Country AND State/Province are required to search by distance.");
      }
      if (!CRM_Utils_Array::value('prox_postal_code', $fields) AND
        !CRM_Utils_Array::value('prox_city', $fields)
      ) {
        $errors["prox_distance"] = ts("City OR Postal Code are required to search by distance.");
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Set the default form values.
   *
   * @param CRM_Core_Form $form
   *
   * @return array
   *   the default array reference
   */
  static public function setDefaultValues($form) {
    $defaults = array();
    $config = CRM_Core_Config::singleton();
    $countryDefault = $config->defaultContactCountry;

    if ($countryDefault) {
      $defaults['prox_country_id'] = $countryDefault;
      if ($countryDefault == '1228') {
        $defaults['prox_distance_unit'] = 'miles';
      }
      else {
        $defaults['prox_distance_unit'] = 'km';
      }
    }
    $form->setDefaults($defaults);
    return $defaults;
  }

}
