<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This class generates form components for batch entry
 *
 */
class CRM_Batch_Form_Batch extends CRM_Admin_Form {

  public function preProcess() {
    parent::preProcess();
    // set the usercontext
    $session = CRM_Core_Session::singleton();
    $session->replaceUserContext(CRM_Utils_System::url('civicrm/batch', "reset=1"));
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $this->applyFilter('__ALL__', 'trim');
    $attributes = CRM_Core_DAO::getAttribute('CRM_Batch_DAO_Batch');
    $this->add('text', 'title', ts('Batch Name'), $attributes['name'], TRUE);

    $batchTypes = CRM_Core_PseudoConstant::getBatchType();
    // unset non-related types
    unset($batchTypes[3]);
    unset($batchTypes[4]);
    $type = $this->add('select', 'type_id', ts('Type'), $batchTypes);

    if ($this->_action & CRM_Core_Action::UPDATE) {
      $type->freeze();
    }

    $this->add('textarea', 'description', ts('Description'), $attributes['description']);
    $this->add('text', 'item_count', ts('Number of items'), $attributes['item_count'], TRUE);
    $this->add('text', 'total', ts('Total Amount'), $attributes['total'], TRUE);
  }

  /**
   * This function sets the default values for the form.
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {
    $defaults = array();

    if ($this->_action & CRM_Core_Action::ADD) {
      // set batch name default
      $defaults['title'] = CRM_Batch_BAO_Batch::generateBatchName();
    }
    else {
      $defaults = $this->_values;
    }


    return $defaults;
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Core_Session::setStatus("", ts("Batch Deleted"), "success");
      CRM_Batch_BAO_Batch::deleteBatch($this->_id);
      return;
    }

    if ($this->_id) {
      $params['id'] = $this->_id;
    }
    else {
      $session = CRM_Core_Session::singleton();
      $params['created_id'] = $session->get('userID');
      $params['created_date'] = CRM_Utils_Date::processDate( date( "Y-m-d" ), date( "H:i:s" ) );
    }
    
    // always create with data entry status
    $params['status_id'] = 3; 
    $batch = CRM_Batch_BAO_Batch::create($params);

    // redirect to batch entry page.
    $session = CRM_Core_Session::singleton();
    if ( $this->_action & CRM_Core_Action::ADD ) {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/batch/entry', "id={$batch->id}&reset=1&action=add"));
    }
    else {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/batch/entry', "id={$batch->id}&reset=1"));
    }
  }
  //end of function
}

