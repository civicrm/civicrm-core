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
 * This class generates form components for Location Type.
 */
class CRM_Admin_Form_PreferencesDate extends CRM_Admin_Form {

  /**
   * @return string
   */
  public function getDefaultEntity(): string {
    return 'PreferencesDate';
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {

    parent::buildQuickForm();

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $attributes = CRM_Core_DAO::getAttribute('CRM_Core_DAO_PreferencesDate');

    $this->applyFilter('__ALL__', 'trim');
    $name = &$this->add('text',
      'name',
      ts('Name'),
      $attributes['name'],
      TRUE
    );
    $name->freeze();

    $this->add('text', 'description', ts('Description'), $attributes['description'], FALSE);
    $this->add('text', 'start', ts('Start Offset'), $attributes['start'], TRUE);
    $this->add('text', 'end', ts('End Offset'), $attributes['end'], TRUE);

    $formatType = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_PreferencesDate', $this->_id, 'name');

    if ($formatType == 'creditCard') {
      $this->add('text', 'date_format', ts('Format'), $attributes['date_format'], TRUE);
    }
    else {
      $this->add('select', 'date_format', ts('Format'),
        ['' => ts('- default input format -')] + CRM_Core_SelectValues::getDatePluginInputFormats()
      );
      $this->add('select', 'time_format', ts('Time'),
        ['' => ts('- none -')] + CRM_Core_SelectValues::getTimeFormats()
      );
    }
    $this->addRule('start', ts('Value must be an integer.'), 'integer');
    $this->addRule('end', ts('Value must be an integer.'), 'integer');

    // add a form rule
    $this->addFormRule(['CRM_Admin_Form_PreferencesDate', 'formRule']);
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   *
   * @return array
   *   if errors then list of errors to be posted back to the form,
   *                  true otherwise
   */
  public static function formRule($fields) {
    $errors = [];

    if ($fields['name'] == 'activityDateTime' && !$fields['time_format']) {
      $errors['time_format'] = ts('Time is required for this format.');
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    if (!($this->_action & CRM_Core_Action::UPDATE)) {
      CRM_Core_Session::setStatus(ts('Preferences Date Options can only be updated'), ts('Sorry'), 'error');
      return;
    }

    // store the submitted values in an array
    $params = $this->controller->exportValues($this->_name);

    // action is taken depending upon the mode
    $dao = new CRM_Core_DAO_PreferencesDate();
    $dao->id = $this->_id;
    $dao->description = $params['description'];
    $dao->start = $params['start'];
    $dao->end = $params['end'];
    $dao->date_format = $params['date_format'];
    $dao->time_format = $params['time_format'];

    $dao->save();

    CRM_Core_Session::setStatus(ts("The date type '%1' has been saved.",
      [1 => $params['name']]
    ), ts('Saved'), 'success');
  }

}
