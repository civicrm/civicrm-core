<?php

/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.5                                                |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * This class generates form components for Financial Type
 *
 */
class CRM_Financial_Form_FinancialType extends CRM_Contribute_Form {

  /**
   * Function to build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    $this->setPageTitle(ts('Financial Type'));

    $this->_id = CRM_Utils_Request::retrieve('id' , 'Positive', $this);
    if ($this->_id) {
      $this->_title = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialType', $this->_id, 'name');
    }
    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }
    $this->applyFilter('__ALL__', 'trim');
    $this->add('text', 'name', ts('Name'), CRM_Core_DAO::getAttribute('CRM_Financial_DAO_FinancialType', 'name'), TRUE);

    $this->add('text', 'description', ts('Description'), CRM_Core_DAO::getAttribute('CRM_Financial_DAO_FinancialType', 'description'));

    $this->add('checkbox', 'is_deductible', ts('Tax-Deductible?'), CRM_Core_DAO::getAttribute('CRM_Financial_DAO_FinancialType', 'is_deductible'));
    $this->add('checkbox', 'is_active', ts('Enabled?'), CRM_Core_DAO::getAttribute('CRM_Financial_DAO_FinancialType', 'is_active'));
    $this->add('checkbox', 'is_reserved', ts('Reserved?'), CRM_Core_DAO::getAttribute('CRM_Financial_DAO_FinancialType', 'is_reserved'));
    if ($this->_action == CRM_Core_Action::UPDATE) {
      $this->assign('aid', $this->_id);
    }
    if ($this->_action == CRM_Core_Action::UPDATE && CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialType', $this->_id, 'is_reserved','vid')) {
      $this->freeze(array('is_active'));
    }
    
    $this->addRule('name', ts('A financial type with this name already exists. Please select another name.'),'objectExists',
      array('CRM_Financial_DAO_FinancialType', $this->_id)
    );
  }

  /**
   * Function to process the form
   *
   * @access public
   * @return void
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      $errors = CRM_Financial_BAO_FinancialType::del($this->_id);
      if (!empty($errors)) {
        CRM_Core_Error::statusBounce($errors['error_message'], CRM_Utils_System::url('civicrm/admin/financial/financialType', "reset=1&action=browse"), ts('Cannot Delete'));
      }
      CRM_Core_Session::setStatus(ts('Selected financial type has been deleted.'), ts('Record Deleted'), 'success');
    }
    else {
      $params = $ids = array( );
      // store the submitted values in an array
      $params = $this->exportValues();

      if ($this->_action & CRM_Core_Action::UPDATE) {
        $ids['financialType'] = $this->_id;
      }

      $financialType = CRM_Financial_BAO_FinancialType::add($params, $ids);
      if ($this->_action & CRM_Core_Action::UPDATE) {
        $url = CRM_Utils_System::url('civicrm/admin/financial/financialType', 'reset=1&action=browse');
        CRM_Core_Session::setStatus(ts('The financial type "%1" has been updated.', array( 1 => $financialType->name)), ts('Saved'), 'success');
      }
      else {
        $url = CRM_Utils_System::url('civicrm/admin/financial/financialType/accounts', 'reset=1&action=browse&aid=' . $financialType->id);
        $statusArray = array(
          1 => $financialType->name,
          2 => $financialType->name,
          3 => CRM_Utils_Array::value(0, $financialType->titles),
          4 => CRM_Utils_Array::value(1, $financialType->titles),
          5 => CRM_Utils_Array::value(2, $financialType->titles),
        );
        if (empty($financialType->titles)) {
          $text = ts('Your Financial "%1" Type has been created and assigned to an existing financial account with the same title. You should review the assigned account and determine whether additional account relationships are needed.', $statusArray);
        }
        else {
          $text = ts('Your Financial "%1" Type has been created, along with a corresponding income account "%2". That income account, along with standard financial accounts "%3", "%4" and "%5" have been linked to the financial type. You may edit or replace those relationships here.', $statusArray);
        }
        CRM_Core_Session::setStatus($text, ts('Saved'), 'success', array('expires' => 0));
      }

      $session = CRM_Core_Session::singleton();
      $session->replaceUserContext($url);
    }
  }

}
