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
 * This class is to build the form for Deleting Group
 */
class CRM_Price_Form_DeleteField extends CRM_Core_Form {

  /**
   * The field id.
   *
   * @var int
   */
  protected $_fid;

  /**
   * The title of the group being deleted.
   *
   * @var string
   */
  protected $_title;

  /**
   * Set up variables to build the form.
   *
   * @return void
   * @access protected
   */
  public function preProcess() {
    $this->_fid = $this->get('fid');

    $this->_title = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceField',
      $this->_fid,
      'label', 'id'
    );

    $this->assign('title', $this->_title);

    $this->setTitle(ts('Confirm Price Field Delete'));
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    $this->addButtons([
      [
        'type' => 'next',
        'name' => ts('Delete Price Field'),
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
    if (CRM_Price_BAO_PriceField::deleteField($this->_fid)) {
      CRM_Core_Session::setStatus(ts('The Price Field \'%1\' has been deleted.', [1 => $this->_title]), '', 'success');
    }
  }

}
