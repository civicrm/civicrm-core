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
 * This class generates form components generic to all the contact types.
 */
class CRM_Profile_Form_Search extends CRM_Profile_Form {

  /**
   * Pre processing work done here.
   */
  public function preProcess(): void {
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
    $defaults = [];
    // note we intentionally overwrite value since we use it as defaults
    // and its all pass by value
    // we need to figure out the type, so we can either set an array element
    // or a scalar -- FIX ME sometime please
    foreach ($_GET as $key => $value) {
      if (substr($key, 0, 7) == 'custom_' || $key == "preferred_communication_method") {
        if (str_contains($value, CRM_Core_DAO::VALUE_SEPARATOR)) {
          $v = explode(CRM_Core_DAO::VALUE_SEPARATOR, $value);
          $value = [];
          foreach ($v as $item) {
            if ($item) {
              $value[$item] = $item;
            }
          }
        }
      }
      elseif ($key == 'group' || $key == 'tag') {
        $v = explode(',', $value);
        $value = [];
        foreach ($v as $item) {
          $value[$item] = 1;
        }
      }
      elseif (in_array($key, ['birth_date', 'deceased_date'])) {
        list($value) = CRM_Utils_Date::setDateDefaults($value);
      }

      $defaults[$key] = $value;
    }
    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm(): void {
    // Is proximity search enabled for this profile?
    $proxSearch = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup',
      $this->get('gid'),
      'is_proximity_search', 'id'
    );
    if ($proxSearch) {
      CRM_Contact_Form_Task_ProximityCommon::buildQuickForm($this, $proxSearch);
    }

    $this->addButtons([
      [
        'type' => 'refresh',
        'name' => ts('Search'),
        'isDefault' => TRUE,
      ],
    ]);

    parent::buildQuickForm();
  }

  /**
   * Post process function.
   */
  public function postProcess(): void {
  }

}
