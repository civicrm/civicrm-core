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
class CRM_Batch_Form_Search extends CRM_Core_Form {

  /**
   * Set default values.
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = [];

    $status = CRM_Utils_Request::retrieve('status', 'Positive', CRM_Core_DAO::$_nullObject, FALSE, 1);

    $defaults['batch_status'] = $status;
    return $defaults;
  }

  public function buildQuickForm() {
    $this->add('text', 'title', ts('Find'),
      CRM_Core_DAO::getAttribute('CRM_Batch_DAO_Batch', 'title')
    );

    $this->addButtons(
      [
        [
          'type' => 'refresh',
          'name' => ts('Search'),
          'isDefault' => TRUE,
        ],
      ]
    );

    parent::buildQuickForm();
    $this->assign('suppressForm', TRUE);
  }

}
