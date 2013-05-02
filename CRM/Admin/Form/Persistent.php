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
 * customize the output to meet our specific requirements
 */
class CRM_Admin_Form_Persistent extends CRM_Core_Form {

  public function preProcess() {
    $this->_indexID = CRM_Utils_Request::retrieve('id', 'Integer', $this, FALSE);
    $this->_config  = CRM_Utils_Request::retrieve('config', 'Integer', $this, 0);
    $this->_action  = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE);

    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/admin/tplstrings', 'reset=1'));
    CRM_Utils_System::setTitle(ts('DB Template Strings'));
    parent::preProcess();
  }

  public function setDefaultValues() {
    $defaults = array();

    if ($this->_indexID && ($this->_action & (CRM_Core_Action::UPDATE))) {
      $params = array('id' => $this->_indexID);
      CRM_Core_BAO_Persistent::retrieve($params, $defaults);
      if (CRM_Utils_Array::value('is_config', $defaults) == 1) {
        $defaults['data'] = implode(',', $defaults['data']);
      }
    }
    return $defaults;
  }

  public function buildQuickForm() {
    $this->add('text', 'context', ts('Context:'), NULL, TRUE);
    $this->add('text', 'name', ts('Name:'), NULL, TRUE);
    $this->add('textarea', 'data', ts('Data:'), array('rows' => 4, 'cols' => 50), TRUE);
    $this->addButtons(array(
        array(
          'type' => 'submit',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
  }

  public function postProcess() {
    $params = $ids = array();
    $params = $this->controller->exportValues($this->_name);

    $params['is_config'] = $this->_config;

    if ($this->_action & CRM_Core_Action::ADD) {
      CRM_Core_Session::setStatus(ts('DB Template has been added successfully.'), ts("Saved"), "success");
    }
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $ids['persistent'] = $this->_indexID;
      CRM_Core_Session::setStatus(ts('DB Template has been updated successfully.'), ts("Saved"), "success");
    }
    CRM_Core_BAO_Persistent::add($params, $ids);

    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/tplstrings', "reset=1"));
  }
}

