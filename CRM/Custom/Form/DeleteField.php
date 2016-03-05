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

    $defaults = array();
    $params = array('id' => $this->_id);
    CRM_Core_BAO_CustomField::retrieve($params, $defaults);

    $this->_title = CRM_Utils_Array::value('label', $defaults);

    CRM_Utils_System::setTitle(ts('Delete %1', array(1 => $this->_title)));
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {

    $this->addButtons(array(
        array(
          'type' => 'next',
          'name' => ts('Delete Custom Field'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
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
    CRM_Core_Session::setStatus(ts('The custom field \'%1\' has been deleted.', array(1 => $field->label)), '', 'success');

  }

}
