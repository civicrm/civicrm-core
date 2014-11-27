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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_ACL_Form_ACL extends CRM_Admin_Form {

  /**
   * This function sets the default values for the form.
   *
   * @access public
   *
   * @return void
   */
  function setDefaultValues() {
    $defaults = parent::setDefaultValues();

    if ($this->_action & CRM_Core_Action::ADD) {
      $defaults['object_type'] = 1;
    }

    $showHide = new CRM_Core_ShowHideBlocks();

    if (isset($defaults['object_table'])) {
      switch ($defaults['object_table']) {
        case 'civicrm_saved_search':
          $defaults['group_id'] = $defaults['object_id'];
          $defaults['object_type'] = 1;
          $showHide->addShow("id-group-acl");
          $showHide->addHide("id-profile-acl");
          $showHide->addHide("id-custom-acl");
          $showHide->addHide("id-event-acl");
          break;

        case 'civicrm_uf_group':
          $defaults['uf_group_id'] = $defaults['object_id'];
          $defaults['object_type'] = 2;
          $showHide->addHide("id-group-acl");
          $showHide->addShow("id-profile-acl");
          $showHide->addHide("id-custom-acl");
          $showHide->addHide("id-event-acl");
          break;

        case 'civicrm_custom_group':
          $defaults['custom_group_id'] = $defaults['object_id'];
          $defaults['object_type'] = 3;
          $showHide->addHide("id-group-acl");
          $showHide->addHide("id-profile-acl");
          $showHide->addShow("id-custom-acl");
          $showHide->addHide("id-event-acl");
          break;

        case 'civicrm_event':
          $defaults['event_id'] = $defaults['object_id'];
          $defaults['object_type'] = 4;
          $showHide->addHide("id-group-acl");
          $showHide->addHide("id-profile-acl");
          $showHide->addHide("id-custom-acl");
          $showHide->addShow("id-event-acl");
          break;
      }
    }
    else {
      $showHide->addHide("id-group-acl");
      $showHide->addHide("id-profile-acl");
      $showHide->addHide("id-custom-acl");
      $showHide->addHide("id-event-acl");
    }

    // Don't assign showHide elements to template in DELETE mode (fields to be shown and hidden don't exist)
    if (!($this->_action & CRM_Core_Action::DELETE)) {
      $showHide->addToTemplate();
    }

    return $defaults;
  }

  /**
   * Function to build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    $this->setPageTitle(ts('ACL'));

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $attributes = CRM_Core_DAO::getAttribute('CRM_ACL_DAO_ACL');

    $this->add('text', 'name', ts('Description'), CRM_Core_DAO::getAttribute('CRM_ACL_DAO_ACL', 'name'), TRUE);

    $operations = array('' => ts('- select -')) + CRM_ACL_BAO_ACL::operation();
    $this->add('select',
      'operation',
      ts('Operation'),
      $operations, TRUE
    );

    $objTypes = array('1' => ts('A group of contacts'),
      '2' => ts('A profile'),
      '3' => ts('A set of custom data fields'),
    );

    if (CRM_Core_Permission::access('CiviEvent')) {
      $objTypes['4'] = ts('Events');
    }

    $extra = array('onclick' => "showObjectSelect();");
    $this->addRadio('object_type',
      ts('Type of Data'),
      $objTypes,
      $extra,
      '&nbsp;', TRUE
    );


    $label = ts('Role');
    $role = array('-1' => ts('- select role -'),
      '0' => ts('Everyone'),
    ) + CRM_Core_OptionGroup::values('acl_role');
    $this->add('select', 'entity_id', $label, $role, TRUE);

    $group = array('-1' => ts('- select -'),
      '0' => ts('All Groups'),
    ) + CRM_Core_PseudoConstant::group();

    $customGroup = array('-1' => ts('- select -'),
      '0' => ts('All Custom Groups'),
    ) + CRM_Core_PseudoConstant::get('CRM_Core_DAO_CustomField', 'custom_group_id');

    $ufGroup = array('-1' => ts('- select -'),
      '0' => ts('All Profiles'),
    ) + CRM_Core_PseudoConstant::get('CRM_Core_DAO_UFField', 'uf_group_id');

    $event = array('-1' => ts('- select -'),
      '0' => ts('All Events'),
    ) + CRM_Event_PseudoConstant::event(NULL, FALSE, "( is_template IS NULL OR is_template != 1 )");

    $this->add('select', 'group_id', ts('Group'), $group);
    $this->add('select', 'custom_group_id', ts('Custom Data'), $customGroup);
    $this->add('select', 'uf_group_id', ts('Profile'), $ufGroup);
    $this->add('select', 'event_id', ts('Event'), $event);

    $this->add('checkbox', 'is_active', ts('Enabled?'));

    $this->addFormRule(array('CRM_ACL_Form_ACL', 'formRule'));
  }

  /**
   * @param $params
   *
   * @return bool
   */
  static function formRule($params) {
    $showHide = new CRM_Core_ShowHideBlocks();

    // Make sure role is not -1
    if ($params['entity_id'] == -1) {
      $errors['entity_id'] = ts('Please assign this permission to a Role.');
    }

    $validOperations = array('View', 'Edit');
    $operationMessage = ts("Only 'View' and 'Edit' operations are valid for this type of data");

    // Figure out which type of object we're permissioning on and make sure user has selected a value.
    switch ($params['object_type']) {
      case 1:
        if ($params['group_id'] == -1) {
          $errors['group_id'] = ts('Please select a Group (or ALL Groups).');
          $showHide->addShow("id-group-acl");
          $showHide->addHide("id-profile-acl");
          $showHide->addHide("id-custom-acl");
          $showHide->addHide("id-event-acl");
        }
        if (!in_array($params['operation'], $validOperations)) {
          $errors['operation'] = $operationMessage;
        }
        break;

      case 2:
        if ($params['uf_group_id'] == -1) {
          $errors['uf_group_id'] = ts('Please select a Profile (or ALL Profiles).');
          $showHide->addShow("id-profile-acl");
          $showHide->addHide("id-group-acl");
          $showHide->addHide("id-custom-acl");
          $showHide->addHide("id-event-acl");
        }
        break;

      case 3:
        if ($params['custom_group_id'] == -1) {
          $errors['custom_group_id'] = ts('Please select a set of Custom Data (or ALL Custom Data).');
          $showHide->addShow("id-custom-acl");
          $showHide->addHide("id-group-acl");
          $showHide->addHide("id-profile-acl");
          $showHide->addHide("id-event-acl");
        }
        if (!in_array($params['operation'], $validOperations)) {
          $errors['operation'] = $operationMessage;
        }
        break;

      case 4:
        if ($params['event_id'] == -1) {
          $errors['event_id'] = ts('Please select an Event (or ALL Events).');
          $showHide->addShow("id-event-acl");
          $showHide->addHide("id-custom-acl");
          $showHide->addHide("id-group-acl");
          $showHide->addHide("id-profile-acl");
        }
        if (!in_array($params['operation'], $validOperations)) {
          $errors['operation'] = $operationMessage;
        }
        break;
    }

    $showHide->addToTemplate();

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
    // note this also resets any ACL cache
    CRM_Core_BAO_Cache::deleteGroup('contact fields');


    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_ACL_BAO_ACL::del($this->_id);
      CRM_Core_Session::setStatus(ts('Selected ACL has been deleted.'), ts('Record Deleted'), 'success');
    }
    else {
      $params = $this->controller->exportValues($this->_name);
      $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);
      $params['deny'] = 0;
      $params['entity_table'] = 'civicrm_acl_role';

      // Figure out which type of object we're permissioning on and set object_table and object_id.
      switch ($params['object_type']) {
        case 1:
          $params['object_table'] = 'civicrm_saved_search';
          $params['object_id'] = $params['group_id'];
          break;

        case 2:
          $params['object_table'] = 'civicrm_uf_group';
          $params['object_id'] = $params['uf_group_id'];
          break;

        case 3:
          $params['object_table'] = 'civicrm_custom_group';
          $params['object_id'] = $params['custom_group_id'];
          break;

        case 4:
          $params['object_table'] = 'civicrm_event';
          $params['object_id'] = $params['event_id'];
          break;
      }

      if ($this->_id) {
        $params['id'] = $this->_id;
      }

      CRM_ACL_BAO_ACL::create($params);
    }
  }

}

