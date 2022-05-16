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
 * This class is to build the form for Deleting Set
 */
class CRM_Price_Form_DeleteSet extends CRM_Core_Form {

  /**
   * The set id.
   *
   * @var int
   */
  protected $_sid;

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
    $this->_sid = $this->get('sid');

    $this->_title = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet',
      $this->_sid, 'title'
    );
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    $this->assign('title', $this->_title);
    $this->addButtons([
      [
        'type' => 'next',
        'name' => ts('Delete Price Set'),
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
    if (CRM_Price_BAO_PriceSet::deleteSet($this->_sid)) {
      CRM_Core_Session::setStatus(ts('The Price Set \'%1\' has been deleted.',
        [1 => $this->_title]
      ), ts('Deleted'), 'success');
    }
    else {
      CRM_Core_Session::setStatus(ts('The Price Set \'%1\' has not been deleted! You must delete all price fields in this set prior to deleting the set.',
        [1 => $this->_title]
      ), 'Unable to Delete', 'error');
    }
  }

}
