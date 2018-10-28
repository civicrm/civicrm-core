<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * This class generates form components for Option Group.
 */
class CRM_Admin_Form_OptionGroup extends CRM_Admin_Form {

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'OptionGroup';
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }
    CRM_Utils_System::setTitle(ts('Dropdown Options'));

    $this->applyFilter('__ALL__', 'trim');
    $this->add('text',
      'name',
      ts('Name'),
      CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionGroup', 'name'),
      TRUE
    );
    $this->addRule('name',
      ts('Name already exists in Database.'),
      'objectExists',
      array('CRM_Core_DAO_OptionGroup', $this->_id)
    );

    $this->add('text',
      'title',
      ts('Group Title'),
      CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionGroup', 'title')
    );

    $this->add('text',
      'description',
      ts('Description'),
      CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionGroup', 'description')
    );

    $this->addSelect('data_type', array('options' => CRM_Utils_Type::dataTypes()), empty($this->_values['is_reserved']));

    $element = $this->add('checkbox', 'is_active', ts('Enabled?'));
    if ($this->_action & CRM_Core_Action::UPDATE) {
      if (in_array($this->_values['name'], array(
        'encounter_medium',
        'case_type',
        'case_status',
      ))) {
        static $caseCount = NULL;
        if (!isset($caseCount)) {
          $caseCount = CRM_Case_BAO_Case::caseCount(NULL, FALSE);
        }

        if ($caseCount > 0) {
          $element->freeze();
        }
      }

      $this->add('checkbox', 'is_reserved', ts('Reserved?'));
      $this->freeze('is_reserved');

      if (!empty($this->_values['is_reserved'])) {
        $this->freeze(array('name', 'is_active', 'data_type'));
      }
    }

    $this->assign('id', $this->_id);
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    CRM_Utils_System::flushCache();

    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Core_BAO_OptionGroup::del($this->_id);
      CRM_Core_Session::setStatus(ts('Selected option group has been deleted.'), ts('Record Deleted'), 'success');
    }
    else {
      // store the submitted values in an array
      $params = $this->exportValues();

      if ($this->_action & CRM_Core_Action::ADD) {
        // If we are adding option group via UI it should not be marked reserved.
        if (!isset($params['is_reserved'])) {
          $params['is_reserved'] = 0;
        }
      }
      elseif ($this->_action & CRM_Core_Action::UPDATE) {
        $params['id'] = $this->_id;
      }

      $optionGroup = CRM_Core_BAO_OptionGroup::add($params);
      CRM_Core_Session::setStatus(ts('The Option Group \'%1\' has been saved.', array(1 => $optionGroup->name)), ts('Saved'), 'success');
    }
  }

}
