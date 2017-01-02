<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 * @copyright CiviCRM LLC (c) 2004-2017
 */

/**
 * This class generates form components for Mapping.
 */
class CRM_Admin_Form_Mapping extends CRM_Admin_Form {

  /**
   * Build the form object.
   */
  public function preProcess() {
    parent::preProcess();
    $mapping = new CRM_Core_DAO_Mapping();
    $mapping->id = $this->_id;
    $mapping->find(TRUE);
    $this->assign('mappingName', $mapping->name);
  }

  public function buildQuickForm() {
    parent::buildQuickForm();
    $this->setPageTitle(ts('Field Mapping'));

    if ($this->_action == CRM_Core_Action::DELETE) {
      return;
    }
    else {
      $this->applyFilter('__ALL__', 'trim');

      $this->add('text', 'name', ts('Name'),
        CRM_Core_DAO::getAttribute('CRM_Core_DAO_Mapping', 'name'), TRUE
      );
      $this->addRule('name', ts('Name already exists in Database.'), 'objectExists', array(
          'CRM_Core_DAO_Mapping',
          $this->_id,
        ));

      $this->addElement('text', 'description', ts('Description'),
        CRM_Core_DAO::getAttribute('CRM_Core_DAO_Mapping', 'description')
      );

      $mappingType = $this->addElement('select', 'mapping_type_id', ts('Mapping Type'), CRM_Core_PseudoConstant::get('CRM_Core_DAO_Mapping', 'mapping_type_id'));

      if ($this->_action == CRM_Core_Action::UPDATE) {
        $mappingType->freeze();
      }
    }
  }

  /**
   * @return array
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();
    return $defaults;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->exportValues();

    if ($this->_action == CRM_Core_Action::DELETE) {
      if ($this->_id) {
        CRM_Core_BAO_Mapping::del($this->_id);
        CRM_Core_Session::setStatus(ts('Selected mapping has been deleted successfully.'), ts('Deleted'), 'success');
      }
    }
    else {
      if ($this->_id) {
        $params['id'] = $this->_id;
      }

      CRM_Core_BAO_Mapping::add($params);
    }
  }

}
