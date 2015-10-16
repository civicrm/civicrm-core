<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
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
 */

/**
 * This class generates form components generic to all the contact types.
 *
 * It delegates the work to lower level subclasses and integrates the changes
 * back in. It also uses a lot of functionality with the CRM API's, so any change
 * made here could potentially affect the API etc. Be careful, be aware, use unit tests.
 */
class CRM_Profile_Form_Search extends CRM_Profile_Form {

  /**
   * Pre processing work done here.
   */
  public function preProcess() {
    $this->_mode = CRM_Profile_Form::MODE_SEARCH;
    parent::preProcess();
  }

  /**
   * Set the default form values.
   *
   * @return array
   *   the default array reference
   */
  public function setDefaultValues() {
    $defaults = array();
    // note we intentionally overwrite value since we use it as defaults
    // and its all pass by value
    // we need to figure out the type, so we can either set an array element
    // or a scalar -- FIX ME sometime please
    foreach ($_GET as $key => $value) {
      if (substr($key, 0, 7) == 'custom_' || $key == "preferred_communication_method") {
        if (strpos($value, CRM_Core_DAO::VALUE_SEPARATOR) !== FALSE) {
          $v = explode(CRM_Core_DAO::VALUE_SEPARATOR, $value);
          $value = array();
          foreach ($v as $item) {
            if ($item) {
              $value[$item] = $item;
            }
          }
        }
      }
      elseif ($key == 'group' || $key == 'tag') {
        $v = explode(',', $value);
        $value = array();
        foreach ($v as $item) {
          $value[$item] = 1;
        }
      }
      elseif (in_array($key, array(
        'birth_date',
        'deceased_date',
      ))) {
        list($value) = CRM_Utils_Date::setDateDefaults($value);
      }

      $defaults[$key] = $value;
    }
    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    // Is proximity search enabled for this profile?
    $proxSearch = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup',
      $this->get('gid'),
      'is_proximity_search', 'id'
    );
    if ($proxSearch) {
      CRM_Contact_Form_Task_ProximityCommon::buildQuickForm($this, $proxSearch);
    }

    $this->addButtons(array(
      array(
        'type' => 'refresh',
        'name' => ts('Search'),
        'isDefault' => TRUE,
      ),
    ));

    parent::buildQuickForm();
  }

  /**
   * Post process function.
   */
  public function postProcess() {
  }

}
