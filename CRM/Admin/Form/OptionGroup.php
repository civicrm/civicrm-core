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

/**
 * This class generates form components for Option Group.
 */
class CRM_Admin_Form_OptionGroup extends CRM_Admin_Form {

  /**
   * @var bool
   */
  public $submitOnce = TRUE;

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
    $this->setTitle(ts('Dropdown Options'));

    $this->applyFilter('__ALL__', 'trim');

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

    $this->addSelect('data_type', ['options' => CRM_Utils_Type::dataTypes()], empty($this->_values['is_reserved']));

    $element = $this->add('checkbox', 'is_active', ts('Enabled?'));
    if ($this->_action & CRM_Core_Action::UPDATE) {
      if (in_array($this->_values['name'], [
        'encounter_medium',
        'case_type',
        'case_status',
      ])) {
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
        $this->freeze(['is_active', 'data_type']);
      }
    }

    $this->assign('id', $this->_id);
    $this->addFormRule(['CRM_Admin_Form_OptionGroup', 'formRule'], $this);
  }

  /**
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   *
   * @param $files
   * @param self $self
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function formRule($fields, $files, $self) {
    $errors = [];
    if ($self->_id) {
      $name = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', $self->_id, 'name');
    }
    else {
      $name = CRM_Utils_String::titleToVar(strtolower($fields['title']));
    }
    if (!CRM_Core_DAO::objectExists($name, 'CRM_Core_DAO_OptionGroup', $self->_id)) {
      $errors['title'] = ts("Option Group name '%1' already exists in the database. Option Group Names must be unique.", [1 => $name]);
    }
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    CRM_Utils_System::flushCache();

    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Core_BAO_OptionGroup::deleteRecord(['id' => $this->_id]);
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
      CRM_Core_Session::setStatus(ts('The Option Group \'%1\' has been saved.', [1 => $optionGroup->title]), ts('Saved'), 'success');
    }
  }

}
