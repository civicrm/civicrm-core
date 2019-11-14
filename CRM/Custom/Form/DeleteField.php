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
 * $Id$
 *
 */

/**
 * This class is to build the form for deleting a field
 */
class CRM_Custom_Form_DeleteField extends CRM_Core_Form {

  /**
   * The group id.
   *
   * @var int
   */
  protected $_id;

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
    $this->_id = $this->get('id');

    $defaults = [];
    $params = ['id' => $this->_id];
    CRM_Core_BAO_CustomField::retrieve($params, $defaults);

    $this->_title = CRM_Utils_Array::value('label', $defaults);
    $this->assign('title', $this->_title);
    CRM_Utils_System::setTitle(ts('Delete %1', [1 => $this->_title]));
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
        'name' => ts('Delete Custom Field'),
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
    $field = new CRM_Core_DAO_CustomField();
    $field->id = $this->_id;
    $field->find(TRUE);

    CRM_Core_BAO_CustomField::deleteField($field);

    // also delete any profiles associted with this custom field
    CRM_Core_Session::setStatus(ts('The custom field \'%1\' has been deleted.', [1 => $field->label]), '', 'success');

  }

}
