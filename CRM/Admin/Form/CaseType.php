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
 * This class generates form components for Case Type
 *
 */
class CRM_Admin_Form_CaseType extends CRM_Admin_Form {

  /**
   * Function to build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }
    $this->applyFilter('__ALL__', 'trim');
    $this->add('text', 'title', ts('Name'),
      CRM_Core_DAO::getAttribute('CRM_Case_DAO_CaseType', 'title'),
      TRUE
    );
    $enabled = $this->add('checkbox', 'is_active', ts('Enabled?'));
    $this->add('text', 'description', ts('Description'),
      CRM_Core_DAO::getAttribute('CRM_Case_DAO_CaseType', 'description')
    );

    $this->assign('cid', $this->_id);
    $this->addFormRule(array('CRM_Admin_Form_CaseType', 'formRule'), $this);
  }

  /**
   * global form rule
   *
   * @param array $fields the input form values
   *
   * @param $files
   * @param $self
   *
   * @return true if no errors, else array of errors
   * @access public
   * @static
   */
  static function formRule($fields, $files, $self) {

    $errors = array();

    if ($self->_id) {
      $caseName = CRM_Core_DAO::getFieldValue('CRM_Case_DAO_CaseType', $self->_id, 'name');
    }
    else {
      $caseName = ucfirst(CRM_Utils_String::munge($fields['title']));
    }

    if (!CRM_Core_DAO::objectExists($caseName, 'CRM_Case_DAO_CaseType', $self->_id)) {
      $errors['title'] = ts('This case type name already exists in database. Case type names must be unique.');
    }

    $reservedKeyWords = CRM_Core_SelectValues::customGroupExtends();
    //restrict "name" from being a reserved keyword when a new contact subtype is created
    if (!$self->_id && in_array($caseName, array_keys($reservedKeyWords))) {
      $errors['title'] = ts('Case Type names should not use reserved keywords.');
    }
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return void
   */
  public function postProcess() {
    CRM_Utils_System::flushCache();

    if ($this->_action & CRM_Core_Action::DELETE) {
      $isDelete = CRM_Case_BAO_CaseType::del($this->_id);
      if ($isDelete) {
        CRM_Core_Session::setStatus(ts('Selected case type has been deleted.'), ts('Record Deleted'), 'success');
      }
      else {
        CRM_Core_Session::setStatus(ts("Selected case type can not be deleted."), ts('Sorry'), 'error');
      }
      return;
    }
    // store the submitted values in an array
    $params = $this->exportValues();

    if ($this->_action & CRM_Core_Action::ADD) {
      $params['name'] = ucfirst(CRM_Utils_String::munge($params['title']));
    } else {
      $params['id'] = $this->_id;
      $params['name'] = CRM_Core_DAO::getFieldValue('CRM_Case_DAO_CaseType', $this->_id, 'name');
    }
    $caseType = CRM_Case_BAO_CaseType::add($params);
    CRM_Core_Session::setStatus(ts("The Case Type '%1' has been saved.",
        array(1 => $caseType->title)
      ), ts('Saved'), 'success');
  }
}

