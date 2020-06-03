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
 * This class provides the functionality to update a saved search.
 */
class CRM_Contact_Form_Task_SaveSearch_Update extends CRM_Contact_Form_Task_SaveSearch {

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    parent::preProcess();

    $this->_id = $this->get('ssID');
    if (!$this->_id) {
      // fetch the value from the group id gid
      $gid = $this->get('gid');
      if ($gid) {
        $this->_id = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $gid, 'saved_search_id');
      }
    }
  }

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {

    $defaults = [];
    $params = [];

    $params = ['saved_search_id' => $this->_id];
    CRM_Contact_BAO_Group::retrieve($params, $defaults);

    if (!empty($defaults['group_type'])) {
      $types = explode(CRM_Core_DAO::VALUE_SEPARATOR,
        substr($defaults['group_type'], 1, -1)
      );
      $defaults['group_type'] = [];
      foreach ($types as $type) {
        $defaults['group_type'][$type] = 1;
      }
    }
    return $defaults;
  }

}
