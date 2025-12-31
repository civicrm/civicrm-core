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

use CRM_Mailing_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Mailing_Form_Delete extends CRM_Core_Form {

  /**
   * The set id.
   *
   * @var int
   */
  protected $_id;

  /**
   * The title of the set being deleted.
   *
   * @var string
   */
  protected $_title;

  /**
   * Set up variables to build the form.
   *
   * @return void
   */
  public function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);

    $this->_title = CRM_Core_DAO::getFieldValue('CRM_Mailing_DAO_Mailing',
      $this->_id, 'name'
    );
  }


  /**
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm(): void {
    $this->assign('title', $this->_title);
    $this->addButtons([
      [
        'type' => 'next',
        'name' => ts('Delete'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);
  }

  /**
   * Process the form when submitted.
   *
   * @return void
   */
  public function postProcess() {
    if (CRM_Mailing_BAO_Mailing::deleteRecord(['id' => $this->_id])) {
      CRM_Core_Session::setStatus(ts('The \'%1\' mailing has been deleted.',
        [1 => $this->_title]
      ), ts('Deleted'), 'success');
    }
    else {
      CRM_Core_Session::setStatus(ts('The \'%1\' mailing has not been deleted!',
        [1 => $this->_title]
      ), 'Unable to Delete', 'error');
    }
  }

}
