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
class CRM_ACL_Form_ACL extends CRM_Admin_Form {

  /**
   * @var bool
   */
  public $submitOnce = TRUE;

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();

    if ($this->_action & CRM_Core_Action::ADD) {
      $defaults['object_type'] = 1;
      $defaults['deny'] = 0;
      $defaults['priority'] = 1 + CRM_Utils_Weight::getMax(CRM_ACL_DAO_ACL::class, NULL, 'priority');
    }

    $showHide = new CRM_Core_ShowHideBlocks();

    if (isset($defaults['object_table'])) {
      switch ($defaults['object_table']) {
        case 'civicrm_group':
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
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $this->add('text', 'name', ts('Description'), CRM_Core_DAO::getAttribute('CRM_ACL_DAO_ACL', 'name'), TRUE);

    $this->add('select',
      'operation',
      ts('Operation'),
      CRM_ACL_BAO_ACL::operation(),
      TRUE,
      ['placeholder' => TRUE]
    );

    $objTypes = [
      '1' => ts('A group of contacts'),
      '2' => ts('A profile'),
      '3' => ts('A set of custom data fields'),
    ];

    if (CRM_Core_Permission::access('CiviEvent')) {
      $objTypes['4'] = ts('Events');
    }

    $extra = ['onclick' => "showObjectSelect();"];
    $this->addRadio('object_type',
      ts('Type of Data'),
      $objTypes,
      $extra,
      '&nbsp;', TRUE
    );

    $label = ts('Role');
    $role = [
      '-1' => ts('- select role -'),
    ] + CRM_Core_OptionGroup::values('acl_role');
    $this->add('select', 'entity_id', $label, $role, TRUE);

    $group = [
      '-1' => ts('- select group -'),
      '0' => ts('All Groups'),
    ] + CRM_Core_PseudoConstant::group();

    $customGroup = [
      '-1' => ts('- select set of custom fields -'),
      '0' => ts('All Custom Groups'),
    ] + CRM_Core_DAO_CustomField::buildOptions('custom_group_id');

    $ufGroup = [
      '-1' => ts('- select profile -'),
      '0' => ts('All Profiles'),
    ] + CRM_Core_DAO_UFField::buildOptions('uf_group_id');

    $event = [
      '-1' => ts('- select event -'),
      '0' => ts('All Events'),
    ] + CRM_Event_PseudoConstant::event(NULL, FALSE, "is_template = 0");

    $this->add('select', 'group_id', ts('Group'), $group);
    $this->add('select', 'custom_group_id', ts('Custom Data'), $customGroup);
    $this->add('select', 'uf_group_id', ts('Profile'), $ufGroup);
    $this->add('select', 'event_id', ts('Event'), $event);

    $this->add('checkbox', 'is_active', ts('Enabled?'));
    $this->addRadio('deny', ts('Mode'), [
      0 => ts('Allow'),
      1 => ts('Deny'),
    ], [], NULL, TRUE);
    $this->add('number', 'priority', ts('Priority'), ['min' => 1], TRUE);

    $this->addFormRule(['CRM_ACL_Form_ACL', 'formRule']);
  }

  /**
   * @param array $params
   *
   * @return bool
   */
  public static function formRule($params) {
    $showHide = new CRM_Core_ShowHideBlocks();

    // Make sure role is not -1
    if ($params['entity_id'] == -1) {
      $errors['entity_id'] = ts('Please assign this permission to a Role.');
    }

    $validOperations = ['View', 'Edit'];
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
   * Process the form submission.
   */
  public function postProcess() {
    // note this also resets any ACL cache
    Civi::cache('fields')->flush();
    // reset ACL and system caches.
    CRM_Core_BAO_Cache::resetCaches();

    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_ACL_BAO_ACL::deleteRecord(['id' => $this->_id]);
      CRM_Core_Session::setStatus(ts('Selected ACL has been deleted.'), ts('Record Deleted'), 'success');
    }
    else {
      $params = $this->controller->exportValues($this->_name);
      $params['is_active'] ??= FALSE;
      $params['entity_table'] = 'civicrm_acl_role';

      // Figure out which type of object we're permissioning on and set object_table and object_id.
      switch ($params['object_type']) {
        case 1:
          $params['object_table'] = 'civicrm_group';
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

      $params['priority'] = \CRM_Utils_Weight::updateOtherWeights(CRM_ACL_DAO_ACL::class, $this->_values['priority'] ?? NULL, $params['priority'], NULL, 'priority');
      CRM_ACL_BAO_ACL::writeRecord($params);
    }
  }

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'ACL';
  }

}
